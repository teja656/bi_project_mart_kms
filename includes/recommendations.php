<?php
require_once 'config/database.php';

class DocumentRecommender {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getRecommendations($user_id, $limit = 5) {
        // Get user's recently viewed documents
        $recent_views = $this->getRecentViews($user_id);
        
        if (empty($recent_views)) {
            // If no recent views, return most viewed documents
            return $this->getMostViewedDocuments($limit);
        }
        
        // Get recommendations based on similar documents
        $recommendations = $this->getSimilarDocuments($recent_views, $limit);
        
        // If not enough recommendations, add most viewed documents
        if (count($recommendations) < $limit) {
            $most_viewed = $this->getMostViewedDocuments($limit - count($recommendations));
            $recommendations = array_merge($recommendations, $most_viewed);
        }
        
        return array_slice($recommendations, 0, $limit);
    }
    
    private function getRecentViews($user_id) {
        $stmt = $this->conn->prepare("
            SELECT d.document_id, d.title, d.description, d.category_id
            FROM document_views v
            JOIN documents d ON v.document_id = d.document_id
            WHERE v.user_id = ? AND d.status = 'approved'
            ORDER BY v.viewed_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function getMostViewedDocuments($limit) {
        $stmt = $this->conn->prepare("
            SELECT d.document_id, d.title, d.description, COUNT(v.view_id) as view_count
            FROM documents d
            LEFT JOIN document_views v ON d.document_id = v.document_id
            WHERE d.status = 'approved'
            GROUP BY d.document_id
            ORDER BY view_count DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function getSimilarDocuments($recent_views, $limit) {
        if (empty($recent_views)) {
            return [];
        }
        
        $document_ids = array_column($recent_views, 'document_id');
        $category_ids = array_column($recent_views, 'category_id');
        
        // Get documents from same categories
        $stmt = $this->conn->prepare("
            SELECT DISTINCT d.document_id, d.title, d.description, COUNT(v.view_id) as view_count
            FROM documents d
            LEFT JOIN document_views v ON d.document_id = v.document_id
            WHERE d.status = 'approved'
            AND d.document_id NOT IN (" . implode(',', $document_ids) . ")
            AND d.category_id IN (" . implode(',', $category_ids) . ")
            GROUP BY d.document_id
            ORDER BY view_count DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getRelatedDocuments($document_id, $limit = 5) {
        // Get document details
        $stmt = $this->conn->prepare("
            SELECT category_id, title, description
            FROM documents
            WHERE document_id = ?
        ");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $document = $stmt->get_result()->fetch_assoc();
        
        if (!$document) {
            return [];
        }
        
        // Get documents from same category
        $stmt = $this->conn->prepare("
            SELECT d.document_id, d.title, d.description, COUNT(v.view_id) as view_count
            FROM documents d
            LEFT JOIN document_views v ON d.document_id = v.document_id
            WHERE d.status = 'approved'
            AND d.document_id != ?
            AND d.category_id = ?
            GROUP BY d.document_id
            ORDER BY view_count DESC
            LIMIT ?
        ");
        $stmt->bind_param("iii", $document_id, $document['category_id'], $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?> 