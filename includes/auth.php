<?php
// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is employee
function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

// Function to require admin access
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: ../login.php");
        exit();
    }
}

// Function to require employee access
function requireEmployee() {
    if (!isLoggedIn() || !isEmployee()) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: ../login.php");
        exit();
    }
}

// Function to check document access
function canAccessDocument($document_id) {
    global $conn;
    
    if (isAdmin()) {
        return true; // Admins can access all documents
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check if document is approved
    $stmt = $conn->prepare("SELECT status FROM documents WHERE document_id = ?");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['status'] === 'approved';
    }
    
    return false;
}

// Function to check if user can edit document
function canEditDocument($document_id) {
    global $conn;
    
    if (isAdmin()) {
        return true; // Admins can edit all documents
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check if user is the uploader
    $stmt = $conn->prepare("SELECT uploaded_by FROM documents WHERE document_id = ?");
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['uploaded_by'] === $_SESSION['user_id'];
    }
    
    return false;
}

// Function to check if user can delete document
function canDeleteDocument($document_id) {
    return isAdmin(); // Only admins can delete documents
}

// Function to check if user can manage users
function canManageUsers() {
    return isAdmin(); // Only admins can manage users
}

// Function to check if user can manage categories
function canManageCategories() {
    return isAdmin(); // Only admins can manage categories
}

// Function to check if user can approve documents
function canApproveDocuments() {
    return isAdmin(); // Only admins can approve documents
}

// Function to get user permissions
function getUserPermissions() {
    $permissions = [
        'view_documents' => true, // All logged-in users can view approved documents
        'upload_documents' => isLoggedIn(), // All logged-in users can upload documents
        'edit_own_documents' => isLoggedIn(), // All logged-in users can edit their own documents
        'delete_documents' => isAdmin(), // Only admins can delete documents
        'manage_users' => isAdmin(), // Only admins can manage users
        'manage_categories' => isAdmin(), // Only admins can manage categories
        'approve_documents' => isAdmin(), // Only admins can approve documents
        'view_all_documents' => isAdmin(), // Only admins can view all documents
        'view_statistics' => isAdmin(), // Only admins can view statistics
    ];
    
    return $permissions;
}
?> 