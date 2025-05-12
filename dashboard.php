<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user's recent document views
$stmt = $conn->prepare("
    SELECT d.title, d.created_at, dv.viewed_at 
    FROM document_views dv 
    JOIN documents d ON dv.document_id = d.document_id 
    WHERE dv.user_id = ? 
    ORDER BY dv.viewed_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_views = $stmt->get_result();

// Get total documents count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM documents WHERE status = 'approved'");
$stmt->execute();
$total_docs = $stmt->get_result()->fetch_assoc()['total'];

// Get most viewed documents (for admin)
$most_viewed = [];
if ($role == 'admin') {
    $stmt = $conn->prepare("
        SELECT d.title, COUNT(dv.view_id) as view_count 
        FROM documents d 
        LEFT JOIN document_views dv ON d.document_id = dv.document_id 
        GROUP BY d.document_id 
        ORDER BY view_count DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $most_viewed = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mart KMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mart KMS</div>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if ($role == 'admin'): ?>
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
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <div class="search-bar">
                    <form action="search.php" method="GET">
                        <input type="text" name="q" placeholder="Search documents...">
                    </form>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Quick Stats</h3>
                    <p>Total Documents: <?php echo $total_docs; ?></p>
                    <?php if ($role == 'admin'): ?>
                        <p>Pending Approvals: 
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM documents WHERE status = 'pending'");
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['pending'];
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="dashboard-card">
                    <h3>Recent Views</h3>
                    <?php if ($recent_views->num_rows > 0): ?>
                        <ul>
                            <?php while ($view = $recent_views->fetch_assoc()): ?>
                                <li>
                                    <?php echo htmlspecialchars($view['title']); ?>
                                    <small>(<?php echo date('M d, Y', strtotime($view['viewed_at'])); ?>)</small>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent document views</p>
                    <?php endif; ?>
                </div>

                <?php if ($role == 'admin'): ?>
                    <div class="dashboard-card">
                        <h3>Most Viewed Documents</h3>
                        <?php if ($most_viewed->num_rows > 0): ?>
                            <ul>
                                <?php while ($doc = $most_viewed->fetch_assoc()): ?>
                                    <li>
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                        <small>(<?php echo $doc['view_count']; ?> views)</small>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>No document views yet</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($role == 'employee'): ?>
                <div class="dashboard-card" style="margin-top: 2rem;">
                    <h3>Submit New Knowledge</h3>
                    <form action="submit_article.php" method="POST">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit for Review</button>
                    </form>
                </div>
            <?php endif; ?>
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