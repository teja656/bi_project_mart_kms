<?php
session_start();
require_once 'config/database.php';

class Chatbot {
    private $conn;
    private $knowledge_base = [
        'upload' => 'To upload a document, go to the Content Management page and use the upload form. You can drag and drop files or click to select them.',
        'search' => 'You can search for documents using the search bar at the top of the Content Management page. Search by title or description.',
        'approve' => 'Only administrators can approve documents. Go to the admin dashboard to manage document approvals.',
        'categories' => 'Documents are organized into categories for better organization. You can filter documents by category.',
        'help' => 'I can help you with: uploading documents, searching, approvals, categories, and general system usage.',
        'default' => 'I\'m not sure about that. Please try rephrasing your question or contact support for assistance.'
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getResponse($query) {
        $query = strtolower($query);
        
        // Check for keywords in the query
        foreach ($this->knowledge_base as $keyword => $response) {
            if (strpos($query, $keyword) !== false) {
                return $response;
            }
        }

        // If no specific match, try to find relevant documents
        $relevant_docs = $this->findRelevantDocuments($query);
        if (!empty($relevant_docs)) {
            return "I found some relevant documents that might help: " . implode(", ", $relevant_docs);
        }

        return $this->knowledge_base['default'];
    }

    private function findRelevantDocuments($query) {
        $relevant_docs = [];
        $search_term = '%' . $query . '%';
        
        $stmt = $this->conn->prepare("SELECT title FROM documents WHERE status = 'approved' AND (title LIKE ? OR description LIKE ?) LIMIT 3");
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $relevant_docs[] = $row['title'];
        }
        
        return $relevant_docs;
    }
}

// Handle AJAX requests
if (isset($_POST['query'])) {
    $chatbot = new Chatbot($conn);
    $response = $chatbot->getResponse($_POST['query']);
    echo json_encode(['response' => $response]);
    exit();
}
?> 