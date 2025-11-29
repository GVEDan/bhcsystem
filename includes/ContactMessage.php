<?php
class ContactMessage {
    
    // Save contact form message to database
    public static function save($name, $email, $subject, $message) {
        global $conn;
        try {
            $sql = "INSERT INTO contact_messages (name, email, subject, message, is_read)
                    VALUES (?, ?, ?, ?, FALSE)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param('ssss', $name, $email, $subject, $message);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Contact message save error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get all contact messages (for admin)
    public static function getAllMessages($limit = 20, $offset = 0, $read_status = null) {
        global $conn;
        try {
            if ($read_status !== null) {
                $sql = "SELECT * FROM contact_messages WHERE is_read = ? AND is_archived = FALSE ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $read_status = (int)$read_status;
                $stmt->bind_param('iii', $read_status, $limit, $offset);
            } else {
                $sql = "SELECT * FROM contact_messages WHERE is_archived = FALSE ORDER BY created_at DESC LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ii', $limit, $offset);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Get messages error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get unread messages count
    public static function getUnreadCount() {
        global $conn;
        try {
            $sql = "SELECT COUNT(*) as count FROM contact_messages WHERE is_read = FALSE AND is_archived = FALSE";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get single message by ID
    public static function getMessageById($id) {
        global $conn;
        try {
            $sql = "SELECT * FROM contact_messages WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Get message error: " . $e->getMessage());
            return null;
        }
    }
    
    // Mark message as read
    public static function markAsRead($id) {
        global $conn;
        try {
            $sql = "UPDATE contact_messages SET is_read = TRUE, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    // Save admin reply to message
    public static function saveReply($id, $reply) {
        global $conn;
        try {
            $sql = "UPDATE contact_messages 
                    SET reply = ?, is_read = TRUE, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $reply, $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Save reply error: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete message (soft delete - archive instead)
    public static function delete($id) {
        global $conn;
        try {
            $sql = "UPDATE contact_messages SET is_archived = TRUE, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Archive message error: " . $e->getMessage());
            return false;
        }
    }
    
    // Restore archived message
    public static function restore($id) {
        global $conn;
        try {
            $sql = "UPDATE contact_messages SET is_archived = FALSE, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Restore message error: " . $e->getMessage());
            return false;
        }
    }
    
    // Search messages by keyword
    public static function search($keyword) {
        global $conn;
        try {
            $sql = "SELECT * FROM contact_messages 
                    WHERE (name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)
                    AND is_archived = FALSE
                    ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $searchTerm = "%" . $keyword . "%";
            $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [];
        }
    }
}
?>
