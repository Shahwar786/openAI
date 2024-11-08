document.getElementById('send-button').addEventListener('click', () => {
    const inputField = document.getElementById('chat-input');
    const fileInput = document.getElementById('file-upload');
    const message = inputField.value.trim();
    const personality = document.getElementById('personality').value;

    const formData = new FormData();
    formData.append('message', message);
    formData.append('personality', personality);
    
    if (fileInput.files[0]) {
        formData.append('file', fileInput.files[0]);
        console.log(fileInput.files[0]);
        
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
        if (data.response) {
            const chatWindow = document.getElementById('chat-window');
            const botMessageElement = document.createElement('div');
            botMessageElement.className = 'chat-message bot';
            botMessageElement.textContent = data.response;

            chatWindow.appendChild(botMessageElement);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        } else {
            console.error('No response received from server');
        }
    })
    .catch(error => console.error('Error:', error));

    inputField.value = '';
    fileInput.value = '';
});
