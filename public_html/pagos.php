<?php
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/wompi.php';

const DOCUMENT_TYPES = ['CC' => 'Cédula de ciudadanía', 'CE' => 'Cédula de extranjería', 'NIT' => 'NIT', 'PP' => 'Pasaporte'];

$errors = [];
$payment = null;
$old = [];

if (is_post()) {
    csrf_check();
    $old = [
        'nombre' => mb_substr(input('nombre'), 0, 120),
        'tipo_documento' => input('tipo_documento'),
        'documento' => preg_replace('/[^\w-]/', '', input('documento')),
        'correo' => mb_substr(input('correo'), 0, 190),
        'telefono' => mb_substr(input('telefono'), 0, 40),
        'concepto' => input('concepto'),
        'referencia' => mb_substr(input('referencia'), 0, 80),
        'valor' => input('valor'),
    ];
    $amount = parse_money($old['valor']);

    if ($old['nombre'] === '') $errors[] = 'Escribe el nombre de quien paga.';
    if (!isset(DOCUMENT_TYPES[$old['tipo_documento']])) $errors[] = 'Selecciona el tipo de documento.';
    if (strlen((string) $old['documento']) < 5) $errors[] = 'Escribe el número de documento.';
    if (!filter_var($old['correo'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Revisa el correo electrónico.';
    if (!isset(PAYMENT_CONCEPTS[$old['concepto']])) $errors[] = 'Selecciona el concepto del pago.';
    if ($old['referencia'] === '') $errors[] = 'Escribe el número de contrato o la dirección del inmueble.';
    if ($amount < 1500) $errors[] = 'El valor mínimo es $1.500.';
    if (input('acepto') !== '1') $errors[] = 'Debes autorizar el tratamiento de datos.';

    if (!$errors) {
        $reference = 'ING-' . date('ymdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        query('INSERT INTO payments (reference, payer_name, payer_document_type, payer_document, payer_email, payer_phone, concept, contract_ref, amount_in_cents)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $reference, $old['nombre'], $old['tipo_documento'], $old['documento'], $old['correo'], $old['telefono'],
            $old['concepto'], $old['referencia'], $amount * 100,
        ]);
        $payment = query('SELECT * FROM payments WHERE reference = ?', [$reference])->fetch();
    }
}

$page = [
    'title' => 'Pagos en línea',
    'description' => 'Paga en línea tu canon de arrendamiento, administración o separación de inmueble con tarjeta, PSE o Nequi a través de Wompi.',
];
view('header', compact('page'));
?>

<section class="page-head">
  <div class="container">
    <p class="eyebrow">Pagos en línea</p>
    <h1 class="page-head__title">Paga en línea, rápido y seguro</h1>
    <p class="page-head__lead">Canon de arrendamiento, administración o separación de inmuebles con tarjeta, PSE, Nequi o Bancolombia, a través de Wompi.</p>
  </div>
</section>

<section class="section section--tight">
  <div class="container pay">
    <?php if ($payment): ?>
      <div class="panel pay__confirm">
        <h2 class="detail__subtitle">Confirma tu pago</h2>
        <dl class="summary">
          <div><dt>Nombre</dt><dd><?= e($payment['payer_name']) ?></dd></div>
          <div><dt>Documento</dt><dd><?= e($payment['payer_document_type'] . ' ' . $payment['payer_document']) ?></dd></div>
          <div><dt>Concepto</dt><dd><?= e(PAYMENT_CONCEPTS[$payment['concept']]) ?></dd></div>
          <div><dt>Contrato / inmueble</dt><dd><?= e($payment['contract_ref']) ?></dd></div>
          <div><dt>Referencia</dt><dd><?= e($payment['reference']) ?></dd></div>
          <div class="summary__total"><dt>Total a pagar</dt><dd><?= e(money($payment['amount_in_cents'] / 100)) ?></dd></div>
        </dl>
        <form action="https://checkout.wompi.co/p/" method="get">
          <?php foreach (wompi_checkout_fields($payment) as $name => $value): if ($value === '') continue; ?>
            <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
          <?php endforeach; ?>
          <button class="btn btn--block" type="submit"><?= icon('card') ?> Pagar con Wompi</button>
        </form>
        <p class="muted">Serás redirigido a Wompi (Bancolombia) para completar el pago de forma segura. <a href="<?= e(url('pagos')) ?>">Corregir datos</a></p>
        <?php if (wompi_is_test()): ?><p class="alert alert--error">Modo de pruebas: no se cobrará dinero real.</p><?php endif; ?>
      </div>
    <?php else: ?>
      <form class="form panel" method="post" action="<?= e(url('pagos')) ?>">
        <?= csrf_field() ?>
        <?php if ($errors): ?><p class="alert alert--error" role="alert"><?= e(implode(' ', $errors)) ?></p><?php endif; ?>
        <div class="field">
          <label for="p-nombre">Nombre de quien paga *</label>
          <input id="p-nombre" name="nombre" required autocomplete="name" value="<?= e($old['nombre'] ?? '') ?>">
        </div>
        <div class="form__row">
          <div class="field">
            <label for="p-tipo">Tipo de documento *</label>
            <select id="p-tipo" name="tipo_documento" required>
              <?php foreach (DOCUMENT_TYPES as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($old['tipo_documento'] ?? 'CC') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="p-doc">Número de documento *</label>
            <input id="p-doc" name="documento" required inputmode="numeric" value="<?= e($old['documento'] ?? '') ?>">
          </div>
        </div>
        <div class="form__row">
          <div class="field">
            <label for="p-correo">Correo electrónico *</label>
            <input id="p-correo" name="correo" type="email" required autocomplete="email" value="<?= e($old['correo'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="p-tel">Celular</label>
            <input id="p-tel" name="telefono" type="tel" autocomplete="tel" value="<?= e($old['telefono'] ?? '') ?>">
          </div>
        </div>
        <div class="form__row">
          <div class="field">
            <label for="p-concepto">Concepto *</label>
            <select id="p-concepto" name="concepto" required>
              <option value="">Selecciona</option>
              <?php foreach (PAYMENT_CONCEPTS as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($old['concepto'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="p-ref">N.º de contrato o dirección del inmueble *</label>
            <input id="p-ref" name="referencia" required value="<?= e($old['referencia'] ?? '') ?>">
          </div>
        </div>
        <div class="field">
          <label for="p-valor">Valor a pagar (COP) *</label>
          <input id="p-valor" name="valor" required inputmode="numeric" data-money placeholder="$0" value="<?= e($old['valor'] ?? '') ?>">
        </div>
        <label class="check">
          <input type="checkbox" name="acepto" value="1" required>
          <span>Autorizo el tratamiento de mis datos personales según la <a href="<?= e(url('politica-de-datos')) ?>" target="_blank">política de tratamiento de datos</a>.</span>
        </label>
        <button class="btn btn--block" type="submit">Continuar</button>
      </form>
    <?php endif; ?>

    <aside class="pay__info">
      <div class="panel">
        <h2 class="detail__subtitle">Antes de pagar</h2>
        <ul class="features features--one">
          <li><?= icon('shield') ?> El pago se procesa en Wompi, la plataforma de pagos de Bancolombia. Nosotros no vemos ni guardamos los datos de tu tarjeta.</li>
          <li><?= icon('mail') ?> Recibirás el comprobante en tu correo.</li>
          <li><?= icon('chat') ?> ¿Dudas con el valor? <a href="<?= e(whatsapp_link('Hola, tengo una duda sobre un pago.')) ?>" target="_blank" rel="noopener">Escríbenos por WhatsApp</a>.</li>
        </ul>
      </div>
    </aside>
  </div>
</section>

<?php view('footer'); ?>
