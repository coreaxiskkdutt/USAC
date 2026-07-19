<?php require_once __DIR__ . '/../helpers.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'USAC') ?> &mdash; UA/UC Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark"></div>
      <div class="brand-text">UA/UC LOG<span>SITE SAFETY REGISTER</span></div>
    </div>

    <?php if ($user): ?>
    <div class="nav-label">Navigation</div>
    <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
      <span class="nav-dot"></span> Dashboard
    </a>
    <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="reports.php">
      <span class="nav-dot"></span> All Reports
    </a>
    <a class="nav-link <?= $currentPage === 'new_report' ? 'active' : '' ?>" href="new_report.php">
      <span class="nav-dot"></span> New Report
    </a>

    <?php if (canManageUsers($user)): ?>
    <div class="nav-label">Management</div>
    <a class="nav-link <?= $currentPage === 'manage_approvals' ? 'active' : '' ?>" href="manage_approvals.php">
      <span class="nav-dot"></span> Approvals
    </a>
    <?php endif; ?>

    <?php if (isAdmin($user)): ?>
    <a class="nav-link <?= $currentPage === 'admin_users' ? 'active' : '' ?>" href="admin_users.php">
      <span class="nav-dot"></span> Users
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <div style="margin-top:auto;padding-top:16px;border-top:1px solid var(--line);">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <div style="width:32px;height:32px;border-radius:50%;background:var(--bg-raised);border:1px solid var(--line-light);display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:13px;color:var(--amber);flex-shrink:0;"><?= e(substr($user['full_name'], 0, 1)) ?></div>
        <div style="min-width:0;">
          <div style="font-size:12px;font-weight:600;color:var(--paper);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($user['full_name']) ?></div>
          <div style="font-family:var(--font-mono);font-size:10px;color:var(--slate-light);">@<?= e($user['username']) ?></div>
        </div>
      </div>
      <a href="logout.php" class="nav-link" style="justify-content:center;color:var(--red);border-color:var(--red-dim);">
        <span class="nav-dot" style="background:var(--red);"></span> Log out
      </a>
    </div>

    <div class="sidebar-foot">
      UA/UC Reporting System<br>
      &copy; <?= date('Y') ?>
    </div>
  </aside>

  <main class="main">
    <?php $flash = getFlash(); if ($flash): ?>
      <div class="flash flash-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div>
    <?php endif; ?>
