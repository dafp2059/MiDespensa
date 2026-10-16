<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('diseno');

$pages = query('SELECT * FROM pages ORDER BY locked DESC, title')->fetchAll();
$title = 'Páginas';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Páginas</h1>
  <a class="btn" href="<?= e(url('admin/pagina.php')) ?>">+ Nueva página</a>
</div>
<p class="admin-help">Aquí editas Nosotros, Contacto, Vende o arrienda, la política de datos y cualquier página nueva. Para que una página nueva aparezca en el menú, agrégala en <a href="<?= e(url('admin/menus.php')) ?>">Menús</a>.</p>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Página</th><th>Dirección</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead>
    <tbody>
    <?php foreach ($pages as $pg): ?>
      <tr>
        <td><strong><?= e(tokens($pg['title'])) ?></strong></td>
        <td><a href="<?= e(url($pg['slug'])) ?>" target="_blank">/<?= e($pg['slug']) ?></a></td>
        <td><?= $pg['published'] ? '<span class="status status--disponible">Publicada</span>' : '<span class="status">Oculta</span>' ?></td>
        <td><a class="btn btn--small btn--ghost" href="<?= e(url('admin/pagina.php?id=' . $pg['id'])) ?>">Editar</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php view('admin_footer'); ?>
