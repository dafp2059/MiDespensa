<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

$projects = query("SELECT pr.*, (SELECT i.filename FROM images i WHERE i.owner_type = 'project' AND i.owner_id = pr.id
    ORDER BY i.sort_order, i.id LIMIT 1) AS cover FROM projects pr ORDER BY pr.sort_order, pr.created_at DESC")->fetchAll();

$title = 'Proyectos';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Proyectos</h1>
  <a class="btn" href="<?= e(url('admin/proyecto.php')) ?>">+ Nuevo proyecto</a>
</div>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Foto</th><th>Proyecto</th><th>Desde</th><th>Web</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($projects as $pr): ?>
      <tr>
        <td><img class="table__thumb" src="<?= e(upload_url($pr['cover'])) ?>" alt=""></td>
        <td><strong><?= e($pr['name']) ?></strong><br><span class="muted"><?= e(trim($pr['location_text'] . ' · ' . $pr['status'], ' ·')) ?></span></td>
        <td><?= $pr['price_from'] ? e(money($pr['price_from'], false)) : '' ?></td>
        <td><?= $pr['published'] ? '<a href="' . e(project_url($pr)) . '" target="_blank">Ver</a>' : '<span class="muted">Oculto</span>' ?></td>
        <td><a class="btn btn--small btn--ghost" href="<?= e(url('admin/proyecto.php?id=' . $pr['id'])) ?>">Editar</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$projects): ?><tr><td colspan="5" class="muted">Aún no hay proyectos.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php view('admin_footer'); ?>
