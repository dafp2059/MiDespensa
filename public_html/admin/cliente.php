<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$id = (int) input('id');
$client = $id ? query('SELECT * FROM clients WHERE id = ?', [$id])->fetch() : null;
if ($id && !$client) {
    redirect('admin/clientes.php');
}
$errors = [];

if (is_post()) {
    csrf_check();
    $action = input('accion');

    if (($action === 'enlace' || $action === 'enlace_correo') && $client) {
        $_SESSION['access_link'] = client_access_link($id);
        if ($action === 'enlace_correo') {
            $sent = send_mail($client['email'], 'Tu acceso a ' . site('name'),
                "Hola {$client['name']},\n\nCrea tu contraseña para entrar a Mi cuenta, donde puedes ver tu contrato, pagar tus cuotas y descargar tus recibos:\n\n"
                . $_SESSION['access_link'] . "\n\nEl enlace vence en 7 días.\n\n" . site('name') . "\n" . phone_display(site('phone')));
            flash($sent ? 'success' : 'error', $sent ? 'Enlace enviado a ' . $client['email'] . '.' : 'No se pudo enviar el correo. Compártelo por WhatsApp.');
        }
        redirect('admin/cliente.php?id=' . $id);
    }
    if ($action === 'estado' && $client) {
        query('UPDATE clients SET active = 1 - active WHERE id = ?', [$id]);
        flash('success', $client['active'] ? 'Acceso bloqueado.' : 'Acceso activado.');
        redirect('admin/cliente.php?id=' . $id);
    }

    $data = [
        'name' => mb_substr(input('name'), 0, 150),
        'document_type' => isset(DOCUMENT_TYPES[input('document_type')]) ? input('document_type') : 'CC',
        'document' => mb_substr(preg_replace('/[^\w.-]/', '', input('document')), 0, 30),
        'email' => mb_strtolower(input('email')),
        'phone' => mb_substr(input('phone'), 0, 40) ?: null,
        'address' => mb_substr(input('address'), 0, 190) ?: null,
        'notes' => input('notes') ?: null,
    ];
    if ($data['name'] === '') $errors[] = 'Escribe el nombre.';
    if ($data['document'] === '') $errors[] = 'Escribe el número de documento.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo no es válido (lo usa para entrar a Mi cuenta).';
    elseif (query('SELECT id FROM clients WHERE email = ? AND id <> ?', [$data['email'], $id])->fetch()) $errors[] = 'Ya existe un cliente con ese correo.';

    if (!$errors) {
        $columns = array_keys($data);
        if ($client) {
            query('UPDATE clients SET ' . implode(', ', array_map(fn($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $id]);
        } else {
            query('INSERT INTO clients (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
            $id = (int) db()->lastInsertId();
        }
        flash('success', 'Cliente guardado.');
        $back = input('volver');
        redirect($back === 'contrato' ? 'admin/contrato.php?cliente=' . $id . '&tipo=' . urlencode(input('tipo')) : 'admin/cliente.php?id=' . $id);
    }
    $client = array_merge($client ?? [], $data);
}

$link = $_SESSION['access_link'] ?? null;
unset($_SESSION['access_link']);
$contracts = $id ? query('SELECT ct.*, p.title AS property_title FROM contracts ct LEFT JOIN properties p ON p.id = ct.property_id
    WHERE ct.client_id = ? ORDER BY ct.created_at DESC', [$id])->fetchAll() : [];
$v = fn(string $k) => e($client[$k] ?? '');
$title = $id ? $client['name'] : 'Nuevo cliente';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1><?= e($title) ?></h1>
  <?php if ($id): ?><a class="btn" href="<?= e(url('admin/contrato.php?cliente=' . $id)) ?>">+ Nuevo contrato</a><?php endif; ?>
</div>
<?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>

<?php if ($id): ?>
  <section class="admin-card">
    <h2>Acceso a "Mi cuenta"</h2>
    <?php if ($link): ?>
      <p>Enlace para que <?= e($client['name']) ?> cree su contraseña (vale 7 días):</p>
      <p><input class="copy-field" readonly value="<?= e($link) ?>" aria-label="Enlace de acceso" onclick="this.select()"></p>
      <p class="owners__actions">
        <?php if ($client['phone']): ?><a class="btn btn--wa" href="<?= e(client_whatsapp($client, 'Hola ' . explode(' ', $client['name'])[0] . ', te escribimos de ' . site('name') . '. Con este enlace creas tu contraseña para ver tu contrato y pagar tus cuotas en línea: ' . $link)) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Enviar por WhatsApp</a><?php endif; ?>
      </p>
    <?php else: ?>
      <p class="muted"><?= $client['password_hash'] ? 'El cliente ya tiene contraseña' . ($client['last_login_at'] ? ' (último ingreso: ' . e(fecha_corta($client['last_login_at'])) . ')' : '') . '. Si la olvidó, genera un enlace nuevo.' : 'El cliente aún no tiene contraseña. Genera el enlace y envíaselo.' ?></p>
    <?php endif; ?>
    <form method="post" class="owners__actions">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn btn--ghost" name="accion" value="enlace">Generar enlace de acceso</button>
      <button class="btn btn--ghost" name="accion" value="enlace_correo">Generar y enviar por correo</button>
      <button class="btn btn--danger" name="accion" value="estado"><?= $client['active'] ? 'Bloquear acceso' : 'Activar acceso' ?></button>
    </form>
  </section>
<?php endif; ?>

<form class="form admin-form" method="post">
  <?= csrf_field() ?>
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
  <input type="hidden" name="volver" value="<?= e(input('volver')) ?>"><input type="hidden" name="tipo" value="<?= e(input('tipo')) ?>">
  <fieldset class="admin-card">
    <legend>Datos del cliente</legend>
    <div class="form__row">
      <div class="field"><label for="name">Nombre completo *</label><input id="name" name="name" required value="<?= $v('name') ?>"></div>
      <div class="field"><label for="document_type">Tipo de documento</label>
        <select id="document_type" name="document_type"><?php foreach (DOCUMENT_TYPES as $k => $l): ?><option value="<?= $k ?>" <?= ($client['document_type'] ?? 'CC') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="document">Número de documento *</label><input id="document" name="document" required value="<?= $v('document') ?>"></div>
    </div>
    <div class="form__row">
      <div class="field"><label for="email">Correo * (para entrar a Mi cuenta)</label><input id="email" name="email" type="email" required value="<?= $v('email') ?>"></div>
      <div class="field"><label for="phone">Celular</label><input id="phone" name="phone" type="tel" value="<?= $v('phone') ?>"></div>
      <div class="field"><label for="address">Dirección de notificaciones</label><input id="address" name="address" value="<?= $v('address') ?>"></div>
    </div>
    <div class="field"><label for="notes">Notas internas (el cliente no las ve)</label><textarea id="notes" name="notes" rows="2"><?= $v('notes') ?></textarea></div>
  </fieldset>
  <div class="admin-actions">
    <button class="btn" type="submit">Guardar cliente</button>
    <a class="btn btn--ghost" href="<?= e(url('admin/clientes.php')) ?>">Volver</a>
  </div>
</form>

<?php if ($contracts): ?>
  <section class="admin-card">
    <h2>Contratos</h2>
    <ul class="admin-list">
      <?php foreach ($contracts as $ct): ?>
        <li class="admin-list__row">
          <span><strong><?= e($ct['code']) ?></strong> · <?= e(CONTRACT_TYPES[$ct['type']]) ?> · <?= e($ct['property_title'] ?? 'Sin inmueble') ?><br>
            <span class="muted"><?= e(CONTRACT_STATUSES[$ct['status']]) ?> · <?= e(money($ct['amount'])) ?></span></span>
          <a class="btn btn--small btn--ghost" href="<?= e(url('admin/contrato.php?id=' . $ct['id'])) ?>">Abrir</a>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
<?php view('admin_footer'); ?>
