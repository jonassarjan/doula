<?php
require_once __DIR__ . '/bootstrap.php';
Auth::require();

// Toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    Database::connect()->prepare('UPDATE doulas SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?')
        ->execute([(int)$_GET['toggle']]);
    header('Location: ' . BASE_URL . '/admin/' . ($_SERVER['QUERY_STRING'] ? '?' . http_build_query(array_diff_key($_GET, ['toggle' => ''])) : ''));
    exit;
}

$name    = trim($_GET['name']    ?? '');
$country = trim($_GET['country'] ?? '');
$city    = trim($_GET['city']    ?? '');

$where  = 'WHERE 1=1';
$params = [];

if ($name !== '') {
    $where   .= ' AND d.name LIKE ?';
    $params[] = '%' . $name . '%';
}
if ($country !== '') {
    $where   .= ' AND d.country LIKE ?';
    $params[] = '%' . $country . '%';
}
if ($city !== '') {
    $where   .= ' AND d.city LIKE ?';
    $params[] = '%' . $city . '%';
}

$doulas = Database::query("
    SELECT d.*,
           GROUP_CONCAT(l.name ORDER BY l.name SEPARATOR ', ') AS language_list
    FROM doulas d
    LEFT JOIN doula_language dl ON dl.doula_id = d.id
    LEFT JOIN languages l ON l.id = dl.language_id
    $where
    GROUP BY d.id
    ORDER BY d.name
", $params);

$title      = 'Doulas';
$activePage = 'doulas';
require_once ADMIN_PATH . '/partials/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h2 class="h4 mb-0 fw-semibold">Doulas
        <span class="badge bg-secondary fw-normal ms-1"><?= count($doulas) ?></span>
    </h2>
    <a href="<?= BASE_URL ?>/admin/doulas/create.php" class="btn btn-dark">
        <i class="bi bi-plus-lg me-1"></i> Add Doula
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-4">
                <label class="form-label small fw-medium mb-1">Name</label>
                <input type="text" name="name" class="form-control form-control-sm"
                       placeholder="e.g. Anna" value="<?= htmlspecialchars($name) ?>">
            </div>
            <div class="col-12 col-sm-3">
                <label class="form-label small fw-medium mb-1">Country</label>
                <input type="text" name="country" class="form-control form-control-sm"
                       placeholder="e.g. Sweden" value="<?= htmlspecialchars($country) ?>">
            </div>
            <div class="col-12 col-sm-3">
                <label class="form-label small fw-medium mb-1">City</label>
                <input type="text" name="city" class="form-control form-control-sm"
                       placeholder="e.g. Stockholm" value="<?= htmlspecialchars($city) ?>">
            </div>
            <div class="col-12 col-sm-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark btn-sm flex-grow-1">
                    <i class="bi bi-search"></i>
                </button>
                <?php if ($name || $country || $city): ?>
                <a href="<?= BASE_URL ?>/admin/" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($doulas)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-people" style="font-size:2.5rem;"></i>
    <p class="mt-2">No doulas found.</p>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:56px;"></th>
                    <th>Name</th>
                    <th>City</th>
                    <th>Country</th>
                    <th class="d-none d-md-table-cell">Languages</th>
                    <th>Status</th>
                    <th style="width:90px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doulas as $d): ?>
                <tr>
                    <td>
                        <?php if ($d['photo']): ?>
                        <img src="<?= UPLOAD_URL . htmlspecialchars($d['photo']) ?>"
                             class="table-photo" alt="">
                        <?php else: ?>
                        <div class="photo-placeholder"><i class="bi bi-person-fill"></i></div>
                        <?php endif; ?>
                    </td>
                    <td class="fw-medium"><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['city']) ?></td>
                    <td><?= htmlspecialchars($d['country']) ?></td>
                    <td class="d-none d-md-table-cell">
                        <small class="text-muted"><?= htmlspecialchars($d['language_list'] ?? '—') ?></small>
                    </td>
                    <td>
                        <a href="?toggle=<?= $d['id'] ?><?= $name ? '&name=' . urlencode($name) : '' ?><?= $country ? '&country=' . urlencode($country) : '' ?><?= $city ? '&city=' . urlencode($city) : '' ?>"
                           class="badge text-decoration-none <?= $d['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $d['is_active'] ? 'Active' : 'Inactive' ?>
                        </a>
                    </td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/admin/doulas/edit.php?id=<?= $d['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/doulas/delete.php?id=<?= $d['id'] ?>"
                           class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once ADMIN_PATH . '/partials/footer.php'; ?>
