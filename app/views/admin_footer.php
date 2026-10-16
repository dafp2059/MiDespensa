</main>
<?php if (!empty($GLOBALS['needs_editor'])): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script><?php endif; ?>
<?php if (!empty($GLOBALS['needs_map'])): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script><?php endif; ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
