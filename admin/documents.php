<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php?type=admin");
    exit();
}

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    
    $file = $_FILES['document'];
    $file_name = time() . '_' . basename($file['name']);
    $target_dir = "../uploads/";
    $target_file = $target_dir . $file_name;
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Insert document into database
        $stmt = $conn->prepare("
            INSERT INTO documents (title, description, file_path, file_type, file_size, category_id, uploaded_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')
        ");
        $stmt->bind_param("ssssiii", $title, $description, $file_name, $file['type'], $file['size'], $category_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $document_id = $conn->insert_id;
            
            // Add tags
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    // Check if tag exists
                    $stmt = $conn->prepare("SELECT tag_id FROM tags WHERE name = ?");
                    $stmt->bind_param("s", $tag);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 0) {
                        // Create new tag
                        $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
                        $stmt->bind_param("s", $tag);
                        $stmt->execute();
                        $tag_id = $conn->insert_id;
                    } else {
                        $tag_id = $result->fetch_assoc()['tag_id'];
                    }
                    
                    // Link tag to document
                    $stmt = $conn->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $document_id, $tag_id);
                    $stmt->execute();
                }
            }
            
            $success = "Document uploaded successfully";
        } else {
            $error = "Error uploading document";
        }
    } else {
        $error = "Error moving uploaded file";
    }
}

// Get all documents
$stmt = $conn->prepare("
    SELECT d.*, c.name as category_name, u.username as uploader 
    FROM documents d 
    LEFT JOIN categories c ON d.category_id = c.category_id 
    LEFT JOIN users u ON d.uploaded_by = u.user_id 
    ORDER BY d.created_at DESC
");
$stmt->execute();
$documents = $stmt->get_result();

// Get categories for dropdown
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - Mart KMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mart KMS</div>
            <ul>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <li><a href="users.php">Manage Users</a></li>
                <li><a href="documents.php">Manage Documents</a></li>
                <li><a href="../chatbot.php">Chatbot</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard">
            <h1>Manage Documents</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="dashboard-card">
                <h2>Upload New Document</h2>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" name="tags" placeholder="e.g., important, guide, manual">
                    </div>
                    
                    <div class="form-group">
                        <label for="document">Document File</label>
                        <input type="file" id="document" name="document" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </form>
            </div>

            <div class="dashboard-card" style="margin-top: 2rem;">
                <h2>Document List</h2>
                <div class="document-list">
                    <?php if ($documents->num_rows > 0): ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Uploader</th>
                                    <th>Status</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($doc = $documents->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['uploader']); ?></td>
                                        <td><?php echo ucfirst($doc['status']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></td>
                                        <td>
                                            <a href="../view_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-primary">View</a>
                                            <a href="edit_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-secondary">Edit</a>
                                            <a href="delete_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-error" onclick="return confirm('Are you sure you want to delete this document?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No documents found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="contact-info">
                <h3>Contact Us</h3>
                <p>Email: info@mart.com</p>
                <p>Phone: (555) 123-4567</p>
                <p>Address: 123 Business Street, City, Country</p>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Mart. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 