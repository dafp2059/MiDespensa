<?php
declare(strict_types=1);

/*
 * Contenido editable desde el panel: valores por defecto, menús, apariencia,
 * páginas y utilidades para mostrar texto enriquecido de forma segura.
 */

const FONT_PAIRS = [
    'moderna' => ['Moderna (Sora + Manrope)', 'Sora', 'Manrope', 'family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700'],
    'clasica' => ['Clásica (Playfair Display + Source Sans 3)', 'Playfair Display', 'Source Sans 3', 'family=Playfair+Display:wght@600;700&family=Source+Sans+3:wght@400;600;700'],
    'elegante' => ['Elegante (Cormorant Garamond + Lato)', 'Cormorant Garamond', 'Lato', 'family=Cormorant+Garamond:wght@600;700&family=Lato:wght@400;700'],
    'amigable' => ['Amigable (Poppins + Nunito Sans)', 'Poppins', 'Nunito Sans', 'family=Nunito+Sans:wght@400;600;700&family=Poppins:wght@500;600;700'],
    'sobria' => ['Sobria (Montserrat + Open Sans)', 'Montserrat', 'Open Sans', 'family=Montserrat:wght@500;600;700&family=Open+Sans:wght@400;600;700'],
];

const HOME_SECTIONS = [
    'banner' => 'Banner principal',
    'confianza' => 'Franja "Por qué elegirnos"',
    'destacados' => 'Inmuebles destacados',
    'zonas' => 'Zonas',
    'propietarios' => 'Vende o arrienda (propietarios)',
    'proyectos' => 'Proyectos',
    'texto' => 'Bloque de texto libre',
    'contacto' => 'Contacto',
];

const ICON_CHOICES = [
    'shield' => 'Escudo', 'clock' => 'Reloj', 'camera' => 'Cámara', 'card' => 'Tarjeta', 'home' => 'Casa',
    'key' => 'Llave', 'check' => 'Visto bueno', 'chat' => 'Chat', 'phone' => 'Teléfono', 'pin' => 'Ubicación',
    'search' => 'Lupa', 'area' => 'Plano', 'mail' => 'Correo',
];

const PAGE_FORMS = [
    'none' => 'Sin formulario',
    'contacto' => 'Formulario de contacto al lado del texto',
    'contacto_info' => 'Formulario + datos de contacto y mapa',
];

/** Direcciones que ya usa la web y no pueden ser páginas. */
const RESERVED_SLUGS = ['inmuebles', 'inmueble', 'proyectos', 'proyecto', 'pagos', 'pagos-resultado', 'admin', 'assets',
    'uploads', 'sitemap', 'wompi-webhook', '404', 'index', 'pagina', 'contacto', 'robots', 'mi-cuenta'];

