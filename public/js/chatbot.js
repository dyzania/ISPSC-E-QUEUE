// Premium Chatbot Logic
document.addEventListener("DOMContentLoaded", function () {
  const chatMessages = document.getElementById("chatMessages");
  const chatForm = document.getElementById("chatForm");
  const messageInput = document.getElementById("messageInput");
  const sendButton = document.getElementById("sendButton");
  const chatToggleBtn = document.getElementById("chatToggleBtn");
  const chatCloseBtn = document.getElementById("chatCloseBtn");
  const chatContainer = document.getElementById("chatContainer");
  const typingIndicator = document.getElementById("typingIndicator");

  let sessionId = generateSessionId();
  let isChatOpen = false;

  // Use the global BASE_URL defined in config/header
  const baseUrl = typeof EQUEUE_BASE_URL !== "undefined" ? EQUEUE_BASE_URL : "";

  function generateSessionId() {
    return (
      "session_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9)
    );
  }

  function formatMessage(text) {
    // Convert Markdown-like syntax to HTML
    text = text.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
    text = text.replace(/\*(.*?)\*/g, "<strong>$1</strong>");
    text = text.replace(/^- (.*)$/gm, "<li>$1</li>");
    if (text.includes("<li>")) {
      text = "<ul>" + text.replace(/(<li>.*<\/li>)/g, "$1") + "</ul>";
    }
    text = text.replace(/\n/g, "<br>");
    return text;
  }

  function addMessage(content, isUser) {
    // Remove welcome card on first message
    if (chatMessages.querySelector(".welcome-card")) {
      chatMessages.querySelector(".welcome-card").style.opacity = "0";
      setTimeout(() => {
        const card = chatMessages.querySelector(".welcome-card");
        if (card) card.remove();
      }, 300);
    }

    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${isUser ? "user" : "bot"}`;
    messageDiv.innerHTML = isUser ? content : formatMessage(content);

    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  async function sendMessage(message) {
    addMessage(message, true);

    // Show Typing Indicator
    typingIndicator.classList.remove("hidden");
    chatMessages.scrollTop = chatMessages.scrollHeight;

    sendButton.disabled = true;
    messageInput.disabled = true;

    try {
      const response = await fetch(baseUrl + "/api/chatbot.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: message, session_id: sessionId }),
      });

      const data = await response.json();
      typingIndicator.classList.add("hidden");

      if (data.success) {
        addMessage(data.response, false);
      } else {
        addMessage("I encountered a small hiccup. Please try again.", false);
      }
    } catch (error) {
      typingIndicator.classList.add("hidden");
      addMessage("Connectivity issue. Please check your internet.", false);
    } finally {
      sendButton.disabled = false;
      messageInput.disabled = false;
      messageInput.focus();
    }
  }

  chatForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message) {
      sendMessage(message);
      messageInput.value = "";
    }
  });

  function toggleChat() {
    isChatOpen = !isChatOpen;
    chatContainer.classList.toggle("active", isChatOpen);
    chatToggleBtn.classList.toggle("hidden", isChatOpen);

    if (isChatOpen) {
      setTimeout(() => messageInput.focus(), 500);
    }
  }

  chatToggleBtn.addEventListener("click", toggleChat);
  chatCloseBtn.addEventListener("click", toggleChat);

  // Close on escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && isChatOpen) toggleChat();
  });

  // Handle clicks outside
  document.addEventListener("click", (e) => {
    if (
      isChatOpen &&
      !chatContainer.contains(e.target) &&
      !chatToggleBtn.contains(e.target)
    ) {
      toggleChat();
    }
  });
});
