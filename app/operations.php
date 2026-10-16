<?php
declare(strict_types=1);

/*
 * Operación diaria: actas de inventario, reajustes por IPC, agenda de visitas y mapa.
 */

// ---------- Actas de inventario ----------

const INVENTORY_TYPES = ['entrega' => 'Acta de entrega', 'recepcion' => 'Acta de recepción'];
const INVENTORY_STATES = ['bueno' => 'Bueno', 'regular' => 'Regular', 'malo' => 'Malo', 'na' => 'No aplica'];

/** Espacios y elementos que trae un acta nueva. Se pueden quitar o agregar al llenarla. */
function default_inventory(): array
{
    $room = ['Paredes y pintura', 'Pisos', 'Techo', 'Puertas y chapas', 'Ventanas y vidrios', 'Tomas e interruptores', 'Iluminación'];
    return [
        'General' => ['Puerta principal y chapa', 'Citófono o timbre', 'Fachada'],
        'Sala y comedor' => [...$room, 'Cortinas o persianas'],
        'Cocina' => [...$room, 'Mesón', 'Lavaplatos y grifería', 'Gabinetes', 'Estufa', 'Horno', 'Campana extractora'],
        'Habitación principal' => [...$room, 'Clóset'],
        'Habitación 2' => [...$room, 'Clóset'],
        'Baño principal' => ['Paredes y enchape', 'Pisos', 'Sanitario', 'Lavamanos y grifería', 'Ducha y grifería', 'Espejo', 'Accesorios', 'Puerta'],
        'Baño social' => ['Paredes y enchape', 'Pisos', 'Sanitario', 'Lavamanos y grifería', 'Espejo', 'Puerta'],
        'Zona de ropas' => ['Lavadero', 'Conexión de lavadora', 'Calentador', 'Pisos y paredes'],
        'Parqueadero y útil' => ['Parqueadero', 'Cuarto útil'],
    ];
}

function inventory_full(int $id): ?array
{
    $inv = query('SELECT i.*, u.name AS created_by_name FROM inventories i LEFT JOIN users u ON u.id = i.created_by WHERE i.id = ?', [$id])->fetch();
    if (!$inv) {
        return null;
    }
    $inv['contract'] = contract_full((int) $inv['contract_id']);
    $inv['items'] = query('SELECT * FROM inventory_items WHERE inventory_id = ? ORDER BY sort_order, id', [$id])->fetchAll();
    $inv['photos'] = query('SELECT * FROM inventory_photos WHERE inventory_id = ? ORDER BY id', [$id])->fetchAll();
    return $inv;
}

/** Agrupa filas por espacio, conservando el orden. */
function group_by_area(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $groups[$row['area']][] = $row;
    }
    return $groups;
}

// ---------- Reajuste por IPC ----------

function ipc_table(): array
{
    $table = json_decode(setting('ipc'), true);
    if (!is_array($table)) {
        return [];
    }
    krsort($table);
    return array_map('floatval', $table);
}

function last_adjustment(int $contractId): ?array
{
    return query('SELECT * FROM contract_adjustments WHERE contract_id = ? ORDER BY effective_date DESC, id DESC LIMIT 1', [$contractId])->fetch() ?: null;
}

/**
 * Próximo reajuste de un arriendo: cada 12 meses desde el inicio (art. 20, Ley 820 de 2003).
 * Devuelve la fecha, el año del IPC que aplica, su valor (si está cargado) y el canon máximo.
 */
function next_adjustment(array $contract): ?array
{
    if ($contract['type'] !== 'arriendo' || empty($contract['start_date']) || $contract['status'] !== 'activo') {
        return null;
    }
    $start = new DateTimeImmutable($contract['start_date']);
    $last = last_adjustment((int) $contract['id']);
    $after = $last ? new DateTimeImmutable($last['effective_date']) : $start;
    $date = $start;
    while ($date <= $after) {
        $date = $date->modify('+12 months');
    }
    $ipcYear = (int) $date->format('Y') - 1;
    $ipc = ipc_table()[(string) $ipcYear] ?? null;
    return [
        'date' => $date->format('Y-m-d'),
        'days' => (int) floor((strtotime($date->format('Y-m-d')) - strtotime(date('Y-m-d'))) / 86400),
        'ipc_year' => $ipcYear,
        'ipc' => $ipc,
        'max_amount' => $ipc !== null ? (int) floor((int) $contract['amount'] * (1 + $ipc / 100)) : null,
    ];
}

/** Redondea hacia abajo a la centena: el canon nunca queda por encima del tope legal. */
function round_down_hundred(int $amount): int
{
    return intdiv($amount, 100) * 100;
}

/** Cuotas mensuales de un arriendo desde un mes dado (para el inicio o una prórroga). */
function add_rent_charges(array $contract, DateTimeImmutable $firstMonth, int $months): int
{
    $amount = (int) $contract['amount'] + (int) $contract['admin_fee'];
    $label = $contract['admin_fee'] ? 'Canon + administración' : 'Canon';
    for ($i = 0; $i < $months; $i++) {
        $month = $firstMonth->modify('first day of +' . $i . ' month');
        $day = min((int) $contract['due_day'], (int) $month->format('t'));
        query('INSERT INTO charges (contract_id, concept, description, amount, due_date) VALUES (?, ?, ?, ?, ?)', [
            $contract['id'], 'canon', $label . ' ' . MONTHS[(int) $month->format('n')] . ' ' . $month->format('Y'), $amount,
            $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day)->format('Y-m-d'),
        ]);
    }
    return $months;
}

