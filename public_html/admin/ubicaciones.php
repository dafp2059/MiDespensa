<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('zonas');

if (is_post()) {
    csrf_check();
    $action = input('accion');
    $id = (int) input('id');
    if ($action === 'crear' && input('name') !== '') {
        query('INSERT INTO locations (name, slug, featured, sort_order) VALUES (?, ?, 1, 99)', [input('name'), unique_slug('locations', input('name'))]);
        flash('success', 'Zona agregada.');
    } elseif ($action === 'guardar' && $id) {
        query('UPDATE locations SET name = ?, featured = ?, sort_order = ? WHERE id = ?', [input('name'), input('featured') === '1' ? 1 : 0, (int) input('sort_order'), $id]);
        $uploaded = save_uploaded_images('photos');
        if ($uploaded) {
            $old = query('SELECT image FROM locations WHERE id = ?', [$id])->fetchColumn();
            if ($old) delete_image_file($old);
            query('UPDATE locations SET image = ? WHERE id = ?', [$uploaded[0], $id]);
        }
        flash('success', 'Zona guardada.');
    } elseif ($action === 'eliminar' && $id && can('eliminar')) {
        $old = query('SELECT image FROM locations WHERE id = ?', [$id])->fetchColumn();
        if ($old) delete_image_file($old);
        query('DELETE FROM locations WHERE id = ?', [$id]);
        flash('success', 'Zona eliminada. Sus inmuebles quedaron sin zona.');
    }
    redirect('admin/ubicaciones.php');
}

$rows = query('SELECT l.*, COUNT(p.id) AS total FROM locations l LEFT JOIN properties p ON p.location_id = l.id GROUP BY l.id ORDER BY l.sort_order, l.name')->fetchAll();
$title = 'Zonas';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1>Zonas</h1></div>
<p class="muted">Las zonas marcadas como "En el inicio" aparecen en la sección "Dónde trabajamos". Sube una foto representativa de cada zona.</p>

<?php foreach ($rows as $z): ?>
  <form class="admin-card admin-zone" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $z['id'] ?>">
    <img class="table__thumb" src="<?= e(upload_url($z['image'])) ?>" alt="">
    <div class="field"><label for="n<?= $z['id'] ?>">Nombre</label><input id="n<?= $z['id'] ?>" name="name" required value="<?= e($z['name']) ?>"></div>
    <div class="field field--small"><label for="o<?= $z['id'] ?>">Orden</label><input id="o<?= $z['id'] ?>" name="sort_order" type="number" value="<?= (int) $z['sort_order'] ?>"></div>
    <div class="field"><label for="f<?= $z['id'] ?>">Foto</label><input id="f<?= $z['id'] ?>" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp"></div>
    <label class="check"><input type="checkbox" name="featured" value="1" <?= $z['featured'] ? 'checked' : '' ?>> <span>En el inicio</span></label>
    <span class="muted"><?= (int) $z['total'] ?> inmuebles</span>
    <button class="btn btn--small" type="submit" name="accion" value="guardar">Guardar</button>
    <?php if (can('eliminar')): ?><button class="btn btn--small btn--danger" type="submit" name="accion" value="eliminar" formnovalidate data-confirm="¿Eliminar la zona <?= e($z['name']) ?>?">Eliminar</button><?php endif; ?>
  </form>
<?php endforeach; ?>

<form class="admin-card admin-zone" method="post">
  <?= csrf_field() ?>
  <div class="field"><label for="new-zone">Nueva zona</label><input id="new-zone" name="name" required placeholder="Ej.: Sabaneta"></div>
  <button class="btn btn--small" type="submit" name="accion" value="crear">Agregar</button>
</form>
<?php view('admin_footer'); ?>
