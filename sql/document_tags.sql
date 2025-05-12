CREATE TABLE IF NOT EXISTS document_tags (
    tag_id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    tag VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(document_id) ON DELETE CASCADE,
    UNIQUE KEY unique_document_tag (document_id, tag)
); 