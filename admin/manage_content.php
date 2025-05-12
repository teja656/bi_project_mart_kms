<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/auto_tag.php';
require_once '../includes/recommendations.php';

// Check if user is logged in and has appropriate permissions
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Get user permissions
$permissions = getUserPermissions();

// Initialize auto-tagger and recommender
$autoTagger = new AutoTagger($conn);
$recommender = new DocumentRecommender($conn);

// Get recommendations for the current user
$recommendations = $recommender->getRecommendations($_SESSION['user_id']);

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Handle search
$search_results = [];
if (isset($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $search_query = "SELECT d.*, c.name as category_name, u.username as uploaded_by 
                    FROM documents d 
                    LEFT JOIN categories c ON d.category_id = c.category_id 
                    LEFT JOIN users u ON d.uploaded_by = u.user_id 
                    WHERE d.title LIKE ? OR d.description LIKE ?";
    
    // If not admin, only show approved documents
    if (!isAdmin()) {
        $search_query .= " AND d.status = 'approved'";
    }
    
    $search_query .= " ORDER BY d.uploaded_at DESC";
    
    $stmt = $conn->prepare($search_query);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $search_results = $stmt->get_result();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (!$permissions['upload_documents']) {
        $error = "You don't have permission to upload documents.";
    } else {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category_id = $_POST['category_id'];
        $status = isAdmin() ? $_POST['status'] : 'pending';
        $uploaded_by = $_SESSION['user_id'];

        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['document_file'];
            $file_name = time() . '_' . basename($file['name']);
            $target_path = '../uploads/' . $file_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO documents (title, description, file_path, category_id, status, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssisi", $title, $description, $file_name, $category_id, $status, $uploaded_by);
                    
                    if ($stmt->execute()) {
                        $document_id = $conn->insert_id;
                        
                        // Generate and save tags
                        $suggested_tags = $autoTagger->generateTags($title, $description);
                        $autoTagger->saveTags($document_id, $suggested_tags);
                        
                        $_SESSION['success'] = "Document uploaded successfully!" . ($status === 'pending' ? " Waiting for admin approval." : "");
                        header("Location: manage_content.php");
                        exit();
                    } else {
                        $error = "Error uploading document: " . $stmt->error;
                    }
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
            } else {
                $error = "Error moving uploaded file!";
            }
        } else {
            $error = "Please select a file to upload!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - Mart KMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/sparkle.js" defer></script>
    <style>
        .content-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .upload-section {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 2rem;
        }

        .search-section {
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }

        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .document-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .document-title {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin: 0;
        }

        .document-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .document-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .status-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .file-upload {
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .file-upload:hover {
            border-color: var(--primary-color);
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload label {
            cursor: pointer;
            color: var(--primary-color);
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            z-index: 1000;
        }

        .chatbot-header {
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            cursor: pointer;
        }

        .chatbot-body {
            padding: 1rem;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }

        .chatbot-input {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .chatbot-input input {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
        }

        .chat-message {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-radius: var(--border-radius);
        }

        .chat-message.user {
            background: var(--primary-color);
            color: white;
            margin-left: 20%;
        }

        .chat-message.bot {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            margin-right: 20%;
        }

        .recommendations-section {
            margin-top: 2rem;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .tag-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: var(--primary-color);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">Mart KMS</a>
            <ul>
                <?php if (isAdmin()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="documents.php">Documents</a></li>
                    <li><a href="users.php">Users</a></li>
                <?php endif; ?>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="card">
            <h1>Content Management</h1>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Recommendations Section -->
            <?php if (!empty($recommendations)): ?>
            <div class="recommendations-section">
                <h2>Recommended Documents</h2>
                <div class="document-grid">
                    <?php foreach ($recommendations as $doc): ?>
                        <div class="document-card">
                            <div class="document-header">
                                <h3 class="document-title"><?php echo htmlspecialchars($doc['title']); ?></h3>
                            </div>
                            <div class="document-meta">
                                <p><?php echo htmlspecialchars($doc['description']); ?></p>
                            </div>
                            <div class="document-actions">
                                <a href="../view_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-secondary">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="content-grid">
                <!-- Upload Section -->
                <?php if ($permissions['upload_documents']): ?>
                <div class="upload-section">
                    <h2>Upload Document</h2>
                    <form method="POST" enctype="multipart/form-data" class="login-form">
                        <div class="form-group">
                            <label for="title">Document Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Select a category</option>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <?php if (isAdmin()): ?>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <div class="file-upload">
                                <label for="document_file">
                                    <div class="action-icon">📤</div>
                                    <p>Click to upload or drag and drop</p>
                                    <input type="file" id="document_file" name="document_file" required>
                                </label>
                                <div class="file-name" id="file-name">No file selected</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" name="upload" class="btn">Upload Document</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Search and Results Section -->
                <div class="search-section">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" class="search-input" placeholder="Search documents..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn">Search</button>
                    </form>

                    <?php if (isset($_GET['search'])): ?>
                        <h2>Search Results</h2>
                        <div class="document-grid">
                            <?php if ($search_results->num_rows > 0): ?>
                                <?php while ($doc = $search_results->fetch_assoc()): ?>
                                    <div class="document-card">
                                        <div class="document-header">
                                            <h3 class="document-title"><?php echo htmlspecialchars($doc['title']); ?></h3>
                                            <?php if (isAdmin()): ?>
                                            <div class="status-badge status-<?php echo strtolower($doc['status']); ?>">
                                                <?php echo ucfirst($doc['status']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="document-meta">
                                            <p>Category: <?php echo htmlspecialchars($doc['category_name']); ?></p>
                                            <p>Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by']); ?></p>
                                            <p>Date: <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></p>
                                            
                                            <?php
                                            // Get document tags
                                            $tags_stmt = $conn->prepare("SELECT tag FROM document_tags WHERE document_id = ?");
                                            $tags_stmt->bind_param("i", $doc['document_id']);
                                            $tags_stmt->execute();
                                            $tags_result = $tags_stmt->get_result();
                                            $tags = [];
                                            while ($tag = $tags_result->fetch_assoc()) {
                                                $tags[] = $tag['tag'];
                                            }
                                            if (!empty($tags)):
                                            ?>
                                            <div class="tag-container">
                                                <?php foreach ($tags as $tag): ?>
                                                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="document-actions">
                                            <a href="../view_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-secondary">View</a>
                                            <?php if (canEditDocument($doc['document_id'])): ?>
                                            <a href="edit_document.php?id=<?php echo $doc['document_id']; ?>" class="btn">Edit</a>
                                            <?php endif; ?>
                                            <?php if (canDeleteDocument($doc['document_id'])): ?>
                                            <a href="delete_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this document?')">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No documents found matching your search.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Chatbot -->
    <div class="chatbot-container">
        <div class="chatbot-header" onclick="toggleChatbot()">
            <h3>Need Help? Ask me!</h3>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chat-message bot">
                Hello! How can I help you today?
            </div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatbotInput" placeholder="Type your question...">
            <button class="btn" onclick="sendMessage()">Send</button>
        </div>
    </div>

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
        // File upload preview
        document.getElementById('document_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });

        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUpload.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileUpload.classList.add('highlight');
        }

        function unhighlight(e) {
            fileUpload.classList.remove('highlight');
        }

        fileUpload.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('document_file').files = files;
            document.getElementById('file-name').textContent = files[0].name;
        }

        // Chatbot functionality
        function toggleChatbot() {
            const body = document.getElementById('chatbotBody');
            body.style.display = body.style.display === 'none' ? 'block' : 'none';
        }

        function sendMessage() {
            const input = document.getElementById('chatbotInput');
            const message = input.value.trim();
            
            if (message) {
                // Add user message
                addMessage(message, 'user');
                input.value = '';
                
                // Send to server
                fetch('../includes/chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'query=' + encodeURIComponent(message)
                })
                .then(response => response.json())
                .then(data => {
                    // Add bot response
                    addMessage(data.response, 'bot');
                })
                .catch(error => {
                    console.error('Error:', error);
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

        // Handle Enter key in chatbot input
        document.getElementById('chatbotInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html> 