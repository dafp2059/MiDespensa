<?php
require __DIR__ . '/../app/bootstrap.php';

if (is_post()) {
    csrf_check();
    $back = input('volver', '/contacto');
    if (!str_starts_with($back, '/') || str_starts_with($back, '//')) {
        $back = '/contacto';
    }

    // Campo trampa para robots: una persona nunca lo llena.
    if (input('website') !== '') {
        redirect($back);
    }

    $data = [
        'nombre' => mb_substr(input('nombre'), 0, 120),
        'telefono' => mb_substr(input('telefono'), 0, 40),
        'correo' => mb_substr(input('correo'), 0, 190),
        'motivo' => input('motivo'),
        'mensaje' => mb_substr(input('mensaje'), 0, 5000),
    ];
    $errors = [];
    if ($data['nombre'] === '') $errors[] = 'Escribe tu nombre.';
    if ($data['telefono'] === '') $errors[] = 'Escribe tu número de celular.';
    if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Revisa tu correo electrónico.';
    if (!isset(CONTACT_REASONS[$data['motivo']])) $errors[] = 'Selecciona el motivo del contacto.';
    if (input('acepto') !== '1') $errors[] = 'Debes autorizar el tratamiento de datos para poder contactarte.';

    // Máximo 5 mensajes por hora desde la misma IP.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $recent = (int) query('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)', [$ip])->fetchColumn();
    if ($recent >= 5) $errors[] = 'Recibimos varios mensajes tuyos. Si es urgente, escríbenos por WhatsApp.';

    if ($errors) {
        $_SESSION['old_contact'] = $data;
        flash('error', implode(' ', $errors));
        redirect($back);
    }

    $propertyId = (int) input('property_id') ?: null;
    $property = $propertyId ? query('SELECT id, title, slug FROM properties WHERE id = ?', [$propertyId])->fetch() : null;
    query('INSERT INTO messages (name, email, phone, reason, message, property_id, ip) VALUES (?, ?, ?, ?, ?, ?, ?)', [
        $data['nombre'], $data['correo'], $data['telefono'], $data['motivo'], $data['mensaje'], $property['id'] ?? null, $ip,
    ]);

    // Aviso por correo al equipo (si el hosting tiene correo configurado).
    $to = (string) config('notify_email');
    if ($to !== '') {
        $body = "Nuevo mensaje desde la web\n\n"
            . 'Nombre: ' . $data['nombre'] . "\n"
            . 'Celular: ' . $data['telefono'] . "\n"
            . 'Correo: ' . $data['correo'] . "\n"
            . 'Motivo: ' . CONTACT_REASONS[$data['motivo']] . "\n"
            . ($property ? 'Inmueble: ' . $property['title'] . ' - ' . property_url($property) . "\n" : '')
            . "\n" . $data['mensaje'] . "\n\n"
            . 'Ver todos los mensajes: ' . url('admin/mensajes.php');
        $host = parse_url((string) config('base_url'), PHP_URL_HOST) ?: 'localhost';
        $headers = 'From: ' . site('name') . ' <no-reply@' . $host . ">\r\n"
            . 'Reply-To: ' . str_replace(["\r", "\n"], '', $data['correo']) . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";
        @mail($to, '=?UTF-8?B?' . base64_encode('Nuevo contacto web: ' . CONTACT_REASONS[$data['motivo']]) . '?=', $body, $headers);
    }

    flash('success', '¡Gracias, ' . explode(' ', $data['nombre'])[0] . '! Recibimos tu mensaje y te responderemos en menos de 24 horas hábiles.');
    redirect($back);
}

// La página de contacto se edita en el panel (Diseño > Páginas > Contacto).
$p = find_page('contacto');
if (!$p) {
    require __DIR__ . '/404.php';
    exit;
}
if (isset(CONTACT_REASONS[input('motivo')])) {
    $p['form_reason'] = input('motivo');
}
view('page', ['p' => $p]);
