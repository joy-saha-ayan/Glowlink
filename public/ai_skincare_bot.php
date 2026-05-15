<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Skincare Advisor - GlowLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .chat-bubble { max-width: 75%; }
        .user-bubble { background: #E8336D; color: white; }
        .ai-bubble { background: #1f2937; color: #e5e7eb; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">

<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold mb-2">✨ GlowLink AI Skincare Advisor</h1>
        <p class="text-gray-400">Tell me about your skin and I'll recommend the best products for you</p>
    </div>

    <!-- Chat Container -->
    <div id="chat" class="bg-gray-900 rounded-3xl p-6 h-[70vh] overflow-y-auto mb-6 border border-gray-700"></div>

    <!-- Input Area -->
    <div class="bg-gray-900 rounded-3xl p-4 border border-gray-700">
        <div class="flex gap-3">
            <input type="text" id="userInput" 
                   class="flex-1 bg-gray-800 border border-gray-600 rounded-2xl px-6 py-4 focus:outline-none focus:border-purple-500"
                   placeholder="E.g. My skin is oily and acne-prone...">
            <button onclick="sendMessage()" 
                    class="bg-gradient-to-r from-purple-600 to-pink-600 px-8 rounded-2xl font-semibold hover:brightness-110 transition">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <p class="text-xs text-gray-500 text-center mt-3">Powered by Google Gemini AI</p>
    </div>
</div>

<script>
// Replace with your actual Gemini API Key
const GEMINI_API_KEY = "YOUR_GEMINI_API_KEY_HERE";   // ← এখানে তোমার API Key দাও

async function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    if (!message) return;

    // Show user message
    addMessage(message, 'user');
    input.value = '';

    // Show typing indicator
    const typingId = addTypingIndicator();

    try {
        const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_API_KEY}`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                contents: [{
                    parts: [{
                        text: `You are a professional dermatologist and skincare expert for GlowLink. 
                        Recommend products based on skin type. Be friendly, helpful and specific.
                        User said: "${message}"
                        
                        Format your reply with:
                        - Recommended products
                        - Key ingredients
                        - Why it's suitable`
                    }]
                }]
            })
        });

        const data = await response.json();
        const aiReply = data.candidates?.[0]?.content?.parts?.[0]?.text || "Sorry, I couldn't process that.";

        // Remove typing indicator and show AI reply
        removeTypingIndicator(typingId);
        addMessage(aiReply, 'ai');

    } catch (error) {
        removeTypingIndicator(typingId);
        addMessage("⚠️ AI is not responding right now. Please try again.", 'ai');
    }
}

function addMessage(text, sender) {
    const chat = document.getElementById('chat');
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble mb-4 ${sender === 'user' ? 'ml-auto user-bubble' : 'ai-bubble'}`;
    bubble.innerHTML = `<div class="p-4 rounded-2xl">${text.replace(/\n/g, '<br>')}</div>`;
    chat.appendChild(bubble);
    chat.scrollTop = chat.scrollHeight;
}

function addTypingIndicator() {
    const chat = document.getElementById('chat');
    const id = 'typing-' + Date.now();
    const typing = document.createElement('div');
    typing.id = id;
    typing.className = "ai-bubble chat-bubble mb-4";
    typing.innerHTML = `<div class="p-4 rounded-2xl flex items-center gap-2"><span class="animate-pulse">AI is thinking</span><span class="animate-bounce">.</span><span class="animate-bounce">.</span><span class="animate-bounce">.</span></div>`;
    chat.appendChild(typing);
    chat.scrollTop = chat.scrollHeight;
    return id;
}

function removeTypingIndicator(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

// Allow Enter key
document.getElementById('userInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') sendMessage();
});

// Welcome Message
window.onload = () => {
    addMessage("Hi! 👋 I'm your AI Skincare Advisor.<br>Tell me about your skin type (oily, dry, combination, sensitive etc.) and any concerns (acne, pigmentation, dryness...)", 'ai');
};
</script>

</body>
</html>