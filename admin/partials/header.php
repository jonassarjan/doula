<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin') ?> — Doula Registry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; }
        .navbar-brand { letter-spacing: .02em; }
        .table-photo { width: 46px; height: 46px; object-fit: cover; border-radius: 50%; }
        .photo-placeholder { width: 46px; height: 46px; background: #e9ecef; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: #adb5bd; font-size: 1.1rem; }
        .lang-scroll { max-height: 190px; overflow-y: auto;
            border: 1px solid #dee2e6; border-radius: .375rem; padding: .75rem; background: #fff; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; font-weight: 600; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= BASE_URL ?>/admin/">
            <i class="bi bi-heart-pulse me-1"></i> Doula Registry
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($activePage ?? '') === 'doulas' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/admin/">
                        <i class="bi bi-people me-1"></i>Doulas
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link text-danger-emphasis" href="<?= BASE_URL ?>/admin/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
<?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert alert-<?= htmlspecialchars($f['type']) ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($f['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
