<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/ContactMessage.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();
$error = '';
$success = '';

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $message_id = (int)$_POST['message_id'];
        
        if ($action === 'mark_read') {
            if (ContactMessage::markAsRead($message_id)) {
                $success = 'Message marked as read';
            }
        } elseif ($action === 'delete') {
            if (ContactMessage::delete($message_id)) {
                $success = 'Message archived successfully';
            }
        } elseif ($action === 'restore') {
            if (ContactMessage::restore($message_id)) {
                $success = 'Message restored successfully';
            }
        } elseif ($action === 'save_reply') {
            $reply = $_POST['reply'] ?? '';
            if (ContactMessage::saveReply($message_id, $reply)) {
                $success = 'Reply saved successfully';
            }
        }
    }
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'unread';
$view_archive = isset($_GET['archive']) && $_GET['archive'] === '1';

// Fetch messages based on view
if ($view_archive) {
    // Get archived messages
    global $conn;
    $sql = "SELECT * FROM contact_messages WHERE is_archived = TRUE ORDER BY updated_at DESC LIMIT 50";
    $messages = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
} else {
    // Get active messages with filter
    global $conn;
    
    if ($filter === 'unread') {
        $sql = "SELECT * FROM contact_messages WHERE is_read = FALSE AND is_archived = FALSE ORDER BY created_at DESC LIMIT 50";
    } elseif ($filter === 'read') {
        $sql = "SELECT * FROM contact_messages WHERE is_read = TRUE AND reply IS NULL AND is_archived = FALSE ORDER BY created_at DESC LIMIT 50";
    } elseif ($filter === 'replied') {
        $sql = "SELECT * FROM contact_messages WHERE reply IS NOT NULL AND is_archived = FALSE ORDER BY created_at DESC LIMIT 50";
    } else { // 'all'
        $sql = "SELECT * FROM contact_messages WHERE is_archived = FALSE ORDER BY created_at DESC LIMIT 50";
    }
    
    $messages = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$unread_count = ContactMessage::getUnreadCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Feedback & Messages - CLINICare Admin</title>
    <link href="../bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../fontawesome-free-7.1.0-web/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #fff 100%);
            font-family: 'Poppins', sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 10px;
        }

        .sidebar-menu a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .messages-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .messages-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .messages-header h3 {
            margin: 0;
            font-weight: 700;
        }

        .badge-unread {
            background: #ffc107;
            color: #000;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #333;
        }

        .filter-btn:not(a) {
            font-family: 'Poppins', sans-serif;
        }

        .filter-btn.active {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }

        .filter-btn:hover {
            border-color: #dc3545;
            color: #dc3545;
        }

        .filter-btn.active:hover {
            color: white;
        }

        .messages-list {
            padding: 0;
        }

        .message-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            cursor: pointer;
            transition: all 0.3s;
        }

        .message-item:hover {
            background: #f9f9f9;
        }

        .message-item.unread {
            background: #f0f7ff;
            border-left: 4px solid #dc3545;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
        }

        .message-from {
            font-weight: 600;
            color: #333;
            font-size: 15px;
        }

        .message-email {
            color: #666;
            font-size: 13px;
        }

        .message-time {
            color: #999;
            font-size: 12px;
            margin-left: auto;
        }

        .message-subject {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .message-preview {
            color: #666;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 600px;
        }

        .message-status {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-unread {
            background: #ffc107;
            color: #000;
        }

        .status-read {
            background: #e0e0e0;
            color: #333;
        }

        .status-replied {
            background: #28a745;
            color: white;
        }

        .message-detail {
            display: none;
            padding: 30px;
            border-top: 1px solid #f0f0f0;
            animation: slideDown 0.3s ease;
        }

        .message-detail.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .detail-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-from {
            font-weight: 700;
            font-size: 18px;
            color: #333;
            margin-bottom: 8px;
        }

        .detail-meta {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            font-size: 14px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .meta-value {
            color: #333;
            font-weight: 600;
        }

        .detail-subject {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .detail-message {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.6;
            color: #555;
        }

        .reply-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .reply-section h5 {
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .reply-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-mark-read {
            background: #e0e0e0;
            color: #333;
        }

        .btn-mark-read:hover {
            background: #d0d0d0;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-save-reply {
            background: #28a745;
            color: white;
        }

        .btn-save-reply:hover {
            background: #218838;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .detail-meta {
                grid-template-columns: 1fr;
            }

            .message-preview {
                max-width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-envelope"></i> Messages
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="messages.php" class="active"><i class="fas fa-envelope"></i> Patient Feedback</a></li>
            <li><a href="manage_consultation_requests.php"><i class="fas fa-stethoscope"></i> Consultations</a></li>
            <li><a href="manage_medicine_requests.php"><i class="fas fa-pills"></i> Medicine</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="messages-container">
            <!-- Header -->
            <div class="messages-header">
                <div>
                    <h3><i class="fas fa-envelope"></i> <?php echo $view_archive ? 'Archived Messages' : 'Patient Feedback & Messages'; ?></h3>
                    <?php if (!$view_archive): ?>
                        <small style="color: rgba(255,255,255,0.8); margin-top: 5px; display: block;">Filter: <?php echo ucfirst($filter); ?></small>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!$view_archive): ?>
                        <span class="badge-unread"><?php echo $unread_count; ?> Unread</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" style="margin: 15px; border-radius: 8px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button type="button" class="filter-btn <?php echo ($filter === 'unread' && !$view_archive ? 'active' : ''); ?>" onclick="location.href='admin/messages.php?filter=unread'; return false;">
                    <i class="fas fa-inbox"></i> Unread
                </button>
                <button type="button" class="filter-btn <?php echo ($filter === 'read' && !$view_archive ? 'active' : ''); ?>" onclick="location.href='admin/messages.php?filter=read'; return false;">
                    <i class="fas fa-envelope-open"></i> Read
                </button>
                <button type="button" class="filter-btn <?php echo ($filter === 'replied' && !$view_archive ? 'active' : ''); ?>" onclick="location.href='admin/messages.php?filter=replied'; return false;">
                    <i class="fas fa-reply"></i> Replied
                </button>
                <button type="button" class="filter-btn <?php echo ($filter === 'all' && !$view_archive ? 'active' : ''); ?>" onclick="location.href='admin/messages.php?filter=all'; return false;">
                    <i class="fas fa-list"></i> All
                </button>
                <button type="button" class="filter-btn <?php echo ($view_archive ? 'active' : ''); ?>" onclick="location.href='admin/messages.php?archive=1'; return false;">
                    <i class="fas fa-archive"></i> Archive
                </button>
                <?php if ($view_archive): ?>
                    <button type="button" class="filter-btn" onclick="location.href='admin/messages.php'; return false;">
                        <i class="fas fa-inbox"></i> Back to Inbox
                    </button>
                <?php endif; ?>
            </div>

            <!-- Messages List -->
            <div class="messages-list">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No messages found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                        $has_reply = !empty($msg['reply']);
                        $read_status = $msg['is_read'] ? 'read' : 'unread';
                        $display_status = $has_reply ? 'replied' : $read_status;
                        $item_class = !$msg['is_read'] ? 'unread' : '';
                        ?>
                        <div class="message-item <?php echo $item_class; ?>" onclick="toggleDetail(this)">
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-from"><?php echo htmlspecialchars($msg['name']); ?></span>
                                    <span class="message-email"><?php echo htmlspecialchars($msg['email']); ?></span>
                                    <span class="message-time"><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></span>
                                </div>
                                <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                <div class="message-preview"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>...</div>
                            </div>
                            <div class="message-status">
                                <span class="status-badge status-<?php echo $display_status; ?>">
                                    <?php echo $has_reply ? 'Replied' : ucfirst($read_status); ?>
                                </span>
                            </div>

                            <!-- Message Detail (Hidden) -->
                            <div class="message-detail" data-message-id="<?php echo $msg['id']; ?>">
                                <div class="detail-header">
                                    <div class="detail-from"><?php echo htmlspecialchars($msg['name']); ?></div>
                                    <div class="detail-meta">
                                        <div class="meta-item">
                                            <span class="meta-label">Email</span>
                                            <span class="meta-value"><?php echo htmlspecialchars($msg['email']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Received</span>
                                            <span class="meta-value"><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <span class="meta-label">Status</span>
                                            <span class="meta-value"><?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="detail-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                <div class="detail-message"><?php echo htmlspecialchars($msg['message']); ?></div>

                                <!-- Show existing reply if available -->
                                <?php if (!empty($msg['reply'])): ?>
                                    <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                                        <h6 style="color: #155724; margin-bottom: 10px;"><i class="fas fa-check-circle"></i> Admin Reply</h6>
                                        <div style="white-space: pre-wrap; color: #155724;"><?php echo htmlspecialchars($msg['reply']); ?></div>
                                    </div>
                                <?php endif; ?>

                                <!-- Reply Form -->
                                <div class="reply-section">
                                    <h5><i class="fas fa-reply"></i> Send Reply</h5>
                                    <form method="POST" style="margin: 0;">
                                        <textarea name="reply" placeholder="Type your response here..." required></textarea>
                                        <div class="action-buttons">
                                            <button type="submit" name="action" value="save_reply" class="btn-small btn-save-reply">
                                                <i class="fas fa-paper-plane"></i> Send Reply
                                            </button>
                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        </div>
                                    </form>

                                    <div style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                                        <?php if (!$view_archive): ?>
                                            <?php if (!$msg['is_read']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <button type="submit" name="action" value="mark_read" class="btn-small btn-mark-read">
                                                        <i class="fas fa-check"></i> Mark as Read
                                                    </button>
                                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" style="display: inline;">
                                                <button type="submit" name="action" value="delete" class="btn-small btn-delete" onclick="return confirm('Archive this message?');">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
                                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <button type="submit" name="action" value="restore" class="btn-small" style="background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDetail(element) {
            const detail = element.querySelector('.message-detail');
            const isShowing = detail.classList.contains('show');

            // Close all other details
            document.querySelectorAll('.message-detail.show').forEach(el => {
                if (el !== detail) {
                    el.classList.remove('show');
                    el.parentElement.style.maxHeight = '150px';
                }
            });

            // Toggle current detail
            if (isShowing) {
                detail.classList.remove('show');
                element.style.maxHeight = '150px';
            } else {
                detail.classList.add('show');
                element.style.maxHeight = 'none';
            }
        }

        // Mark as read when opening message
        document.querySelectorAll('.message-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!e.target.closest('form')) {
                    const messageId = this.querySelector('.message-detail').dataset.messageId;
                    // Optionally auto-mark as read when opened
                    // fetch('mark_read.php?id=' + messageId);
                }
            });
        });
    </script>
</body>
</html>
