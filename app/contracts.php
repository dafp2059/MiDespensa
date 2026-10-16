<?php
declare(strict_types=1);

/*
 * Clientes, contratos (arriendo y venta), cuotas y portal "Mi cuenta".
 */

const CONTRACT_TYPES = ['arriendo' => 'Arrendamiento', 'venta' => 'Promesa de compraventa'];
const CONTRACT_STATUSES = ['activo' => 'Activo', 'terminado' => 'Terminado', 'cancelado' => 'Cancelado'];
const CHARGE_CONCEPTS = [
    'canon' => 'Canon de arrendamiento',
    'separacion' => 'Separación',
    'cuota_inicial' => 'Cuota inicial',
    'cuota' => 'Cuota',
    'saldo' => 'Saldo',
    'otro' => 'Otro',
];
const MANUAL_METHODS = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'consignacion' => 'Consignación', 'otro' => 'Otro'];
const DOCUMENT_TYPES = ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'NIT' => 'NIT', 'PP' => 'Pasaporte'];
const MONTHS = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

function fecha_larga(?string $date): string
{
    if (!$date) {
        return '';
    }
    $t = strtotime($date);
    return date('j', $t) . ' de ' . MONTHS[(int) date('n', $t)] . ' de ' . date('Y', $t);
}

function fecha_corta(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : '';
}

// ---------- Números en letras (para contratos) ----------

function numero_a_letras(int $n): string
{
    if ($n === 0) {
        return 'cero';
    }
    $millions = intdiv($n, 1000000);
    $rest = $n % 1000000;
    $parts = [];
    if ($millions > 0) {
        $parts[] = $millions === 1 ? 'un millón' : apocope(miles_a_letras($millions)) . ' millones';
    }
    if ($rest > 0) {
        $parts[] = miles_a_letras($rest);
    }
    return implode(' ', $parts);
}

function miles_a_letras(int $n): string
{
    $thousands = intdiv($n, 1000);
    $rest = $n % 1000;
    $parts = [];
    if ($thousands === 1) {
        $parts[] = 'mil';
    } elseif ($thousands > 1) {
        $parts[] = apocope(cientos_a_letras($thousands)) . ' mil';
    }
    if ($rest > 0) {
        $parts[] = cientos_a_letras($rest);
    }
    return implode(' ', $parts);
}

function cientos_a_letras(int $n): string
{
    if ($n === 100) {
        return 'cien';
    }
    $hundreds = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
    $h = intdiv($n, 100);
    $rest = $n % 100;
    return trim($hundreds[$h] . ($rest ? ' ' . decenas_a_letras($rest) : ''));
}

function decenas_a_letras(int $n): string
{
    $under30 = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece',
        'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés',
        'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
    if ($n < 30) {
        return $under30[$n];
    }
    $tens = [3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    return $tens[intdiv($n, 10)] . ($n % 10 ? ' y ' . $under30[$n % 10] : '');
}

/** "veintiuno" → "veintiún", "uno" → "un" (delante de mil / millones). */
function apocope(string $text): string
{
    if (str_ends_with($text, 'veintiuno')) {
        return substr($text, 0, -strlen('veintiuno')) . 'veintiún';
    }
    return str_ends_with($text, 'uno') ? substr($text, 0, -3) . 'un' : $text;
}

function pesos_en_letras(int $amount): string
{
    if ($amount === 1) {
        return 'UN PESO M/CTE';
    }
    $words = apocope(numero_a_letras($amount));
    $de = preg_match('/(millón|millones)$/u', $words) ? ' de' : '';
    return mb_strtoupper($words . $de . ' pesos') . ' M/CTE';
}

// ---------- Clientes ----------

function current_client(): ?array
{
    static $client = false;
    if ($client === false) {
        $client = null;
        if (!empty($_SESSION['client_id'])) {
            $client = query('SELECT * FROM clients WHERE id = ? AND active = 1', [$_SESSION['client_id']])->fetch() ?: null;
        }
    }
    return $client;
}

function require_client(): array
{
    $client = current_client();
    if (!$client) {
        redirect('mi-cuenta/ingresar.php');
    }
    return $client;
}

/** Crea un enlace para que el cliente defina su contraseña. Vale 7 días. */
function client_access_link(int $clientId): string
{
    $token = bin2hex(random_bytes(24));
    query('DELETE FROM client_tokens WHERE client_id = ? OR expires_at < NOW()', [$clientId]);
    query('INSERT INTO client_tokens (client_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 7 DAY)', [$clientId, hash('sha256', $token)]);
    return url('mi-cuenta/clave.php?t=' . $token);
}

function client_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
        return null;
    }
    return query('SELECT c.* FROM client_tokens t JOIN clients c ON c.id = t.client_id
        WHERE t.token_hash = ? AND t.expires_at > NOW() AND c.active = 1', [hash('sha256', $token)])->fetch() ?: null;
}

