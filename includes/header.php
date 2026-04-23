<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$userRole    = $_SESSION['role'] ?? '';
$userName    = $_SESSION['full_name'] ?? 'Guest';
$initials    = '';
foreach (explode(' ', $userName) as $w) { $initials .= strtoupper(substr($w, 0, 1)); }
$initials = substr($initials, 0, 2);

$db = getDB();
$sysTitle = getSetting('system_title', 'Student Evaluation System');
$sysLogo  = getSetting('system_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? $sysTitle) ?></title>
  <link rel="stylesheet" href="/grading_systemv2/assets/css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <?php if ($sysLogo): ?>
        <img src="/grading_systemv2/assets/uploads/<?= htmlspecialchars($sysLogo) ?>" alt="Logo" style="width:40px;height:40px;border-radius:var(--radius-md);object-fit:cover;">
      <?php else: ?>
        <div class="sidebar-brand-icon">SE</div>
      <?php endif; ?>
      <div>
        <h2><?= htmlspecialchars($sysTitle) ?></h2>
        <small>Evaluation System</small>
      </div>
    </div>

    <nav class="sidebar-nav">
      <?php if ($userRole === 'admin'): ?>
        <div class="sidebar-label">Main</div>
        <a href="/grading_systemv2/pages/admin/dashboard.php" class="sidebar-link <?= $currentPage==='dashboard'&&$currentDir==='admin'?'active':'' ?>">
          <span class="icon">&#9776;</span> Dashboard
        </a>
        <div class="sidebar-label">Management</div>
        <a href="/grading_systemv2/pages/admin/courses.php" class="sidebar-link <?= $currentPage==='courses'?'active':'' ?>">
          <span class="icon">&#128218;</span> Courses
        </a>
        <a href="/grading_systemv2/pages/admin/subjects.php" class="sidebar-link <?= $currentPage==='subjects'&&$currentDir==='admin'?'active':'' ?>">
          <span class="icon">&#128203;</span> Subjects
        </a>
        <a href="/grading_systemv2/pages/admin/instructors.php" class="sidebar-link <?= $currentPage==='instructors'?'active':'' ?>">
          <span class="icon">&#128100;</span> Instructors
        </a>
        <div class="sidebar-label">System</div>
        <a href="/grading_systemv2/pages/admin/semesters.php" class="sidebar-link <?= $currentPage==='semesters'?'active':'' ?>">
          <span class="icon">&#128197;</span> Semesters
        </a>
        <a href="/grading_systemv2/pages/admin/settings.php" class="sidebar-link <?= $currentPage==='settings'?'active':'' ?>">
          <span class="icon">&#9881;</span> Settings
        </a>
        <a href="/grading_systemv2/pages/admin/profile.php" class="sidebar-link <?= $currentPage==='profile'&&$currentDir==='admin'?'active':'' ?>">
          <span class="icon">&#128113;</span> Profile
        </a>

      <?php elseif ($userRole === 'instructor'): ?>
        <div class="sidebar-label">Main</div>
        <a href="/grading_systemv2/pages/instructor/dashboard.php" class="sidebar-link <?= $currentPage==='dashboard'&&$currentDir==='instructor'?'active':'' ?>">
          <span class="icon">&#9776;</span> Dashboard
        </a>
        <div class="sidebar-label">Academic</div>
        <a href="/grading_systemv2/pages/instructor/subjects.php" class="sidebar-link <?= $currentPage==='subjects'&&$currentDir==='instructor'?'active':'' ?>">
          <span class="icon">&#128203;</span> Subjects
        </a>
        <a href="/grading_systemv2/pages/instructor/students.php" class="sidebar-link <?= $currentPage==='students'?'active':'' ?>">
          <span class="icon">&#128101;</span> Students
        </a>
        <a href="/grading_systemv2/pages/instructor/criteria.php" class="sidebar-link <?= $currentPage==='criteria'?'active':'' ?>">
          <span class="icon">&#128202;</span> Criteria
        </a>
        <a href="/grading_systemv2/pages/instructor/activities.php" class="sidebar-link <?= $currentPage==='activities'?'active':'' ?>">
          <span class="icon">&#128221;</span> Quiz / Recitation / Attendance
        </a>
        <a href="/grading_systemv2/pages/instructor/grades.php" class="sidebar-link <?= $currentPage==='grades'?'active':'' ?>">
          <span class="icon">&#127942;</span> Grades
        </a>
        <a href="/grading_systemv2/pages/instructor/evaluate.php" class="sidebar-link <?= $currentPage==='evaluate'?'active':'' ?>">
          <span class="icon">&#128200;</span> Evaluate
        </a>
        <div class="sidebar-label">Account</div>
        <a href="/grading_systemv2/pages/instructor/profile.php" class="sidebar-link <?= $currentPage==='profile'&&$currentDir==='instructor'?'active':'' ?>">
          <span class="icon">&#128113;</span> Profile
        </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar"><?= $initials ?></div>
        <div class="sidebar-user-info">
          <strong><?= htmlspecialchars($userName) ?></strong>
          <span><?= htmlspecialchars($userRole) ?></span>
        </div>
      </div>
      <a href="/grading_systemv2/pages/logout.php" class="sidebar-link" style="padding-left:0;margin-top:.5rem;font-size:.8rem;color:var(--slate-400);">
        <span class="icon">&#x2B05;</span> Sign Out
      </a>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
        <h1 class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
      </div>
    </header>
    <div class="page-container">
      <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
      <?php endif; ?>
