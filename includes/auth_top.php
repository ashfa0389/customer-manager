<?php
// Shared top of the login and setup pages. Set $authTitle and $authHeadline first.
$notices = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($authTitle) ?> - <?= e(APP_NAME . ' ' . APP_SUBTITLE) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=1">
</head>
<body class="auth-body">
<div class="auth">
  <section class="auth-art">
    <div class="brand">
      <span class="brand-mark"><?= icon('globe', 20) ?></span>
      <span class="brand-text"><?= e(APP_NAME) ?><small><?= e(APP_SUBTITLE) ?></small></span>
    </div>
    <h1><?= e($authHeadline) ?></h1>
    <svg class="waves" viewBox="0 0 800 300" preserveAspectRatio="none" aria-hidden="true">
      <path d="M0 170 C 150 110, 300 230, 450 170 S 700 110, 800 160 L800 300 L0 300 Z" fill="rgba(127,209,199,.38)"/>
      <path d="M0 205 C 160 155, 320 255, 480 210 S 720 165, 800 200 L800 300 L0 300 Z" fill="rgba(91,141,239,.42)"/>
      <path d="M0 245 C 170 205, 330 285, 500 250 S 730 215, 800 240 L800 300 L0 300 Z" fill="rgba(255,255,255,.16)"/>
    </svg>
  </section>

  <section class="auth-form">
    <div class="auth-card">
      <?php foreach ($notices as $n): ?>
        <div class="flash <?= e($n['type']) ?>" role="status"><span><?= e($n['msg']) ?></span></div>
      <?php endforeach; ?>
