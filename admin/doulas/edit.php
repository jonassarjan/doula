<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::require();

$id = (int)($_GET['id'] ?? 0);
$doula = Database::query('SELECT * FROM doulas WHERE id = ?', [$id])[0] ?? null;

if (!$doula) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Doula not found.'];
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

$languages     = Database::query('SELECT id, name FROM languages ORDER BY name');
$categories    = Database::query('SELECT id, name FROM categories ORDER BY id');
$selectedLangs = array_column(
    Database::query('SELECT language_id FROM doula_language WHERE doula_id = ?', [$id]),
    'language_id'
);
$selectedCats  = array_column(
    Database::query('SELECT category_id FROM doula_category WHERE doula_id = ?', [$id]),
    'category_id'
);

$errors = [];
$old    = $doula;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old     = $_POST;
    $name    = trim($_POST['name']             ?? '');
    $bio     = trim($_POST['bio']              ?? '');
    $certs   = trim($_POST['certifications']   ?? '');
    $years   = ($_POST['years_experience'] ?? '') !== '' ? (int)$_POST['years_experience'] : null;
    $email   = trim($_POST['email']            ?? '');
    $phone   = trim($_POST['phone']            ?? '');
    $website = trim($_POST['website']          ?? '');
    $city    = trim($_POST['city']             ?? '');
    $country = trim($_POST['country']          ?? '');
    $lat     = trim($_POST['latitude']         ?? '');
    $lng     = trim($_POST['longitude']        ?? '');
    $active  = isset($_POST['is_active']) ? 1 : 0;
    $langs   = array_map('intval', $_POST['languages']  ?? []);
    $cats    = array_map('intval', $_POST['categories'] ?? []);

    if (!$name)    $errors['name']      = 'Name is required.';
    if (!$city)    $errors['city']      = 'City is required.';
    if (!$country) $errors['country']   = 'Country is required.';
    if ($lat === '' || !is_numeric($lat) || $lat < -90  || $lat > 90)
                   $errors['latitude']  = 'Enter a valid latitude (−90 to 90).';
    if ($lng === '' || !is_numeric($lng) || $lng < -180 || $lng > 180)
                   $errors['longitude'] = 'Enter a valid longitude (−180 to 180).';
    if ($email   && !filter_var($email,   FILTER_VALIDATE_EMAIL)) $errors['email']   = 'Invalid email address.';
    if ($website && !filter_var($website, FILTER_VALIDATE_URL))   $errors['website'] = 'Invalid URL.';

    $photo = $doula['photo'];
    if (!empty($_FILES['photo']['name'])) {
        $file    = $_FILES['photo'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            $errors['photo'] = 'Only JPG, PNG, GIF, or WebP images allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors['photo'] = 'Image must be under 5 MB.';
        } else {
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newPhoto = uniqid('doula_', true) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newPhoto)) {
                $errors['photo'] = 'Failed to save image.';
            } else {
                if ($photo && file_exists(UPLOAD_DIR . $photo)) unlink(UPLOAD_DIR . $photo);
                $photo = $newPhoto;
            }
        }
    }

    if (!$errors) {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('
                UPDATE doulas SET
                    name=?, bio=?, photo=?, certifications=?, years_experience=?,
                    email=?, phone=?, website=?, city=?, country=?,
                    latitude=?, longitude=?, is_active=?, updated_at=NOW()
                WHERE id=?
            ')->execute([$name, $bio ?: null, $photo, $certs ?: null, $years, $email ?: null,
                         $phone ?: null, $website ?: null, $city, $country, $lat, $lng, $active, $id]);

            $pdo->prepare('DELETE FROM doula_language WHERE doula_id = ?')->execute([$id]);
            foreach ($langs as $lid) {
                $pdo->prepare('INSERT INTO doula_language (doula_id, language_id) VALUES (?, ?)')->execute([$id, $lid]);
            }
            $pdo->prepare('DELETE FROM doula_category WHERE doula_id = ?')->execute([$id]);
            foreach ($cats as $cid) {
                $pdo->prepare('INSERT INTO doula_category (doula_id, category_id) VALUES (?, ?)')->execute([$id, $cid]);
            }

            $pdo->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => "\"$name\" has been updated."];
            header('Location: ' . BASE_URL . '/admin/');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Could not save: ' . $e->getMessage();
        }
    }

    $selectedLangs = $langs;
    $selectedCats  = $cats;
}

