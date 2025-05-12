<?php
require_once 'config/database.php';

class AutoTagger {
    private $conn;
    private $common_words = ['the', 'and', 'is', 'in', 'to', 'of', 'a', 'for', 'with', 'on', 'at'];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function generateTags($title, $description) {
        // Combine title and description
        $text = strtolower($title . ' ' . $description);
        
        // Remove special characters and split into words
        $words = str_word_count(preg_replace('/[^a-zA-Z0-9\s]/', '', $text), 1);
        
        // Remove common words
        $words = array_diff($words, $this->common_words);
        
        // Count word frequency
        $word_freq = array_count_values($words);
        
        // Get existing tags from database
        $existing_tags = $this->getExistingTags();
        
        // Find matching tags
        $suggested_tags = [];
        foreach ($word_freq as $word => $freq) {
            if (strlen($word) > 3 && $freq > 1) { // Only consider words longer than 3 chars and appearing more than once
                foreach ($existing_tags as $tag) {
                    if (strpos($tag, $word) !== false || strpos($word, $tag) !== false) {
                        $suggested_tags[] = $tag;
                    }
                }
            }
        }
        
        // Add new potential tags
        foreach ($word_freq as $word => $freq) {
            if (strlen($word) > 3 && $freq > 1 && !in_array($word, $suggested_tags)) {
                $suggested_tags[] = $word;
            }
        }
        
        return array_unique($suggested_tags);
    }
    
    private function getExistingTags() {
        $tags = [];
        $result = $this->conn->query("SELECT DISTINCT tag FROM document_tags");
        while ($row = $result->fetch_assoc()) {
            $tags[] = $row['tag'];
        }
        return $tags;
    }
    
    public function saveTags($document_id, $tags) {
        // First, remove existing tags
        $stmt = $this->conn->prepare("DELETE FROM document_tags WHERE document_id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        
        // Then, insert new tags
        $stmt = $this->conn->prepare("INSERT INTO document_tags (document_id, tag) VALUES (?, ?)");
        foreach ($tags as $tag) {
            $stmt->bind_param("is", $document_id, $tag);
            $stmt->execute();
        }
    }
}
?> 