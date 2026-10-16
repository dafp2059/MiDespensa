<?php
require __DIR__ . '/../app/bootstrap.php';

$company = site('legal_name') ?: site('name');
$nit = site('nit');
$page = [
    'title' => 'Política de tratamiento de datos personales',
    'description' => 'Política de tratamiento de datos personales de ' . $company . ' conforme a la Ley 1581 de 2012.',
];
view('header', compact('page'));
?>

<section class="page-head">
  <div class="container">
    <p class="eyebrow">Legal</p>
    <h1 class="page-head__title">Política de tratamiento de datos personales</h1>
  </div>
</section>

<section class="section section--tight">
  <div class="container narrow prose">
    <h2>1. Responsable del tratamiento</h2>
    <p><strong><?= e($company) ?></strong><?= $nit ? ', NIT ' . e($nit) : '' ?>, con domicilio en <?= e(site('address')) ?>, <?= e(site('city')) ?>.
      Correo: <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a>. Teléfono: <?= e(phone_display(site('phone'))) ?>.</p>

    <h2>2. Marco legal</h2>
    <p>Esta política se expide en cumplimiento de la Ley Estatutaria 1581 de 2012, el Decreto 1377 de 2013 (compilado en el Decreto 1074 de 2015) y demás normas que las modifiquen o complementen.</p>

    <h2>3. Datos que recolectamos</h2>
    <p>Nombre, número de documento, teléfono, correo electrónico, dirección e información relacionada con los inmuebles de su interés o de su propiedad, así como la información necesaria para procesar pagos (la información de tarjetas y cuentas bancarias la procesa directamente la pasarela de pagos y no es almacenada por nosotros).</p>

    <h2>4. Finalidades</h2>
    <ul>
      <li>Responder solicitudes de información y agendar visitas a inmuebles.</li>
      <li>Gestionar la promoción, venta y arrendamiento de inmuebles.</li>
      <li>Realizar estudios de arrendatarios y celebrar contratos.</li>
      <li>Procesar y conciliar pagos realizados por medios electrónicos.</li>
      <li>Enviar información comercial sobre inmuebles y servicios, cuando el titular lo haya autorizado.</li>
      <li>Cumplir obligaciones legales, contables y tributarias.</li>
    </ul>

    <h2>5. Derechos del titular</h2>
    <p>Como titular de los datos usted puede: conocer, actualizar y rectificar sus datos; solicitar prueba de la autorización otorgada; ser informado sobre el uso que se ha dado a sus datos; presentar quejas ante la Superintendencia de Industria y Comercio; revocar la autorización o solicitar la supresión de sus datos cuando no exista un deber legal o contractual de conservarlos; y acceder gratuitamente a sus datos.</p>

    <h2>6. Consultas y reclamos</h2>
    <p>Puede ejercer sus derechos escribiendo a <a href="mailto:<?= e(site('email')) ?>"><?= e(site('email')) ?></a> o en nuestra oficina. Las consultas se atenderán en un máximo de diez (10) días hábiles y los reclamos en un máximo de quince (15) días hábiles, prorrogables en los términos de la ley.</p>

    <h2>7. Seguridad</h2>
    <p>Adoptamos medidas técnicas, humanas y administrativas razonables para proteger los datos personales contra pérdida, consulta, uso o acceso no autorizado.</p>

    <h2>8. Vigencia</h2>
    <p>Esta política rige a partir de su publicación. Los datos se conservarán mientras sean necesarios para las finalidades descritas o mientras exista un deber legal de conservarlos.</p>
  </div>
</section>

<?php view('footer'); ?>
