<?php
/** Formulario de contacto. Opcional: $property (inmueble de la ficha), $reason (motivo preseleccionado). */
$property = $property ?? null;
$reason = $reason ?? ($property ? 'visita' : '');
$old = $_SESSION['old_contact'] ?? [];
unset($_SESSION['old_contact']);
$defaultMessage = $property ? 'Hola, me interesa el inmueble "' . $property['title'] . '" (código ' . ($property['code'] ?: $property['id']) . '). ¿Podemos agendar una visita?' : '';
?>
<form class="form panel" action="<?= e(url('contacto')) ?>" method="post">
  <?= csrf_field() ?>
  <?php if ($property): ?><input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>"><?php endif; ?>
  <input type="hidden" name="volver" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
  <div class="hp" aria-hidden="true"><label>No llenar <input name="website" tabindex="-1" autocomplete="off"></label></div>

  <div class="form__row">
    <div class="field">
      <label for="c-nombre">Nombre completo <span aria-hidden="true">*</span></label>
      <input id="c-nombre" name="nombre" required autocomplete="name" value="<?= e($old['nombre'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="c-telefono">Celular <span aria-hidden="true">*</span></label>
      <input id="c-telefono" name="telefono" type="tel" required autocomplete="tel" value="<?= e($old['telefono'] ?? '') ?>">
    </div>
  </div>
  <div class="form__row">
    <div class="field">
      <label for="c-correo">Correo electrónico <span aria-hidden="true">*</span></label>
      <input id="c-correo" name="correo" type="email" required autocomplete="email" value="<?= e($old['correo'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="c-motivo">Motivo <span aria-hidden="true">*</span></label>
      <select id="c-motivo" name="motivo" required>
        <option value="">Selecciona un motivo</option>
        <?php foreach (CONTACT_REASONS as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= ($old['motivo'] ?? $reason) === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="field">
    <label for="c-mensaje">Mensaje</label>
    <textarea id="c-mensaje" name="mensaje" rows="4" placeholder="¿Cómo podemos ayudarte?"><?= e($old['mensaje'] ?? $defaultMessage) ?></textarea>
  </div>
  <label class="check">
    <input type="checkbox" name="acepto" value="1" required>
    <span>Autorizo el tratamiento de mis datos personales según la <a href="<?= e(url('politica-de-datos')) ?>" target="_blank">política de tratamiento de datos</a>.</span>
  </label>
  <button class="btn btn--block" type="submit">Enviar mensaje</button>
</form>
