<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if document ID is provided
if (!isset($_GET['id'])) {
    header("Location: documents.php");
    exit();
}

$document_id = $_GET['id'];

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Get document data
$stmt = $conn->prepare("SELECT * FROM documents WHERE document_id = ?");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();

if (!$document) {
    header("Location: documents.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $status = $_POST['status'];

    try {
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            // Handle new file upload
            $file = $_FILES['document_file'];
            $file_name = time() . '_' . basename($file['name']);
            $target_path = '../uploads/' . $file_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old file
                if (file_exists('../uploads/' . $document['file_path'])) {
                    unlink('../uploads/' . $document['file_path']);
                }

                $stmt = $conn->prepare("UPDATE documents SET title = ?, description = ?, file_path = ?, category_id = ?, status = ? WHERE document_id = ?");
                $stmt->bind_param("sssisi", $title, $description, $file_name, $category_id, $status, $document_id);
            } else {
                $error = "Error moving uploaded file!";
            }
        } else {
            // Update without changing file
            $stmt = $conn->prepare("UPDATE documents SET title = ?, description = ?, category_id = ?, status = ? WHERE document_id = ?");
            $stmt->bind_param("ssisi", $title, $description, $category_id, $status, $document_id);
        }

        if (isset($stmt) && $stmt->execute()) {
            $_SESSION['success'] = "Document updated successfully!";
            header("Location: documents.php");
            exit();
        } else {
            $error = "Error updating document: " . $stmt->error;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document - Mart KMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/sparkle.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">Mart KMS</a>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="documents.php">Documents</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="card">
            <h1>Edit Document</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="login-form">
                <div class="form-group">
                    <label for="title">Document Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($document['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($document['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo $category['category_id'] == $document['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending" <?php echo $document['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $document['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $document['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document_file">Current File: <?php echo htmlspecialchars($document['file_path']); ?></label>
                    <input type="file" id="document_file" name="document_file">
                    <small>Leave empty to keep the current file</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">Update Document</button>
                    <a href="documents.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
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