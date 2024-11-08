@extends('layouts.app')

@section('title', 'Chat Interface')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar for New Chat and Settings -->
            @include('components.sidebar', ['sessionId' => $sessionId])

            <!-- Chat Window for Conversation -->
            <div class="col-md-9">
                <div class="chat-container bg-light vh-100 d-flex flex-column">
                    <!-- Chat Window Displaying Messages -->
                    <div class="chat-window flex-grow-1 p-4 overflow-auto" id="chat-window">
                        @foreach ($chatHistory as $entry)
                            <div class="chat-message {{ $entry->is_user ? 'user' : 'bot' }}">
                                <strong>{{ $entry->is_user ? 'You' : 'Bot' }}:</strong>
                                <p>{{ $entry->message }}</p>
                            </div>
                        @endforeach
                    </div>

                    <!-- Chat Input, File Upload, and Send Button -->
                    <div class="chat-input-container d-flex align-items-center p-3">
                        <!-- File Upload Button with Hidden Input -->
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="document.getElementById('file-upload').click()">
                                <i class="bi bi-paperclip mr-2"></i> Attach File
                            </button>
                            <input type="file" id="file-upload" style="display: none;" aria-label="Attach file">
                        </div>

                        <!-- Display Selected File Name -->
                        <div id="file-name" class="text-muted mr-3">No file chosen</div>

                        <!-- Text Input Field for Message -->
                        <input type="text" id="chat-input" class="form-control mr-2" placeholder="Type a message">

                        <!-- Send Button -->
                        <button id="send-button" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('file-upload');
            const fileNameDisplay = document.getElementById('file-name');
            const inputField = document.getElementById('chat-input');
            const chatWindow = document.getElementById('chat-window');
            const sendButton = document.getElementById('send-button');
            const sessionId = @json($sessionId);

            // Update file name display when a file is selected
            fileInput.addEventListener('change', function() {
                fileNameDisplay.textContent = fileInput.files[0] ? fileInput.files[0].name :
                    'No file chosen';
            });

            // Send message or file on button click
            sendButton.addEventListener('click', sendMessage);

            function sendMessage() {
                const message = inputField.value.trim();
                const personality = 'formal';

                if (!message && fileInput.files.length === 0) {
                    alert('Please enter a message or upload a file.');
                    return;
                }

                const formData = new FormData();
                formData.append('message', message || "");
                formData.append('personality', personality);
                formData.append('sessionId', sessionId);

                if (fileInput.files.length > 0) {
                    formData.append('file', fileInput.files[0]);
                }

                fetch('/chat/send', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.response) {
                            // Display the bot's response in the chat window
                            const botMessageElement = document.createElement('div');
                            botMessageElement.className = 'chat-message bot';
                            botMessageElement.innerHTML = `<strong>Bot:</strong> <p>${data.response}</p>`;
                            chatWindow.appendChild(botMessageElement);
                            chatWindow.scrollTop = chatWindow.scrollHeight;
                        } else {
                            console.error('No response from the server.');
                        }
                    })
                    .catch(error => console.error('Error:', error));

                // Display the user's message in the chat window
                if (message) {
                    const userMessageElement = document.createElement('div');
                    userMessageElement.className = 'chat-message user';
                    userMessageElement.innerHTML = `<strong>You:</strong> <p>${message}</p>`;
                    chatWindow.appendChild(userMessageElement);
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                }

                // Clear the input fields
                inputField.value = '';
                fileInput.value = '';
                fileNameDisplay.textContent = 'No file chosen';
            }
        });
    </script>
@endsection
