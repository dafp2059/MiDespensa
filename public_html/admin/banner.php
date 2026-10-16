<?php
require __DIR__ . '/../../app/bootstrap.php';
require_login();

/** Borra del disco la foto o el video de una diapositiva. */
$deleteMedia = function (array $slide): void {
    if (in_array($slide['media_type'], ['image', 'video'], true) && $slide['media']) {
        delete_image_file($slide['media']);
    }
};

if (is_post()) {
    csrf_check();
    $action = input('accion');
    $id = (int) input('id');
    $slide = $id ? query('SELECT * FROM slides WHERE id = ?', [$id])->fetch() : null;

    if ($action === 'opciones') {
        save_setting('hero.interval', (string) max(3, min(30, (int) input('interval'))));
        save_setting('hero.search', input('search') === '1' ? '1' : '0');
        save_setting('hero.height', in_array(input('height'), ['normal', 'alto', 'pantalla'], true) ? input('height') : 'normal');
        flash('success', 'Opciones del banner guardadas.');
    } elseif ($action === 'eliminar' && $slide) {
        $deleteMedia($slide);
        query('DELETE FROM slides WHERE id = ?', [$id]);
        flash('success', 'Diapositiva eliminada.');
    } elseif ($action === 'guardar' || $action === 'crear') {
        $data = [
            'eyebrow' => mb_substr(input('eyebrow'), 0, 190) ?: null,
            'title' => mb_substr(input('title'), 0, 255) ?: null,
            'subtitle' => input('subtitle') ?: null,
            'button_text' => mb_substr(input('button_text'), 0, 80) ?: null,
            'button_url' => mb_substr(input('button_url'), 0, 255) ?: null,
            'button2_text' => mb_substr(input('button2_text'), 0, 80) ?: null,
            'button2_url' => mb_substr(input('button2_url'), 0, 255) ?: null,
            'align' => input('align') === 'center' ? 'center' : 'left',
            'overlay' => max(0, min(90, (int) input('overlay', '55'))),
            'sort_order' => (int) input('sort_order'),
            'active' => input('active') === '1' ? 1 : 0,
        ];
        $mediaType = $slide['media_type'] ?? 'none';
        $media = $slide['media'] ?? null;
        $error = null;

        $upload = save_banner_upload('file');
        if (is_string($upload)) {
            $error = $upload;
        } elseif (is_array($upload)) {
            if ($slide) $deleteMedia($slide);
            [$media, $mediaType] = $upload;
        } elseif (input('media_type') === 'youtube') {
            if (!youtube_id(input('youtube'))) {
                $error = 'El enlace de YouTube no es válido. Copia la dirección del video desde el navegador o el botón Compartir.';
            } else {
                if ($slide && $slide['media_type'] !== 'youtube') $deleteMedia($slide);
                [$media, $mediaType] = [input('youtube'), 'youtube'];
            }
        } elseif (input('media_type') === 'none') {
            if ($slide) $deleteMedia($slide);
            [$media, $mediaType] = [null, 'none'];
        }

        if ($error) {
            flash('error', $error);
        } else {
            $data['media_type'] = $mediaType;
            $data['media'] = $media;
            $columns = array_keys($data);
            if ($slide) {
                query('UPDATE slides SET ' . implode(', ', array_map(fn($c) => "{$c} = ?", $columns)) . ' WHERE id = ?', [...array_values($data), $id]);
                flash('success', 'Diapositiva guardada.');
            } else {
                query('INSERT INTO slides (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')', array_values($data));
                flash('success', 'Diapositiva agregada.');
            }
        }
    }
    redirect('admin/banner.php');
}

$slides = slides(false);
$nextOrder = $slides ? max(array_column($slides, 'sort_order')) + 1 : 0;
$title = 'Banner';
view('admin_header', compact('title'));

