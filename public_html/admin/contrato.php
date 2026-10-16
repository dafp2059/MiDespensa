<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();
$GLOBALS['needs_editor'] = true;

$id = (int) input('id');
$contract = $id ? contract_full($id) : null;
if ($id && !$contract) {
    redirect('admin/contratos.php');
}
$type = $contract['type'] ?? (isset(CONTRACT_TYPES[input('tipo')]) ? input('tipo') : '');
$errors = [];

/** Lee del formulario los datos comunes del contrato. */
$readData = function (string $type): array {
    $date = fn(string $k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', input($k)) ? input($k) : null;
    $data = [
        'property_id' => (int) input('property_id') ?: null,
        'owner_name' => mb_substr(input('owner_name'), 0, 150) ?: null,
        'owner_document' => mb_substr(input('owner_document'), 0, 40) ?: null,
        'owner_phone' => mb_substr(input('owner_phone'), 0, 40) ?: null,
        'amount' => parse_money(input('amount')),
        'signed_at' => $date('signed_at') ?: date('Y-m-d'),
        'notes' => input('notes') ?: null,
    ];
    if ($type === 'arriendo') {
        $data += [
            'cosigner_name' => mb_substr(input('cosigner_name'), 0, 150) ?: null,
            'cosigner_document' => mb_substr(input('cosigner_document'), 0, 40) ?: null,
            'cosigner_phone' => mb_substr(input('cosigner_phone'), 0, 40) ?: null,
            'cosigner_address' => mb_substr(input('cosigner_address'), 0, 190) ?: null,
            'admin_fee' => parse_money(input('admin_fee')),
            'due_day' => max(1, min(28, (int) input('due_day', '5'))),
            'start_date' => $date('start_date'),
            'duration_months' => max(1, min(120, (int) input('duration_months', '12'))),
        ];
        if ($data['start_date']) {
            $data['end_date'] = (new DateTimeImmutable($data['start_date']))->modify('+' . $data['duration_months'] . ' months -1 day')->format('Y-m-d');
        }
    } else {
        $data += ['deed_date' => $date('deed_date'), 'notary' => mb_substr(input('notary'), 0, 120) ?: null];
    }
    return $data;
};

if (is_post()) {
    csrf_check();
    $action = input('accion');

    if ($action === 'crear' && !$contract && $type) {
        $data = $readData($type);
        $clientId = (int) input('client_id');
        $template = query('SELECT * FROM contract_templates WHERE id = ? AND type = ?', [(int) input('template_id'), $type])->fetch();
        if (!query('SELECT id FROM clients WHERE id = ?', [$clientId])->fetch()) $errors[] = 'Elige el cliente.';
        if (!$data['property_id']) $errors[] = 'Elige el inmueble.';
        if ($data['amount'] <= 0) $errors[] = $type === 'arriendo' ? 'Escribe el canon mensual.' : 'Escribe el precio de venta.';
        if (!$template) $errors[] = 'Elige la plantilla del contrato.';
        if ($type === 'arriendo' && empty($data['start_date'])) $errors[] = 'Escribe la fecha de inicio.';

        $plan = [];
        if ($type === 'venta') {
            foreach ((array) ($_POST['plan_description'] ?? []) as $i => $desc) {
                $amount = parse_money((string) ($_POST['plan_amount'][$i] ?? ''));
                $due = (string) ($_POST['plan_date'][$i] ?? '');
                if (trim((string) $desc) === '' && !$amount) continue;
                if (!$amount || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due)) {
                    $errors[] = 'Cada fila del plan de pagos necesita fecha y valor.';
                    break;
                }
                $concept = isset(CHARGE_CONCEPTS[$_POST['plan_concept'][$i] ?? '']) ? $_POST['plan_concept'][$i] : 'cuota';
                $plan[] = [$concept, mb_substr(trim((string) $desc) ?: CHARGE_CONCEPTS[$concept], 0, 190), $amount, $due];
            }
            if (!$plan) $errors[] = 'Agrega al menos un pago al plan de pagos.';
        }

        if (!$errors) {
            $data += ['code' => next_contract_code($type), 'type' => $type, 'client_id' => $clientId, 'template_id' => (int) $template['id']];
            $columns = array_keys($data);
            query('INSERT INTO contracts (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $id = (int) db()->lastInsertId();
            $contract = contract_full($id);
            query('UPDATE contracts SET body = ? WHERE id = ?', [render_contract_template($template['body'], $contract), $id]);

            if ($type === 'arriendo') {
                $n = generate_rent_charges($contract);
            } else {
                foreach ($plan as [$concept, $desc, $amount, $due]) {
                    query('INSERT INTO charges (contract_id, concept, description, amount, due_date) VALUES (?, ?, ?, ?, ?)', [$id, $concept, $desc, $amount, $due]);
                }
                $n = count($plan);
                $sum = array_sum(array_column($plan, 2));
                if ($sum !== (int) $data['amount']) {
                    flash('error', 'Ojo: el plan de pagos suma ' . money($sum) . ' y el precio es ' . money($data['amount']) . '. Puedes ajustar las cuotas abajo.');
                }
            }
            if (input('marcar_inmueble') === '1') {
                query('UPDATE properties SET status = ? WHERE id = ?', [$type === 'arriendo' ? 'arrendado' : 'vendido', $data['property_id']]);
            }
            flash('success', "Contrato {$data['code']} creado con {$n} cuota(s).");
            redirect('admin/contrato.php?id=' . $id);
        }
    }

    if ($contract) {
        $chargeId = (int) input('charge_id');
        $charge = $chargeId ? query('SELECT * FROM charges WHERE id = ? AND contract_id = ?', [$chargeId, $id])->fetch() : null;

        if ($action === 'pago_manual' && $charge && $charge['status'] === 'pendiente') {
            $method = MANUAL_METHODS[input('method')] ?? 'Otro';
            $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', input('paid_date')) ? input('paid_date') . ' 12:00:00' : null;
            mark_charge_paid($chargeId, $method, null, $date);
            flash('success', 'Pago registrado: ' . $charge['description'] . '.');
        } elseif ($action === 'anular' && $charge && $charge['status'] === 'pendiente') {
            query("UPDATE charges SET status = 'anulado' WHERE id = ?", [$chargeId]);
            flash('success', 'Cuota anulada.');
        } elseif ($action === 'reabrir' && $charge && $charge['status'] !== 'pendiente' && !$charge['payment_id']) {
            query("UPDATE charges SET status = 'pendiente', paid_at = NULL, paid_method = NULL WHERE id = ?", [$chargeId]);
            flash('success', 'La cuota volvió a quedar pendiente.');
        } elseif ($action === 'editar_cuota' && $charge && $charge['status'] === 'pendiente') {
            $amount = parse_money(input('amount'));
            if ($amount > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', input('due_date'))) {
                query('UPDATE charges SET description = ?, amount = ?, due_date = ? WHERE id = ?', [mb_substr(input('description'), 0, 190) ?: $charge['description'], $amount, input('due_date'), $chargeId]);
                flash('success', 'Cuota actualizada.');
            }
        } elseif ($action === 'agregar_cuota') {
            $amount = parse_money(input('amount'));
            $concept = isset(CHARGE_CONCEPTS[input('concept')]) ? input('concept') : 'otro';
            if ($amount > 0 && preg_match('/^\d{4}-\d{2}-\d{2}$/', input('due_date'))) {
                query('INSERT INTO charges (contract_id, concept, description, amount, due_date) VALUES (?, ?, ?, ?, ?)',
                    [$id, $concept, mb_substr(input('description'), 0, 190) ?: CHARGE_CONCEPTS[$concept], $amount, input('due_date')]);
                flash('success', 'Cuota agregada.');
            } else {
                flash('error', 'La cuota necesita valor y fecha.');
            }
        } elseif ($action === 'guardar_texto') {
            query('UPDATE contracts SET body = ? WHERE id = ?', [clean_html((string) ($_POST['body'] ?? '')), $id]);
            flash('success', 'Texto del contrato guardado.');
        } elseif ($action === 'regenerar') {
            $template = query('SELECT * FROM contract_templates WHERE id = ?', [(int) input('template_id')])->fetch();
            if ($template) {
                query('UPDATE contracts SET body = ?, template_id = ? WHERE id = ?', [render_contract_template($template['body'], $contract), $template['id'], $id]);
                flash('success', 'Texto generado de nuevo desde la plantilla "' . $template['name'] . '".');
            }
        } elseif ($action === 'guardar_datos') {
            $data = $readData($contract['type']);
            $data['status'] = isset(CONTRACT_STATUSES[input('status')]) ? input('status') : $contract['status'];
            unset($data['property_id']);
            query('UPDATE contracts SET ' . implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data))) . ' WHERE id = ?', [...array_values($data), $id]);
            flash('success', 'Datos guardados. Si cambiaste nombres, valores o fechas, usa "Generar de nuevo" para actualizar el texto del contrato.');
        } elseif ($action === 'eliminar') {
            if (query("SELECT id FROM charges WHERE contract_id = ? AND status = 'pagado' LIMIT 1", [$id])->fetch()) {
                flash('error', 'El contrato tiene pagos registrados: en lugar de eliminarlo, cámbialo a Cancelado o Terminado.');
            } else {
                query('DELETE FROM contracts WHERE id = ?', [$id]);
                flash('success', 'Contrato eliminado.');
                redirect('admin/contratos.php');
            }
        }
        redirect('admin/contrato.php?id=' . $id);
    }
}

