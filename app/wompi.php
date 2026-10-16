<?php
declare(strict_types=1);

/**
 * Integración con Wompi (Web Checkout + webhook de eventos).
 * Documentación: https://docs.wompi.co/docs/colombia/widget-checkout-web/
 */

function wompi_is_test(): bool
{
    return str_starts_with((string) config('wompi.public_key'), 'pub_test_');
}

function wompi_api_base(): string
{
    return wompi_is_test() ? 'https://sandbox.wompi.co/v1' : 'https://production.wompi.co/v1';
}

/** Firma de integridad: SHA256(referencia + monto en centavos + moneda + secreto de integridad). */
function wompi_integrity_signature(string $reference, int $amountInCents, string $currency = 'COP'): string
{
    return hash('sha256', $reference . $amountInCents . $currency . config('wompi.integrity_secret'));
}

/** Campos del formulario GET que envía al cliente a https://checkout.wompi.co/p/ */
function wompi_checkout_fields(array $payment): array
{
    return [
        'public-key' => config('wompi.public_key'),
        'currency' => 'COP',
        'amount-in-cents' => (string) $payment['amount_in_cents'],
        'reference' => $payment['reference'],
        'signature:integrity' => wompi_integrity_signature($payment['reference'], (int) $payment['amount_in_cents']),
        'redirect-url' => url('pagos-resultado'),
        'customer-data:email' => $payment['payer_email'],
        'customer-data:full-name' => $payment['payer_name'],
        'customer-data:phone-number' => preg_replace('/\D/', '', (string) $payment['payer_phone']),
        'customer-data:legal-id' => $payment['payer_document'],
        'customer-data:legal-id-type' => $payment['payer_document_type'],
    ];
}

/** Consulta una transacción en Wompi. Devuelve el arreglo "data" o null. */
function wompi_fetch_transaction(string $transactionId): ?array
{
    if (!preg_match('/^[\w-]{5,80}$/', $transactionId)) {
        return null;
    }
    $ch = curl_init(wompi_api_base() . '/transactions/' . rawurlencode($transactionId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $status !== 200) {
        return null;
    }
    $json = json_decode((string) $body, true);
    return is_array($json['data'] ?? null) ? $json['data'] : null;
}

/** Guarda el estado de una transacción de Wompi en el pago con la misma referencia. */
function wompi_apply_transaction(array $tx): ?array
{
    $payment = query('SELECT * FROM payments WHERE reference = ?', [$tx['reference'] ?? ''])->fetch();
    if (!$payment) {
        return null;
    }
    // Si el monto no coincide, no se marca como aprobado.
    $status = (string) ($tx['status'] ?? 'ERROR');
    if ((int) ($tx['amount_in_cents'] ?? 0) !== (int) $payment['amount_in_cents'] && $status === 'APPROVED') {
        $status = 'ERROR';
    }
    query('UPDATE payments SET status = ?, wompi_transaction_id = ?, payment_method = ? WHERE id = ?', [
        $status, $tx['id'] ?? null, $tx['payment_method_type'] ?? null, $payment['id'],
    ]);
    return query('SELECT * FROM payments WHERE id = ?', [$payment['id']])->fetch();
}

/** Verifica la firma de un evento del webhook. */
function wompi_valid_event(array $event): bool
{
    $properties = $event['signature']['properties'] ?? null;
    $checksum = $event['signature']['checksum'] ?? null;
    if (!is_array($properties) || !is_string($checksum) || !isset($event['timestamp'])) {
        return false;
    }
    $concat = '';
    foreach ($properties as $path) {
        $value = $event['data'] ?? [];
        foreach (explode('.', (string) $path) as $key) {
            $value = is_array($value) && array_key_exists($key, $value) ? $value[$key] : '';
        }
        $concat .= is_scalar($value) ? (string) $value : '';
    }
    $expected = hash('sha256', $concat . $event['timestamp'] . config('wompi.events_secret'));
    return hash_equals($expected, strtolower($checksum));
}
