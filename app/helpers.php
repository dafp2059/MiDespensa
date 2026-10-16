<?php
declare(strict_types=1);

const OPERATIONS = ['venta' => 'Venta', 'arriendo' => 'Arriendo'];
const TYPES = [
    'casa' => 'Casa',
    'apartamento' => 'Apartamento',
    'oficina' => 'Oficina',
    'local' => 'Local',
    'lote' => 'Lote',
    'bodega' => 'Bodega',
    'finca' => 'Finca',
];
const TYPES_PLURAL = [
    'casa' => 'Casas',
    'apartamento' => 'Apartamentos',
    'oficina' => 'Oficinas',
    'local' => 'Locales',
    'lote' => 'Lotes',
    'bodega' => 'Bodegas',
    'finca' => 'Fincas',
];
const STATUSES = [
    'disponible' => 'Disponible',
    'reservado' => 'Reservado',
    'vendido' => 'Vendido',
    'arrendado' => 'Arrendado',
];
const CONTACT_REASONS = [
    'inmueble' => 'Consulta sobre un inmueble',
    'visita' => 'Agendar una visita',
    'vender' => 'Quiero vender mi inmueble',
    'arrendar' => 'Quiero arrendar mi inmueble',
    'inversion' => 'Asesoría de inversión',
    'otro' => 'Otra consulta',
];
const PAYMENT_CONCEPTS = [
    'canon' => 'Canon de arrendamiento',
    'administracion' => 'Cuota de administración',
    'separacion' => 'Separación o abono de inmueble',
    'servicios' => 'Servicios públicos',
    'otro' => 'Otro concepto',
];
const PAYMENT_STATUSES = [
    'PENDING' => 'Pendiente',
    'APPROVED' => 'Aprobado',
    'DECLINED' => 'Rechazado',
    'VOIDED' => 'Anulado',
    'ERROR' => 'Error',
];

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Ajustes guardados desde el panel (tabla settings). Si la tabla no existe, devuelve []. */
function settings(bool $refresh = false): array
{
    static $settings = null;
    if ($settings === null || $refresh) {
        try {
            $settings = query('SELECT name, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $settings = [];
        }
    }
    return $settings;
}

/** Dato de la empresa: primero lo guardado en el panel, si no, lo de app/config.php. */
function site(string $key): string
{
    $settings = settings();
    return array_key_exists('site.' . $key, $settings) ? (string) $settings['site.' . $key] : (string) config('site.' . $key, '');
}

/** Logo de la empresa. $dark = versión para fondos oscuros (pie de página y panel). */
function logo_html(bool $dark = false, int $size = 40): string
{
    $settings = settings();
    $file = $dark ? ($settings['logo_dark'] ?? $settings['logo'] ?? '') : ($settings['logo'] ?? '');
    if ($file !== '') {
        $chip = $dark && empty($settings['logo_dark']) ? ' logo__img--chip' : '';
        return '<img class="logo__img' . $chip . '" src="' . e(upload_url($file)) . '" alt="' . e(site('name')) . '">';
    }
    return '<img src="' . e(asset('img/logo.svg')) . '" alt="" width="' . $size . '" height="' . $size . '">'
        . '<span>INGESPRO<small>Inmobiliaria</small></span>';
}

function favicon_url(): string
{
    $logo = settings()['favicon'] ?? '';
    return $logo !== '' ? upload_url($logo) : asset('img/favicon.svg');
}

/** Guarda un logo subido tal cual (PNG, JPG o WEBP; conserva la transparencia). */
function save_logo_upload(string $field): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) {
        return null;
    }
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset($types[$info[2]])) {
        return null;
    }
    if (!is_dir(UPLOAD_DIR . '/marca')) {
        mkdir(UPLOAD_DIR . '/marca', 0755, true);
    }
    $filename = 'marca/' . bin2hex(random_bytes(6)) . '.' . $types[$info[2]];
    return move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $filename) ? $filename : null;
}