$properties = query('SELECT id, title, code, status FROM properties ORDER BY title')->fetchAll();
$clients = query('SELECT id, name, document FROM clients WHERE active = 1 ORDER BY name')->fetchAll();
$templates = $type ? query('SELECT id, name FROM contract_templates WHERE type = ? ORDER BY name', [$type])->fetchAll() : [];
$v = fn(string $k, $d = '') => e($contract[$k] ?? input($k, (string) $d));
$title = $contract ? 'Contrato ' . $contract['code'] : 'Nuevo contrato';
view('admin_header', compact('title'));

// ---------- Elegir tipo ----------
if (!$contract && !$type): ?>
  <div class="admin-head"><h1>Nuevo contrato</h1></div>
  <div class="admin-columns">
    <a class="admin-card choice" href="<?= e(url('admin/contrato.php?tipo=arriendo&cliente=' . (int) input('cliente'))) ?>"><?= icon('key') ?><strong>Arrendamiento</strong><span>Canon mensual. Se crean solas las cuotas de cada mes.</span></a>
    <a class="admin-card choice" href="<?= e(url('admin/contrato.php?tipo=venta&cliente=' . (int) input('cliente'))) ?>"><?= icon('home') ?><strong>Promesa de compraventa</strong><span>Precio de venta con plan de pagos: separación, cuotas y saldo.</span></a>
  </div>