function client_whatsapp(array $client, string $text): string
{
    $phone = preg_replace('/\D/', '', (string) $client['phone']);
    if (strlen($phone) === 10) {
        $phone = '57' . $phone;
    }
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
}

function send_mail(string $to, string $subject, string $body): bool
{
    $host = parse_url((string) config('base_url'), PHP_URL_HOST) ?: 'localhost';
    $headers = 'From: ' . site('name') . ' <no-reply@' . $host . ">\r\n"
        . 'Reply-To: ' . site('email') . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

// ---------- Contratos ----------

function contract_full(int $id): ?array
{
    return query('SELECT ct.*, c.name AS client_name, c.document_type AS client_document_type, c.document AS client_document,
            c.email AS client_email, c.phone AS client_phone, c.address AS client_address,
            p.title AS property_title, p.code AS property_code, p.type AS property_type, p.address AS property_address,
            p.registry_number AS property_registry, p.neighborhood AS property_neighborhood, p.slug AS property_slug,
            l.name AS property_city
        FROM contracts ct
        JOIN clients c ON c.id = ct.client_id
        LEFT JOIN properties p ON p.id = ct.property_id
        LEFT JOIN locations l ON l.id = p.location_id
        WHERE ct.id = ?', [$id])->fetch() ?: null;
}

function contract_charges(int $contractId): array
{
    return query('SELECT * FROM charges WHERE contract_id = ? ORDER BY due_date, id', [$contractId])->fetchAll();
}

function charge_state(array $charge): string
{
    if ($charge['status'] === 'pendiente' && $charge['due_date'] < date('Y-m-d')) {
        return 'vencida';
    }
    return $charge['status'];
}

const CHARGE_STATE_LABELS = ['pendiente' => 'Pendiente', 'vencida' => 'Vencida', 'pagado' => 'Pagada', 'anulado' => 'Anulada'];

/** Datos que se pueden usar en las plantillas como {{variable}}. */
function contract_variables(array $c): array
{
    $place = implode(', ', array_filter([$c['property_address'] ?: $c['property_title'], $c['property_neighborhood'], $c['property_city']]));
    return [
        '{{contrato_codigo}}' => $c['code'],
        '{{fecha_contrato}}' => fecha_larga($c['signed_at'] ?: date('Y-m-d')),
        '{{cliente_nombre}}' => $c['client_name'],
        '{{cliente_documento}}' => trim($c['client_document_type'] . ' ' . $c['client_document']),
        '{{cliente_telefono}}' => $c['client_phone'],
        '{{cliente_correo}}' => $c['client_email'],
        '{{cliente_direccion}}' => (string) $c['client_address'],
        '{{propietario_nombre}}' => (string) $c['owner_name'],
        '{{propietario_documento}}' => (string) $c['owner_document'],
        '{{propietario_telefono}}' => (string) $c['owner_phone'],
        '{{codeudor_nombre}}' => (string) $c['cosigner_name'],
        '{{codeudor_documento}}' => (string) $c['cosigner_document'],
        '{{codeudor_telefono}}' => (string) $c['cosigner_phone'],
        '{{codeudor_direccion}}' => (string) $c['cosigner_address'],
        '{{inmueble_titulo}}' => (string) $c['property_title'],
        '{{inmueble_codigo}}' => (string) $c['property_code'],
        '{{inmueble_tipo}}' => isset(TYPES[$c['property_type'] ?? '']) ? mb_strtolower(TYPES[$c['property_type']]) : 'inmueble',
        '{{inmueble_direccion}}' => $place,
        '{{inmueble_matricula}}' => (string) $c['property_registry'],
        '{{valor}}' => money($c['amount']),
        '{{valor_letras}}' => pesos_en_letras((int) $c['amount']),
        '{{administracion}}' => $c['admin_fee'] ? money($c['admin_fee']) : 'incluida en el canon',
        '{{dia_pago}}' => (string) $c['due_day'],
        '{{fecha_inicio}}' => fecha_larga($c['start_date']),
        '{{fecha_fin}}' => fecha_larga($c['end_date']),
        '{{duracion_meses}}' => (string) $c['duration_months'],
        '{{fecha_escritura}}' => fecha_larga($c['deed_date']),
        '{{notaria}}' => (string) $c['notary'],
        '{{portal}}' => url('mi-cuenta/'),
    ];
}

/** Texto del contrato a partir de una plantilla, con los datos del contrato. */
function render_contract_template(string $templateBody, array $contract): string
{
    return tokens(strtr(clean_html($templateBody), array_map('e', contract_variables($contract))));
}

/** Tabla del plan de pagos (reemplaza {{plan_pagos}} al mostrar o imprimir). */
function plan_pagos_html(array $charges): string
{
    $rows = '';
    $total = 0;
    foreach ($charges as $ch) {
        if ($ch['status'] === 'anulado') continue;
        $total += (int) $ch['amount'];
        $rows .= '<tr><td>' . e($ch['description']) . '</td><td>' . e(fecha_corta($ch['due_date'])) . '</td><td class="num">' . e(money($ch['amount'], false)) . '</td></tr>';
    }
    return '<table class="doc-table"><thead><tr><th>Concepto</th><th>Fecha</th><th class="num">Valor (COP)</th></tr></thead><tbody>'
        . $rows . '</tbody><tfoot><tr><th colspan="2">Total</th><th class="num">' . e(money($total, false)) . '</th></tr></tfoot></table>';
}

function contract_body_html(array $contract): string
{
    $body = (string) $contract['body'];
    if (str_contains($body, '{{plan_pagos}}')) {
        $table = plan_pagos_html(contract_charges((int) $contract['id']));
        $body = preg_replace_callback('#<p>\s*\{\{plan_pagos\}\}\s*</p>#', fn() => $table, $body);
        $body = str_replace('{{plan_pagos}}', $table, $body);
    }
    return $body;
}

/** Cuotas mensuales de un arriendo: una por mes con canon + administración. */
function generate_rent_charges(array $contract): int
{
    return add_rent_charges($contract, new DateTimeImmutable($contract['start_date']), (int) $contract['duration_months']);
}

function next_contract_code(string $type): string
{
    $prefix = $type === 'venta' ? 'VEN' : 'ARR';
    $n = (int) query('SELECT COUNT(*) FROM contracts WHERE type = ?', [$type])->fetchColumn() + 1;
    do {
        $code = sprintf('%s-%04d', $prefix, $n++);
    } while (query('SELECT id FROM contracts WHERE code = ?', [$code])->fetch());
    return $code;
}

/** Marca una cuota como pagada (Wompi o pago registrado a mano). */
function mark_charge_paid(int $chargeId, string $method, ?int $paymentId = null, ?string $date = null): void
{
    query("UPDATE charges SET status = 'pagado', paid_at = ?, paid_method = ?, payment_id = ? WHERE id = ? AND status = 'pendiente'",
        [$date ?: date('Y-m-d H:i:s'), $method, $paymentId, $chargeId]);
}

/** Datos de una cuota pagada para su recibo. $clientId limita a las cuotas de ese cliente. */
function receipt_data(int $chargeId, ?int $clientId = null): ?array
{
    $row = query("SELECT ch.*, ct.code, ct.type, ct.client_id, c.name AS client_name, c.document_type AS client_document_type, c.document AS client_document,
            p.title AS property_title, p.address AS property_address, l.name AS property_city, pay.reference
        FROM charges ch
        JOIN contracts ct ON ct.id = ch.contract_id
        JOIN clients c ON c.id = ct.client_id
        LEFT JOIN properties p ON p.id = ct.property_id
        LEFT JOIN locations l ON l.id = p.location_id
        LEFT JOIN payments pay ON pay.id = ch.payment_id
        WHERE ch.id = ? AND ch.status = 'pagado'", [$chargeId])->fetch();
    if (!$row || ($clientId !== null && (int) $row['client_id'] !== $clientId)) {
        return null;
    }
    return $row;
}
