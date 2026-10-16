<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

// Crea la tabla si la base de datos se instaló antes de existir esta sección.
db()->exec('CREATE TABLE IF NOT EXISTS settings (name VARCHAR(60) NOT NULL PRIMARY KEY, value TEXT NULL)
    ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

$fields = [
    'name' => ['Nombre comercial', 'text', ''],
    'legal_name' => ['Razón social (como aparece en el RUT)', 'text', ''],
    'nit' => ['NIT', 'text', 'Aparece en la política de datos.'],
    'tagline' => ['Frase de la empresa', 'text', 'Se muestra en el pie de página.'],
    'phone' => ['Teléfono', 'tel', ''],
    'whatsapp' => ['WhatsApp', 'tel', 'Con indicativo de país, sin + ni espacios. Ej.: 573042579430'],
    'email' => ['Correo de contacto', 'email', ''],
    'address' => ['Dirección', 'text', ''],
    'city' => ['Ciudad', 'text', 'Ej.: Medellín, Antioquia'],
    'hours' => ['Horario de atención', 'text', ''],
    'instagram' => ['Instagram (enlace completo)', 'url', ''],
    'facebook' => ['Facebook (enlace completo)', 'url', 'Déjalo vacío si no tienen.'],
];
$logos = [
    'logo' => ['Logo principal', 'Se usa en la parte superior de la web. Ideal: PNG con fondo transparente, unos 400 px de ancho.'],
    'logo_dark' => ['Logo para fondo oscuro (opcional)', 'Versión clara o blanca del logo para el pie de página y el panel. Si no la subes, se muestra el principal sobre un recuadro blanco.'],
    'favicon' => ['Ícono de la pestaña (opcional)', 'Imagen cuadrada, por ejemplo 512 × 512 px.'],
];

if (is_post()) {
    csrf_check();
    foreach ($fields as $key => $_) {
        save_setting('site.' . $key, mb_substr(input($key), 0, 300));
    }
    $current = settings(true);
    $errors = [];
    foreach ($logos as $key => [$label]) {
        $old = $current[$key] ?? '';
        if (!empty($_FILES[$key]['name'])) {
            $new = save_logo_upload($key);
            if ($new === null) {
                $errors[] = $label . ': usa una imagen PNG, JPG o WEBP de máximo 3 MB.';
                continue;
            }
            save_setting($key, $new);
            if ($old) delete_image_file($old);
        } elseif (input('quitar_' . $key) === '1' && $old) {
            save_setting($key, null);
            delete_image_file($old);
        }
    }
    $errors ? flash('error', implode(' ', $errors)) : flash('success', 'Ajustes guardados.');
    redirect('admin/ajustes.php');
}

$settings = settings();
$title = 'Ajustes';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1>Ajustes de la web</h1></div>

<form class="form admin-form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <fieldset class="admin-card">
    <legend>Logo e ícono</legend>
    <?php foreach ($logos as $key => [$label, $help]): ?>
      <div class="admin-logo">
        <div class="admin-logo__preview<?= $key === 'logo_dark' ? ' admin-logo__preview--dark' : '' ?>">
          <?php if (!empty($settings[$key])): ?>
            <img src="<?= e(upload_url($settings[$key])) ?>" alt="<?= e($label) ?> actual">
          <?php else: ?>
            <span class="muted">Sin subir</span>
          <?php endif; ?>
        </div>
        <div class="field">
          <label for="<?= $key ?>"><?= e($label) ?></label>
          <input id="<?= $key ?>" type="file" name="<?= $key ?>" accept="image/png,image/jpeg,image/webp">
          <span class="muted"><?= e($help) ?></span>
          <?php if (!empty($settings[$key])): ?>
            <label class="check"><input type="checkbox" name="quitar_<?= $key ?>" value="1"> <span>Quitar y volver al predeterminado</span></label>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </fieldset>

  <fieldset class="admin-card">
    <legend>Datos de la empresa</legend>
    <p class="muted">Aparecen en la cabecera, el pie de página, el botón de WhatsApp, la página de contacto y la política de datos.</p>
    <div class="form__row">
      <?php foreach ($fields as $key => [$label, $type, $help]): ?>
        <div class="field">
          <label for="f-<?= $key ?>"><?= e($label) ?></label>
          <input id="f-<?= $key ?>" name="<?= $key ?>" type="<?= $type ?>" value="<?= e(site($key)) ?>">
          <?php if ($help): ?><span class="muted"><?= e($help) ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="admin-actions">
    <button class="btn" type="submit">Guardar ajustes</button>
    <a class="btn btn--ghost" href="<?= e(url('')) ?>" target="_blank">Ver la web</a>
  </div>
</form>
<?php view('admin_footer'); ?>
