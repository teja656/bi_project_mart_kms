<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if document ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$document_id = $_GET['id'];

// Get document data with category and uploader info
$stmt = $conn->prepare("
    SELECT d.*, c.name as category_name, u.username as uploaded_by 
    FROM documents d 
    LEFT JOIN categories c ON d.category_id = c.category_id 
    LEFT JOIN users u ON d.uploaded_by = u.user_id 
    WHERE d.document_id = ?
");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();

if (!$document) {
    header("Location: index.php");
    exit();
}

// Record document view
$view_stmt = $conn->prepare("INSERT INTO document_views (document_id, user_id) VALUES (?, ?)");
$view_stmt->bind_param("ii", $document_id, $_SESSION['user_id']);
$view_stmt->execute();

// Handle file download
if (isset($_GET['download'])) {
    $file_path = 'uploads/' . $document['file_path'];
    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($document['file_path']) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($document['title']); ?> - Mart KMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/sparkle.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">Mart KMS</a>
            <ul>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin/dashboard.php">Dashboard</a></li>
                    <li><a href="admin/documents.php">Documents</a></li>
                    <li><a href="admin/users.php">Users</a></li>
                <?php endif; ?>
                <li><a href="search.php">Search</a></li>
                <li><a href="chatbot.php">Chatbot</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="card">
            <div class="document-header">
                <h1><?php echo htmlspecialchars($document['title']); ?></h1>
                <div class="status-badge status-<?php echo strtolower($document['status']); ?>">
                    <?php echo ucfirst($document['status']); ?>
                </div>
            </div>

            <div class="document-meta">
                <p><strong>Category:</strong> <?php echo htmlspecialchars($document['category_name']); ?></p>
                <p><strong>Uploaded by:</strong> <?php echo htmlspecialchars($document['uploaded_by']); ?></p>
                <p><strong>Upload date:</strong> <?php echo date('F d, Y', strtotime($document['uploaded_at'])); ?></p>
            </div>

            <div class="document-description">
                <h2>Description</h2>
                <p><?php echo nl2br(htmlspecialchars($document['description'])); ?></p>
            </div>

            <div class="document-actions">
                <a href="?id=<?php echo $document_id; ?>&download=1" class="btn">Download Document</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/edit_document.php?id=<?php echo $document_id; ?>" class="btn btn-secondary">Edit Document</a>
                <?php endif; ?>
                <a href="javascript:history.back()" class="btn btn-secondary">Back</a>
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
</body>
</html> 