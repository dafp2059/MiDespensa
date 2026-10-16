<?php
require __DIR__ . '/../../app/bootstrap.php';
$client = require_client();

$contracts = query("SELECT ct.*, p.title AS property_title, p.slug AS property_slug,
        (SELECT i.filename FROM images i WHERE i.owner_type = 'property' AND i.owner_id = p.id ORDER BY i.sort_order, i.id LIMIT 1) AS cover
    FROM contracts ct LEFT JOIN properties p ON p.id = ct.property_id
    WHERE ct.client_id = ? AND ct.status <> 'cancelado' ORDER BY ct.status = 'activo' DESC, ct.created_at DESC", [$client['id']])->fetchAll();
$charges = query("SELECT ch.*, ct.code FROM charges ch JOIN contracts ct ON ct.id = ch.contract_id
    WHERE ct.client_id = ? AND ch.status <> 'anulado' AND ct.status <> 'cancelado' ORDER BY ch.due_date, ch.id", [$client['id']])->fetchAll();

$pending = array_values(array_filter($charges, fn($c) => $c['status'] === 'pendiente'));
$paid = array_reverse(array_values(array_filter($charges, fn($c) => $c['status'] === 'pagado')));
// Se muestran las vencidas y las que vencen en los próximos 45 días; el resto, en "Próximas cuotas".
$soon = array_values(array_filter($pending, fn($c) => $c['due_date'] <= date('Y-m-d', strtotime('+45 days'))));
$later = array_values(array_filter($pending, fn($c) => $c['due_date'] > date('Y-m-d', strtotime('+45 days'))));

$page = ['title' => 'Mi cuenta', 'noindex' => true];
view('header', compact('page'));
?>
<section class="page-head">
  <div class="container account-head">
    <div>
      <p class="eyebrow">Mi cuenta</p>
      <h1 class="page-head__title">Hola, <?= e(explode(' ', $client['name'])[0]) ?></h1>
    </div>
    <div class="owners__actions">
      <a class="btn btn--ghost btn--small" href="<?= e(url('mi-cuenta/clave.php')) ?>">Cambiar contraseña</a>
      <form method="post" action="<?= e(url('mi-cuenta/salir.php')) ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--small" type="submit">Salir</button></form>
    </div>
  </div>
</section>

<section class="section section--tight">
  <div class="container account">
    <div>
      <h2 class="detail__subtitle">Cuotas por pagar</h2>
      <?php if ($soon): ?>
        <ul class="charges">
          <?php foreach ($soon as $ch): $state = charge_state($ch); ?>
            <li class="charge charge--<?= e($state) ?>">
              <div>
                <strong><?= e($ch['description']) ?></strong>
                <span><?= $state === 'vencida' ? 'Venció el ' : 'Vence el ' ?><?= e(fecha_larga($ch['due_date'])) ?> · <?= e($ch['code']) ?></span>
              </div>
              <div class="charge__pay">
                <strong class="charge__amount"><?= e(money($ch['amount'])) ?></strong>
                <form method="post" action="<?= e(url('mi-cuenta/pagar.php')) ?>"><?= csrf_field() ?><input type="hidden" name="charge_id" value="<?= (int) $ch['id'] ?>"><button class="btn" type="submit"><?= icon('card') ?> Pagar</button></form>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="empty"><?= icon('check') ?> Estás al día. No tienes cuotas pendientes en este momento.</p>
      <?php endif; ?>

      <?php if ($later): ?>
        <details class="account-more">
          <summary>Próximas cuotas (<?= count($later) ?>)</summary>
          <ul class="charges charges--compact">
            <?php foreach ($later as $ch): ?>
              <li class="charge"><div><strong><?= e($ch['description']) ?></strong><span>Vence el <?= e(fecha_larga($ch['due_date'])) ?></span></div><strong class="charge__amount"><?= e(money($ch['amount'])) ?></strong></li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>

      <h2 class="detail__subtitle">Pagos realizados</h2>
      <?php if ($paid): ?>
        <ul class="charges charges--compact">
          <?php foreach ($paid as $ch): ?>
            <li class="charge charge--pagado">
              <div><strong><?= e($ch['description']) ?></strong><span>Pagado el <?= e(fecha_larga($ch['paid_at'])) ?> · <?= e($ch['paid_method']) ?></span></div>
              <div class="charge__pay"><strong class="charge__amount"><?= e(money($ch['amount'])) ?></strong><a class="btn btn--ghost btn--small" href="<?= e(url('mi-cuenta/recibo.php?id=' . $ch['id'])) ?>" target="_blank">Recibo</a></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="muted">Aquí aparecerán tus pagos y sus recibos.</p>
      <?php endif; ?>
    </div>

    <aside>
      <h2 class="detail__subtitle">Mis contratos</h2>
      <?php foreach ($contracts as $ct): ?>
        <div class="panel contract-card">
          <?php if ($ct['cover']): ?><img src="<?= e(upload_url($ct['cover'])) ?>" alt=""><?php endif; ?>
          <p class="eyebrow"><?= e(CONTRACT_TYPES[$ct['type']]) ?> · <?= e($ct['code']) ?></p>
          <strong><?= e($ct['property_title'] ?? '') ?></strong>
          <p class="muted"><?= $ct['type'] === 'arriendo' ? 'Canon ' . e(money($ct['amount'] + $ct['admin_fee'])) . ' al mes · del ' . e(fecha_corta($ct['start_date'])) . ' al ' . e(fecha_corta($ct['end_date'])) : 'Precio ' . e(money($ct['amount'])) ?></p>
          <a class="btn btn--ghost btn--small" href="<?= e(url('mi-cuenta/contrato.php?id=' . $ct['id'])) ?>" target="_blank">Ver contrato</a>
        </div>
      <?php endforeach; ?>
      <?php if (!$contracts): ?><p class="muted">Aún no tienes contratos registrados.</p><?php endif; ?>
      <div class="panel">
        <p>¿Dudas con tus pagos o tu contrato?</p>
        <a class="btn btn--wa btn--block" href="<?= e(whatsapp_link('Hola, soy ' . $client['name'] . ' y tengo una pregunta sobre mi contrato.')) ?>" target="_blank" rel="noopener"><?= icon('chat') ?> Escríbenos</a>
      </div>
    </aside>
  </div>
</section>
<?php view('footer'); ?>
