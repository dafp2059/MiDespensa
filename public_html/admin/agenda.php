<?php
require __DIR__ . '/../../app/bootstrap.php';
$user = require_can('agenda');

if (is_post()) {
    csrf_check();
    $status = input('status');
    if (isset(VISIT_STATUSES[$status])) {
        query('UPDATE visits SET status = ? WHERE id = ?', [$status, (int) input('id')]);
        flash('success', 'Visita marcada como ' . mb_strtolower(VISIT_STATUSES[$status]) . '.');
    }
    redirect('admin/agenda.php' . query_with([]));
}

// Un asesor ve por defecto solo sus visitas.
$agent = isset($_GET['asesor']) ? (int) input('asesor') : ($user['role'] === 'asesor' ? (int) $user['id'] : 0);
$past = input('pasadas') === '1';
$where = [$past ? 'v.scheduled_at < CURDATE()' : 'v.scheduled_at >= CURDATE()'];
$params = [];
if ($agent) { $where[] = 'v.agent_user_id = ?'; $params[] = $agent; }
if (!$past && input('todas') !== '1') { $where[] = "v.status = 'programada'"; }
$visits = query('SELECT v.*, p.title AS property_title, p.code AS property_code, u.name AS agent_name, u.phone AS agent_phone
    FROM visits v LEFT JOIN properties p ON p.id = v.property_id LEFT JOIN users u ON u.id = v.agent_user_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY v.scheduled_at ' . ($past ? 'DESC LIMIT 100' : 'ASC LIMIT 300'), $params)->fetchAll();
$byDay = [];
foreach ($visits as $v) {
    $byDay[substr($v['scheduled_at'], 0, 10)][] = $v;
}

$title = 'Agenda';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Agenda de visitas</h1>
  <a class="btn" href="<?= e(url('admin/visita.php')) ?>">+ Agendar visita</a>
</div>
<form class="admin-filters" method="get">
  <div class="field"><label for="asesor">Asesor</label>
    <select id="asesor" name="asesor" data-autosubmit><option value="0">Todos</option><?php foreach (staff_users() as $u): ?><option value="<?= (int) $u['id'] ?>" <?= $agent === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?><?= (int) $u['id'] === (int) $user['id'] ? ' (yo)' : '' ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="ver">Mostrar</label>
    <select id="ver" name="pasadas" data-autosubmit><option value="0">Próximas</option><option value="1" <?= $past ? 'selected' : '' ?>>Pasadas</option></select></div>
  <?php if (!$past): ?><label class="check"><input type="checkbox" name="todas" value="1" <?= input('todas') === '1' ? 'checked' : '' ?> data-autosubmit> <span>Incluir canceladas y realizadas</span></label><?php endif; ?>
  <noscript><button class="btn btn--ghost" type="submit">Filtrar</button></noscript>
</form>

<?php if (!$byDay): ?><p class="empty"><?= $past ? 'No hay visitas pasadas con esos filtros.' : 'No hay visitas programadas. Agéndalas desde aquí o desde un mensaje de contacto.' ?></p><?php endif; ?>
<?php foreach ($byDay as $day => $items): ?>
  <section class="agenda-day">
    <h2 class="agenda-day__title"><?= $day === date('Y-m-d') ? 'Hoy, ' . e(dia_largo($day)) : ($day === date('Y-m-d', strtotime('+1 day')) ? 'Mañana, ' . e(dia_largo($day)) : e(ucfirst(dia_largo($day)))) ?></h2>
    <?php foreach ($items as $v): ?>
      <article class="admin-card visit visit--<?= e($v['status']) ?>">
        <div class="visit__time"><?= e(hora($v['scheduled_at'])) ?></div>
        <div class="visit__body">
          <strong><?= e($v['client_name']) ?></strong>
          <span><?= $v['property_title'] ? e(($v['property_code'] ? $v['property_code'] . ' · ' : '') . $v['property_title']) : 'Sin inmueble' ?></span>
          <span class="muted"><?= e($v['agent_name'] ?? 'Sin asesor') ?><?= $v['status'] !== 'programada' ? ' · ' . e(VISIT_STATUSES[$v['status']]) : '' ?></span>
        </div>
        <div class="visit__actions">
          <?php if ($v['client_phone']): ?><a class="btn btn--small btn--wa" href="https://wa.me/<?= e(phone_whatsapp($v['client_phone'])) ?>" target="_blank" rel="noopener" aria-label="WhatsApp a <?= e($v['client_name']) ?>"><?= icon('chat') ?></a><?php endif; ?>
          <a class="btn btn--small btn--ghost" href="<?= e(url('admin/visita.php?id=' . $v['id'])) ?>">Abrir</a>
          <?php if ($v['status'] === 'programada'): ?>
            <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
              <button class="btn btn--small btn--ghost" name="status" value="realizada">Realizada</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endforeach; ?>
<?php view('admin_footer'); ?>