// ---------- Agenda de visitas ----------

const VISIT_STATUSES = ['programada' => 'Programada', 'realizada' => 'Realizada', 'cancelada' => 'Cancelada', 'no_asistio' => 'No asistió'];
const WEEKDAYS = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

function hora(string $datetime): string
{
    return date('g:i a', strtotime($datetime));
}

function dia_largo(string $date): string
{
    $t = strtotime($date);
    return WEEKDAYS[(int) date('N', $t)] . ' ' . date('j', $t) . ' de ' . MONTHS[(int) date('n', $t)];
}

function visit_full(int $id): ?array
{
    return query('SELECT v.*, p.title AS property_title, p.code AS property_code, p.slug AS property_slug, p.address AS property_address,
            p.neighborhood AS property_neighborhood, l.name AS property_city, u.name AS agent_name, u.phone AS agent_phone
        FROM visits v LEFT JOIN properties p ON p.id = v.property_id LEFT JOIN locations l ON l.id = p.location_id
        LEFT JOIN users u ON u.id = v.agent_user_id WHERE v.id = ?', [$id])->fetch() ?: null;
}

/** Otra visita del mismo asesor que se cruza con este horario. */
function visit_conflict(?int $agentId, string $start, int $minutes, int $ignoreId = 0): ?array
{
    if (!$agentId) {
        return null;
    }
    return query("SELECT v.*, p.title AS property_title FROM visits v LEFT JOIN properties p ON p.id = v.property_id
        WHERE v.agent_user_id = ? AND v.id <> ? AND v.status = 'programada'
          AND v.scheduled_at < (? + INTERVAL ? MINUTE) AND (v.scheduled_at + INTERVAL v.duration_minutes MINUTE) > ?
        LIMIT 1", [$agentId, $ignoreId, $start, $minutes, $start])->fetch() ?: null;
}

function visit_place(array $v): string
{
    return implode(', ', array_filter([$v['property_address'] ?: $v['property_title'], $v['property_neighborhood'], $v['property_city']]));
}

/** Archivo .ics para agregar la visita al calendario del celular. */
function visit_ics(array $v): string
{
    $start = new DateTimeImmutable($v['scheduled_at'], new DateTimeZone('America/Bogota'));
    $end = $start->modify('+' . (int) $v['duration_minutes'] . ' minutes');
    $utc = fn(DateTimeImmutable $d) => $d->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
    $esc = fn(string $t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $t);
    $host = parse_url((string) config('base_url'), PHP_URL_HOST) ?: 'localhost';
    $description = 'Cliente: ' . $v['client_name'] . ($v['client_phone'] ? ' · ' . $v['client_phone'] : '')
        . ($v['property_code'] ? "\nInmueble código " . $v['property_code'] : '') . ($v['notes'] ? "\n" . $v['notes'] : '');
    return implode("\r\n", [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . site('name') . '//Agenda//ES', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
        'UID:visita-' . $v['id'] . '@' . $host,
        'DTSTAMP:' . $utc(new DateTimeImmutable()),
        'DTSTART:' . $utc($start),
        'DTEND:' . $utc($end),
        'SUMMARY:' . $esc('Visita: ' . ($v['property_title'] ?: 'inmueble') . ' – ' . $v['client_name']),
        'LOCATION:' . $esc(visit_place($v)),
        'DESCRIPTION:' . $esc($description),
        'BEGIN:VALARM', 'TRIGGER:-PT1H', 'ACTION:DISPLAY', 'DESCRIPTION:' . $esc('Visita en 1 hora'), 'END:VALARM',
        'END:VEVENT', 'END:VCALENDAR', '',
    ]);
}

function phone_whatsapp(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    return strlen($digits) === 10 ? '57' . $digits : $digits;
}

// ---------- Mapa ----------

/** Precio corto para los marcadores del mapa: $450M, $2,8M, $950K. */
function short_money(int $amount): string
{
    if ($amount >= 1000000) {
        $value = $amount / 1000000;
        return '$' . ($value >= 100 ? number_format(round($value), 0, ',', '.') : str_replace('.', ',', (string) round($value, 1))) . 'M';
    }
    return $amount >= 1000 ? '$' . round($amount / 1000) . 'K' : '$' . $amount;
}

/** Datos de los inmuebles con coordenadas, listos para el mapa. */
function map_points(array $properties): array
{
    $points = [];
    foreach ($properties as $p) {
        if ($p['latitude'] === null || $p['longitude'] === null) continue;
        $points[] = [
            'lat' => (float) $p['latitude'],
            'lng' => (float) $p['longitude'],
            'title' => $p['title'],
            'price' => $p['price'] ? short_money((int) $p['price']) : '',
            'priceFull' => $p['price'] ? money($p['price']) . ($p['operation'] === 'arriendo' ? ' / mes' : '') : 'Precio a convenir',
            'place' => property_place($p),
            'code' => (string) $p['code'],
            'op' => $p['operation'],
            'closed' => is_closed($p),
            'url' => property_url($p),
            'img' => $p['cover'] ? upload_url($p['cover']) : '',
        ];
    }
    return $points;
}