<?php view('admin_footer'); return; endif;

// ---------- Crear ----------
if (!$contract): ?>
  <div class="admin-head"><h1><?= e(CONTRACT_TYPES[$type]) ?>: nuevo contrato</h1></div>
  <?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>
  <form class="form admin-form" method="post">
    <?= csrf_field() ?><input type="hidden" name="tipo" value="<?= e($type) ?>">
    <fieldset class="admin-card">
      <legend>Inmueble y cliente</legend>
      <div class="form__row">
        <div class="field"><label for="property_id">Inmueble *</label>
          <select id="property_id" name="property_id" required><option value="">Elige el inmueble</option>
            <?php foreach ($properties as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) input('property_id') === (int) $p['id'] ? 'selected' : '' ?>><?= e(($p['code'] ? $p['code'] . ' · ' : '') . $p['title'] . ' (' . STATUSES[$p['status']] . ')') ?></option><?php endforeach; ?>
          </select></div>
        <div class="field"><label for="client_id"><?= $type === 'arriendo' ? 'Arrendatario' : 'Comprador' ?> *</label>
          <select id="client_id" name="client_id" required><option value="">Elige el cliente</option>
            <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) input('client_id', input('cliente')) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name'] . ' · ' . $c['document']) ?></option><?php endforeach; ?>
          </select>
          <a href="<?= e(url('admin/cliente.php?volver=contrato&tipo=' . $type)) ?>">+ Crear cliente nuevo</a></div>
        <div class="field"><label for="template_id">Plantilla *</label>
          <select id="template_id" name="template_id" required><?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <p class="muted">La dirección y la matrícula inmobiliaria del inmueble se toman de su ficha (Inmuebles → Editar → Datos para contratos).</p>
    </fieldset>
    <?php require APP_DIR . '/views/admin_contract_fields.php'; ?>
    <?php if ($type === 'venta'): ?>
      <fieldset class="admin-card" data-repeater>
        <legend>Plan de pagos</legend>
        <p class="muted">Cada fila es una cuota que el comprador podrá pagar en línea. La suma debería ser igual al precio.</p>
        <div data-rows>
          <?php $planRow = function (string $concept = 'cuota', string $desc = '', string $date = '', string $amount = ''): void { ?>
            <div class="repeater__row">
              <label class="field field--small"><span class="field__label">Concepto</span>
                <select name="plan_concept[]"><?php foreach (CHARGE_CONCEPTS as $k => $l): if ($k === 'canon') continue; ?><option value="<?= $k ?>" <?= $concept === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
              <label class="field"><span class="field__label">Descripción</span><input name="plan_description[]" value="<?= e($desc) ?>" placeholder="Ej.: Cuota 1 de 6"></label>
              <label class="field field--small"><span class="field__label">Fecha</span><input type="date" name="plan_date[]" value="<?= e($date) ?>"></label>
              <label class="field field--small"><span class="field__label">Valor</span><input name="plan_amount[]" inputmode="numeric" data-money value="<?= e($amount) ?>"></label>
              <div class="repeater__tools"><button class="icon-btn icon-btn--danger" type="button" data-remove aria-label="Quitar">✕</button></div>
            </div>
          <?php };
          $planRow('separacion', 'Separación', date('Y-m-d')); $planRow('cuota_inicial', 'Cuota inicial'); $planRow('saldo', 'Saldo a la firma de la escritura'); ?>
        </div>
        <template><?php $planRow(); ?></template>
        <div><button class="btn btn--small btn--ghost" type="button" data-add>+ Agregar pago</button></div>
      </fieldset>
    <?php endif; ?>
    <label class="check"><input type="checkbox" name="marcar_inmueble" value="1" checked> <span>Marcar el inmueble como <?= $type === 'arriendo' ? 'arrendado' : 'vendido' ?> en la web</span></label>
    <div class="admin-actions"><button class="btn" type="submit" name="accion" value="crear">Crear contrato</button><a class="btn btn--ghost" href="<?= e(url('admin/contratos.php')) ?>">Cancelar</a></div>
  </form>
