<?php /** @var array $images Fotos actuales del inmueble o proyecto. */ ?>
<fieldset class="admin-card">
  <legend>Fotos</legend>
  <?php if ($images): ?>
    <p class="muted">La foto marcada como portada es la que aparece en el listado.</p>
    <div class="admin-images">
      <?php foreach ($images as $i => $img): ?>
        <div class="admin-images__item">
          <img src="<?= e(upload_url($img['filename'])) ?>" alt="Foto <?= $i + 1 ?>">
          <label><input type="radio" name="cover_id" value="<?= (int) $img['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>> Portada</label>
          <label><input type="checkbox" name="delete_images[]" value="<?= (int) $img['id'] ?>"> Eliminar</label>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="field">
    <label for="photos">Agregar fotos (JPG, PNG o WEBP; puedes elegir varias)</label>
    <input id="photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
  </div>
</fieldset>