$form = function (?array $s) use ($nextOrder): void {
    $v = fn(string $k, $d = '') => e($s[$k] ?? $d);
    $uid = $s ? 's' . $s['id'] : 'nuevo';
    $type = $s['media_type'] ?? 'none';
    ?>
    <form class="admin-card slide-card" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <?php if ($s): ?><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><?php endif; ?>
      <div>
        <div class="slide-card__preview">
          <?php if ($type === 'image'): ?><img src="<?= e(upload_url($s['media'])) ?>" alt="Imagen actual">
          <?php elseif ($type === 'video'): ?><video src="<?= e(upload_url($s['media'])) ?>" muted loop autoplay playsinline></video>
          <?php elseif ($type === 'youtube' && youtube_id((string) $s['media'])): ?><img src="https://i.ytimg.com/vi/<?= e(youtube_id((string) $s['media'])) ?>/hqdefault.jpg" alt="Video de YouTube">
          <?php else: ?><span>Sin foto ni video<br>(fondo de color)</span><?php endif; ?>
        </div>
        <?php if ($s): ?>
          <label class="check" style="margin-top:12px"><input type="checkbox" name="active" value="1" <?= $s['active'] ? 'checked' : '' ?>> <span>Visible en la web</span></label>
        <?php else: ?><input type="hidden" name="active" value="1"><?php endif; ?>
      </div>
      <div class="form">
        <div class="form__row">
          <div class="field">
            <label for="<?= $uid ?>-type">Fondo</label>
            <select id="<?= $uid ?>-type" name="media_type">
              <option value="keep"<?= $s ? ' selected' : '' ?>><?= $s ? 'Dejar el actual' : 'Elegir…' ?></option>
              <option value="upload">Subir foto o video</option>
              <option value="youtube">Video de YouTube</option>
              <option value="none">Sin foto (solo color)</option>
            </select>
          </div>
          <div class="field">
            <label for="<?= $uid ?>-file">Foto (JPG, PNG, WEBP) o video (MP4, WEBM)</label>
            <input id="<?= $uid ?>-file" type="file" name="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
          </div>
          <div class="field">
            <label for="<?= $uid ?>-yt">Enlace de YouTube</label>
            <input id="<?= $uid ?>-yt" name="youtube" placeholder="https://youtu.be/..." value="<?= $type === 'youtube' ? $v('media') : '' ?>">
          </div>
        </div>
        <div class="form__row">
          <div class="field"><label for="<?= $uid ?>-eyebrow">Texto pequeño superior</label><input id="<?= $uid ?>-eyebrow" name="eyebrow" value="<?= $v('eyebrow') ?>"></div>
          <div class="field"><label for="<?= $uid ?>-title">Título</label><input id="<?= $uid ?>-title" name="title" value="<?= $v('title') ?>"></div>
        </div>
        <div class="field"><label for="<?= $uid ?>-sub">Texto</label><textarea id="<?= $uid ?>-sub" name="subtitle" rows="2"><?= $v('subtitle') ?></textarea></div>
        <div class="form__row form__row--4">
          <div class="field"><label for="<?= $uid ?>-b1">Botón 1: texto</label><input id="<?= $uid ?>-b1" name="button_text" value="<?= $v('button_text') ?>"></div>
          <div class="field"><label for="<?= $uid ?>-b1u">Botón 1: enlace</label><input id="<?= $uid ?>-b1u" name="button_url" list="enlaces" placeholder="/inmuebles" value="<?= $v('button_url') ?>"></div>
          <div class="field"><label for="<?= $uid ?>-b2">Botón 2: texto</label><input id="<?= $uid ?>-b2" name="button2_text" value="<?= $v('button2_text') ?>"></div>
          <div class="field"><label for="<?= $uid ?>-b2u">Botón 2: enlace</label><input id="<?= $uid ?>-b2u" name="button2_url" list="enlaces" placeholder="/contacto" value="<?= $v('button2_url') ?>"></div>
        </div>
        <div class="form__row form__row--4">
          <div class="field"><label for="<?= $uid ?>-align">Posición del texto</label>
            <select id="<?= $uid ?>-align" name="align"><option value="left">Izquierda</option><option value="center" <?= ($s['align'] ?? '') === 'center' ? 'selected' : '' ?>>Centro</option></select></div>
          <div class="field"><label for="<?= $uid ?>-ov">Oscurecer el fondo: <?= (int) ($s['overlay'] ?? 55) ?> %</label><input id="<?= $uid ?>-ov" type="range" name="overlay" min="0" max="90" step="5" value="<?= (int) ($s['overlay'] ?? 55) ?>"></div>
          <div class="field"><label for="<?= $uid ?>-order">Orden</label><input id="<?= $uid ?>-order" type="number" name="sort_order" value="<?= (int) ($s['sort_order'] ?? $nextOrder) ?>"></div>
        </div>
        <div class="admin-actions" style="position:static;background:none;padding:0">
          <button class="btn" type="submit" name="accion" value="<?= $s ? 'guardar' : 'crear' ?>"><?= $s ? 'Guardar' : 'Agregar diapositiva' ?></button>
          <?php if ($s): ?><button class="btn btn--danger" type="submit" name="accion" value="eliminar" data-confirm="¿Eliminar esta diapositiva?">Eliminar</button><?php endif; ?>
        </div>
      </div>
    </form>
    <?php
};
?>
<div class="admin-head"><h1>Banner del inicio</h1></div>
<p class="admin-help">Agrega varias diapositivas y el banner cambiará solo. Cada una puede tener foto, video o video de YouTube, título, texto y hasta dos botones. Un video subido debe pesar menos de <?= e(ini_get('upload_max_filesize')) ?> (límite de tu hosting); si pesa más, súbelo a YouTube y pega el enlace.</p>

<form class="admin-card form" method="post">
  <?= csrf_field() ?>
  <h2>Opciones</h2>
  <div class="form__row form__row--4">
    <div class="field"><label for="interval">Segundos por diapositiva</label><input id="interval" type="number" name="interval" min="3" max="30" value="<?= e(setting('hero.interval')) ?>"></div>
    <div class="field"><label for="height">Alto del banner</label>
      <select id="height" name="height">
        <?php foreach (['normal' => 'Normal', 'alto' => 'Alto', 'pantalla' => 'Pantalla completa'] as $k => $l): ?><option value="<?= $k ?>" <?= setting('hero.height') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select></div>
  </div>
  <label class="check"><input type="checkbox" name="search" value="1" <?= setting('hero.search') === '1' ? 'checked' : '' ?>> <span>Mostrar el buscador de inmuebles debajo del banner</span></label>
  <div><button class="btn" type="submit" name="accion" value="opciones">Guardar opciones</button></div>
</form>

<h2>Diapositivas (<?= count($slides) ?>)</h2>
<?php foreach ($slides as $s) $form($s); ?>

<details class="admin-card"<?= $slides ? '' : ' open' ?>>
  <summary>+ Agregar diapositiva</summary>
  <?php $form(null); ?>
</details>

<?php view('admin_links'); ?>
<?php view('admin_footer'); ?>
