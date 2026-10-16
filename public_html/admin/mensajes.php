<?php
require __DIR__ . '/../../app/bootstrap.php';
require_can('mensajes');

if (is_post()) {
    csrf_check();
    $id = (int) input('id');
    if (input('accion') === 'leido') {
        query('UPDATE messages SET read_at = IF(read_at IS NULL, NOW(), NULL) WHERE id = ?', [$id]);
    } elseif (input('accion') === 'eliminar' && can('mensajes.eliminar')) {
        query('DELETE FROM messages WHERE id = ?', [$id]);
        flash('success', 'Mensaje eliminado.');
    }
    redirect('admin/mensajes.php');
}

$messages = query('SELECT m.*, p.title AS property_title, p.slug FROM messages m LEFT JOIN properties p ON p.id = m.property_id
    ORDER BY m.read_at IS NULL DESC, m.created_at DESC LIMIT 300')->fetchAll();

$title = 'Mensajes';
view('admin_header', compact('title'));
?>
<div class="admin-head"><h1>Mensajes</h1></div>
<?php if (!$messages): ?><p class="muted">Aún no hay mensajes.</p><?php endif; ?>
<?php foreach ($messages as $m): ?>
  <article class="admin-card message<?= $m['read_at'] ? '' : ' is-unread' ?>">
    <header class="message__head">
      <div>
        <strong><?= e($m['name']) ?></strong> · <?= e(CONTACT_REASONS[$m['reason']] ?? $m['reason']) ?>
        <?php if (!$m['read_at']): ?><span class="pill">Nuevo</span><?php endif; ?><br>
        <span class="muted"><?= e(date('d/m/Y g:i a', strtotime($m['created_at']))) ?></span>
      </div>
      <div class="message__actions">
        <?php $wa = preg_replace('/\D/', '', (string) $m['phone']); if (strlen($wa) === 10) $wa = '57' . $wa; ?>
        <?php if ($wa): ?><a class="btn btn--small btn--wa" href="https://wa.me/<?= e($wa) ?>?text=<?= e(rawurlencode('Hola ' . explode(' ', $m['name'])[0] . ', te escribimos de ' . site('name') . '.')) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
        <a class="btn btn--small btn--ghost" href="mailto:<?= e($m['email']) ?>">Correo</a>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
          <button class="btn btn--small btn--ghost" name="accion" value="leido"><?= $m['read_at'] ? 'Marcar como no leído' : 'Marcar como leído' ?></button>
          <?php if (can('mensajes.eliminar')): ?><button class="btn btn--small btn--danger" name="accion" value="eliminar" data-confirm="¿Eliminar este mensaje?">Eliminar</button><?php endif; ?>
        </form>
      </div>
    </header>
    <p class="muted"><?= e($m['phone']) ?> · <?= e($m['email']) ?><?php if ($m['property_title']): ?> · Inmueble: <a href="<?= e(property_url($m)) ?>" target="_blank"><?= e($m['property_title']) ?></a><?php endif; ?></p>
    <?php if ($m['message']): ?><p><?= nl2br(e($m['message'])) ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>
<?php view('admin_footer'); ?>
