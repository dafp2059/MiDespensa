<?php
/** @var array $p Página editable (tabla pages). */
$content = rich((string) $p['content']);
$form = $p['form'] ?? 'none';
$page = [
    'title' => tokens($p['title']),
    'description' => tokens((string) ($p['meta_description'] ?: $p['intro'])) ?: null,
    'canonical' => url($p['slug']),
    'image' => $p['image'] ? upload_url($p['image']) : null,
];
view('header', compact('page'));
?>

<section class="page-head<?= $p['image'] ? ' page-head--image' : '' ?>"<?= $p['image'] ? ' style="--page-image:url(\'' . e(upload_url($p['image'])) . '\')"' : '' ?>>
  <div class="container">
    <?php if ($p['eyebrow']): ?><p class="eyebrow"><?= e(tokens($p['eyebrow'])) ?></p><?php endif; ?>
    <h1 class="page-head__title"><?= e(tokens($p['title'])) ?></h1>
    <?php if ($p['intro']): ?><p class="page-head__lead"><?= nl2br(e(tokens($p['intro']))) ?></p><?php endif; ?>
  </div>
</section>

<section class="section section--tight">
  <?php if ($form === 'none'): ?>
    <div class="container narrow prose"><?= $content ?></div>
  <?php elseif ($form === 'contacto_info'): ?>
    <div class="container contact-band">
      <div>
        <?php if ($content !== ''): ?><div class="prose"><?= $content ?></div><?php endif; ?>
        <?php view('contact_info', ['big' => true]); ?>
        <?php if (site('address')): ?>
          <div class="map">
            <iframe src="https://maps.google.com/maps?q=<?= e(rawurlencode(site('address') . ', ' . site('city') . ', Colombia')) ?>&amp;z=16&amp;output=embed" title="Mapa de nuestra oficina" loading="lazy"></iframe>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($p['form_title']): ?><h2 class="detail__subtitle"><?= e(tokens($p['form_title'])) ?></h2><?php endif; ?>
        <?php view('contact_form', ['reason' => $p['form_reason'] ?? '']); ?>
      </div>
    </div>
  <?php else: ?>
    <div class="container owners">
      <div class="prose"><?= $content ?></div>
      <div>
        <?php if ($p['form_title']): ?><h2 class="detail__subtitle"><?= e(tokens($p['form_title'])) ?></h2><?php endif; ?>
        <?php view('contact_form', ['reason' => $p['form_reason'] ?? '']); ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php view('footer'); ?>