function default_settings(): array
{
    return [
        // Apariencia
        'theme.accent' => '#0f6b52',
        'theme.dark' => '#13222c',
        'theme.highlight' => '#c8932e',
        'theme.fonts' => 'moderna',

        // Banner
        'hero.interval' => '6',
        'hero.search' => '1',
        'hero.height' => 'normal',

        // Inicio
        'home.sections' => json_encode([
            ['key' => 'banner', 'visible' => 1], ['key' => 'confianza', 'visible' => 1], ['key' => 'destacados', 'visible' => 1],
            ['key' => 'zonas', 'visible' => 1], ['key' => 'propietarios', 'visible' => 1], ['key' => 'proyectos', 'visible' => 1],
            ['key' => 'texto', 'visible' => 0], ['key' => 'contacto', 'visible' => 1],
        ]),
        'home.confianza' => json_encode([
            ['icon' => 'shield', 'title' => 'Asesoría legal y técnica', 'text' => 'Revisamos títulos y documentos antes de cada negocio.'],
            ['icon' => 'clock', 'title' => 'Respuesta en 24 horas', 'text' => 'Por WhatsApp, teléfono o correo, como prefieras.'],
            ['icon' => 'camera', 'title' => 'Visitas acompañadas', 'text' => 'Un asesor te muestra el inmueble y resuelve tus dudas.'],
            ['icon' => 'card', 'title' => 'Pagos en línea', 'text' => 'Paga tu canon o tu separación con Wompi, sin filas.'],
        ], JSON_UNESCAPED_UNICODE),
        'home.destacados.eyebrow' => 'Inmuebles destacados',
        'home.destacados.title' => 'Propiedades seleccionadas para ti',
        'home.destacados.link' => 'Ver todos los inmuebles',
        'home.destacados.count' => '6',
        'home.zonas.eyebrow' => 'Zonas',
        'home.zonas.title' => 'Dónde trabajamos',
        'home.propietarios.eyebrow' => 'Propietarios',
        'home.propietarios.title' => '¿Quieres vender o arrendar tu inmueble?',
        'home.propietarios.text' => 'Nos encargamos de todo: avalúo comercial, fotos profesionales, publicación en portales, filtro de interesados, estudio de arrendatarios y acompañamiento legal hasta la firma.',
        'home.propietarios.button' => 'Quiero vender o arrendar',
        'home.propietarios.button_url' => '/propietarios',
        'home.propietarios.whatsapp' => 'Hablar por WhatsApp',
        'home.pasos' => json_encode([
            ['title' => 'Nos cuentas de tu inmueble', 'text' => 'Por el formulario, WhatsApp o una llamada.'],
            ['title' => 'Lo visitamos y lo valoramos', 'text' => 'Te damos un precio realista según el mercado de la zona.'],
            ['title' => 'Lo promocionamos', 'text' => 'Fotos, publicación en portales y redes, y visitas con interesados filtrados.'],
            ['title' => 'Cerramos el negocio', 'text' => 'Revisamos documentos, promesa de compraventa o contrato de arriendo.'],
        ], JSON_UNESCAPED_UNICODE),
        'home.proyectos.eyebrow' => 'Proyectos',
        'home.proyectos.title' => 'Nuestros proyectos',
        'home.proyectos.link' => 'Ver todos los proyectos',
        'home.texto.eyebrow' => '',
        'home.texto.title' => '',
        'home.texto.content' => '',
        'home.texto.button' => '',
        'home.texto.button_url' => '',
        'home.contacto.eyebrow' => 'Contacto',
        'home.contacto.title' => 'Cuéntanos qué estás buscando',
        'home.contacto.text' => 'Te respondemos en menos de 24 horas hábiles. Si es urgente, escríbenos por WhatsApp.',

        // Menús
        'menu.superior' => json_encode([
            ['label' => 'Pagos en línea', 'url' => '/pagos', 'new_tab' => 0],
        ], JSON_UNESCAPED_UNICODE),
        'menu.principal' => json_encode([
            ['label' => 'Inicio', 'url' => '/', 'new_tab' => 0],
            ['label' => 'Inmuebles', 'url' => '/inmuebles', 'new_tab' => 0],
            ['label' => 'Proyectos', 'url' => '/proyectos', 'new_tab' => 0],
            ['label' => 'Vende o arrienda', 'url' => '/propietarios', 'new_tab' => 0],
            ['label' => 'Nosotros', 'url' => '/nosotros', 'new_tab' => 0],
            ['label' => 'Contacto', 'url' => '/contacto', 'new_tab' => 0],
        ], JSON_UNESCAPED_UNICODE),
        'menu.pie_1' => json_encode([
            ['label' => 'En venta', 'url' => '/inmuebles?operacion=venta', 'new_tab' => 0],
            ['label' => 'En arriendo', 'url' => '/inmuebles?operacion=arriendo', 'new_tab' => 0],
            ['label' => 'Proyectos', 'url' => '/proyectos', 'new_tab' => 0],
            ['label' => 'Vende o arrienda tu inmueble', 'url' => '/propietarios', 'new_tab' => 0],
        ], JSON_UNESCAPED_UNICODE),
        'menu.pie_2' => json_encode([
            ['label' => 'Nosotros', 'url' => '/nosotros', 'new_tab' => 0],
            ['label' => 'Contacto', 'url' => '/contacto', 'new_tab' => 0],
            ['label' => 'Pagos en línea', 'url' => '/pagos', 'new_tab' => 0],
            ['label' => 'Política de tratamiento de datos', 'url' => '/politica-de-datos', 'new_tab' => 0],
        ], JSON_UNESCAPED_UNICODE),

        // Pie de página
        'footer.col1_title' => 'Inmuebles',
        'footer.col2_title' => 'La empresa',
        'footer.contact_title' => 'Contacto',
        'footer.copyright' => '© {{año}} {{empresa}}. Todos los derechos reservados.',
        'footer.team_link' => '1',

        // WhatsApp
        'whatsapp.float' => '1',
        'whatsapp.message' => 'Hola, vengo de la página web y quiero información.',
        'whatsapp.nav_button' => '1',
        'whatsapp.nav_label' => 'WhatsApp',

        // SEO del inicio
        'seo.home_title' => '',
        'seo.home_description' => '',
    ];
}

