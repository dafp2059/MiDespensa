<?php
/** Campos del contrato (crear y editar). Usa $type y $v (valor actual o enviado). */
$isRent = $type === 'arriendo';
?>
<fieldset class="admin-card">
  <legend><?= $isRent ? 'Propietario (por quien actúa la inmobiliaria)' : 'Vendedor (propietario)' ?></legend>
  <div class="form__row">
    <div class="field"><label for="owner_name">Nombre</label><input id="owner_name" name="owner_name" value="<?= $v('owner_name') ?>"></div>
    <div class="field"><label for="owner_document">Documento (ej.: CC 43.123.456)</label><input id="owner_document" name="owner_document" value="<?= $v('owner_document') ?>"></div>
    <div class="field"><label for="owner_phone">Teléfono</label><input id="owner_phone" name="owner_phone" type="tel" value="<?= $v('owner_phone') ?>"></div>
  </div>
</fieldset>

<?php if ($isRent): ?>
  <fieldset class="admin-card">
    <legend>Deudor solidario (codeudor)</legend>
    <div class="form__row form__row--4">
      <div class="field"><label for="cosigner_name">Nombre</label><input id="cosigner_name" name="cosigner_name" value="<?= $v('cosigner_name') ?>"></div>
      <div class="field"><label for="cosigner_document">Documento</label><input id="cosigner_document" name="cosigner_document" value="<?= $v('cosigner_document') ?>"></div>
      <div class="field"><label for="cosigner_phone">Teléfono</label><input id="cosigner_phone" name="cosigner_phone" type="tel" value="<?= $v('cosigner_phone') ?>"></div>
      <div class="field"><label for="cosigner_address">Dirección</label><input id="cosigner_address" name="cosigner_address" value="<?= $v('cosigner_address') ?>"></div>
    </div>
  </fieldset>
<?php endif; ?>

<fieldset class="admin-card">
  <legend><?= $isRent ? 'Canon y fechas' : 'Precio y escritura' ?></legend>
  <div class="form__row form__row--4">
    <div class="field"><label for="amount"><?= $isRent ? 'Canon mensual (COP) *' : 'Precio de venta (COP) *' ?></label>
      <input id="amount" name="amount" inputmode="numeric" data-money required value="<?= !empty($contract['amount']) ? e(money($contract['amount'], false)) : e(input('amount')) ?>"></div>
    <?php if ($isRent): ?>
      <div class="field"><label for="admin_fee">Administración mensual (COP)</label>
        <input id="admin_fee" name="admin_fee" inputmode="numeric" data-money placeholder="0 si está incluida" value="<?= !empty($contract['admin_fee']) ? e(money($contract['admin_fee'], false)) : e(input('admin_fee')) ?>"></div>
      <div class="field"><label for="due_day">Día de pago de cada mes</label><input id="due_day" name="due_day" type="number" min="1" max="28" value="<?= $v('due_day', 5) ?>"></div>
      <div class="field"><label for="signed_at">Fecha del contrato</label><input id="signed_at" name="signed_at" type="date" value="<?= $v('signed_at', date('Y-m-d')) ?>"></div>
  </div>
  <div class="form__row form__row--4">
      <div class="field"><label for="start_date">Inicio del arriendo *</label><input id="start_date" name="start_date" type="date" required value="<?= $v('start_date') ?>"></div>
      <div class="field"><label for="duration_months">Duración (meses)</label><input id="duration_months" name="duration_months" type="number" min="1" max="120" value="<?= $v('duration_months', 12) ?>"></div>
      <?php if (!empty($contract['end_date'])): ?><div class="field"><span class="field__label">Termina</span><span><?= e(fecha_larga($contract['end_date'])) ?></span></div><?php endif; ?>
    <?php else: ?>
      <div class="field"><label for="signed_at">Fecha de la promesa</label><input id="signed_at" name="signed_at" type="date" value="<?= $v('signed_at', date('Y-m-d')) ?>"></div>
      <div class="field"><label for="deed_date">Fecha de la escritura</label><input id="deed_date" name="deed_date" type="date" value="<?= $v('deed_date') ?>"></div>
      <div class="field"><label for="notary">Notaría</label><input id="notary" name="notary" placeholder="Ej.: Notaría 15 de Medellín" value="<?= $v('notary') ?>"></div>
    <?php endif; ?>
  </div>
  <div class="field"><label for="notes">Notas internas</label><textarea id="notes" name="notes" rows="2"><?= $v('notes') ?></textarea></div>
</fieldset>
