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
$messages = query('SELECT m.*, p.title AS property_title FROM messages m LEFT JOIN properties p ON p.id = m.property_id
    ORDER BY m.created_at DESC LIMIT 5')->fetchAll();
$payments = query("SELECT * FROM payments WHERE status <> 'PENDING' OR created_at > (NOW() - INTERVAL 1 DAY)
    ORDER BY created_at DESC LIMIT 5")->fetchAll();

$title = 'Resumen';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Hola, <?= e(explode(' ', $user['name'])[0]) ?></h1>
  <a class="btn" href="<?= e(url('admin/inmueble.php')) ?>">+ Nuevo inmueble</a>
</div>

<div class="stats">
  <div class="stat"><span>Disponibles</span><strong><?= (int) $counts['disponibles'] ?></strong></div>
  <div class="stat"><span>Reservados</span><strong><?= (int) $counts['reservados'] ?></strong></div>
  <div class="stat"><span>Vendidos o arrendados</span><strong><?= (int) $counts['cerrados'] ?></strong></div>
  <div class="stat"><span>Ocultos</span><strong><?= (int) $counts['ocultos'] ?></strong></div>
</div>

<?php if ($noPhotos): ?>
  <p class="alert alert--info"><?= $noPhotos ?> inmueble(s) publicados no tienen fotos. Los inmuebles con fotos reciben muchos más contactos. <a href="<?= e(url('admin/inmuebles.php?sin_fotos=1')) ?>">Ver cuáles</a></p>
<?php endif; ?>

<div class="admin-columns">
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
</div>
<?php view('admin_footer'); ?>