function setting(string $key): string
{
    $settings = settings();
    if (array_key_exists($key, $settings)) {
        return (string) $settings[$key];
    }
    return (string) (default_settings()[$key] ?? '');
}

function setting_list(string $key): array
{
    $list = json_decode(setting($key), true);
    return is_array($list) ? $list : [];
}

/** Reemplaza {{empresa}}, {{telefono}}, {{año}}… en los textos editables. */
function tokens(string $text): string
{
    return strtr($text, [
        '{{empresa}}' => site('name'),
        '{{razon_social}}' => site('legal_name') ?: site('name'),
        '{{nit}}' => site('nit'),
        '{{direccion}}' => site('address'),
        '{{ciudad}}' => site('city'),
        '{{telefono}}' => phone_display(site('phone')),
        '{{correo}}' => site('email'),
        '{{horario}}' => site('hours'),
        '{{año}}' => date('Y'),
    ]);
}

/** Texto plano editable → HTML seguro con los datos de la empresa. */
function t(string $key): string
{
    return e(tokens(setting($key)));
}

/** Enlace de un menú: "/inmuebles" → URL completa; "https://…", "mailto:", "tel:" y "#" se dejan igual. */
function link_url(string $href): string
{
    $href = trim($href);
    if ($href === '' || preg_match('#^(https?:|mailto:|tel:|\#)#i', $href)) {
        return $href === '' ? url('') : $href;
    }
    return url(ltrim($href, '/'));
}

function menu_items(string $menu): array
{
    return array_values(array_filter(setting_list('menu.' . $menu), fn($i) => trim((string) ($i['label'] ?? '')) !== ''));
}

function is_current_link(string $href): bool
{
    $target = link_url($href);
    if (parse_url($target, PHP_URL_HOST) !== parse_url((string) config('base_url'), PHP_URL_HOST)) {
        return false;
    }
    $current = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    $path = parse_url($target, PHP_URL_PATH) ?: '/';
    $base = parse_url((string) config('base_url'), PHP_URL_PATH) ?: '';
    $current = '/' . trim(substr($current, strlen(rtrim($base, '/'))), '/');
    $path = '/' . trim(substr($path, strlen(rtrim($base, '/'))), '/');
    return $path === '/' ? $current === '/' : str_starts_with($current . '/', $path . '/');
}

// ---------- Apariencia ----------

function color_mix(string $hex, string $with, float $amount): string
{
    $a = sscanf(ltrim($hex, '#'), '%2x%2x%2x');
    $b = sscanf(ltrim($with, '#'), '%2x%2x%2x');
    $mixed = array_map(fn($x, $y) => (int) round($x * (1 - $amount) + $y * $amount), $a, $b);
    return vsprintf('#%02x%02x%02x', $mixed);
}

function valid_color(string $value, string $fallback): string
{
    return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $fallback;
}

