<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = require_login();

$counts = query("SELECT
    SUM(status = 'disponible') AS disponibles,
    SUM(status = 'reservado') AS reservados,
    SUM(status IN ('vendido','arrendado')) AS cerrados,
    SUM(published = 0) AS ocultos
    FROM properties")->fetch();
$noPhotos = (int) query("SELECT COUNT(*) FROM properties p WHERE p.published = 1 AND NOT EXISTS
    (SELECT 1 FROM images i WHERE i.owner_type = 'property' AND i.owner_id = p.id)")->fetchColumn();
$overdue = query("SELECT ch.*, ct.code, ct.id AS contract_id, c.name AS client_name, c.phone AS client_phone
    FROM charges ch JOIN contracts ct ON ct.id = ch.contract_id JOIN clients c ON c.id = ct.client_id
    WHERE ch.status = 'pendiente' AND ch.due_date < CURDATE() AND ct.status = 'activo' ORDER BY ch.due_date LIMIT 10")->fetchAll();
$monthPaid = (int) query("SELECT COALESCE(SUM(amount), 0) FROM charges WHERE status = 'pagado' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$monthDue = (int) query("SELECT COALESCE(SUM(ch.amount), 0) FROM charges ch JOIN contracts ct ON ct.id = ch.contract_id
    WHERE ch.status = 'pendiente' AND ct.status = 'activo' AND ch.due_date <= LAST_DAY(CURDATE())")->fetchColumn();
$messages = query('SELECT m.*, p.title AS property_title FROM messages m LEFT JOIN properties p ON p.id = m.property_id
    ORDER BY m.created_at DESC LIMIT 5')->fetchAll();
$payments = query("SELECT * FROM payments WHERE status <> 'PENDING' OR created_at > (NOW() - INTERVAL 1 DAY)
    ORDER BY created_at DESC LIMIT 5")->fetchAll();

$title = 'Resumen';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Hola, <?= e(explode(' ', $user['name'])[0]) ?></h1>
  <?php if (can('inmuebles.editar')): ?><a class="btn" href="<?= e(url('admin/inmueble.php')) ?>">+ Nuevo inmueble</a><?php endif; ?>
</div>

<div class="stats">
  <div class="stat"><span>Disponibles</span><strong><?= (int) $counts['disponibles'] ?></strong></div>
  <div class="stat"><span>Reservados</span><strong><?= (int) $counts['reservados'] ?></strong></div>
  <div class="stat"><span>Vendidos o arrendados</span><strong><?= (int) $counts['cerrados'] ?></strong></div>
  <div class="stat"><span>Ocultos</span><strong><?= (int) $counts['ocultos'] ?></strong></div>
</div>

<?php if (can('cuotas.pagos')): ?>
<div class="stats">
  <div class="stat"><span>Recaudado este mes</span><strong><?= e(money($monthPaid, false)) ?></strong></div>
  <div class="stat"><span>Por cobrar (hasta fin de mes)</span><strong><?= e(money($monthDue, false)) ?></strong></div>
  <div class="stat"><span>Cuotas vencidas</span><strong<?= $overdue ? ' class="text-danger"' : '' ?>><?= count($overdue) ?><?= count($overdue) === 10 ? '+' : '' ?></strong></div>
</div>

<?php endif; ?>

<?php if ($overdue && can('contratos.ver')): ?>
  <section class="admin-card">
    <h2>Cuotas vencidas</h2>
    <ul class="admin-list">
      <?php foreach ($overdue as $ch): ?>
        <li class="admin-list__row">
          <span><strong><?= e($ch['client_name']) ?></strong> · <?= e($ch['description']) ?><br>
            <span class="muted"><?= e(money($ch['amount'])) ?> · venció el <?= e(fecha_corta($ch['due_date'])) ?> · <a href="<?= e(url('admin/contrato.php?id=' . $ch['contract_id'])) ?>"><?= e($ch['code']) ?></a></span></span>
          <?php if ($ch['client_phone']): ?><a class="btn btn--small btn--wa" href="<?= e(client_whatsapp(['phone' => $ch['client_phone']], 'Hola ' . explode(' ', $ch['client_name'])[0] . ', te recordamos que tu cuota "' . $ch['description'] . '" por ' . money($ch['amount']) . ' venció el ' . fecha_corta($ch['due_date']) . '. Puedes pagarla en línea aquí: ' . url('mi-cuenta/'))) ?>" target="_blank" rel="noopener">Recordar</a><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if ($noPhotos && can('inmuebles.editar')): ?>
  <p class="alert alert--info"><?= $noPhotos ?> inmueble(s) publicados no tienen fotos. Los inmuebles con fotos reciben muchos más contactos. <a href="<?= e(url('admin/inmuebles.php?sin_fotos=1')) ?>">Ver cuáles</a></p>
<?php endif; ?>

<div class="admin-columns">
  <?php if (can('mensajes')): ?>
  <section class="admin-card">
    <h2>Últimos mensajes</h2>
    <?php if ($messages): ?>
      <ul class="admin-list">
        <?php foreach ($messages as $m): ?>
          <li class="<?= $m['read_at'] ? '' : 'is-unread' ?>">
            <strong><?= e($m['name']) ?></strong> · <?= e(CONTACT_REASONS[$m['reason']] ?? $m['reason']) ?>
            <?php if ($m['property_title']): ?><br><span class="muted"><?= e($m['property_title']) ?></span><?php endif; ?>
            <br><span class="muted"><?= e(date('d/m/Y g:i a', strtotime($m['created_at']))) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <a href="<?= e(url('admin/mensajes.php')) ?>">Ver todos los mensajes</a>
    <?php else: ?><p class="muted">Aún no hay mensajes.</p><?php endif; ?>
  </section>
  <?php endif; ?>
  <?php if (can('pagos')): ?>
  <section class="admin-card">
    <h2>Últimos pagos</h2>
    <?php if ($payments): ?>
      <ul class="admin-list">
        <?php foreach ($payments as $pay): ?>
          <li><strong><?= e(money($pay['amount_in_cents'] / 100)) ?></strong> · <?= e($pay['payer_name']) ?>
            <span class="status status--<?= e(strtolower($pay['status'])) ?>"><?= e(PAYMENT_STATUSES[$pay['status']] ?? $pay['status']) ?></span>
            <br><span class="muted"><?= e(PAYMENT_CONCEPTS[$pay['concept']] ?? $pay['concept']) ?> · <?= e(date('d/m/Y', strtotime($pay['created_at']))) ?></span></li>
        <?php endforeach; ?>
      </ul>
      <a href="<?= e(url('admin/pagos.php')) ?>">Ver todos los pagos</a>
    <?php else: ?><p class="muted">Aún no hay pagos.</p><?php endif; ?>
  </section>
  <?php endif; ?>
</div>
<?php view('admin_footer'); ?>
