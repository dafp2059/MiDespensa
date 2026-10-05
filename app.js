// Solufidge: menú semanal, lista del mercado y aseo, y control de calorías.
// Todo se guarda en el dispositivo (localStorage).

const STORAGE_KEY = 'solufidge-v1';

// ---------- Utilidades ----------

const $ = (sel) => document.querySelector(sel);

function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

function norm(s) {
  return String(s ?? '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
}

function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
}

function fechaKey(d = new Date()) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function indiceHoy() {
  return (new Date().getDay() + 6) % 7; // 0 = lunes
}

function toast(msg) {
  const el = $('#toast');
  el.textContent = msg;
  el.classList.add('show');
  clearTimeout(toast.t);
  toast.t = setTimeout(() => el.classList.remove('show'), 2200);
}

// Adivina la categoría de un producto por palabras clave.
const PALABRAS_CATEGORIA = {
  carnes: ['carne', 'bistec', 'chuleta', 'chorizo', 'jamon', 'salchicha', 'res', 'cerdo', 'tocino', 'pescado', 'filete'],
  verduras: ['papa', 'cebolla', 'jitomate', 'tomate', 'lechuga', 'aguacate', 'limon', 'pepino', 'zanahoria', 'cilantro', 'champinon', 'manzana', 'platano', 'naranja', 'chile', 'ajo', 'fruta', 'verdura', 'nopal', 'calabaza', 'espinaca'],
  lacteos: ['queso', 'leche', 'yogur', 'crema', 'mantequilla', 'panela'],
  panaderia: ['tortilla', 'pan', 'tostada', 'bolillo'],
  congelados: ['congelad', 'helado', 'hielo'],
  salsas: ['salsa', 'soya', 'chipotle', 'mayonesa', 'catsup', 'mostaza', 'sal de mesa', 'pimienta', 'consome', 'vinagre'],
  alacena: ['arroz', 'atun', 'pasta', 'aceite', 'azucar', 'cafe', 'galleta', 'cereal', 'sardina', 'lata', 'avena', 'harina', 'lenteja', 'garbanzo'],
  aseoPersonal: ['papel higienico', 'jabon de bano', 'shampoo', 'champu', 'pasta dental', 'cepillo', 'desodorante', 'rastrillo', 'crema corporal', 'hilo dental', 'toalla femenina'],
  limpieza: ['detergente', 'cloro', 'lavatrastes', 'limpiador', 'bolsa', 'esponja', 'suavizante', 'servilleta', 'escoba', 'trapeador', 'fibra', 'jabon'],
};

function adivinarCategoria(nombre) {
  const n = norm(nombre);
  // Aseo primero para que "pasta dental" no caiga en alacena.
  for (const cat of ['aseoPersonal', 'limpieza', 'carnes', 'salsas', 'verduras', 'lacteos', 'panaderia', 'congelados', 'alacena']) {
    if (PALABRAS_CATEGORIA[cat].some((p) => n.includes(p))) return cat;
  }
  return 'otros';
}

// ---------- Estado ----------

function estadoInicial() {
  return {
    version: 1,
    exclusiones: [...EXCLUSIONES_INICIALES],
    recetasPropias: [],
    menu: Array(7).fill(null),
    mercado: [],
    meta: 2000,
    registro: [],
  };
}

function cargar() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) return { ...estadoInicial(), ...JSON.parse(raw) };
  } catch (e) {
    console.warn('No se pudo leer el estado guardado', e);
  }
  return estadoInicial();
}

let S = cargar();
let ui = { tab: 'menu', filtroMercado: 'todo', fechaCal: fechaKey() };

function guardar() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(S));
  } catch (e) {
    toast('No se pudo guardar en este dispositivo');
  }
  render();
}

// ---------- Recetas ----------

function todasRecetas() {
  return [...RECETAS_BASE, ...S.recetasPropias];
}

function receta(id) {
  return todasRecetas().find((r) => r.id === id);
}

function exclusionDe(r) {
  const textos = [r.nombre, ...(r.tags || []), ...r.ingredientes.map((i) => i.nombre)].map(norm);
  return S.exclusiones.find((ex) => textos.some((t) => t.includes(norm(ex))));
}

function recetasPermitidas() {
  return todasRecetas().filter((r) => !exclusionDe(r));
}