function theme_css(): string
{
    $defaults = default_settings();
    $accent = valid_color(setting('theme.accent'), $defaults['theme.accent']);
    $dark = valid_color(setting('theme.dark'), $defaults['theme.dark']);
    $highlight = valid_color(setting('theme.highlight'), $defaults['theme.highlight']);
    $fonts = FONT_PAIRS[setting('theme.fonts')] ?? FONT_PAIRS['moderna'];
    return ':root{'
        . '--accent:' . $accent . ';--accent-dark:' . color_mix($accent, '#000000', .22) . ';--accent-soft:' . color_mix($accent, '#ffffff', .88) . ';'
        . '--ink:' . $dark . ';--gold:' . $highlight . ';'
        . '--font:"' . $fonts[2] . '",system-ui,sans-serif;--font-display:"' . $fonts[1] . '","' . $fonts[2] . '",system-ui,sans-serif;}';
}

function fonts_url(): string
{
    $fonts = FONT_PAIRS[setting('theme.fonts')] ?? FONT_PAIRS['moderna'];
    return 'https://fonts.googleapis.com/css2?' . $fonts[3] . '&display=swap';
}

// ---------- Texto enriquecido ----------

/** Deja solo etiquetas seguras del editor (párrafos, títulos, listas, enlaces, negrita…). */
function clean_html(string $html): string
{
    $html = trim($html);
    if ($html === '' || $html === '<p><br></p>') {
        return '';
    }
    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'blockquote'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('root');
    if (!$root) {
        return '';
    }
    $walk = function (DOMNode $node) use (&$walk, $allowed, $doc) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, $allowed, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                        $node->removeChild($child);
                        continue;
                    }
                    // Etiqueta no permitida: se conserva su texto.
                    $walk($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    $keep = ($tag === 'a' && in_array($name, ['href', 'target'], true))
                        || ($name === 'class' && preg_match('/^(ql-[\w-]+\s*)+$/', $attr->value));
                    if ($tag === 'a' && $name === 'href' && !preg_match('#^(https?:|mailto:|tel:|/|\#)#i', trim($attr->value))) {
                        $keep = false;
                    }
                    if (!$keep) {
                        $child->removeAttribute($attr->name);
                    }
                }
                if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener');
                }
                $walk($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

/** HTML guardado por el editor, listo para mostrar (con los datos de la empresa). */
function rich(string $html): string
{
    return tokens(clean_html($html));
}

// ---------- Banner ----------

function youtube_id(string $url): ?string
{
    return preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#', $url, $m) ? $m[1] : null;
}

function slides(bool $onlyActive = true): array
{
    try {
        return query('SELECT * FROM slides' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, id')->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/** Guarda una imagen o video subido para el banner. Devuelve [nombre, tipo] o un mensaje de error. */
function save_banner_upload(string $field): array|string|null
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        return 'El archivo pesa más de lo que permite el hosting (' . ini_get('upload_max_filesize') . '). Comprime el video o súbelo a YouTube y pega el enlace.';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'No se pudo subir el archivo. Inténtalo de nuevo.';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (in_array($mime, ['video/mp4', 'video/webm'], true)) {
        if (!is_dir(UPLOAD_DIR . '/banner')) {
            mkdir(UPLOAD_DIR . '/banner', 0755, true);
        }
        $name = 'banner/' . bin2hex(random_bytes(8)) . ($mime === 'video/mp4' ? '.mp4' : '.webm');
        return move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name) ? [$name, 'video'] : 'No se pudo guardar el video.';
    }
    $_FILES['__banner'] = array_map(fn($v) => [$v], $file);
    $saved = save_uploaded_images('__banner');
    unset($_FILES['__banner']);
    return $saved ? [$saved[0], 'image'] : 'Usa una imagen JPG, PNG o WEBP, o un video MP4 o WEBM.';
}

// ---------- Páginas ----------

function find_page(string $slug): ?array
{
    try {
        return query('SELECT * FROM pages WHERE slug = ? AND published = 1', [$slug])->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}