function save_setting(string $name, ?string $value): void
{
    if ($value === null) {
        query('DELETE FROM settings WHERE name = ?', [$name]);
    } else {
        query('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$name, $value]);
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', config('db.host'), config('db.name'));
        $pdo = new PDO($dsn, config('db.user'), config('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim((string) config('base_url'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . $path;
    $version = is_file($file) ? filemtime($file) : 0;
    return url('assets/' . $path) . '?v=' . $version;
}

function upload_url(?string $filename): string
{
    return $filename ? url('uploads/' . $filename) : asset('img/sin-foto.svg');
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function money($amount, bool $withCurrency = true): string
{
    if ($amount === null || $amount === '') {
        return '';
    }
    return '$' . number_format((float) $amount, 0, ',', '.') . ($withCurrency ? ' COP' : '');
}

function number_es($value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    $value = (float) $value;
    return number_format($value, floor($value) == $value ? 0 : 1, ',', '.');
}

/** Convierte "$1.250.000" o "1250000" en entero. */
function parse_money(string $value): int
{
    return (int) preg_replace('/\D/', '', $value);
}

function slugify(string $text): string
{
    $text = mb_strtolower($text);
    $text = strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-') ?: 'inmueble';
}

function unique_slug(string $table, string $base, int $ignoreId = 0): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $i = 2;
    while (query("SELECT id FROM {$table} WHERE slug = ? AND id <> ?", [$candidate, $ignoreId])->fetch()) {
        $candidate = $slug . '-' . $i++;
    }
    return $candidate;
}

function whatsapp_link(string $text = ''): string
{
    $link = 'https://wa.me/' . preg_replace('/\D/', '', site('whatsapp'));
    return $text === '' ? $link : $link . '?text=' . rawurlencode($text);
}

function phone_display(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    return strlen($digits) === 10 ? substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6) : $phone;
}

// ---------- Seguridad ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('La sesión expiró. Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = query('SELECT id, name, email FROM users WHERE id = ?', [$_SESSION['user_id']])->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('admin/login.php');
    }
    return $user;
}

// ---------- Inmuebles ----------

/** Añade a cada inmueble su foto de portada (cover) y el nombre de la ubicación. */
function property_select(): string
{
    return "SELECT p.*, l.name AS location_name, l.slug AS location_slug,
        (SELECT i.filename FROM images i WHERE i.owner_type = 'property' AND i.owner_id = p.id
         ORDER BY i.sort_order, i.id LIMIT 1) AS cover
        FROM properties p LEFT JOIN locations l ON l.id = p.location_id";
}

function property_url(array $p): string
{
    return url('inmueble/' . $p['slug']);
}

function project_url(array $p): string
{
    return url('proyecto/' . $p['slug']);
}

function property_place(array $p): string
{
    return implode(', ', array_filter([$p['neighborhood'] ?? '', $p['location_name'] ?? '']));
}

function is_closed(array $p): bool
{
    return in_array($p['status'], ['vendido', 'arrendado'], true);
}

function images_for(string $ownerType, int $ownerId): array
{
    return query('SELECT * FROM images WHERE owner_type = ? AND owner_id = ? ORDER BY sort_order, id', [$ownerType, $ownerId])->fetchAll();
}

function locations(): array
{
    static $rows = null;
    if ($rows === null) {
        $rows = query('SELECT * FROM locations ORDER BY sort_order, name')->fetchAll();
    }
    return $rows;
}

/**
 * Guarda las fotos subidas en un campo <input type="file" multiple>.
 * Las reduce a 1800 px de ancho como máximo y devuelve los nombres de archivo.
 */
function save_uploaded_images(string $field): array
{
    if (empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) {
        return [];
    }
    $saved = [];
    $files = $_FILES[$field];
    $folder = date('Y/m');
    if (!is_dir(UPLOAD_DIR . '/' . $folder)) {
        mkdir(UPLOAD_DIR . '/' . $folder, 0755, true);
    }
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] > 15 * 1024 * 1024) {
            continue;
        }
        $tmp = $files['tmp_name'][$i];
        $info = @getimagesize($tmp);
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($types[$info[2]])) {
            continue;
        }
        $filename = $folder . '/' . bin2hex(random_bytes(8)) . '.jpg';
        $dest = UPLOAD_DIR . '/' . $filename;
        if (!resize_image($tmp, $info, $dest)) {
            $filename = $folder . '/' . bin2hex(random_bytes(8)) . '.' . $types[$info[2]];
            $dest = UPLOAD_DIR . '/' . $filename;
            if (!move_uploaded_file($tmp, $dest)) {
                continue;
            }
        }
        $saved[] = $filename;
    }
    return $saved;
}

