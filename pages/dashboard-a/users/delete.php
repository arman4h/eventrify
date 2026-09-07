<?php
require_once BASE_PATH . '/app/config/app.php';
require_once BASE_PATH . '/app/helpers/functions.php';
require_once BASE_PATH . '/app/config/database.php';

requireAuth();
requireAdmin();

if (isPost()) {
    $userId = (int) post('user_id');

    if ($userId === currentUserId()) {
        $_SESSION['flash']['error'] = 'You cannot delete your own account.';
        redirect('/admin/users');
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        $_SESSION['flash']['success'] = 'User deleted successfully.';
    } else {
        $_SESSION['flash']['error'] = 'Failed to delete user.';
    }
}

redirect('/admin/users');