<?php view('admin_footer'); return; endif;

// ---------- Ver y administrar ----------
$charges = contract_charges($id);
$totals = ['pagado' => 0, 'pendiente' => 0, 'vencida' => 0];
foreach ($charges as $ch) {
    $state = charge_state($ch);
    if (isset($totals[$state])) $totals[$state] += (int) $ch['amount'];
}
$allTemplates = query('SELECT id, name FROM contract_templates WHERE type = ? ORDER BY name', [$contract['type']])->fetchAll();
?>
<div class="admin-head">
  <div>
    <h1><?= e($contract['code']) ?> · <?= e(CONTRACT_TYPES[$contract['type']]) ?></h1>
    <p class="muted"><a href="<?= e(url('admin/cliente.php?id=' . $contract['client_id'])) ?>"><?= e($contract['client_name']) ?></a> · <?= e($contract['property_title'] ?? 'Sin inmueble') ?> · <span class="status status--<?= $contract['status'] === 'activo' ? 'disponible' : 'vendido' ?>"><?= e(CONTRACT_STATUSES[$contract['status']]) ?></span></p>
  </div>
  <a class="btn" href="<?= e(url('admin/contrato-imprimir.php?id=' . $id)) ?>" target="_blank">Imprimir / PDF</a>
</div>

<div class="stats">
  <div class="stat"><span>Pagado</span><strong><?= e(money($totals['pagado'], false)) ?></strong></div>
  <div class="stat"><span>Pendiente</span><strong><?= e(money($totals['pendiente'], false)) ?></strong></div>
  <div class="stat"><span>Vencido</span><strong<?= $totals['vencida'] ? ' class="text-danger"' : '' ?>><?= e(money($totals['vencida'], false)) ?></strong></div>
</div>

