import './bootstrap';
document.getElementById('send-button').addEventListener('click', () => {
    const inputField = document.getElementById('chat-input');
    const message = inputField.value.trim();
    
    if (message) {
        // Append message to chat window
        const chatWindow = document.getElementById('chat-window');
        const messageElement = document.createElement('div');
        messageElement.className = 'chat-message';
        messageElement.textContent = message;
        
        chatWindow.appendChild(messageElement);
        inputField.value = '';
    }
});