$title      = 'Edit Doula';
$activePage = 'doulas';
require_once ADMIN_PATH . '/partials/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= BASE_URL ?>/admin/" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h4 mb-0 fw-semibold">Edit — <?= htmlspecialchars($doula['name']) ?></h2>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" novalidate>

    <div class="card mb-3">
        <div class="card-header py-3">Basic Information</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>
            </div>
            <div>
                <label class="form-label fw-medium">Bio</label>
                <textarea name="bio" class="form-control" rows="5"><?= htmlspecialchars($old['bio'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-3">Professional</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-medium">Certifications</label>
                <textarea name="certifications" class="form-control" rows="3"><?= htmlspecialchars($old['certifications'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="form-label fw-medium">Years of Experience</label>
                <input type="number" name="years_experience" class="form-control" style="max-width:120px;"
                       min="0" max="99" value="<?= htmlspecialchars($old['years_experience'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-3">Contact</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium">Email</label>
                    <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium">Phone</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium">Website</label>
                    <input type="url" name="website" class="form-control <?= isset($errors['website']) ? 'is-invalid' : '' ?>"
                           placeholder="https://" value="<?= htmlspecialchars($old['website'] ?? '') ?>">
                    <?php if (isset($errors['website'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['website']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-3">Location</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium">City <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['city'] ?? '') ?>">
                    <?php if (isset($errors['city'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['city']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium">Country <span class="text-danger">*</span></label>
                    <input type="text" name="country" class="form-control <?= isset($errors['country']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['country'] ?? '') ?>">
                    <?php if (isset($errors['country'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['country']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium">Latitude <span class="text-danger">*</span></label>
                    <input type="text" name="latitude" inputmode="decimal"
                           class="form-control <?= isset($errors['latitude']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['latitude'] ?? '') ?>">
                    <?php if (isset($errors['latitude'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['latitude']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium">Longitude <span class="text-danger">*</span></label>
                    <input type="text" name="longitude" inputmode="decimal"
                           class="form-control <?= isset($errors['longitude']) ? 'is-invalid' : '' ?>"
                           value="<?= htmlspecialchars($old['longitude'] ?? '') ?>">
                    <?php if (isset($errors['longitude'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['longitude']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <p class="form-text mt-2">
                <i class="bi bi-info-circle me-1"></i>
                Right-click on <a href="https://maps.google.com" target="_blank" rel="noreferrer">Google Maps</a>
                and select "What's here?" to get coordinates.
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-3">Photo</div>
        <div class="card-body">
            <?php if ($doula['photo']): ?>
            <div class="mb-2">
                <img src="<?= UPLOAD_URL . htmlspecialchars($doula['photo']) ?>"
                     style="max-height:100px;" class="img-thumbnail rounded-3" alt="Current photo">
                <p class="form-text">Current photo. Upload a new one to replace it.</p>
            </div>
            <?php endif; ?>
            <input type="file" name="photo" id="photo" accept="image/*"
                   class="form-control <?= isset($errors['photo']) ? 'is-invalid' : '' ?>"
                   onchange="previewPhoto(this)">
            <?php if (isset($errors['photo'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['photo']) ?></div>
            <?php endif; ?>
            <div id="photoPreview" class="mt-2"></div>
            <p class="form-text">JPG, PNG, WebP or GIF — max 5 MB.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-3">Categories</div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($categories as $cat): ?>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="categories[]"
                           value="<?= $cat['id'] ?>" id="cat_<?= $cat['id'] ?>"
                           <?= in_array((int)$cat['id'], array_map('intval', $selectedCats)) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cat_<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header py-3">Languages</div>
        <div class="card-body">
            <div class="lang-scroll">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-1">
                    <?php foreach ($languages as $l): ?>
                    <div class="col">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="languages[]"
                                   value="<?= $l['id'] ?>" id="lang_<?= $l['id'] ?>"
                                   <?= in_array((int)$l['id'], array_map('intval', $selectedLangs)) ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="lang_<?= $l['id'] ?>">
                                <?= htmlspecialchars($l['name']) ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="form-check form-switch mb-0">
                <input type="checkbox" class="form-check-input" role="switch"
                       name="is_active" id="is_active" value="1"
                       <?= ($old['is_active'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label fw-medium" for="is_active">Active listing</label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-dark px-4">
            <i class="bi bi-check-lg me-1"></i> Save Changes
        </button>
        <a href="<?= BASE_URL ?>/admin/" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
function previewPhoto(input) {
    const preview = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = '<img src="' + e.target.result + '" style="max-height:120px;" class="img-thumbnail rounded-3">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once ADMIN_PATH . '/partials/footer.php'; ?>
