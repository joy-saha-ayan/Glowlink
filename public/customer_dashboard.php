<?php
// glowlinkp/public/customer_dashboard.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlowLink - My Skincare Assistant</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Dashboard Specific Premium Overrides */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); /* Clean, professional background */
            display: block; /* Overrides the login page flexbox */
            margin: 0;
            padding: 20px;
            height: 100vh;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Sleek Top Navbar */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .top-nav h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn {
            background: #ff4757;
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .logout-btn:hover {
            background: #ff6b81;
            box-shadow: 0 4px 10px rgba(255, 71, 87, 0.3);
        }

        /* 50/50 Split Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            flex: 1;
            min-height: 0; /* Keeps it contained */
        }

        /* Premium Glass Panels */
        .panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 1);
        }

        .panel-header {
            padding: 20px;
            border-bottom: 1px solid #edf2f7;
            background: #ffffff;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 18px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #718096;
        }

        /* Chat Area */
        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background: #f8fafc;
        }

        .msg-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .bot-msg {
            background: #ffffff;
            color: #2d3748;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .user-msg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        .chat-footer {
            padding: 15px;
            background: #ffffff;
            border-top: 1px solid #edf2f7;
            display: flex;
            gap: 10px;
        }

        .chat-footer input {
            flex: 1;
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 25px;
            outline: none;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .chat-footer input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .chat-footer button {
            background: #667eea;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-footer button:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }

        /* Products Area */
        .products-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
        }

        .empty-state {
            text-align: center;
            color: #a0aec0;
            margin-top: 50px;
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #cbd5e0;
        }

        /* Dummy Product Card to show the design vision */
        .demo-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
            opacity: 0.6; /* Dimmed because it's a demo */
        }
        
        .demo-info h4 { margin: 0; color: #2d3748; font-size: 15px;}
        .demo-info p { margin: 3px 0 0 0; font-size: 12px; color: #718096;}
        .demo-price { font-weight: bold; color: #38a169; background: #c6f6d5; padding: 5px 10px; border-radius: 8px; font-size: 14px;}
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        
        <header class="top-nav">
            <h1><i class="fa-solid fa-sparkles" style="color: #f6e05e;"></i> GlowLink</h1>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
        </header>

        <main class="dashboard-grid">
            
            <section class="panel">
                <div class="panel-header">
                    <h2><i class="fa-solid fa-robot" style="color: #667eea;"></i> GlowBot Assistant</h2>
                    <p>Status: <span style="color: #48bb78; font-weight: bold;">● Online</span></p>
                </div>
                
                <div class="chat-body" id="chatWindow">
                    <div class="msg-bubble bot-msg">
                        Hi there! 👋 I am your GlowLink AI. Let's find the perfect skincare routine for you. What is your skin type, or what specific issues (like acne or dryness) are you looking to treat today?
                    </div>
                </div>

                <div class="chat-footer">
                    <input type="text" id="userInput" placeholder="Type your skincare concerns here..." onkeypress="handleKeyPress(event)">
                    <button onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2><i class="fa-solid fa-tags" style="color: #ed8936;"></i> Live Price Tracker</h2>
                    <p>Aggregating the best deals across multiple retailers.</p>
                </div>
                
                <div class="products-body" id="productResults">
                    
                    <div class="empty-state" id="emptyState">
                        <i class="fa-solid fa-magnifying-glass-chart"></i>
                        <p>Start a conversation with GlowBot.<br>Your personalized matches and lowest prices will appear here.</p>
                    </div>

                    <div class="demo-card" style="display: none;" id="demoCard">
                        <div class="demo-info">
                            <h4>Cerave Hydrating Cleanser</h4>
                            <p><i class="fa-solid fa-store"></i> Daraz | <i class="fa-solid fa-check-circle" style="color: green;"></i> In Stock</p>
                        </div>
                        <div class="demo-price">$12.99</div>
                    </div>

                </div>
            </section>

        </main>
    </div>

    <script>
        const chatWindow = document.getElementById('chatWindow');
        const userInput = document.getElementById('userInput');
        const emptyState = document.getElementById('emptyState');
        const demoCard = document.getElementById('demoCard');

        function handleKeyPress(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        }

        function sendMessage() {
            const text = userInput.value.trim();
            if (text === '') return;

            // 1. User Message
            addMessage(text, 'user-msg');
            userInput.value = '';

            // 2. Typing Indicator
            const typingId = 'typing-' + Date.now();
            addMessage('<i class="fa-solid fa-ellipsis"></i>', 'bot-msg', typingId);

            // 3. Simulate AI response & show dummy product
            setTimeout(() => {
                document.getElementById(typingId).remove();
                addMessage("I am searching our database for the best matches and comparing prices in real-time... (Backend API coming next!)", 'bot-msg');
                
                // Hide empty state and show the beautiful product card demo
                emptyState.style.display = 'none';
                demoCard.style.display = 'flex';
                demoCard.style.opacity = '1'; 
            }, 1000);
        }

        function addMessage(text, className, id = '') {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'msg-bubble ' + className;
            msgDiv.innerHTML = text; // innerHTML allows us to use FontAwesome icons in messages
            if (id) msgDiv.id = id;
            
            chatWindow.appendChild(msgDiv);
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }
    </script>

</body>
</html>