<section class="admin-card">
  <h2>Cuotas</h2>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Cuota</th><th>Vence</th><th>Valor</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
      <tbody>
      <?php foreach ($charges as $ch): $state = charge_state($ch); ?>
        <tr>
          <td><strong><?= e($ch['description']) ?></strong></td>
          <td><?= e(fecha_corta($ch['due_date'])) ?></td>
          <td><?= e(money($ch['amount'], false)) ?></td>
          <td><span class="status status--<?= ['pagado' => 'approved', 'vencida' => 'declined', 'anulado' => 'vendido'][$state] ?? 'pending' ?>"><?= e(CHARGE_STATE_LABELS[$state]) ?></span>
            <?php if ($ch['status'] === 'pagado'): ?><br><span class="muted"><?= e($ch['paid_method'] . ' · ' . fecha_corta($ch['paid_at'])) ?></span><?php endif; ?></td>
          <td><div class="table__actions">
            <?php if ($ch['status'] === 'pendiente'): ?>
              <details class="inline-form">
                <summary class="btn btn--small btn--ghost">Registrar pago</summary>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="charge_id" value="<?= (int) $ch['id'] ?>">
                  <select name="method" aria-label="Medio de pago"><?php foreach (MANUAL_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select>
                  <input type="date" name="paid_date" value="<?= date('Y-m-d') ?>" aria-label="Fecha del pago">
                  <button class="btn btn--small" name="accion" value="pago_manual">Guardar pago</button>
                </form>
              </details>
              <details class="inline-form">
                <summary class="btn btn--small btn--ghost">Editar</summary>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="charge_id" value="<?= (int) $ch['id'] ?>">
                  <input name="description" value="<?= e($ch['description']) ?>" aria-label="Descripción">
                  <input type="date" name="due_date" value="<?= e($ch['due_date']) ?>" aria-label="Fecha de vencimiento">
                  <input name="amount" inputmode="numeric" data-money value="<?= e(money($ch['amount'], false)) ?>" aria-label="Valor">
                  <button class="btn btn--small" name="accion" value="editar_cuota">Guardar</button>
                  <button class="btn btn--small btn--danger" name="accion" value="anular" data-confirm="¿Anular esta cuota?">Anular</button>
                </form>
              </details>
              <?php if ($contract['client_phone']): ?>
                <a class="btn btn--small btn--wa" href="<?= e(client_whatsapp(['phone' => $contract['client_phone']], 'Hola ' . explode(' ', $contract['client_name'])[0] . ', te recordamos que tu cuota "' . $ch['description'] . '" por ' . money($ch['amount']) . ($state === 'vencida' ? ' venció el ' : ' vence el ') . fecha_corta($ch['due_date']) . '. Puedes pagarla en línea aquí: ' . url('mi-cuenta/'))) ?>" target="_blank" rel="noopener">Recordar</a>
              <?php endif; ?>
            <?php elseif ($ch['status'] === 'pagado'): ?>
              <a class="btn btn--small btn--ghost" href="<?= e(url('admin/recibo.php?id=' . $ch['id'])) ?>" target="_blank">Recibo</a>
              <?php if (!$ch['payment_id']): ?>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="charge_id" value="<?= (int) $ch['id'] ?>"><button class="btn btn--small btn--ghost" name="accion" value="reabrir" data-confirm="¿Deshacer este pago registrado a mano?">Deshacer</button></form>
              <?php endif; ?>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <details class="inline-form" style="margin-top:14px">
    <summary class="btn btn--small btn--ghost">+ Agregar cuota</summary>
    <form method="post" class="form__row form__row--4" style="margin-top:12px"><?= csrf_field() ?>
      <div class="field"><label for="n-concept">Concepto</label><select id="n-concept" name="concept"><?php foreach (CHARGE_CONCEPTS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="n-desc">Descripción</label><input id="n-desc" name="description" placeholder="Ej.: Reparación pagada por el arrendador"></div>
      <div class="field"><label for="n-date">Vence</label><input id="n-date" type="date" name="due_date" value="<?= date('Y-m-d') ?>"></div>
      <div class="field"><label for="n-amount">Valor</label><input id="n-amount" name="amount" inputmode="numeric" data-money></div>
      <div><button class="btn btn--small" name="accion" value="agregar_cuota">Agregar</button></div>
    </form>
  </details>
</section>

<form class="admin-card form" method="post">
  <?= csrf_field() ?>
  <h2>Texto del contrato</h2>
  <p class="muted">Puedes corregir el texto aquí. {{plan_pagos}} se reemplaza por la tabla de cuotas al imprimir.</p>
  <input type="hidden" id="body" name="body" value="<?= e($contract['body']) ?>">
  <div class="editor" data-editor="body"></div>
  <div class="owners__actions">
    <button class="btn" name="accion" value="guardar_texto">Guardar texto</button>
    <select name="template_id" aria-label="Plantilla"><?php foreach ($allTemplates as $t): ?><option value="<?= (int) $t['id'] ?>" <?= (int) $contract['template_id'] === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select>
    <button class="btn btn--ghost" name="accion" value="regenerar" data-confirm="Se reemplazará el texto actual por el de la plantilla con los datos actuales. ¿Continuar?">Generar de nuevo</button>
  </div>
</form>

<form class="form admin-form" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="property_id" value="<?= (int) $contract['property_id'] ?>">
  <?php $type = $contract['type']; require APP_DIR . '/views/admin_contract_fields.php'; ?>
  <fieldset class="admin-card">
    <legend>Estado</legend>
    <div class="field" style="max-width:320px"><label for="status">Estado del contrato</label>
      <select id="status" name="status"><?php foreach (CONTRACT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $contract['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    <p class="muted">Cambiar valores o fechas aquí no modifica las cuotas ya creadas: ajústalas en la tabla de cuotas.</p>
  </fieldset>
  <div class="admin-actions">
    <button class="btn" name="accion" value="guardar_datos">Guardar datos</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/contratos.php')) ?>">Volver</a>
    <button class="btn btn--danger" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar este contrato y sus cuotas? No se puede deshacer.">Eliminar</button>
  </div>
</form>
<?php view('admin_footer'); ?>
