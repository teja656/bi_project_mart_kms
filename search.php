<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$tag_filter = isset($_GET['tag']) ? $_GET['tag'] : '';

// Get all categories for filter
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->get_result();

// Get all tags for filter
$stmt = $conn->prepare("SELECT * FROM tags ORDER BY name");
$stmt->execute();
$tags = $stmt->get_result();

// Build search query
$query = "
    SELECT DISTINCT d.*, c.name as category_name, u.username as uploader 
    FROM documents d 
    LEFT JOIN categories c ON d.category_id = c.category_id 
    LEFT JOIN users u ON d.uploaded_by = u.user_id 
    LEFT JOIN document_tags dt ON d.document_id = dt.document_id 
    LEFT JOIN tags t ON dt.tag_id = t.tag_id 
    WHERE d.status = 'approved'
";

$params = [];
$types = "";

if ($search_query) {
    $query .= " AND (d.title LIKE ? OR d.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($category_filter) {
    $query .= " AND d.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if ($tag_filter) {
    $query .= " AND t.tag_id = ?";
    $params[] = $tag_filter;
    $types .= "i";
}

$query .= " ORDER BY d.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$documents = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Mart KMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mart KMS</div>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin/users.php">Manage Users</a></li>
                    <li><a href="admin/documents.php">Manage Documents</a></li>
                <?php endif; ?>
                <li><a href="documents.php">Documents</a></li>
                <li><a href="chatbot.php">Chatbot</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard">
            <div class="dashboard-header">
                <h1>Search Results</h1>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>

            <div class="dashboard-card">
                <form action="search.php" method="GET" class="search-form">
                    <div class="form-group">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search documents...">
                    </div>
                    
                    <div class="form-group">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <select name="tag">
                            <option value="">All Tags</option>
                            <?php while ($tag = $tags->fetch_assoc()): ?>
                                <option value="<?php echo $tag['tag_id']; ?>" <?php echo $tag_filter == $tag['tag_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <div class="search-results">
                    <?php if ($documents->num_rows > 0): ?>
                        <h2>Found <?php echo $documents->num_rows; ?> document(s)</h2>
                        <div class="document-list">
                            <?php while ($doc = $documents->fetch_assoc()): ?>
                                <div class="document-item">
                                    <div class="document-info">
                                        <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                                        <p>
                                            <strong>Category:</strong> <?php echo htmlspecialchars($doc['category_name']); ?> |
                                            <strong>Uploaded by:</strong> <?php echo htmlspecialchars($doc['uploader']); ?> |
                                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($doc['created_at'])); ?>
                                        </p>
                                        <?php if ($doc['description']): ?>
                                            <p><?php echo htmlspecialchars(substr($doc['description'], 0, 200)) . '...'; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="document-actions">
                                        <a href="view_document.php?id=<?php echo $doc['document_id']; ?>" class="btn btn-primary">View</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>No documents found matching your search criteria.</p>
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