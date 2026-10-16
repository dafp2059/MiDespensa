<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('diseno');

$templates = query('SELECT t.*, (SELECT COUNT(*) FROM contracts c WHERE c.template_id = t.id) AS used FROM contract_templates t ORDER BY t.type, t.name')->fetchAll();
$title = 'Plantillas de contrato';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Plantillas de contrato</h1>
  <a class="btn" href="<?= e(url('admin/plantilla.php')) ?>">+ Nueva plantilla</a>
</div>
<p class="admin-help">Las plantillas son modelos de referencia. <strong>Haz que tu abogado las revise</strong> y ajústalas a tu forma de trabajar antes de usarlas con clientes.</p>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Plantilla</th><th>Tipo</th><th>Usada en</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($templates as $t): ?>
      <tr>
        <td><strong><?= e($t['name']) ?></strong></td>
        <td><?= e(CONTRACT_TYPES[$t['type']]) ?></td>
        <td><?= (int) $t['used'] ?> contrato(s)</td>
        <td><a class="btn btn--small btn--ghost" href="<?= e(url('admin/plantilla.php?id=' . $t['id'])) ?>">Editar</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p><a href="<?= e(url('admin/contratos.php')) ?>">← Volver a contratos</a></p>
<?php view('admin_footer'); ?>
