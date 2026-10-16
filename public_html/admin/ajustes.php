<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('diseno');

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
    $defaults = default_settings();
    save_setting('theme.accent', valid_color(input('accent'), $defaults['theme.accent']));
    save_setting('theme.dark', valid_color(input('dark'), $defaults['theme.dark']));
    save_setting('theme.highlight', valid_color(input('highlight'), $defaults['theme.highlight']));
    save_setting('theme.fonts', isset(FONT_PAIRS[input('fonts')]) ? input('fonts') : 'moderna');
    if (input('reset_colors') === '1') {
        foreach (['theme.accent', 'theme.dark', 'theme.highlight', 'theme.fonts'] as $key) save_setting($key, null);
    }
    save_setting('whatsapp.float', input('wa_float') === '1' ? '1' : '0');
    save_setting('whatsapp.nav_button', input('wa_nav') === '1' ? '1' : '0');
    save_setting('whatsapp.nav_label', mb_substr(input('wa_label'), 0, 40));
    save_setting('whatsapp.message', mb_substr(input('wa_message'), 0, 300));

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

  <fieldset class="admin-card">
    <legend>Colores y tipografía</legend>
    <div class="form__row form__row--4">
      <div class="field"><label for="accent">Color principal (botones, enlaces)</label><input id="accent" name="accent" type="color" value="<?= e(valid_color(setting('theme.accent'), '#0f6b52')) ?>"></div>
      <div class="field"><label for="dark">Color oscuro (barra superior, pie)</label><input id="dark" name="dark" type="color" value="<?= e(valid_color(setting('theme.dark'), '#13222c')) ?>"></div>
      <div class="field"><label for="highlight">Color de resalte (etiqueta "Arriendo")</label><input id="highlight" name="highlight" type="color" value="<?= e(valid_color(setting('theme.highlight'), '#c8932e')) ?>"></div>
      <div class="field"><label for="fonts">Tipografía</label>
        <select id="fonts" name="fonts"><?php foreach (FONT_PAIRS as $k => $f): ?><option value="<?= $k ?>" <?= setting('theme.fonts') === $k ? 'selected' : '' ?>><?= e($f[0]) ?></option><?php endforeach; ?></select></div>
    </div>
    <p class="muted">Consejo: usa colores oscuros o intensos para que el texto blanco de los botones se lea bien.</p>
    <label class="check"><input type="checkbox" name="reset_colors" value="1"> <span>Volver a los colores y la tipografía originales</span></label>
  </fieldset>

  <fieldset class="admin-card">
    <legend>WhatsApp</legend>
    <div class="form__row">
      <div class="field"><label for="wa_message">Mensaje que se escribe solo al abrir WhatsApp</label><input id="wa_message" name="wa_message" value="<?= e(setting('whatsapp.message')) ?>"></div>
      <div class="field"><label for="wa_label">Texto del botón del menú</label><input id="wa_label" name="wa_label" value="<?= e(setting('whatsapp.nav_label')) ?>"></div>
    </div>
    <div class="checks">
      <label class="check"><input type="checkbox" name="wa_float" value="1" <?= setting('whatsapp.float') === '1' ? 'checked' : '' ?>> <span>Botón flotante de WhatsApp en todas las páginas</span></label>
      <label class="check"><input type="checkbox" name="wa_nav" value="1" <?= setting('whatsapp.nav_button') === '1' ? 'checked' : '' ?>> <span>Botón de WhatsApp en el menú</span></label>
    </div>
  </fieldset>

  <div class="admin-actions">
    <button class="btn" type="submit">Guardar ajustes</button>
    <a class="btn btn--ghost" href="<?= e(url('')) ?>" target="_blank">Ver la web</a>
  </div>
</form>
<?php view('admin_footer'); ?>
