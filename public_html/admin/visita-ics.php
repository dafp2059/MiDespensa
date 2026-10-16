<?php
// Descarga la visita como evento de calendario (.ics), con recordatorio una hora antes.
require __DIR__ . '/../../app/bootstrap.php';
require_can('agenda');

$visit = visit_full((int) input('id'));
if (!$visit) {
    redirect('admin/agenda.php');
}
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="visita-' . $visit['id'] . '.ics"');
echo visit_ics($visit);
