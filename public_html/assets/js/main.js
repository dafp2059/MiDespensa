// INGESPRO: menú móvil, carrusel, galería, formato de precios y herramientas del panel.
(function () {
  'use strict';

  // Menú móvil
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('menu');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    });
  }

  // Galería de fotos
  document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
    var main = gallery.querySelector('[data-gallery-main]');
    var thumbs = gallery.querySelectorAll('.gallery__thumbs button');
    thumbs.forEach(function (btn) {
      btn.addEventListener('click', function () {
        main.src = btn.dataset.src;
        main.alt = btn.dataset.alt;
        thumbs.forEach(function (b) { b.removeAttribute('aria-current'); });
        btn.setAttribute('aria-current', 'true');
      });
    });
  });

  // Precios con puntos de miles mientras se escribe: 1250000 -> 1.250.000
  document.querySelectorAll('[data-money]').forEach(function (input) {
    input.addEventListener('input', function () {
      var digits = input.value.replace(/\D/g, '');
      input.value = digits ? Number(digits).toLocaleString('es-CO') : '';
    });
  });

  // Ordenar el listado sin botón
  document.querySelectorAll('[data-autosubmit]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // Carrusel del banner: cambia solo cada N segundos, con flechas, puntos y pausa.
  document.querySelectorAll('[data-carousel]').forEach(function (hero) {
    var slides = hero.querySelectorAll('.hero__slide');
    var dots = hero.querySelectorAll('[data-go]');
    var pauseBtn = hero.querySelector('[data-pause]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var delay = (parseInt(hero.dataset.interval, 10) || 6) * 1000;
    var current = 0, timer = null, paused = reduce, hovering = false;

    function syncVideos() {
      slides.forEach(function (slide, i) {
        var video = slide.querySelector('video');
        if (!video) return;
        if (i === current) { video.preload = 'auto'; var p = video.play(); if (p && p.catch) p.catch(function () {}); }
        else video.pause();
      });
    }
    function show(index) {
      current = (index + slides.length) % slides.length;
      slides.forEach(function (slide, i) {
        slide.classList.toggle('is-active', i === current);
        if (i === current) slide.removeAttribute('aria-hidden'); else slide.setAttribute('aria-hidden', 'true');
      });
      dots.forEach(function (dot, i) {
        if (i === current) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
      });
      syncVideos();
    }
    function schedule() {
      clearTimeout(timer);
      if (slides.length > 1 && !paused && !hovering) timer = setTimeout(function () { show(current + 1); schedule(); }, delay);
    }
    function setPaused(value) {
      paused = value;
      if (pauseBtn) {
        pauseBtn.setAttribute('aria-label', paused ? 'Reproducir' : 'Pausar');
        pauseBtn.innerHTML = paused
          ? '<svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5v14l12-7z"/></svg>'
          : '<svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14"/><rect x="14" y="5" width="4" height="14"/></svg>';
      }
      schedule();
    }

    if (slides.length > 1) {
      hero.querySelector('[data-prev]').addEventListener('click', function () { show(current - 1); schedule(); });
      hero.querySelector('[data-next]').addEventListener('click', function () { show(current + 1); schedule(); });
      dots.forEach(function (dot) { dot.addEventListener('click', function () { show(+dot.dataset.go); schedule(); }); });
      if (pauseBtn) pauseBtn.addEventListener('click', function () { setPaused(!paused); });
      var slidesBox = hero.querySelector('.hero__slides');
      slidesBox.addEventListener('mouseenter', function () { hovering = true; schedule(); });
      slidesBox.addEventListener('mouseleave', function () { hovering = false; schedule(); });
      // Deslizar con el dedo en el celular
      var startX = null;
      slidesBox.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
      slidesBox.addEventListener('touchend', function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 50) { show(current + (dx < 0 ? 1 : -1)); schedule(); }
        startX = null;
      });
      setPaused(paused);
    }
    syncVideos();
  });

  // Panel: filas que se pueden agregar, quitar y mover (menús, franjas, pasos…)
  document.querySelectorAll('[data-repeater]').forEach(function (box) {
    var rows = box.querySelector('[data-rows]');
    var template = box.querySelector('template');
    box.addEventListener('click', function (event) {
      var btn = event.target.closest('button');
      if (!btn || !box.contains(btn)) return;
      var row = btn.closest('.repeater__row');
      if (btn.hasAttribute('data-add')) {
        rows.appendChild(template.content.cloneNode(true));
        var inputs = rows.lastElementChild.querySelectorAll('input, select, textarea');
        if (inputs[0]) inputs[0].focus();
      } else if (row && btn.hasAttribute('data-remove')) {
        row.remove();
      } else if (row && btn.hasAttribute('data-up') && row.previousElementSibling) {
        rows.insertBefore(row, row.previousElementSibling);
        btn.focus();
      } else if (row && btn.hasAttribute('data-down') && row.nextElementSibling) {
        rows.insertBefore(row.nextElementSibling, row);
        btn.focus();
      }
    });
  });

  // Panel: editor de texto (Quill) para páginas y bloques de texto
  if (window.Quill) {
    document.querySelectorAll('[data-editor]').forEach(function (box) {
      var input = document.getElementById(box.dataset.editor);
      var quill = new Quill(box, {
        theme: 'snow',
        modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link'], [{ align: [] }], ['clean']] }
      });
      quill.root.innerHTML = input.value;
      input.form.addEventListener('submit', function () { input.value = quill.root.innerHTML; });
    });
  }

  // Confirmar acciones peligrosas (eliminar)
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      if (!window.confirm(el.dataset.confirm)) event.preventDefault();
    });
  });
})();
