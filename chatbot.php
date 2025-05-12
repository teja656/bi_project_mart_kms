<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = strtolower($_POST['query']);
    $response = '';
    
    // Basic knowledge base
    $knowledge_base = [
        'upload' => 'To upload a document, go to the Content Management page and use the upload form. You can drag and drop files or click to select them.',
        'search' => 'You can search for documents using the search bar at the top of the Content Management page. Search by title or description.',
        'approve' => 'Only administrators can approve documents. Go to the admin dashboard to manage document approvals.',
        'categories' => 'Documents are organized into categories for better organization. You can filter documents by category.',
        'help' => 'I can help you with: uploading documents, searching, approvals, categories, and general system usage.',
        'default' => 'I\'m not sure about that. Please try rephrasing your question or contact support for assistance.'
    ];
    
    // Check for keywords in the query
    foreach ($knowledge_base as $keyword => $answer) {
        if (strpos($query, $keyword) !== false) {
            $response = $answer;
            break;
        }
    }
    
    // If no specific match, try to find relevant documents
    if (empty($response)) {
        $search_term = '%' . $query . '%';
        $stmt = $conn->prepare("SELECT title FROM documents WHERE status = 'approved' AND (title LIKE ? OR description LIKE ?) LIMIT 3");
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $relevant_docs = [];
        while ($row = $result->fetch_assoc()) {
            $relevant_docs[] = $row['title'];
        }
        
        if (!empty($relevant_docs)) {
            $response = "I found some relevant documents that might help: " . implode(", ", $relevant_docs);
        } else {
            $response = $knowledge_base['default'];
        }
    }
    
    echo json_encode(['response' => $response]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot - Mart KMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .chatbot-container {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .chatbot-header {
            padding: 1.5rem;
            background: var(--primary-color);
            color: white;
        }

        .chatbot-body {
            padding: 1.5rem;
            height: 400px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .chat-message {
            max-width: 70%;
            padding: 1rem;
            border-radius: var(--border-radius);
            animation: fadeIn 0.3s ease-in-out;
        }

        .chat-message.user {
            background: var(--primary-color);
            color: white;
            margin-left: auto;
        }

        .chat-message.bot {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            margin-right: auto;
        }

        .chatbot-input {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--card-bg);
        }

        .chatbot-input input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .chatbot-input button {
            padding: 0.8rem 1.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .suggestion-chip {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .suggestion-chip:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow);
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">Mart KMS</a>
            <ul>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/dashboard.php">Dashboard</a></li>
                    <li><a href="admin/documents.php">Documents</a></li>
                    <li><a href="admin/users.php">Users</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="chatbot-container">
            <div class="chatbot-header">
                <h1>Knowledge Base Assistant</h1>
                <p>Ask me anything about the system</p>
            </div>
            
            <div class="chatbot-body" id="chatbotBody">
                <div class="chat-message bot">
                    Hello! I'm your Knowledge Base Assistant. How can I help you today?
                </div>
                
                <div class="suggestions">
                    <div class="suggestion-chip" onclick="askQuestion('How do I upload a document?')">How do I upload a document?</div>
                    <div class="suggestion-chip" onclick="askQuestion('How do I search for documents?')">How do I search for documents?</div>
                    <div class="suggestion-chip" onclick="askQuestion('What are document categories?')">What are document categories?</div>
                    <div class="suggestion-chip" onclick="askQuestion('How do I get help?')">How do I get help?</div>
                </div>
            </div>
            
            <div class="chatbot-input">
                <input type="text" id="chatbotInput" placeholder="Type your question..." onkeypress="handleKeyPress(event)">
                <button class="btn" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div>
                <h3>Mart KMS</h3>
                <p>Knowledge Management System</p>
            </div>
            <div>
                <h3>Contact</h3>
                <p>Email: support@mart.com</p>
                <p>Phone: (123) 456-7890</p>
            </div>
        </div>
    </footer>

    <script>
        function sendMessage() {
            const input = document.getElementById('chatbotInput');
            const message = input.value.trim();
            
            if (message) {
                addMessage(message, 'user');
                input.value = '';
                
                // Show typing indicator
                const typingDiv = document.createElement('div');
                typingDiv.className = 'chat-message bot';
                typingDiv.textContent = 'Typing...';
                document.getElementById('chatbotBody').appendChild(typingDiv);
                
                // Send to server
                fetch('chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'query=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    // Remove typing indicator
                    typingDiv.remove();
                    // Add bot response
                    addMessage(data.response, 'bot');
                })
                .catch(error => {
                    console.error('Error:', error);
                    typingDiv.remove();
                    addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                });
            }
        }

        function addMessage(message, type) {
            const body = document.getElementById('chatbotBody');
            const div = document.createElement('div');
            div.className = 'chat-message ' + type;
            div.textContent = message;
            body.appendChild(div);
            body.scrollTop = body.scrollHeight;
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function askQuestion(question) {
            document.getElementById('chatbotInput').value = question;
            sendMessage();
        }
    </script>
</body>
</html> 