function resize_image(string $source, array $info, string $dest, int $maxWidth = 1800): bool
{
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    $image = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
        IMAGETYPE_PNG => @imagecreatefrompng($source),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
        default => false,
    };
    if (!$image) {
        return false;
    }
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $orientation = (@exif_read_data($source)['Orientation'] ?? 1);
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if ($angle) {
            $image = imagerotate($image, $angle, 0);
        }
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $newWidth = min($width, $maxWidth);
    $newHeight = (int) round($height * $newWidth / $width);
    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $ok = imagejpeg($canvas, $dest, 82);
    imagedestroy($image);
    imagedestroy($canvas);
    return $ok;
}

function delete_image_file(string $filename): void
{
    $path = realpath(UPLOAD_DIR . '/' . $filename);
    if ($path && str_starts_with($path, realpath(UPLOAD_DIR) . DIRECTORY_SEPARATOR) && is_file($path)) {
        unlink($path);
    }
}

// ---------- Vistas ----------

function view(string $name, array $data = []): void
{
    extract($data);
    require APP_DIR . '/views/' . $name . '.php';
}

function icon(string $name, string $class = 'icon'): string
{
    $paths = [
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'bed' => '<path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/>',
        'bath' => '<path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.7 3 4 3.7 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5H4M7 19v2M17 19v2"/>',
        'area' => '<path d="M3 3h18v18H3zM3 9h18M9 21V9"/>',
        'car' => '<path d="M5 17h14v-5l-2-5H7l-2 5zM5 17v2M19 17v2M7.5 14h.01M16.5 14h.01"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'chat' => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.9-.9L3 21l2-5a8.4 8.4 0 0 1-.9-3.9A8.5 8.5 0 0 1 12.5 3 8.4 8.4 0 0 1 21 11.5z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.3-9.3M17 6l3 3M14 9l2 2"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'camera' => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3.5"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
    ];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/** Mantiene los filtros actuales en la URL cambiando solo algunos valores. */
function query_with(array $changes): string
{
    $params = array_merge($_GET, $changes);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return $params ? '?' . http_build_query($params) : '';
}

/** Procesa el bloque de fotos del panel: borrar, elegir portada y subir nuevas. */
function sync_images(string $ownerType, int $ownerId): int
{
    $deleteIds = array_map('intval', (array) ($_POST['delete_images'] ?? []));
    foreach (images_for($ownerType, $ownerId) as $img) {
        if (in_array((int) $img['id'], $deleteIds, true)) {
            query('DELETE FROM images WHERE id = ?', [$img['id']]);
            delete_image_file($img['filename']);
        }
    }

    $coverId = (int) ($_POST['cover_id'] ?? 0);
    $order = 1;
    foreach (images_for($ownerType, $ownerId) as $img) {
        query('UPDATE images SET sort_order = ? WHERE id = ?', [(int) $img['id'] === $coverId ? 0 : $order++, $img['id']]);
    }

    $uploaded = save_uploaded_images('photos');
    foreach ($uploaded as $filename) {
        query('INSERT INTO images (owner_type, owner_id, filename, sort_order) VALUES (?, ?, ?, ?)', [$ownerType, $ownerId, $filename, $order++]);
    }
    return count($uploaded);
}

function delete_owner_images(string $ownerType, int $ownerId): void
{
    foreach (images_for($ownerType, $ownerId) as $img) {
        delete_image_file($img['filename']);
    }
    query('DELETE FROM images WHERE owner_type = ? AND owner_id = ?', [$ownerType, $ownerId]);
}
