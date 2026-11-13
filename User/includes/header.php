<?php
// admin/includes/header.php
// Shared top of every admin page: <head>, sidebar, page title.
//
// Set these before including it:
//   $pageTitle   text for the browser tab
//   $active      the page's file name without ".php" (highlights the sidebar link)
//   $heading     big title on the page
//   $subtitle    one line under the title
//   $topActions  (optional) HTML for buttons at the top right
//   $extraHead   (optional) extra <head> HTML, e.g. a script tag

$nav = [
    ['dashboard.php',        'Dashboard',        'ph-squares-four'],
    ['farmers-data.php',     'Farmers data',     'ph-database'],
    ['manage-crops.php',     'Manage crops',     'ph-plant'],
    ['manage-farmers.php',   'Manage farmers',   'ph-users-three'],
    ['reports.php',          'Reports',          'ph-chart-bar'],
    ['weather-forecast.php', 'Weather forecast', 'ph-cloud-sun'],
    ['settings.php',         'Settings',         'ph-gear'],
];

$pageTitle  = $pageTitle  ?? 'Admin';
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
    <meta name="theme-color" content="#0d4529">
    <script>try{if(localStorage.getItem("agritrack.nav")==="collapsed")document.documentElement.classList.add("nav-collapsed")}catch(e){}</script>
    <title><?= htmlspecialchars($pageTitle) ?> | AgriTrack</title>

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='24' fill='%231a8348'/%3E%3Ctext x='50' y='68' font-size='56' text-anchor='middle'%3E%F0%9F%8C%B1%3C/text%3E%3C/svg%3E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Phosphor Icons -->
    <link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/duotone/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/bold/style.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/admin.css">
    <?= $extraHead ?>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <i class="ph-duotone ph-leaf" aria-hidden="true"></i>
            <span class="brand-name">AgriTrack</span>
        </div>

        <button type="button" class="nav-toggle" id="navToggle" aria-expanded="true" aria-controls="sidebarNav" aria-label="Collapse sidebar" title="Collapse sidebar">
            <i class="ph-bold ph-caret-left" aria-hidden="true"></i>
        </button>

        <nav class="nav" id="sidebarNav" aria-label="Admin">
            <?php foreach ($nav as $item): ?>
                <?php $isActive = ($item[0] === $active . '.php'); ?>
                <a href="<?= htmlspecialchars($item[0]) ?>"
                   title="<?= htmlspecialchars($item[1]) ?>"
                   class="<?= $isActive ? 'active' : '' ?>"
                   <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <i class="ph-duotone <?= htmlspecialchars($item[2]) ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($item[1]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="spacer"></div>

        <button type="button" class="logout" id="logoutBtn" title="Sign out">
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