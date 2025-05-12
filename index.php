<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mart - Knowledge Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">Mart KMS</div>
            <ul>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Welcome to Mart Knowledge Management System</h1>
            <p>Your centralized platform for knowledge sharing and document management</p>
            <div class="login-buttons">
                <a href="login.php?type=admin" class="btn btn-primary">Admin Login</a>
                <a href="login.php?type=employee" class="btn btn-secondary">Employee Login</a>
            </div>
        </section>

        <section id="about" class="about">
            <h2>About Mart</h2>
            <p>Mart is a leading company dedicated to excellence in knowledge management and information sharing. Our Knowledge Management System provides a secure and efficient platform for employees to access, share, and manage important documents and information.</p>
            <div class="features">
                <div class="feature">
                    <h3>Document Management</h3>
                    <p>Upload, organize, and access documents with ease</p>
                </div>
                <div class="feature">
                    <h3>Knowledge Sharing</h3>
                    <p>Share and collaborate on important information</p>
                </div>
                <div class="feature">
                    <h3>Secure Access</h3>
                    <p>Role-based access control for enhanced security</p>
                </div>
            </div>
        </section>
    </main>

    <footer id="contact">
        <div class="footer-content">
            <div class="contact-info">
                <h3>Contact Us</h3>
                <p>Email:kaluguriteja@gmail.com</p>
                <p>Phone: (+91) 9390689839</p>
                <p>Address: bhagya nagar, bengaluru, india</p>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Mart. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 