<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('contratos.ver');

if (is_post() && input('accion') === 'ipc') {
    csrf_check();
    if (can('reajustes')) {
        $table = [];
        foreach ((array) ($_POST['ipc_year'] ?? []) as $i => $year) {
            $value = str_replace(',', '.', trim((string) ($_POST['ipc_value'][$i] ?? '')));
            if (preg_match('/^\d{4}$/', trim((string) $year)) && is_numeric($value)) {
                $table[trim((string) $year)] = round((float) $value, 2);
            }
        }
        save_setting('ipc', json_encode($table));
        flash('success', 'Tabla de IPC guardada.');
    }
    redirect('admin/contratos.php#ipc');
}

// Reajustes que se cumplen en los próximos 45 días (o ya vencidos)
$dueAdjustments = [];
foreach (query("SELECT id FROM contracts WHERE type = 'arriendo' AND status = 'activo'")->fetchAll(PDO::FETCH_COLUMN) as $cid) {
    $ct = contract_full((int) $cid);
    $next = next_adjustment($ct);
    if ($next && $next['days'] <= 45) {
        $dueAdjustments[] = ['contract' => $ct, 'next' => $next];
    }
}

$where = ['1 = 1'];
$params = [];
if (isset(CONTRACT_TYPES[input('tipo')])) { $where[] = 'ct.type = ?'; $params[] = input('tipo'); }
if (isset(CONTRACT_STATUSES[input('estado')])) { $where[] = 'ct.status = ?'; $params[] = input('estado'); }
if (($q = input('q')) !== '') {
    $where[] = '(ct.code LIKE ? OR c.name LIKE ? OR c.document LIKE ? OR p.title LIKE ?)';
    array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
}
$contracts = query('SELECT ct.*, c.name AS client_name, p.title AS property_title,
        (SELECT COUNT(*) FROM charges ch WHERE ch.contract_id = ct.id AND ch.status = \'pendiente\' AND ch.due_date < CURDATE()) AS overdue,
        (SELECT MIN(ch.due_date) FROM charges ch WHERE ch.contract_id = ct.id AND ch.status = \'pendiente\') AS next_due
    FROM contracts ct JOIN clients c ON c.id = ct.client_id LEFT JOIN properties p ON p.id = ct.property_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY overdue DESC, ct.created_at DESC', $params)->fetchAll();

$title = 'Contratos';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Contratos</h1>
  <div class="owners__actions">
    <?php if (can('diseno')): ?><a class="btn btn--ghost" href="<?= e(url('admin/plantillas.php')) ?>">Plantillas</a><?php endif; ?>
    <?php if (can('contratos.editar')): ?><a class="btn" href="<?= e(url('admin/contrato.php')) ?>">+ Nuevo contrato</a><?php endif; ?>
  </div>
</div>
<form class="admin-filters" method="get">
  <div class="field"><label for="q">Buscar</label><input id="q" name="q" placeholder="Código, cliente, cédula o inmueble" value="<?= e($q) ?>"></div>
  <div class="field"><label for="tipo">Tipo</label><select id="tipo" name="tipo"><option value="">Todos</option><?php foreach (CONTRACT_TYPES as $k => $l): ?><option value="<?= $k ?>" <?= input('tipo') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach (CONTRACT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= input('estado') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--ghost" type="submit">Filtrar</button>
</form>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Contrato</th><th>Cliente</th><th>Valor</th><th>Cuotas</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($contracts as $ct): ?>
      <tr>
        <td><strong><?= e($ct['code']) ?></strong> <span class="muted"><?= e(CONTRACT_TYPES[$ct['type']]) ?></span><br><span class="muted"><?= e($ct['property_title'] ?? 'Sin inmueble') ?> · <?= e(CONTRACT_STATUSES[$ct['status']]) ?></span></td>
        <td><?= e($ct['client_name']) ?></td>
        <td><?= e(money($ct['amount'], false)) ?><?= $ct['type'] === 'arriendo' ? ' <span class="muted">/ mes</span>' : '' ?></td>
        <td><?php if ($ct['overdue']): ?><span class="status status--declined"><?= (int) $ct['overdue'] ?> vencida(s)</span><?php elseif ($ct['next_due']): ?><span class="muted">Próxima: <?= e(fecha_corta($ct['next_due'])) ?></span><?php else: ?><span class="status status--approved">Al día</span><?php endif; ?></td>
        <td><a class="btn btn--small btn--ghost" href="<?= e(url('admin/contrato.php?id=' . $ct['id'])) ?>">Abrir</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$contracts): ?><tr><td colspan="5" class="muted">Aún no hay contratos.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($dueAdjustments): ?>
  <section class="admin-card">
    <h2>Reajustes por IPC próximos</h2>
    <ul class="admin-list">
      <?php foreach ($dueAdjustments as $d): ?>
        <li class="admin-list__row"><span><strong><?= e($d['contract']['code']) ?></strong> · <?= e($d['contract']['client_name']) ?><br>
          <span class="muted"><?= e(fecha_larga($d['next']['date'])) ?> · canon <?= e(money($d['contract']['amount'])) ?><?= $d['next']['max_amount'] ? ' → máximo ' . e(money($d['next']['max_amount'])) : ' · falta IPC ' . $d['next']['ipc_year'] ?></span></span>
          <a class="btn btn--small btn--ghost" href="<?= e(url('admin/contrato.php?id=' . $d['contract']['id'] . '#reajuste')) ?>">Reajustar</a></li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if (can('reajustes')): ?>
  <form class="admin-card form" method="post" id="ipc" data-repeater>
    <?= csrf_field() ?><input type="hidden" name="accion" value="ipc">
    <h2>Tabla de IPC</h2>
    <p class="muted">Variación anual del IPC a diciembre que publica el DANE (dane.gov.co). Con ella se calcula el tope de los reajustes. Revisa los valores y agrega cada año nuevo en enero.</p>
    <div data-rows>
      <?php $ipcRow = function (string $year = '', string $value = ''): void { ?>
        <div class="repeater__row">
          <label class="field field--small"><span class="field__label">Año</span><input name="ipc_year[]" inputmode="numeric" maxlength="4" value="<?= e($year) ?>"></label>
          <label class="field field--small"><span class="field__label">IPC (%)</span><input name="ipc_value[]" inputmode="decimal" value="<?= e($value) ?>"></label>
          <div class="repeater__tools"><button class="icon-btn icon-btn--danger" type="button" data-remove aria-label="Quitar">✕</button></div>
        </div>
      <?php }; foreach (ipc_table() as $year => $value) $ipcRow((string) $year, str_replace('.', ',', (string) $value)); ?>
    </div>
    <template><?php $ipcRow(); ?></template>
    <div class="owners__actions"><button class="btn btn--small btn--ghost" type="button" data-add>+ Agregar año</button><button class="btn btn--small" type="submit">Guardar IPC</button></div>
  </form>
<?php endif; ?>
<?php view('admin_footer'); ?>
