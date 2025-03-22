// Inline comments included for clarity

// 1. Get references to elements
const chatButton = document.getElementById('ai-chat-button'); // Floating chat icon
const chatPanel = document.getElementById('ai-chat-panel');   // The messenger-style chat box
const closeBtn = document.getElementById('chat-close-btn');   // The 'X' button in chat header
const sendBtn = document.getElementById('chat-send-btn');     // The Send button
const chatInput = document.getElementById('chat-input');      // User's message input
const chatMessages = document.getElementById('chat-messages');// Container for chat bubbles

// 2. Toggle chat panel visibility
chatButton.addEventListener('click', () => {
  // If hidden, show; if shown, hide
  if (chatPanel.style.display === 'none' || chatPanel.style.display === '') {
    chatPanel.style.display = 'block';
  } else {
    chatPanel.style.display = 'none';
  }
});

// 3. Close chat panel when the 'X' is clicked
closeBtn.addEventListener('click', () => {
  chatPanel.style.display = 'none';
});

// 4. Handle sending a message
sendBtn.addEventListener('click', () => {
  const userMessage = chatInput.value.trim();
  if (!userMessage) return; // Don't send empty messages

  // (A) Create a user bubble
  createChatBubble(userMessage, 'user');

  // (B) Clear the input
  chatInput.value = '';

  // (C) Fake AI Response (replace later with real API call)
  setTimeout(() => {
    const aiReply = getFakeAIResponse(userMessage);
    createChatBubble(aiReply, 'ai');
  }, 1000);
});

// 5. Function to create a chat bubble and append to chatMessages
function createChatBubble(message, sender = 'user') {
  // Create a div for bubble
  const bubble = document.createElement('div');
  bubble.classList.add('chat-bubble', sender);
  bubble.textContent = message;

  // Append it to messages container
  chatMessages.appendChild(bubble);

  // Auto-scroll to bottom
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// 6. Fake AI response generator
function getFakeAIResponse(userMsg) {
  // You can make a real fetch() call to OpenAI or a local server here
  // For now, let's do a simple placeholder
  return `AI says: "You asked about '${userMsg}' — great question!"`;
}

// ==== BONUS: Navbar or other site-wide logic here if needed ====

// Example: Active link highlight
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', function () {
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    this.classList.add('active');
  });
});
