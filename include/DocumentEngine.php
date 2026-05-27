<?php
/**
 * DocumentEngine for CLMS
 * Manages document uploads, types, and verification.
 */
class DocumentEngine {
    private $conn;
    private $upload_dir = __DIR__ . '/../uploads/documents/';

    public function __construct($conn) {
        $this->conn = $conn;
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    public function upload($workman_id, $type, $file) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $workman_id . '_' . $type . '_' . time() . '.' . $ext;
        $target = $this->upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $this->conn->prepare("INSERT INTO documents (workman_id, type, file_path, status) VALUES (?, ?, ?, 'pending') ON DUPLICATE KEY UPDATE file_path = ?, status = 'pending'");
            $stmt->bind_param("isss", $workman_id, $type, $filename, $filename);
            return $stmt->execute();
        }
        return false;
    }

    public function verify($doc_id, $status, $reason = null) {
        $stmt = $this->conn->prepare("UPDATE documents SET status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $reason, $doc_id);
        return $stmt->execute();
    }

    public function getWorkmanDocuments($workman_id) {
        $stmt = $this->conn->prepare("SELECT * FROM documents WHERE workman_id = ?");
        $stmt->bind_param("i", $workman_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

