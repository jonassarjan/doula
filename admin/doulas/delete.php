<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::require();

$id    = (int)($_GET['id'] ?? 0);
$doula = Database::query('SELECT id, name, photo FROM doulas WHERE id = ?', [$id])[0] ?? null;

if (!$doula) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Doula not found.'];
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Database::connect()->prepare('DELETE FROM doulas WHERE id = ?')->execute([$id]);
    if ($doula['photo'] && file_exists(UPLOAD_DIR . $doula['photo'])) {
        unlink(UPLOAD_DIR . $doula['photo']);
    }
    $_SESSION['flash'] = ['type' => 'success', 'message' => "\"" . $doula['name'] . "\" has been deleted."];
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

$title      = 'Delete Doula';
$activePage = 'doulas';
require_once ADMIN_PATH . '/partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card text-center">
            <div class="card-body py-5 px-4">
                <div class="mb-3 text-danger" style="font-size:2.5rem;">
                    <i class="bi bi-trash"></i>
                </div>
                <h2 class="h5 fw-semibold mb-2">Delete Doula?</h2>
                <p class="text-muted mb-4">
                    This will permanently remove <strong><?= htmlspecialchars($doula['name']) ?></strong>
                    and cannot be undone.
                </p>
                <form method="POST" class="d-flex justify-content-center gap-2">
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                    <a href="<?= BASE_URL ?>/admin/" class="btn btn-outline-secondary px-4">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once ADMIN_PATH . '/partials/footer.php'; ?>
