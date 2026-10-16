<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();
$GLOBALS['needs_editor'] = true;

// Campos de texto simples de cada sección: clave => [etiqueta, tipo]
$texts = [
    'destacados' => ['home.destacados.eyebrow' => ['Texto pequeño superior', 'text'], 'home.destacados.title' => ['Título', 'text'],
        'home.destacados.link' => ['Texto del enlace "ver todos"', 'text'], 'home.destacados.count' => ['Cantidad de inmuebles a mostrar', 'number']],
    'zonas' => ['home.zonas.eyebrow' => ['Texto pequeño superior', 'text'], 'home.zonas.title' => ['Título', 'text']],
    'propietarios' => ['home.propietarios.eyebrow' => ['Texto pequeño superior', 'text'], 'home.propietarios.title' => ['Título', 'text'],
        'home.propietarios.text' => ['Texto', 'textarea'], 'home.propietarios.button' => ['Botón: texto', 'text'],
        'home.propietarios.button_url' => ['Botón: enlace', 'link'], 'home.propietarios.whatsapp' => ['Botón de WhatsApp: texto (vacío para ocultarlo)', 'text']],
    'proyectos' => ['home.proyectos.eyebrow' => ['Texto pequeño superior', 'text'], 'home.proyectos.title' => ['Título', 'text'],
        'home.proyectos.link' => ['Texto del enlace "ver todos"', 'text']],
    'texto' => ['home.texto.eyebrow' => ['Texto pequeño superior', 'text'], 'home.texto.title' => ['Título', 'text'],
        'home.texto.button' => ['Botón: texto', 'text'], 'home.texto.button_url' => ['Botón: enlace', 'link']],
    'contacto' => ['home.contacto.eyebrow' => ['Texto pequeño superior', 'text'], 'home.contacto.title' => ['Título', 'text'],
        'home.contacto.text' => ['Texto', 'textarea']],
];

if (is_post()) {
    csrf_check();

    $order = [];
    foreach ((array) ($_POST['section_key'] ?? []) as $i => $key) {
        if (isset(HOME_SECTIONS[$key])) {
            $order[] = ['key' => $key, 'visible' => ($_POST['section_visible'][$i] ?? '1') === '1' ? 1 : 0];
        }
    }
    save_setting('home.sections', json_encode($order));

    foreach ($texts as $fields) {
        foreach ($fields as $key => $_) {
            save_setting($key, mb_substr((string) ($_POST['s'][$key] ?? ''), 0, 2000));
        }
    }
    save_setting('home.texto.content', clean_html((string) ($_POST['texto_content'] ?? '')));

    $trust = [];
    foreach ((array) ($_POST['trust_title'] ?? []) as $i => $titleText) {
        if (trim((string) $titleText) === '') continue;
        $trust[] = ['icon' => isset(ICON_CHOICES[$_POST['trust_icon'][$i] ?? '']) ? $_POST['trust_icon'][$i] : 'check',
            'title' => mb_substr(trim((string) $titleText), 0, 120), 'text' => mb_substr(trim((string) ($_POST['trust_text'][$i] ?? '')), 0, 300)];
    }
    save_setting('home.confianza', json_encode($trust, JSON_UNESCAPED_UNICODE));

    $steps = [];
    foreach ((array) ($_POST['step_title'] ?? []) as $i => $titleText) {
        if (trim((string) $titleText) === '') continue;
        $steps[] = ['title' => mb_substr(trim((string) $titleText), 0, 120), 'text' => mb_substr(trim((string) ($_POST['step_text'][$i] ?? '')), 0, 300)];
    }
    save_setting('home.pasos', json_encode($steps, JSON_UNESCAPED_UNICODE));

    save_setting('seo.home_title', mb_substr(input('seo_title'), 0, 120));
    save_setting('seo.home_description', mb_substr(input('seo_description'), 0, 300));

    flash('success', 'Página de inicio guardada.');
    redirect('admin/inicio.php');
}

// Orden actual, agregando al final las secciones que falten.
$current = setting_list('home.sections');
$known = array_column($current, 'key');
foreach (array_keys(HOME_SECTIONS) as $key) {
    if (!in_array($key, $known, true)) $current[] = ['key' => $key, 'visible' => 0];
}

$field = function (string $key, string $label, string $type): void {
    $id = 'f-' . str_replace('.', '-', $key); ?>
    <div class="field">
      <label for="<?= $id ?>"><?= e($label) ?></label>
      <?php if ($type === 'textarea'): ?>
        <textarea id="<?= $id ?>" name="s[<?= e($key) ?>]" rows="3"><?= e(setting($key)) ?></textarea>
      <?php else: ?>
        <input id="<?= $id ?>" name="s[<?= e($key) ?>]" type="<?= $type === 'number' ? 'number' : 'text' ?>"<?= $type === 'link' ? ' list="enlaces"' : '' ?> value="<?= e(setting($key)) ?>">
      <?php endif; ?>
    </div>
<?php };

