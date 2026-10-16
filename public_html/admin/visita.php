<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = require_can('agenda');

$id = (int) input('id');
$visit = $id ? visit_full($id) : null;
if ($id && !$visit) {
    redirect('admin/agenda.php');
}
$conflict = null;
$form = $visit ?? [];

// Agendar desde un mensaje de contacto: se llenan los datos del cliente y el inmueble.
if (!$visit && ($messageId = (int) input('mensaje'))) {
    $m = query('SELECT * FROM messages WHERE id = ?', [$messageId])->fetch();
    if ($m) {
        $form = ['client_name' => $m['name'], 'client_phone' => $m['phone'], 'client_email' => $m['email'], 'property_id' => $m['property_id'],
            'message_id' => $m['id'], 'notes' => $m['message']];
        $agentId = $m['property_id'] ? query('SELECT agent_user_id FROM properties WHERE id = ?', [$m['property_id']])->fetchColumn() : null;
        $form['agent_user_id'] = $agentId ?: ($user['role'] === 'asesor' ? $user['id'] : null);
    }
}
if (!$visit && ($propertyId = (int) input('inmueble'))) {
    $form['property_id'] = $propertyId;
}

if (is_post()) {
    csrf_check();
    if (input('accion') === 'eliminar' && $visit && can('eliminar')) {
        query('DELETE FROM visits WHERE id = ?', [$id]);
        flash('success', 'Visita eliminada.');
        redirect('admin/agenda.php');
    }
    $date = input('date');
    $time = input('time');
    $data = [
        'property_id' => (int) input('property_id') ?: null,
        'agent_user_id' => (int) input('agent_user_id') ?: null,
        'message_id' => (int) input('message_id') ?: null,
        'client_name' => mb_substr(input('client_name'), 0, 150),
        'client_phone' => mb_substr(input('client_phone'), 0, 40) ?: null,
        'client_email' => mb_substr(input('client_email'), 0, 190) ?: null,
        'scheduled_at' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^\d{2}:\d{2}$/', $time) ? "{$date} {$time}:00" : null,
        'duration_minutes' => max(15, min(480, (int) input('duration_minutes', '60'))),
        'status' => isset(VISIT_STATUSES[input('status')]) ? input('status') : 'programada',
        'notes' => input('notes') ?: null,
        'result' => input('result') ?: null,
    ];
    $form = array_merge($form, $data);
    $errors = [];
    if ($data['client_name'] === '') $errors[] = 'Escribe el nombre del cliente.';
    if (!$data['scheduled_at']) $errors[] = 'Elige la fecha y la hora.';
    if (!$errors && $data['status'] === 'programada' && input('forzar') !== '1') {
        $conflict = visit_conflict($data['agent_user_id'], $data['scheduled_at'], $data['duration_minutes'], $id);
    }
    if ($errors) {
        flash('error', implode(' ', $errors));
    } elseif (!$conflict) {
        $columns = array_keys($data);
        if ($visit) {
            query('UPDATE visits SET ' . implode(', ', array_map(fn($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $id]);
        } else {
            $data['created_by'] = $user['id'];
            $columns[] = 'created_by';
            query('INSERT INTO visits (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $id = (int) db()->lastInsertId();
            if ($data['message_id']) {
                query('UPDATE messages SET read_at = COALESCE(read_at, NOW()) WHERE id = ?', [$data['message_id']]);
            }
        }
        flash('success', 'Visita guardada. Envía la confirmación al cliente y al asesor.');
        redirect('admin/visita.php?id=' . $id);
    }
}

$properties = query('SELECT id, code, title FROM properties ORDER BY CAST(code AS UNSIGNED), title')->fetchAll();
$f = fn(string $k, $d = '') => e($form[$k] ?? $d);
$when = $form['scheduled_at'] ?? null;
$title = $visit ? 'Visita' : 'Agendar visita';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1><?= e($title) ?></h1><a href="<?= e(url('admin/agenda.php')) ?>">← Agenda</a></div>

<?php if ($visit): ?>
  <?php
  $place = visit_place($visit);
  $clientText = 'Hola ' . explode(' ', $visit['client_name'])[0] . ', te confirmamos la visita ' . ($visit['property_title'] ? 'al inmueble "' . $visit['property_title'] . '"' . ($visit['property_code'] ? ' (código ' . $visit['property_code'] . ')' : '') : '')
      . ' el ' . dia_largo($visit['scheduled_at']) . ' a las ' . hora($visit['scheduled_at']) . '.'
      . ($visit['agent_name'] ? ' Te atenderá ' . $visit['agent_name'] . ($visit['agent_phone'] ? ' (' . phone_display($visit['agent_phone']) . ')' : '') . '.' : '')
      . ($place ? ' Dirección: ' . $place . '.' : '') . ($visit['property_slug'] ? ' ' . url('inmueble/' . $visit['property_slug']) : '') . ' – ' . site('name');
  $agentText = 'Visita agendada: ' . dia_largo($visit['scheduled_at']) . ', ' . hora($visit['scheduled_at']) . '. Cliente: ' . $visit['client_name']
      . ($visit['client_phone'] ? ' (' . $visit['client_phone'] . ')' : '') . '. Inmueble: ' . ($visit['property_code'] ? $visit['property_code'] . ' · ' : '') . ($visit['property_title'] ?: '—')
      . ($place ? '. ' . $place : '') . '. ' . url('admin/visita.php?id=' . $visit['id']);
  ?>
  <section class="admin-card visit-summary">
    <p class="visit-summary__when"><?= e(ucfirst(dia_largo($visit['scheduled_at']))) ?> · <?= e(hora($visit['scheduled_at'])) ?> <span class="status status--<?= ['programada' => 'pending', 'realizada' => 'approved'][$visit['status']] ?? 'declined' ?>"><?= e(VISIT_STATUSES[$visit['status']]) ?></span></p>
    <p><strong><?= e($visit['client_name']) ?></strong> · <?= e($visit['property_title'] ?? 'Sin inmueble') ?> · <?= e($visit['agent_name'] ?? 'Sin asesor') ?></p>
    <div class="owners__actions">
      <?php if ($visit['client_phone']): ?><a class="btn btn--wa" href="https://wa.me/<?= e(phone_whatsapp($visit['client_phone'])) ?>?text=<?= e(rawurlencode($clientText)) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Confirmar al cliente</a><?php endif; ?>
      <?php if ($visit['agent_phone']): ?><a class="btn btn--ghost" href="https://wa.me/<?= e(phone_whatsapp($visit['agent_phone'])) ?>?text=<?= e(rawurlencode($agentText)) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Avisar al asesor</a><?php endif; ?>
      <a class="btn btn--ghost" href="<?= e(url('admin/visita-ics.php?id=' . $visit['id'])) ?>"><?= icon('clock') ?> Agregar a mi calendario</a>
    </div>
  </section>
<?php endif; ?>

<?php if ($conflict): ?>
  <div class="alert alert--error" role="alert">
    Ese asesor ya tiene una visita a esa hora: <?= e(hora($conflict['scheduled_at'])) ?> con <?= e($conflict['client_name']) ?><?= $conflict['property_title'] ? ' (' . e($conflict['property_title']) . ')' : '' ?>.
    Cambia la hora o el asesor, o guárdala de todas formas.
  </div>
<?php endif; ?>

<form class="form admin-form" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="message_id" value="<?= $f('message_id') ?>">
  <fieldset class="admin-card">
    <legend>Cuándo y dónde</legend>
    <div class="form__row form__row--4">
      <div class="field"><label for="date">Fecha *</label><input id="date" type="date" name="date" required value="<?= e($when ? substr($when, 0, 10) : date('Y-m-d', strtotime('+1 day'))) ?>"></div>
      <div class="field"><label for="time">Hora *</label><input id="time" type="time" name="time" step="900" required value="<?= e($when ? substr($when, 11, 5) : '10:00') ?>"></div>
      <div class="field"><label for="duration_minutes">Duración</label>
        <select id="duration_minutes" name="duration_minutes"><?php foreach ([30 => '30 minutos', 60 => '1 hora', 90 => '1 hora y media', 120 => '2 horas'] as $k => $l): ?><option value="<?= $k ?>" <?= (int) ($form['duration_minutes'] ?? 60) === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="status">Estado</label>
        <select id="status" name="status"><?php foreach (VISIT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= ($form['status'] ?? 'programada') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form__row">
      <div class="field"><label for="property_id">Inmueble</label>
        <select id="property_id" name="property_id"><option value="">Sin inmueble</option><?php foreach ($properties as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) ($form['property_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['code'] . ' · ' . $p['title']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="agent_user_id">Asesor</label>
        <select id="agent_user_id" name="agent_user_id"><option value="">Sin asignar</option><?php foreach (staff_users() as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) ($form['agent_user_id'] ?? ($user['role'] === 'asesor' ? $user['id'] : 0)) === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select></div>
    </div>
  </fieldset>
  <fieldset class="admin-card">
    <legend>Cliente</legend>
    <div class="form__row">
      <div class="field"><label for="client_name">Nombre *</label><input id="client_name" name="client_name" required value="<?= $f('client_name') ?>"></div>
      <div class="field"><label for="client_phone">Celular</label><input id="client_phone" name="client_phone" type="tel" value="<?= $f('client_phone') ?>"></div>
      <div class="field"><label for="client_email">Correo</label><input id="client_email" name="client_email" type="email" value="<?= $f('client_email') ?>"></div>
    </div>
    <div class="field"><label for="notes">Notas</label><textarea id="notes" name="notes" rows="2"><?= $f('notes') ?></textarea></div>
    <div class="field"><label for="result">Resultado de la visita</label><textarea id="result" name="result" rows="2" placeholder="Ej.: le gustó, va a pensarlo; pidió rebaja…"><?= $f('result') ?></textarea></div>
  </fieldset>
  <div class="admin-actions">
    <button class="btn" type="submit">Guardar visita</button>
    <?php if ($conflict): ?><button class="btn btn--ghost" name="forzar" value="1">Guardar de todas formas</button><?php endif; ?>
    <?php if ($visit && can('eliminar')): ?><button class="btn btn--danger" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar esta visita?">Eliminar</button><?php endif; ?>
  </div>
</form>
<?php view('admin_footer'); ?>