function mezclar(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

function generarMenu() {
  const permitidas = recetasPermitidas();
  if (!permitidas.length) return toast('No hay recetas permitidas. Revisa Ajustes.');
  let bolsa = [];
  S.menu = DIAS.map(() => {
    if (!bolsa.length) bolsa = mezclar(permitidas);
    return bolsa.pop().id;
  });
  guardar();
  toast('Menú generado');
}

// ---------- Mercado ----------

function agregarAlMercado(nombre, cat) {
  nombre = nombre.trim();
  if (!nombre) return false;
  const existente = S.mercado.find((i) => norm(i.nombre) === norm(nombre));
  if (existente) {
    if (!existente.hecho) return false;
    existente.hecho = false;
    return true;
  }
  S.mercado.push({ id: uid(), nombre, cat: cat || adivinarCategoria(nombre), hecho: false });
  return true;
}

function ingredientesAlMercado(recetas) {
  let n = 0;
  recetas.forEach((r) => r.ingredientes.forEach((i) => { if (agregarAlMercado(i.nombre, i.cat)) n++; }));
  guardar();
  toast(n ? `${n} producto(s) agregados al mercado` : 'Todo ya estaba en la lista');
}

function textoLista() {
  const pendientes = S.mercado.filter((i) => !i.hecho);
  const grupos = {};
  pendientes.forEach((i) => (grupos[i.cat] ||= []).push(i.nombre));
  return Object.keys(CATEGORIAS)
    .filter((c) => grupos[c])
    .map((c) => `${CATEGORIAS[c].nombre}:\n${grupos[c].map((n) => `- ${n}`).join('\n')}`)
    .join('\n\n');
}

async function compartirLista() {
  const texto = textoLista();
  if (!texto) return toast('No hay pendientes');
  try {
    if (navigator.share) {
      await navigator.share({ title: 'Lista del mercado', text: texto });
    } else {
      await navigator.clipboard.writeText(texto);
      toast('Lista copiada');
    }
  } catch (e) {
    if (e.name !== 'AbortError') toast('No se pudo compartir');
  }
}

// ---------- Calorías ----------

function registrar(nombre, kcal, fecha = ui.fechaCal) {
  kcal = Math.round(Number(kcal));
  if (!nombre || !(kcal > 0)) return toast('Escribe nombre y calorías');
  S.registro.push({ id: uid(), fecha, nombre, kcal });
  guardar();
  toast(`+${kcal} kcal: ${nombre}`);
}

function totalDia(fecha) {
  return S.registro.filter((r) => r.fecha === fecha).reduce((s, r) => s + r.kcal, 0);
}

function ultimos7() {
  const dias = [];
  const base = new Date(ui.fechaCal + 'T12:00:00');
  for (let i = 6; i >= 0; i--) {
    const d = new Date(base);
    d.setDate(d.getDate() - i);
    dias.push({ key: fechaKey(d), etiqueta: DIAS[(d.getDay() + 6) % 7].slice(0, 2), total: totalDia(fechaKey(d)) });
  }
  return dias;
}

// ---------- Render ----------

function opcionesRecetas(seleccion) {
  const permitidas = recetasPermitidas();
  const lista = [...permitidas];
  const actual = seleccion && receta(seleccion);
  if (actual && !permitidas.includes(actual)) lista.unshift(actual);
  return ['<option value="">— Sin plan —</option>']
    .concat(lista.map((r) => `<option value="${esc(r.id)}" ${r.id === seleccion ? 'selected' : ''}>${esc(r.nombre)}</option>`))
    .join('');
}

function renderMenu() {
  const hoy = indiceHoy();
  const totalSemana = S.menu.reduce((s, id) => s + (receta(id)?.kcal || 0), 0);
  const dias = DIAS.map((dia, i) => {
    const r = receta(S.menu[i]);
    return `
      <div class="card day ${i === hoy ? 'today' : ''}">
        <div class="row">
          <div class="label">${dia}${i === hoy ? '<div class="muted">Hoy</div>' : ''}</div>
          <div class="grow">
            <select data-change="menu-dia" data-dia="${i}" aria-label="Comida del ${dia}">${opcionesRecetas(S.menu[i])}</select>
            ${r ? `<div class="row wrap" style="margin-top:6px">
              <span class="badge">${r.minutos} min</span>
              <span class="badge">${r.kcal} kcal</span>
              ${i === hoy ? `<button class="btn small" data-action="comi" data-id="${esc(r.id)}">Lo comí ✓</button>` : ''}
            </div>` : ''}
          </div>
        </div>
      </div>`;
  }).join('');

  $('#tab-menu').innerHTML = `
    <div class="row spread wrap">
      <h2>Menú de la semana</h2>
      <span class="muted">${totalSemana} kcal en comidas</span>
    </div>
    <div class="row wrap" style="margin-bottom:12px">
      <button class="btn primary" data-action="generar-menu">🎲 Generar automático</button>
      <button class="btn" data-action="menu-a-mercado">🛒 Pasar al mercado</button>
      <button class="btn" data-action="limpiar-menu">Limpiar</button>
    </div>
    ${dias}
    <p class="muted">Excluido: ${S.exclusiones.map(esc).join(', ') || 'nada'}. Cámbialo en Ajustes.</p>`;
}

function renderRecetas() {
  const permitidas = recetasPermitidas();
  const ocultas = todasRecetas().length - permitidas.length;
  const tarjetas = permitidas.map((r) => `
    <details class="card receta">
      <summary class="row spread">
        <strong class="grow">${esc(r.nombre)}</strong>
        <span class="badge">${r.minutos} min</span>
        <span class="badge">${r.kcal} kcal</span>
      </summary>
      <h3>Ingredientes</h3>
      <ul>${r.ingredientes.map((i) => `<li>${esc(i.nombre)}</li>`).join('')}</ul>
      ${r.pasos?.length ? `<h3>Pasos</h3><ol>${r.pasos.map((p) => `<li>${esc(p)}</li>`).join('')}</ol>` : ''}
      <div class="row wrap">
        <button class="btn small" data-action="receta-a-mercado" data-id="${esc(r.id)}">🛒 Al mercado</button>
        <button class="btn small" data-action="comi" data-id="${esc(r.id)}">🔥 Lo comí hoy</button>
        ${r.propia ? `<button class="btn small danger" data-action="borrar-receta" data-id="${esc(r.id)}">Eliminar</button>` : ''}
      </div>
    </details>`).join('');

  $('#tab-recetas').innerHTML = `
    <h2>Recetas de 15 minutos o menos</h2>
    ${tarjetas || '<p class="empty">No hay recetas permitidas.</p>'}
    ${ocultas ? `<p class="muted">${ocultas} receta(s) ocultas por tus exclusiones.</p>` : ''}
    <h2>Agregar mi receta</h2>
    <form class="card" data-form="receta">
      <input name="nombre" placeholder="Nombre" required style="width:100%;margin-bottom:8px">
      <div class="row" style="margin-bottom:8px">
        <input name="minutos" type="number" min="1" placeholder="Minutos" class="grow">
        <input name="kcal" type="number" min="1" placeholder="kcal por porción" class="grow" required>
      </div>
      <textarea name="ingredientes" rows="3" placeholder="Ingredientes (uno por línea)" required></textarea>
      <textarea name="pasos" rows="3" placeholder="Pasos (uno por línea, opcional)" style="margin-top:8px"></textarea>
      <button class="btn primary" style="margin-top:8px">Guardar receta</button>
    </form>`;
}

function renderMercado() {
  const f = ui.filtroMercado;
  const visible = (cat) => f === 'todo' || CATEGORIAS[cat]?.tipo === f || (f === 'comida' && CATEGORIAS[cat]?.tipo === 'otro');
  const items = S.mercado.filter((i) => visible(i.cat));
  const pendientes = items.filter((i) => !i.hecho).length;

  const grupos = Object.keys(CATEGORIAS).map((cat) => {
    const lista = items.filter((i) => i.cat === cat).sort((a, b) => a.hecho - b.hecho);
    if (!lista.length) return '';
    return `
      <h3>${CATEGORIAS[cat].nombre}</h3>
      <div class="card">
        ${lista.map((i) => `
          <div class="item ${i.hecho ? 'done' : ''}">
            <input type="checkbox" data-change="toggle-item" data-id="${i.id}" ${i.hecho ? 'checked' : ''} aria-label="${esc(i.nombre)}">
            <span class="name grow">${esc(i.nombre)}</span>
            <button class="x" data-action="borrar-item" data-id="${i.id}" aria-label="Quitar">✕</button>
          </div>`).join('')}
      </div>`;
  }).join('');

  const sugeridos = f === 'comida' ? '' : `
    <h3>Aseo y limpieza rápidos</h3>
    <div>${ASEO_SUGERIDOS.map((a, k) => `<span class="chip" data-action="add-sugerido" data-k="${k}">+ ${esc(a.nombre)}</span>`).join('')}</div>`;

  $('#tab-mercado').innerHTML = `
    <div class="row spread"><h2>Lista del mercado</h2><span class="muted">${pendientes} pendiente(s)</span></div>
    <div class="segmented">
      ${[['todo', 'Todo'], ['comida', 'Comida'], ['aseo', 'Aseo']].map(([k, t]) =>
        `<button data-action="filtro-mercado" data-f="${k}" class="${f === k ? 'active' : ''}">${t}</button>`).join('')}
    </div>
    <form class="row" data-form="item" style="margin-bottom:8px">
      <input name="nombre" placeholder="Agregar producto…" class="grow" autocomplete="off" required>
      <select name="cat" aria-label="Categoría">
        <option value="">Auto</option>
        ${Object.entries(CATEGORIAS).map(([k, c]) => `<option value="${k}">${c.nombre}</option>`).join('')}
      </select>
      <button class="btn primary">+</button>
    </form>
    ${sugeridos}
    ${grupos || '<p class="empty">La lista está vacía. Agrega productos o usa “Pasar al mercado” en el Menú.</p>'}
    ${S.mercado.length ? `
      <div class="row wrap" style="margin-top:12px">
        <button class="btn" data-action="compartir-lista">📤 Compartir / copiar</button>
        <button class="btn" data-action="borrar-comprados">Borrar comprados</button>
        <button class="btn danger" data-action="vaciar-lista">Vaciar lista</button>
      </div>` : ''}`;
}

function renderCalorias() {
  const fecha = ui.fechaCal;
  const total = totalDia(fecha);
  const pct = Math.min(100, (total / S.meta) * 100);
  const restante = S.meta - total;
  const entradas = S.registro.filter((r) => r.fecha === fecha);
  const semana = ultimos7();
  const max = Math.max(S.meta, ...semana.map((d) => d.total));
  const conDatos = semana.filter((d) => d.total > 0);
  const promedio = conDatos.length ? Math.round(conDatos.reduce((s, d) => s + d.total, 0) / conDatos.length) : 0;

  $('#tab-calorias').innerHTML = `
    <div class="row spread wrap">
      <h2>Calorías</h2>
      <input type="date" value="${fecha}" data-change="fecha-cal" aria-label="Fecha">
    </div>
    <div class="card">
      <div class="row spread"><span class="big">${total}</span><span class="muted">meta ${S.meta} kcal</span></div>
      <div class="progress ${total > S.meta ? 'over' : ''}"><div style="width:${pct}%"></div></div>
      <div class="muted">${restante >= 0 ? `Te quedan ${restante} kcal` : `Te pasaste ${-restante} kcal`}</div>
    </div>

    <h3>Agregar comida</h3>
    <div class="card">
      <form class="row" data-form="cal-receta" style="margin-bottom:8px">
        <select name="id" class="grow" aria-label="Receta">${recetasPermitidas().map((r) => `<option value="${esc(r.id)}">${esc(r.nombre)} (${r.kcal})</option>`).join('')}</select>
        <button class="btn primary">+</button>
      </form>
      <form class="row" data-form="cal-libre">
        <input name="nombre" placeholder="Otra cosa…" class="grow" required>
        <input name="kcal" type="number" min="1" placeholder="kcal" style="width:80px" required>
        <button class="btn primary">+</button>
      </form>
      <div style="margin-top:10px">${SNACKS.map((s, k) => `<span class="chip" data-action="add-snack" data-k="${k}">${esc(s.nombre)} · ${s.kcal}</span>`).join('')}</div>
    </div>

    <h3>Registro del día</h3>
    <div class="card">
      ${entradas.length ? entradas.map((e) => `
        <div class="item">
          <span class="name grow">${esc(e.nombre)}</span>
          <span class="muted">${e.kcal} kcal</span>
          <button class="x" data-action="borrar-registro" data-id="${e.id}" aria-label="Quitar">✕</button>
        </div>`).join('') : '<p class="empty">Nada registrado este día.</p>'}
    </div>

    <h3>Últimos 7 días</h3>
    <div class="card">
      <div class="bars">
        ${semana.map((d) => `
          <div class="bar ${d.total > S.meta ? 'over' : ''}" title="${d.key}: ${d.total} kcal">
            <div style="height:${(d.total / max) * 100}%"></div>${d.etiqueta}
          </div>`).join('')}
      </div>
      <p class="muted">Promedio de los días con registro: ${promedio} kcal</p>
    </div>
    <p class="muted">Las calorías de las recetas son aproximadas por porción.</p>`;
}

function renderAjustes() {
  $('#tab-ajustes').innerHTML = `
    <h2>Ajustes</h2>
    <div class="card">
      <form class="row" data-form="meta">
        <label class="grow" for="meta">Meta diaria de calorías</label>
        <input id="meta" name="meta" type="number" min="800" max="6000" value="${S.meta}" style="width:100px">
        <button class="btn primary">Guardar</button>
      </form>
    </div>

    <h3>Alimentos que no como</h3>
    <div class="card">
      <div style="margin-bottom:8px">${S.exclusiones.map((e, k) =>
        `<span class="chip removable" data-action="quitar-exclusion" data-k="${k}">${esc(e)}</span>`).join('') || '<span class="muted">Ninguno</span>'}</div>
      <form class="row" data-form="exclusion">
        <input name="ex" placeholder="Ej. cerdo, lenteja…" class="grow" required>
        <button class="btn primary">Agregar</button>
      </form>
      <p class="muted">Las recetas con estos ingredientes no aparecen en el menú.</p>
    </div>

    <h3>Respaldo</h3>
    <div class="card row wrap">
      <button class="btn" data-action="exportar">⬇️ Exportar datos</button>
      <label class="btn">⬆️ Importar<input type="file" accept="application/json" data-change="importar" hidden></label>
      <button class="btn danger" data-action="reiniciar">Borrar todo</button>
    </div>
    <p class="muted">Tus datos se guardan solo en este dispositivo. Exporta un respaldo de vez en cuando.</p>`;
}

function render() {
  renderMenu();
  renderRecetas();
  renderMercado();
  renderCalorias();
  renderAjustes();
  const pend = S.mercado.filter((i) => !i.hecho).length;
  $('#subtitulo').textContent = `${totalDia(fechaKey())}/${S.meta} kcal hoy · ${pend} en lista`;
}

function irA(tab) {
  ui.tab = tab;
  document.querySelectorAll('.tab').forEach((t) => (t.hidden = t.dataset.tab !== tab));
  document.querySelectorAll('.bottomnav button').forEach((b) => b.classList.toggle('active', b.dataset.goto === tab));
  window.scrollTo(0, 0);
}

// ---------- Eventos ----------

const acciones = {
  'generar-menu': generarMenu,
  'limpiar-menu': () => { S.menu = Array(7).fill(null); guardar(); },
  'menu-a-mercado': () => {
    const rs = S.menu.map(receta).filter(Boolean);
    if (!rs.length) return toast('Primero arma el menú');
    ingredientesAlMercado(rs);
  },
  'receta-a-mercado': (d) => ingredientesAlMercado([receta(d.id)]),
  comi: (d) => { const r = receta(d.id); registrar(r.nombre, r.kcal, fechaKey()); },
  'borrar-receta': (d) => {
    if (!confirm('¿Eliminar esta receta?')) return;
    S.recetasPropias = S.recetasPropias.filter((r) => r.id !== d.id);
    S.menu = S.menu.map((id) => (id === d.id ? null : id));
    guardar();
  },
  'filtro-mercado': (d) => { ui.filtroMercado = d.f; renderMercado(); },
  'add-sugerido': (d) => {
    const a = ASEO_SUGERIDOS[d.k];
    toast(agregarAlMercado(a.nombre, a.cat) ? `${a.nombre} agregado` : 'Ya está en la lista');
    guardar();
  },
  'borrar-item': (d) => { S.mercado = S.mercado.filter((i) => i.id !== d.id); guardar(); },
  'borrar-comprados': () => { S.mercado = S.mercado.filter((i) => !i.hecho); guardar(); },
  'vaciar-lista': () => { if (confirm('¿Vaciar toda la lista?')) { S.mercado = []; guardar(); } },
  'compartir-lista': compartirLista,
  'add-snack': (d) => { const s = SNACKS[d.k]; registrar(s.nombre, s.kcal); },
  'borrar-registro': (d) => { S.registro = S.registro.filter((r) => r.id !== d.id); guardar(); },
  'quitar-exclusion': (d) => { S.exclusiones.splice(Number(d.k), 1); guardar(); },
  exportar: () => {
    const blob = new Blob([JSON.stringify(S, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `solufidge-${fechaKey()}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
  },
  reiniciar: () => {
    if (!confirm('Se borrarán menú, lista, recetas propias y registro de calorías. ¿Continuar?')) return;
    S = estadoInicial();
    guardar();
  },
};

document.addEventListener('click', (e) => {
  const nav = e.target.closest('[data-goto]');
  if (nav) return irA(nav.dataset.goto);
  const el = e.target.closest('[data-action]');
  if (el && acciones[el.dataset.action]) acciones[el.dataset.action](el.dataset);
});

document.addEventListener('change', (e) => {
  const el = e.target.closest('[data-change]');
  if (!el) return;
  const d = el.dataset;
  switch (d.change) {
    case 'menu-dia':
      S.menu[Number(d.dia)] = el.value || null;
      guardar();
      break;
    case 'toggle-item': {
      const it = S.mercado.find((i) => i.id === d.id);
      if (it) it.hecho = el.checked;
      guardar();
      break;
    }
    case 'fecha-cal':
      ui.fechaCal = el.value || fechaKey();
      renderCalorias();
      break;
    case 'importar': {
      const file = el.files[0];
      if (!file) return;
      file.text().then((txt) => {
        const datos = JSON.parse(txt);
        if (!datos || typeof datos !== 'object' || !Array.isArray(datos.mercado)) throw new Error('formato');
        S = { ...estadoInicial(), ...datos };
        guardar();
        toast('Datos importados');
      }).catch(() => toast('Archivo no válido'));
      break;
    }
  }
});

document.addEventListener('submit', (e) => {
  const form = e.target.closest('[data-form]');
  if (!form) return;
  e.preventDefault();
  const v = Object.fromEntries(new FormData(form));
  switch (form.dataset.form) {
    case 'item':
      toast(agregarAlMercado(v.nombre, v.cat) ? 'Agregado' : 'Ya está en la lista');
      guardar();
      $('#tab-mercado input[name=nombre]').focus();
      break;
    case 'receta': {
      const lineas = (t) => String(t || '').split('\n').map((s) => s.trim()).filter(Boolean);
      S.recetasPropias.push({
        id: 'propia-' + uid(),
        propia: true,
        nombre: v.nombre.trim(),
        minutos: Number(v.minutos) || 15,
        kcal: Number(v.kcal) || 0,
        ingredientes: lineas(v.ingredientes).map((n) => ({ nombre: n, cat: adivinarCategoria(n) })),
        pasos: lineas(v.pasos),
      });
      guardar();
      toast('Receta guardada');
      break;
    }
    case 'cal-receta': {
      const r = receta(v.id);
      if (r) registrar(r.nombre, r.kcal);
      break;
    }
    case 'cal-libre':
      registrar(v.nombre.trim(), v.kcal);
      break;
    case 'meta':
      S.meta = Math.max(800, Math.min(6000, Number(v.meta) || 2000));
      guardar();
      toast('Meta guardada');
      break;
    case 'exclusion': {
      const ex = v.ex.trim().toLowerCase();
      if (ex && !S.exclusiones.map(norm).includes(norm(ex))) S.exclusiones.push(ex);
      // Quita del menú lo que ya no está permitido.
      S.menu = S.menu.map((id) => (id && receta(id) && exclusionDe(receta(id)) ? null : id));
      guardar();
      break;
    }
  }
});

// ---------- Arranque ----------

render();
irA('menu');

if ('serviceWorker' in navigator && location.protocol.startsWith('http')) {
  navigator.serviceWorker.register('sw.js').catch(() => {});
}
