<?php
session_start();
require_once dirname(__DIR__) . '/Database.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Auth.php';

define('ADMIN_PATH', __DIR__);

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::attempt(trim($_POST['email'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: ' . BASE_URL . '/admin/');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Doula Registry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; display: flex; align-items: center; min-height: 100vh; }
        .card { border: none; box-shadow: 0 2px 16px rgba(0,0,0,.10); }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-9 col-md-6 col-lg-4">
            <div class="text-center mb-4">
                <i class="bi bi-heart-pulse text-dark" style="font-size:2.4rem;"></i>
                <h1 class="h4 mt-2 fw-semibold">Doula Registry</h1>
                <p class="text-muted small">Admin</p>
            </div>
            <div class="card rounded-4 p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Email</label>
                        <input type="email" name="email" class="form-control" autofocus autocomplete="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Password</label>
                        <input type="password" name="password" class="form-control" autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
