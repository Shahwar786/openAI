<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatHistory;
use Intervention\Image\Facades\Image;
use OpenAI;
use Illuminate\Support\Str;
use Log;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        // Generate a new session ID if it doesn’t exist or use the existing one
        $sessionId = $request->input('sessionId', Str::uuid()->toString());

        // Fetch the chat history for the specific session
        $chatHistory = ChatHistory::where('session_id', $sessionId)->orderBy('created_at', 'asc')->get();

        return view('chat.index', compact('chatHistory', 'sessionId'));
    }

    public function uploadFile(Request $request)
    {
        try {
            $sessionId = $request->input('sessionId');
            if (!$request->hasFile('file')) return response()->json(['error' => 'No file uploaded.'], 400);

            $file = $request->file('file');
            $tempPath = storage_path('app/temp/' . uniqid() . '.' . $file->getClientOriginalExtension());
            $file->move(dirname($tempPath), basename($tempPath));
            $fileContent = $this->analyzeFileContent($tempPath);
            unlink($tempPath);

            ChatHistory::create(['session_id' => $sessionId, 'message' => $fileContent, 'is_user' => false]);

            return response()->json(['response' => $fileContent]);
        } catch (\Exception $e) {
            \Log::error("Error in file upload: " . $e->getMessage());
            return response()->json(['error' => 'Failed to process the file.'], 500);
        }
    }

    public function sendMessage(Request $request)
{
    $request->validate([
        'sessionId' => 'required|string',
        'file' => 'nullable|file|max:10240', // 10MB max size
        'message' => 'nullable|string',
    ]);

    if (!$request->has('message') && !$request->hasFile('file')) {
        return response()->json(['error' => 'Message or file is required.'], 400);
    }

    
    
    try {
        $sessionId = $request->input('sessionId');
        $message = $request->input('message');
        $personality = $request->input('personality', 'formal');
        $hasFile = $request->hasFile('file');

        Log::info("Session ID: " . ($sessionId ?? 'null'));
        Log::info("Message: " . ($message ?? 'null'));
        Log::info("Personality: " . $personality);
        Log::info("File Present: " . ($hasFile ? 'Yes' : 'No'));

        if (empty($message) && !$hasFile) {
            Log::error("Error: No message or file uploaded");
            return response()->json(['error' => 'Message or file is required.'], 400);
        }

        if (!empty($message)) {
            ChatHistory::create(['session_id' => $sessionId, 'message' => $message, 'is_user' => true]);
        }

        if ($hasFile) {
            $file = $request->file('file');
            $tempPath = storage_path('app/temp/' . uniqid() . '.' . $file->getClientOriginalExtension());
            $file->move(dirname($tempPath), basename($tempPath));

            $fileContent = $this->analyzeFileContent($tempPath);
            Log::info("Processed file content: " . $fileContent);
            unlink($tempPath);

            ChatHistory::create(['session_id' => $sessionId, 'message' => $fileContent, 'is_user' => true]);
        }

        $chatHistory = ChatHistory::where('session_id', $sessionId)->orderBy('created_at', 'asc')->get();

        $messages = [
            ['role' => 'system', 'content' => "You are a {$personality} assistant."],
        ];

        foreach ($chatHistory as $entry) {
            $role = $entry->is_user ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $entry->message];
        }

        $client = OpenAI::client(env('OPENAI_API_KEY'));
        $response = $client->chat()->create([
            'model' => 'gpt-4',
            'messages' => $messages,
            'max_tokens' => 150,
        ]);

        $botResponse = $response->choices[0]->message->content ?? 'No response';

        ChatHistory::create(['session_id' => $sessionId, 'message' => $botResponse, 'is_user' => false]);
        return response()->json(['response' => $botResponse]);

    } catch (\Exception $e) {
        Log::error("Error in sendMessage: " . $e->getMessage());
        return response()->json(['error' => 'An error occurred on the server.'], 500);
    }
}



    public function newChat()
    {
        // Generate a new session ID and redirect to a fresh chat page
        $newSessionId = Str::uuid()->toString();
        return redirect()->route('chat.index', ['sessionId' => $newSessionId]);
    }

    private function analyzeFileContent($filePath)
    {
        // Check the file extension to handle PDF or Image
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if ($extension === 'pdf') {
            return $this->extractTextFromPdf($filePath);
        } else {
            $textContent = $this->extractMultilingualText($filePath);
            if (!empty($textContent) && $textContent !== "No readable text detected in the image.") {
                return $this->generateTextExplanation($textContent);
            }
            return $this->generateImageExplanation();
        }
    }

    private function extractTextFromPdf($filePath)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            return !empty($text) ? $text : "No readable text detected in the PDF.";
        } catch (\Exception $e) {
            Log::error("Error parsing PDF: " . $e->getMessage());
            return "Error extracting text from the PDF.";
        }
    }

    private function extractMultilingualText($filePath)
    {
        $outputPath = storage_path('app/temp/ocr_output');
        $command = "/opt/homebrew/bin/tesseract " . escapeshellarg($filePath) . " " . escapeshellarg($outputPath) . " -l eng";
        exec($command . " 2>&1", $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error("Tesseract error: " . implode("\n", $output));
            return "No readable text detected in the image.";
        }

        $outputTextFile = "{$outputPath}.txt";
        if (file_exists($outputTextFile)) {
            $text = file_get_contents($outputTextFile);
            unlink($outputTextFile);
            return $text ?: "No text found.";
        } else {
            Log::error("Tesseract did not produce an output file at $outputTextFile. Check Tesseract installation and permissions.");
            return "No readable text detected.";
        }
    }

    private function generateTextExplanation($text)
    {
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $messages = [
                ['role' => 'system', 'content' => "You are a helpful assistant that provides explanations for text extracted from images."],
                ['role' => 'user', 'content' => "The text extracted from the image is: \"$text\". Break it down into key phrases or keywords, and explain each part briefly."]
            ];

            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 200,
            ]);

            return $response->choices[0]->message->content ?? "No explanation generated.";
        } catch (\Exception $e) {
            Log::error("Error in generateTextExplanation: " . $e->getMessage());
            return "Error generating explanation for text.";
        }
    }

    private function generateImageExplanation()
    {
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $messages = [
                ['role' => 'system', 'content' => "You are a helpful assistant that can provide a descriptive analysis of images without specific text."],
                ['role' => 'user', 'content' => "The image provided contains no readable text. Based on general appearance, provide a detailed description or interpretation of what could be in the image, focusing on general themes, colors, or potential objects."]
            ];

            $response = $client->chat()->create([
                'model' => 'gpt-4',
                'messages' => $messages,
                'max_tokens' => 200,
            ]);

            return $response->choices[0]->message->content ?? "No description available for the image.";
        } catch (\Exception $e) {
            Log::error("Error in generateImageExplanation: " . $e->getMessage());
            return "Error generating description for the image.";
        }
    }
}
