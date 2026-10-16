<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('pagos');

$where = ['1 = 1'];
$params = [];
if (isset(PAYMENT_STATUSES[input('estado')])) {
    $where[] = 'status = ?';
    $params[] = input('estado');
}
if (($q = input('q')) !== '') {
    $where[] = '(reference LIKE ? OR payer_name LIKE ? OR payer_document LIKE ? OR contract_ref LIKE ?)';
    array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
}
$payments = query('SELECT * FROM payments WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 500', $params)->fetchAll();
$approvedTotal = array_sum(array_map(fn($p) => $p['status'] === 'APPROVED' ? $p['amount_in_cents'] : 0, $payments)) / 100;

if (input('exportar') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="pagos-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Fecha', 'Referencia', 'Estado', 'Valor', 'Concepto', 'Contrato/inmueble', 'Nombre', 'Documento', 'Correo', 'Celular', 'Medio de pago', 'ID Wompi'], ';');
    foreach ($payments as $p) {
        fputcsv($out, [$p['created_at'], $p['reference'], PAYMENT_STATUSES[$p['status']] ?? $p['status'], $p['amount_in_cents'] / 100,
            PAYMENT_CONCEPTS[$p['concept']] ?? $p['concept'], $p['contract_ref'], $p['payer_name'], $p['payer_document_type'] . ' ' . $p['payer_document'],
            $p['payer_email'], $p['payer_phone'], $p['payment_method'], $p['wompi_transaction_id']], ';');
    }
    exit;
}

$title = 'Pagos';
view('admin_header', compact('title'));
?>
<div class="admin-head">
  <h1>Pagos en línea</h1>
  <a class="btn btn--ghost" href="<?= e(query_with(['exportar' => 'csv'])) ?>">Descargar Excel (CSV)</a>
</div>
<form class="admin-filters" method="get">
  <div class="field"><label for="q">Buscar</label><input id="q" name="q" placeholder="Referencia, nombre, documento o contrato" value="<?= e($q) ?>"></div>
  <div class="field"><label for="estado">Estado</label>
    <select id="estado" name="estado"><option value="">Todos</option><?php foreach (PAYMENT_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= input('estado') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <button class="btn btn--ghost" type="submit">Filtrar</button>
</form>
<p>Total aprobado en esta lista: <strong><?= e(money($approvedTotal)) ?></strong></p>
<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Fecha</th><th>Pagador</th><th>Concepto</th><th>Valor</th><th>Estado</th><th>Referencia</th></tr></thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= e(date('d/m/Y g:i a', strtotime($p['created_at']))) ?></td>
        <td><strong><?= e($p['payer_name']) ?></strong><br><span class="muted"><?= e($p['payer_document_type'] . ' ' . $p['payer_document']) ?> · <?= e($p['payer_email']) ?></span></td>
        <td><?= e(PAYMENT_CONCEPTS[$p['concept']] ?? $p['concept']) ?><br><span class="muted"><?= e($p['contract_ref']) ?></span></td>
        <td><?= e(money($p['amount_in_cents'] / 100, false)) ?></td>
        <td><span class="status status--<?= e(strtolower($p['status'])) ?>"><?= e(PAYMENT_STATUSES[$p['status']] ?? $p['status']) ?></span><?php if ($p['payment_method']): ?><br><span class="muted"><?= e($p['payment_method']) ?></span><?php endif; ?></td>
        <td><code><?= e($p['reference']) ?></code></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="6" class="muted">No hay pagos.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php view('admin_footer'); ?>
