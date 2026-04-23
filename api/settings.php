<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();
$action = $_POST['action'] ?? 'settings';

if ($action === 'profile') {
    $uid = $_SESSION['user_id'];
    if (!empty($_POST['password'])) {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $db->prepare("UPDATE users SET full_name=?, password=? WHERE id=?")->execute([trim($_POST['full_name']), $hash, $uid]);
    } else {
        $db->prepare("UPDATE users SET full_name=? WHERE id=?")->execute([trim($_POST['full_name']), $uid]);
    }
    $_SESSION['full_name'] = trim($_POST['full_name']);
    setFlash('success', 'Profile updated.');
    $role = $_SESSION['role'];
    header("Location: /grading_systemv2/pages/$role/profile.php");
    exit;
}

// Settings (admin only)
requireRole('admin');

// System title
if (isset($_POST['system_title'])) {
    $title = trim($_POST['system_title']);
    $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='system_title'")->execute([$title]);
}

// Logo upload
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $filename = 'logo_' . time() . '.' . $ext;
        $dest = __DIR__ . '/../assets/uploads/' . $filename;
        move_uploaded_file($_FILES['logo']['tmp_name'], $dest);

        // Remove old logo
        $old = $db->query("SELECT setting_value FROM settings WHERE setting_key='system_logo'")->fetchColumn();
        if ($old && file_exists(__DIR__ . '/../assets/uploads/' . $old)) {
            unlink(__DIR__ . '/../assets/uploads/' . $old);
        }
        $db->prepare("UPDATE settings SET setting_value=? WHERE setting_key='system_logo'")->execute([$filename]);
    }
}

setFlash('success', 'Settings saved.');
header('Location: /grading_systemv2/pages/admin/settings.php');
exit;
