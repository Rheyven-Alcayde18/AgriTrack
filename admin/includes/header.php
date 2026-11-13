<?php
// user/includes/header.php
// Shared top of every user/farmer page: <head>, sidebar, page title.

$nav = [
    ['dashboard.php',        'Dashboard',           'ph-squares-four'],
    ['crop-monitoring.php',  'Crop monitoring',     'ph-plant'],
    ['weather-forecast.php', 'Weather forecast',    'ph-cloud-sun'],
    ['harvest-management.php','Harvest management', 'ph-grains'],
    ['market-locations.php', 'Market locations',    'ph-map-pin'],
    ['recommendations.php',  'Recommendations',     'ph-hand-heart'],
];

$pageTitle  = $pageTitle  ?? 'AgriTrack';
$active     = $active     ?? '';
$heading    = $heading    ?? $pageTitle;
$subtitle   = $subtitle   ?? '';
$topActions = $topActions ?? '';
$extraHead  = $extraHead  ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a8348">
    <title><?= htmlspecialchars($pageTitle) ?> | AgriTrack</title>

    <!-- Apply saved theme BEFORE CSS loads to prevent white flash -->
    <script>
      try {
        var t = localStorage.getItem('theme');
        if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) {
          document.documentElement.setAttribute('data-theme', 'dark');
        }
      } catch (e) {}
    </script>

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='24' fill='%231a8348'/%3E%3Ctext x='50' y='68' font-size='56' text-anchor='middle'%3E%F0%9F%8C%B1%3C/text%3E%3C/svg%3E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/duotone/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        /* Dark mode variables and overrides for user pages */
        :root[data-theme="dark"] {
            color-scheme: dark;
        }
        :root[data-theme="dark"] body {
            background: #0d1712 !important;
            color: #e4efe7 !important;
        }
        :root[data-theme="dark"] .main,
        :root[data-theme="dark"] .sidebar {
            background-color: var(--sidebar-bg, #0d1712);
        }
        
        /* Floating Dark Mode Toggle Button */
        #theme-toggle.user-theme-toggle {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 1000;
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            border: 1px solid #dbe6d0;
            background: #fff;
            color: #08301d;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 8px 20px -10px rgba(8, 48, 29, .45);
            transition: all 0.2s ease;
        }
        #theme-toggle.user-theme-toggle:hover {
            border-color: #1a8348;
            transform: scale(1.05);
        }
        :root[data-theme="dark"] #theme-toggle.user-theme-toggle {
            background: #14211a;
            color: #e4efe7;
            border-color: #2b3d33;
        }
    </style>

    <?= $extraHead ?>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <i class="ph-duotone ph-leaf" aria-hidden="true"></i>
            AgriTrack
        </div>

        <nav class="nav" aria-label="Farmer">
            <?php foreach ($nav as $item): ?>
                <?php $isActive = ($item[0] === $active . '.php'); ?>
                <a href="<?= htmlspecialchars($item[0]) ?>"
                   class="<?= $isActive ? 'active' : '' ?>"
                   <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <i class="ph-duotone <?= htmlspecialchars($item[2]) ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($item[1]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="spacer"></div>

        <button type="button" class="logout" id="logoutBtn">
            <i class="ph-duotone ph-sign-out" aria-hidden="true"></i>
            <span>Sign out</span>
        </button>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">
            <div>
                <h1><?= htmlspecialchars($heading) ?></h1>
                <?php if ($subtitle !== ''): ?>
                    <p><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="top-actions">
                <span class="welcome" id="welcome"></span>
                <?= $topActions ?>
            </div>
        </div>