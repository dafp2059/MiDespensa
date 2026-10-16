<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('clientes.ver');

$q = input('q');
$params = [];
$where = '1 = 1';
if ($q !== '') {
    $where = '(c.name LIKE ? OR c.document LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
    $params = ["%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"];
}
$clients = query("SELECT c.*,
        (SELECT COUNT(*) FROM contracts ct WHERE ct.client_id = c.id AND ct.status = 'activo') AS active_contracts,
        (SELECT COUNT(*) FROM charges ch JOIN contracts ct ON ct.id = ch.contract_id
         WHERE ct.client_id = c.id AND ch.status = 'pendiente' AND ch.due_date < CURDATE()) AS overdue
    FROM clients c WHERE {$where} ORDER BY c.name", $params)->fetchAll();

$title = 'Clientes';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Clientes</h1>
  <?php if (can('clientes.editar')): ?><a class="btn" href="<?= e(url('admin/cliente.php')) ?>">+ Nuevo cliente</a><?php endif; ?>
</div>
<p class="admin-help">Arrendatarios y compradores. Cada cliente puede entrar a <strong>Mi cuenta</strong> para ver su contrato, pagar sus cuotas y descargar recibos.</p>

<form class="admin-filters" method="get">
  <div class="field"><label for="q">Buscar</label><input id="q" name="q" placeholder="Nombre, cédula, correo o celular" value="<?= e($q) ?>"></div>
  <button class="btn btn--ghost" type="submit">Buscar</button>
</form>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Cliente</th><th>Contacto</th><th>Contratos</th><th>Acceso</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td><strong><?= e($c['name']) ?></strong><br><span class="muted"><?= e($c['document_type'] . ' ' . $c['document']) ?></span></td>
        <td><?= e($c['phone']) ?><br><span class="muted"><?= e($c['email']) ?></span></td>
        <td><?= (int) $c['active_contracts'] ?> activo(s)<?php if ($c['overdue']): ?><br><span class="status status--declined"><?= (int) $c['overdue'] ?> cuota(s) vencida(s)</span><?php endif; ?></td>
        <td><?= !$c['active'] ? '<span class="status">Bloqueado</span>' : ($c['password_hash'] ? '<span class="status status--disponible">Activo</span>' : '<span class="status status--pending">Sin contraseña</span>') ?></td>
        <td><a class="btn btn--small btn--ghost" href="<?= e(url('admin/cliente.php?id=' . $c['id'])) ?>">Ver</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$clients): ?><tr><td colspan="5" class="muted">Aún no hay clientes.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php view('admin_footer'); ?>
