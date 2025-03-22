document.addEventListener('DOMContentLoaded', () => {
  /* 
    All JavaScript for your site, including the modern chat panel logic
    and inline code comments to explain each part.
  */

  // ========== 1. QUERY SELECTORS ==========
  const chatButton   = document.getElementById('ai-chat-button');  // The floating chat icon
  const chatPanel    = document.getElementById('ai-chat-panel');   // The entire chat panel (aside)
  const closeBtn     = document.getElementById('chat-close-btn');  // The 'X' close button
  const sendBtn      = document.getElementById('chat-send-btn');   // Send button
  const chatInput    = document.getElementById('chat-input');      // User text input
  const chatMessages = document.getElementById('chat-messages');   // Container for bubble messages

  // Example: Navbar links for active state highlight
  const navLinks = document.querySelectorAll('.nav-link');

  // ========== 2. CHAT PANEL TOGGLE ==========
  chatButton.addEventListener('click', () => {
    // Toggle the 'show-panel' class on the chat panel
    const isHidden = !chatPanel.classList.contains('show-panel');
    if (isHidden) {
      // Show the panel
      chatPanel.classList.add('show-panel');
    } else {
      // Hide the panel
      chatPanel.classList.remove('show-panel');
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      chatPanel.classList.remove('show-panel');
    });
  }

  // ========== 3. SEND MESSAGE LOGIC ==========
  sendBtn.addEventListener('click', () => {
    const userMessage = chatInput.value.trim();
    if (!userMessage) return;  // Ignore empty messages

    // Create a user bubble in the chat
    createChatBubble(userMessage, 'user');

    // Clear the input
    chatInput.value = '';

    // Show an AI is typing... bubble (optional) or skip directly to final reply
    // We'll do a short delay to mimic thinking time
    setTimeout(() => {
      // Remove or replace any 'AI is typing...' placeholders
      // Show a loading bubble
      const loadingBubble = createChatBubble("...", "ai");

      // Send message to chat.php via fetch
      fetch('chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: userMessage })
      })
      .then(response => response.json())
      .then(data => {
        loadingBubble.textContent = data.reply;
      })
      .catch(err => {
        loadingBubble.textContent = "⚠️ Error: Unable to reach AI server.";
      });
    }, 800);
  });

  // ========== 4. CREATE CHAT BUBBLE FUNCTION ==========
  function createChatBubble(message, sender = 'user') {
    // Make a div for the bubble
    const bubble = document.createElement('div');
    bubble.classList.add('chat-bubble', sender);
    bubble.textContent = message;

    // Append bubble to chat
    chatMessages.appendChild(bubble);

    // Auto-scroll to the bottom whenever a new bubble appears
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // ========== 5. FAKE AI REPLY GENERATOR ==========
  function getFakeAIResponse(userMsg) {
    // In production, you'd do a fetch() call to your AI endpoint here
    // For demonstration, return a placeholder
    return `AI says: \"You asked about '${userMsg}'. Great question!\"`;
  }

  // ========== 6. NAVBAR ACTIVE LINK LOGIC ==========
  navLinks.forEach(link => {
    link.addEventListener('click', function () {
      navLinks.forEach(el => el.classList.remove('active'));
      this.classList.add('active');
    });

      // Parallax wave effect
  document.addEventListener("mousemove", (e) => {
    const wave = document.getElementById("hero-wave");
    if (!wave) return;

    const x = (e.clientX / window.innerWidth - 0.5) * 30;
    const y = (e.clientY / window.innerHeight - 0.5) * 20;

    wave.style.transform = `translate(${x}px, ${y}px)`;
  });

  });

  // ========== 7. OPTIONAL EXTRAS ==========
  // - Animate chat button
  // - Detect user pressing 'Enter' in chat input
  // - Replace getFakeAIResponse with real backend calls
});