$trustRow = function (array $item = []): void { ?>
  <div class="repeater__row">
    <label class="field field--small"><span class="field__label">Ícono</span>
      <select name="trust_icon[]"><?php foreach (ICON_CHOICES as $k => $l): ?><option value="<?= $k ?>" <?= ($item['icon'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label class="field"><span class="field__label">Título</span><input name="trust_title[]" value="<?= e($item['title'] ?? '') ?>"></label>
    <label class="field"><span class="field__label">Texto</span><input name="trust_text[]" value="<?= e($item['text'] ?? '') ?>"></label>
    <div class="repeater__tools">
      <button class="icon-btn" type="button" data-up aria-label="Subir">↑</button>
      <button class="icon-btn" type="button" data-down aria-label="Bajar">↓</button>
      <button class="icon-btn icon-btn--danger" type="button" data-remove aria-label="Quitar">✕</button>
    </div>
  </div>
<?php };

$stepRow = function (array $item = []): void { ?>
  <div class="repeater__row">
    <label class="field"><span class="field__label">Paso</span><input name="step_title[]" value="<?= e($item['title'] ?? '') ?>"></label>
    <label class="field"><span class="field__label">Explicación</span><input name="step_text[]" value="<?= e($item['text'] ?? '') ?>"></label>
    <div class="repeater__tools">
      <button class="icon-btn" type="button" data-up aria-label="Subir">↑</button>
      <button class="icon-btn" type="button" data-down aria-label="Bajar">↓</button>
      <button class="icon-btn icon-btn--danger" type="button" data-remove aria-label="Quitar">✕</button>
    </div>
  </div>
<?php };

$title = 'Inicio';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Página de inicio</h1>
  <a href="<?= e(url('')) ?>" target="_blank">Ver el inicio</a>
</div>
<p class="admin-help">Elige qué secciones se muestran y en qué orden, y cambia sus textos. Puedes usar {{empresa}}, {{ciudad}}, {{telefono}} o {{correo}} y se reemplazan solos con los datos de Ajustes.</p>

<form class="form" method="post">
  <?= csrf_field() ?>

  <fieldset class="admin-card" data-repeater>
    <legend>Secciones y orden</legend>
    <div data-rows>
      <?php foreach ($current as $s): if (!isset(HOME_SECTIONS[$s['key']])) continue; ?>
        <div class="repeater__row section-row">
          <input type="hidden" name="section_key[]" value="<?= e($s['key']) ?>">
          <strong><?= e(HOME_SECTIONS[$s['key']]) ?></strong>
          <label class="field field--small"><span class="sr-only">Mostrar <?= e(HOME_SECTIONS[$s['key']]) ?></span>
            <select name="section_visible[]"><option value="1">Mostrar</option><option value="0" <?= empty($s['visible']) ? 'selected' : '' ?>>Ocultar</option></select></label>
          <div class="repeater__tools">
            <button class="icon-btn" type="button" data-up aria-label="Subir">↑</button>
            <button class="icon-btn" type="button" data-down aria-label="Bajar">↓</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="muted">El contenido del banner se cambia en <a href="<?= e(url('admin/banner.php')) ?>">Banner</a>, y las zonas en <a href="<?= e(url('admin/ubicaciones.php')) ?>">Zonas</a>.</p>
  </fieldset>

  <details class="admin-card" data-repeater>
    <summary>Franja "Por qué elegirnos"</summary>
    <div data-rows><?php foreach (setting_list('home.confianza') as $item) $trustRow($item); ?></div>
    <template><?php $trustRow(); ?></template>
    <div><button class="btn btn--small btn--ghost" type="button" data-add>+ Agregar elemento</button></div>
  </details>

  <?php foreach ($texts as $section => $fields): ?>
    <details class="admin-card"<?= $section === 'propietarios' ? ' data-repeater' : '' ?>>
      <summary><?= e(HOME_SECTIONS[$section]) ?></summary>
      <div class="form">
        <div class="form__row">
          <?php foreach ($fields as $key => [$label, $type]) $field($key, $label, $type); ?>
        </div>
        <?php if ($section === 'propietarios'): ?>
          <h3>Pasos</h3>
          <div data-rows><?php foreach (setting_list('home.pasos') as $item) $stepRow($item); ?></div>
          <template><?php $stepRow(); ?></template>
          <div><button class="btn btn--small btn--ghost" type="button" data-add>+ Agregar paso</button></div>
        <?php elseif ($section === 'texto'): ?>
          <div class="field">
            <span class="field__label">Contenido</span>
            <input type="hidden" id="texto_content" name="texto_content" value="<?= e(setting('home.texto.content')) ?>">
            <div class="editor" data-editor="texto_content"></div>
          </div>
        <?php endif; ?>
      </div>
    </details>
  <?php endforeach; ?>

  <details class="admin-card">
    <summary>Google (SEO)</summary>
    <div class="form">
      <div class="field"><label for="seo_title">Título que aparece en Google</label><input id="seo_title" name="seo_title" maxlength="120" placeholder="<?= e(site('name') . ' | Inmobiliaria en ' . site('city')) ?>" value="<?= e(setting('seo.home_title')) ?>"></div>
      <div class="field"><label for="seo_description">Descripción en Google</label><textarea id="seo_description" name="seo_description" rows="2" maxlength="300"><?= e(setting('seo.home_description')) ?></textarea></div>
    </div>
  </details>

  <div class="admin-actions"><button class="btn" type="submit">Guardar el inicio</button></div>
</form>
<?php view('admin_links'); ?>
<?php view('admin_footer'); ?>
