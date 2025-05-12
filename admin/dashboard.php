<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get total documents count
$total_docs_query = "SELECT COUNT(*) as total FROM documents";
$total_docs_result = $conn->query($total_docs_query);
$total_docs = $total_docs_result->fetch_assoc()['total'];

// Get pending approvals count
$pending_query = "SELECT COUNT(*) as pending FROM documents WHERE status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_count = $pending_result->fetch_assoc()['pending'];

// Get total users count
$users_query = "SELECT COUNT(*) as total FROM users";
$users_result = $conn->query($users_query);
$users_count = $users_result->fetch_assoc()['total'];

// Get total views count
$views_query = "SELECT COUNT(*) as total FROM document_views";
$views_result = $conn->query($views_query);
$views_count = $views_result->fetch_assoc()['total'];

// Get recent views
$recent_views_query = "SELECT d.title, dv.viewed_at, u.username 
                      FROM document_views dv 
                      JOIN documents d ON dv.document_id = d.document_id 
                      JOIN users u ON dv.user_id = u.user_id
                      ORDER BY dv.viewed_at DESC 
                      LIMIT 5";
$recent_views_result = $conn->query($recent_views_query);

// Get most viewed documents
$most_viewed_query = "SELECT d.title, COUNT(dv.view_id) as view_count 
                     FROM documents d 
                     LEFT JOIN document_views dv ON d.document_id = dv.document_id 
                     GROUP BY d.document_id 
                     ORDER BY view_count DESC 
                     LIMIT 5";
$most_viewed_result = $conn->query($most_viewed_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mart KMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/sparkle.js" defer></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0.5rem 0;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard-section {
            margin-bottom: 2rem;
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        .activity-list {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: var(--text-color);
        }

        .activity-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .activity-count {
            background: var(--primary-color);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }

        .action-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">Mart KMS</a>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="documents.php">Documents</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="../chatbot.php">Chatbot</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-number"><?php echo $total_docs; ?></div>
                <div class="stat-label">Total Documents</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Approvals</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $users_count; ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-number"><?php echo $views_count; ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>

        <div class="dashboard-section">
            <h2 class="section-title">Recent Activity</h2>
            <div class="activity-list">
                <?php while ($view = $recent_views_result->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title"><?php echo htmlspecialchars($view['title']); ?></div>
                            <div class="activity-meta">
                                Viewed by <?php echo htmlspecialchars($view['username']); ?> • 
                                <?php echo date('M d, Y H:i', strtotime($view['viewed_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="dashboard-section">
            <h2 class="section-title">Most Viewed Documents</h2>
            <div class="activity-list">
                <?php while ($doc = $most_viewed_result->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-info">
                            <div class="activity-title"><?php echo htmlspecialchars($doc['title']); ?></div>
                        </div>
                        <div class="activity-count"><?php echo $doc['view_count']; ?> views</div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="quick-actions">
            <a href="add_document.php" class="action-card">
                <div class="action-icon">📤</div>
                <h3>Upload Document</h3>
                <p>Add new documents to the system</p>
            </a>

            <a href="users.php" class="action-card">
                <div class="action-icon">👥</div>
                <h3>Manage Users</h3>
                <p>View and manage user accounts</p>
            </a>

            <a href="documents.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>View Documents</h3>
                <p>Browse and manage all documents</p>
            </a>

            <a href="../search.php" class="action-card">
                <div class="action-icon">🔍</div>
                <h3>Search</h3>
                <p>Search through documents</p>
            </a>
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