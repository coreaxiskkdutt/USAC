<?php require_once __DIR__ . '/../helpers.php'; ?>
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
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="brand-mark" style="margin:0 auto 14px;width:48px;height:48px;"></div>
      <div class="auth-title"><?= e($authTitle ?? '') ?></div>
      <div class="auth-sub"><?= e($authSub ?? '') ?></div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
      <div class="flash flash-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div>
    <?php endif; ?>
