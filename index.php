<?php
require_once __DIR__ . '/Database.php';

$name             = trim($_GET['name']    ?? '');
$country          = trim($_GET['country'] ?? '');
$city             = trim($_GET['city']    ?? '');
$selectedCats     = array_map('intval', $_GET['category'] ?? []);

$allCategories = Database::query('SELECT id, name FROM categories ORDER BY id');

$where  = 'WHERE d.is_active = 1';
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
if (!empty($selectedCats)) {
    $placeholders = implode(',', array_fill(0, count($selectedCats), '?'));
    $where       .= " AND d.id IN (SELECT doula_id FROM doula_category WHERE category_id IN ($placeholders))";
    $params       = array_merge($params, $selectedCats);
}

$doulas = Database::query("
    SELECT d.id, d.name, d.photo, d.city, d.country,
           d.latitude, d.longitude, d.email, d.phone, d.website,
           d.years_experience,
           GROUP_CONCAT(DISTINCT l.name   ORDER BY l.name   SEPARATOR ', ') AS language_list,
           GROUP_CONCAT(DISTINCT cat.name ORDER BY cat.id   SEPARATOR ', ') AS category_list
    FROM doulas d
    LEFT JOIN doula_language dl  ON dl.doula_id  = d.id
    LEFT JOIN languages l        ON l.id          = dl.language_id
    LEFT JOIN doula_category dc  ON dc.doula_id  = d.id
    LEFT JOIN categories cat     ON cat.id        = dc.category_id
    $where
    GROUP BY d.id
    ORDER BY d.name
", $params);

$mapData = json_encode(
    array_map(fn($d) => [
        'id'      => (int)$d['id'],
        'name'    => $d['name'],
        'city'    => $d['city'],
        'country' => $d['country'],
        'lat'     => (float)$d['latitude'],
        'lng'     => (float)$d['longitude'],
        'email'   => $d['email']   ?? '',
        'phone'   => $d['phone']   ?? '',
        'website' => $d['website'] ?? '',
        'langs'  => $d['language_list']  ?? '',
        'cats'   => $d['category_list']  ?? '',
        'photo'  => $d['photo']          ?? '',
        'years'  => $d['years_experience'] ?? '',
    ], $doulas),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);

$isFiltered = $name || $country || $city || !empty($selectedCats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Death Doula — Doula Registry</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">

    <style>
        /* --- Layout -------------------------------------------------- */
        html, body {
            height: 100%;
            overflow: hidden;
            background: #f5f6fa;
        }
        .page-wrapper {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-row {
            flex: 1;
            overflow: hidden;
            display: flex;
            gap: .75rem;
            padding: .75rem;
            min-height: 0;
        }

        /* --- List panel ---------------------------------------------- */
        .list-panel {
            width: 380px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .list-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 2px;
        }
        .list-scroll::-webkit-scrollbar { width: 4px; }
        .list-scroll::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

        /* --- Map panel ----------------------------------------------- */
        .map-panel {
            flex: 1;
            min-width: 0;
        }
        #map {
            width: 100%;
            height: 100%;
            border-radius: .5rem;
            box-shadow: 0 1px 6px rgba(0,0,0,.1);
        }

        /* --- Doula card ---------------------------------------------- */
        .doula-card {
            border: 2px solid transparent;
            border-radius: .5rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.07);
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
            margin-bottom: .5rem;
        }
        .doula-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,.12); }
        .doula-card.active { border-color: #212529; }
        .doula-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .doula-avatar-placeholder {
            width: 52px; height: 52px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex; align-items: center; justify-content: center;
            color: #adb5bd; font-size: 1.3rem;
            flex-shrink: 0;
        }

        /* --- Header -------------------------------------------------- */
        .site-header {
            background: #212529;
            color: #fff;
            padding: .75rem 1rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .site-header .brand { font-weight: 600; font-size: 1.1rem; white-space: nowrap; }
        .site-header .tagline { font-size: .8rem; color: #adb5bd; margin-top: 1px; }

        /* --- Search bar --------------------------------------------- */
        .search-bar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: .5rem .75rem;
        }

        /* --- Result count ------------------------------------------- */
        .list-meta {
            font-size: .8rem;
            color: #6c757d;
            padding: .25rem .25rem .4rem;
        }

        /* --- Leaflet popup ------------------------------------------ */
        .lf-popup { min-width: 170px; font-family: inherit; }
        .lf-popup-name { font-weight: 600; font-size: .9rem; margin-bottom: 2px; }
        .lf-popup-loc  { color: #666; font-size: .8rem; margin-bottom: 4px; }
        .lf-popup-langs { color: #888; font-size: .75rem; margin-bottom: 8px; }
        .lf-popup-links { display: flex; flex-wrap: wrap; gap: 5px; }
        .lf-popup-links a {
            font-size: .75rem; padding: 2px 8px;
            border: 1px solid #dee2e6; border-radius: 4px;
            text-decoration: none; color: #212529;
        }
        .lf-popup-links a:hover { background: #f8f9fa; }

        /* --- Mobile ------------------------------------------------- */
        @media (max-width: 767px) {
            html, body { overflow: auto; }
            .page-wrapper { height: auto; }
            .content-row {
                flex-direction: column;
                overflow: visible;
                height: auto;
            }
            .list-panel { width: 100%; }
            .list-scroll { max-height: 50vh; }
            .map-panel  { height: 320px; }
            .mobile-toggle { display: flex !important; }
        }
        .mobile-toggle { display: none; }
    </style>
</head>
<body>
<div class="page-wrapper">

    <!-- Header -->
    <div class="site-header">
        <div>
            <div class="brand"><i class="bi bi-heart-pulse me-1"></i> Doula Registry</div>
            <div class="tagline">Find a death doula near you</div>
        </div>

        <!-- Search -->
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center flex-grow-1">
            <input type="text" name="name" class="form-control form-control-sm"
                   placeholder="Name" style="max-width:150px;"
                   value="<?= htmlspecialchars($name) ?>">
            <input type="text" name="country" class="form-control form-control-sm"
                   placeholder="Country" style="max-width:130px;"
                   value="<?= htmlspecialchars($country) ?>">
            <input type="text" name="city" class="form-control form-control-sm"
                   placeholder="City" style="max-width:130px;"
                   value="<?= htmlspecialchars($city) ?>">
            <button type="submit" class="btn btn-sm btn-light">
                <i class="bi bi-search"></i> Search
            </button>
            <?php if ($isFiltered): ?>
            <a href="/doula/" class="btn btn-sm btn-outline-light">
                <i class="bi bi-x"></i> Clear
            </a>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-1 align-items-center w-100 mt-1">
                <small class="text-secondary me-1" style="white-space:nowrap;">Category:</small>
                <?php foreach ($allCategories as $cat): ?>
                <input type="checkbox" class="btn-check" name="category[]"
                       id="cat_<?= $cat['id'] ?>" value="<?= $cat['id'] ?>"
                       <?= in_array((int)$cat['id'], $selectedCats) ? 'checked' : '' ?>>
                <label class="btn btn-sm btn-outline-light py-0 px-2" style="font-size:.75rem;"
                       for="cat_<?= $cat['id'] ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <!-- Mobile list/map toggle -->
    <div class="mobile-toggle gap-0 p-2">
        <button class="btn btn-sm btn-outline-dark active flex-grow-1 rounded-end-0" id="btnList" onclick="mobileView('list')">
            <i class="bi bi-list-ul me-1"></i> List
        </button>
        <button class="btn btn-sm btn-outline-dark flex-grow-1 rounded-start-0" id="btnMap" onclick="mobileView('map')">
            <i class="bi bi-map me-1"></i> Map
        </button>
    </div>

    <!-- Content -->
    <div class="content-row">

        <!-- List panel -->
        <div class="list-panel" id="listPanel">
            <div class="list-meta">
                <?php if ($isFiltered): ?>
                    <?= count($doulas) ?> result<?= count($doulas) !== 1 ? 's' : '' ?> found
                <?php else: ?>
                    <?= count($doulas) ?> doula<?= count($doulas) !== 1 ? 's' : '' ?> registered
                <?php endif; ?>
            </div>
            <div class="list-scroll">
                <?php if (empty($doulas)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-search" style="font-size:2rem;"></i>
                    <p class="mt-2 small">No doulas found for your search.</p>
                    <a href="/doula/" class="btn btn-sm btn-outline-secondary">Clear filters</a>
                </div>
                <?php else: ?>
                <?php foreach ($doulas as $d): ?>
                <div class="doula-card p-3 d-flex gap-3"
                     data-id="<?= $d['id'] ?>"
                     onclick="selectDoula(<?= $d['id'] ?>)">

                    <?php if ($d['photo']): ?>
                    <img src="/doula/uploads/<?= htmlspecialchars($d['photo']) ?>"
                         class="doula-avatar" alt="">
                    <?php else: ?>
                    <div class="doula-avatar-placeholder"><i class="bi bi-person-fill"></i></div>
                    <?php endif; ?>

                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-semibold text-truncate"><?= htmlspecialchars($d['name']) ?></div>
                        <div class="text-muted small">
                            <?= htmlspecialchars($d['city']) ?>, <?= htmlspecialchars($d['country']) ?>
                            <?php if ($d['years_experience']): ?>
                            · <?= (int)$d['years_experience'] ?> yr<?= $d['years_experience'] != 1 ? 's' : '' ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($d['category_list']): ?>
                        <div class="mt-1">
                            <?php foreach (explode(', ', $d['category_list']) as $cat): ?>
                            <span class="badge fw-normal" style="font-size:.7rem;background:#e8f0fe;color:#1a56c4;">
                                <?= htmlspecialchars(trim($cat)) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($d['language_list']): ?>
                        <div class="mt-1">
                            <?php foreach (explode(', ', $d['language_list']) as $lang): ?>
                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.7rem;">
                                <?= htmlspecialchars(trim($lang)) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="mt-2 d-flex flex-wrap gap-1">
                            <?php if ($d['website']): ?>
                            <a href="<?= htmlspecialchars($d['website']) ?>" target="_blank" rel="noreferrer"
                               class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:.75rem;"
                               onclick="event.stopPropagation()">
                                <i class="bi bi-globe2 me-1"></i>Website
                            </a>
                            <?php endif; ?>
                            <?php if ($d['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($d['email']) ?>"
                               class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:.75rem;"
                               onclick="event.stopPropagation()">
                                <i class="bi bi-envelope me-1"></i>Email
                            </a>
                            <?php endif; ?>
                            <?php if ($d['phone']): ?>
                            <a href="tel:<?= htmlspecialchars($d['phone']) ?>"
                               class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size:.75rem;"
                               onclick="event.stopPropagation()">
                                <i class="bi bi-telephone me-1"></i>Phone
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Map panel -->
        <div class="map-panel" id="mapPanel">
            <div id="map"></div>
        </div>

    </div><!-- .content-row -->
</div><!-- .page-wrapper -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const doulas  = <?= $mapData ?>;
const markers = {};

// --- Map init ---------------------------------------------------------
const map = L.map('map', { zoomControl: true });

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// --- HTML escape helpers (for popup content) -------------------------
function esc(s) {
    return String(s ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// --- Add markers ------------------------------------------------------
doulas.forEach(d => {
    const links = [
        d.website ? `<a href="${esc(d.website)}" target="_blank" rel="noreferrer">Website</a>` : '',
        d.email   ? `<a href="mailto:${esc(d.email)}">Email</a>`   : '',
        d.phone   ? `<a href="tel:${esc(d.phone)}">Phone</a>`      : '',
    ].filter(Boolean).join('');

    const popup = `
        <div class="lf-popup">
            <div class="lf-popup-name">${esc(d.name)}</div>
            <div class="lf-popup-loc">${esc(d.city)}, ${esc(d.country)}</div>
            ${d.cats  ? `<div class="lf-popup-langs">${esc(d.cats)}</div>`  : ''}
            ${d.langs ? `<div class="lf-popup-langs">${esc(d.langs)}</div>` : ''}
            ${links   ? `<div class="lf-popup-links">${links}</div>` : ''}
        </div>`;

    const marker = L.marker([d.lat, d.lng])
        .addTo(map)
        .bindPopup(popup, { minWidth: 180 });

    marker.on('click', () => {
        const card = document.querySelector(`.doula-card[data-id="${d.id}"]`);
        if (card) {
            setActiveCard(d.id);
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    markers[d.id] = marker;
});

// --- Fit map to markers -----------------------------------------------
if (doulas.length === 1) {
    map.setView([doulas[0].lat, doulas[0].lng], 12);
} else if (doulas.length > 1) {
    const group = L.featureGroup(Object.values(markers));
    map.fitBounds(group.getBounds().pad(0.15));
} else {
    map.setView([20, 0], 2);
}

// --- Card → map interaction ------------------------------------------
function setActiveCard(id) {
    document.querySelectorAll('.doula-card').forEach(c => c.classList.remove('active'));
    const card = document.querySelector(`.doula-card[data-id="${id}"]`);
    if (card) card.classList.add('active');
}

function selectDoula(id) {
    setActiveCard(id);
    const m = markers[id];
    if (!m) return;
    map.flyTo(m.getLatLng(), 13, { animate: true, duration: 0.6 });
    m.openPopup();
}

// --- Mobile view toggle -----------------------------------------------
function mobileView(view) {
    const listPanel = document.getElementById('listPanel');
    const mapPanel  = document.getElementById('mapPanel');
    const btnList   = document.getElementById('btnList');
    const btnMap    = document.getElementById('btnMap');

    if (view === 'map') {
        listPanel.style.display = 'none';
        mapPanel.style.display  = 'block';
        btnMap.classList.add('active');
        btnList.classList.remove('active');
        setTimeout(() => map.invalidateSize(), 50);
    } else {
        mapPanel.style.display  = '';
        listPanel.style.display = '';
        btnList.classList.add('active');
        btnMap.classList.remove('active');
    }
}
</script>
</body>
</html>
