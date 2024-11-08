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
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('file-upload').click()">
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

<script>
    // Handle file selection and display the selected file name
    document.getElementById('file-upload').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : "No file chosen";
        document.getElementById('file-name').textContent = fileName;
    });

    document.getElementById('send-button').addEventListener('click', sendMessage);

    function sendMessage() {
        const inputField = document.getElementById('chat-input');
        const fileInput = document.getElementById('file-upload');
        const message = inputField.value.trim();
        const personality = document.getElementById('personality') ? document.getElementById('personality').value : 'formal';
        const sessionId = "{{ $sessionId }}";

        const formData = new FormData();
        formData.append('message', message);
        formData.append('personality', personality);
        formData.append('sessionId', sessionId);

        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        fetch('/chat/send', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
            const chatWindow = document.getElementById('chat-window');
            const botMessageElement = document.createElement('div');
            botMessageElement.className = 'chat-message bot';
            botMessageElement.innerHTML = `<strong>Bot:</strong> <p>${data.response}</p>`;
            chatWindow.appendChild(botMessageElement);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        })
        .catch(error => console.error('Error:', error));

        // Display user message in chat window
        const userMessageElement = document.createElement('div');
        userMessageElement.className = 'chat-message user';
        userMessageElement.innerHTML = `<strong>You:</strong> <p>${message}</p>`;
        document.getElementById('chat-window').appendChild(userMessageElement);

        inputField.value = '';  // Clear input after sending
        fileInput.value = '';   // Clear file input after sending
        document.getElementById('file-name').textContent = 'No file chosen'; // Reset file name display
    }
</script>
@endsection
