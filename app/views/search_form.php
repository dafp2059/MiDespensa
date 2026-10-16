<?php
/** Buscador reutilizado en el inicio y en el listado. $compact = versión horizontal del inicio. */
$compact = $compact ?? false;
$op = input('operacion');
?>
<form class="search<?= $compact ? ' search--hero' : '' ?>" action="<?= e(url('inmuebles')) ?>" method="get" role="search">
  <fieldset class="search__tabs">
    <legend class="sr-only">Tipo de operación</legend>
    <label><input type="radio" name="operacion" value="" <?= $op === '' ? 'checked' : '' ?>><span>Todos</span></label>
    <label><input type="radio" name="operacion" value="venta" <?= $op === 'venta' ? 'checked' : '' ?>><span>Comprar</span></label>
    <label><input type="radio" name="operacion" value="arriendo" <?= $op === 'arriendo' ? 'checked' : '' ?>><span>Arrendar</span></label>
  </fieldset>
  <div class="search__fields">
    <div class="field">
      <label for="s-ubicacion">Ubicación</label>
      <select id="s-ubicacion" name="ubicacion">
        <option value="">Todas las zonas</option>
        <?php foreach (locations() as $loc): ?>
          <option value="<?= e($loc['slug']) ?>" <?= input('ubicacion') === $loc['slug'] ? 'selected' : '' ?>><?= e($loc['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="s-tipo">Tipo de inmueble</label>
      <select id="s-tipo" name="tipo">
        <option value="">Todos los tipos</option>
        <?php foreach (TYPES as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= input('tipo') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="s-precio">Precio máximo</label>
      <input id="s-precio" name="precio_max" inputmode="numeric" placeholder="Sin límite" data-money value="<?= e(input('precio_max')) ?>">
    </div>
    <?php if (!$compact): ?>
      <div class="field">
        <label for="s-hab">Habitaciones</label>
        <select id="s-hab" name="habitaciones">
          <option value="">Cualquiera</option>
          <?php foreach ([1, 2, 3, 4] as $n): ?>
            <option value="<?= $n ?>" <?= input('habitaciones') === (string) $n ? 'selected' : '' ?>><?= $n ?> o más</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="s-q">Palabra clave o código</label>
        <input id="s-q" name="q" placeholder="Ej.: piscina, ING-001" value="<?= e(input('q')) ?>">
      </div>
    <?php endif; ?>
    <button class="btn search__submit" type="submit"><?= icon('search') ?> Buscar</button>
  </div>
</form>
