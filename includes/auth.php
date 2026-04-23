<?php
/**
 * Authentication & Authorization helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to login if user is not authenticated
 */
function requireLogin() {
    // Avoid redirect loop if we are already on the login page
    if (basename($_SERVER['PHP_SELF']) === 'login.php') {
        return;
    }
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: /grading_systemv2/pages/login.php');
        exit;
    }
}

/**
 * Redirect to dashboard if user does not have the required role
 */
function requireRole($role) {
    requireLogin();
    
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        $dashboard = (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor') 
                     ? 'instructor/dashboard.php' 
                     : 'admin/dashboard.php';
        
        // Prevent redirecting to the same page we are already on
        $currentPage = basename(dirname($_SERVER['PHP_SELF'])) . '/' . basename($_SERVER['PHP_SELF']);
        if ($currentPage !== $dashboard) {
            header("Location: /grading_systemv2/pages/$dashboard");
            exit;
        }
    }
}

/**
 * Check if current user has a specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Get current user ID
 */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's full name
 */
function currentUserName() {
    return $_SESSION['full_name'] ?? 'Guest';
}

/**
 * Flash message helpers
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
