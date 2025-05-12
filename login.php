<?php
session_start();
require_once 'config/database.php';

$error = '';
$type = isset($_GET['type']) ? $_GET['type'] : 'employee';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($type == 'admin' && $user['role'] != 'admin') {
                $error = "Access denied. Admin login required.";
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login
                $update = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
                $update->bind_param("i", $user['user_id']);
                $update->execute();
                
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($type); ?> Login - Mart KMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/sparkle.js" defer></script>
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mart KMS</div>
            <ul>
                <li><a href="index.php">Home</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="login-form">
            <h2><?php echo ucfirst($type); ?> Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
            </form>
            
            <p style="text-align: center; margin-top: 1rem;">
                <?php if ($type == 'admin'): ?>
                    <a href="login.php?type=employee">Switch to Employee Login</a>
                <?php else: ?>
                    <a href="login.php?type=admin">Switch to Admin Login</a>
                <?php endif; ?>
            </p>
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