// Mi Despensa: inventario de lo que hay en casa, con descuento y fotos.
// Usa las funciones globales de app.js (S, guardar, toast, norm, esc…), que se cargan después.

// ---------- Fotos (IndexedDB) ----------

const FOTOS = new Map(); // id del producto → dataURL

const fotosDB = {
  abrir() {
    if (!this.promesa) {
      this.promesa = new Promise((resolve, reject) => {
        const req = indexedDB.open('mi-despensa-fotos', 1);
        req.onupgradeneeded = () => req.result.createObjectStore('fotos');
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
      });
    }
    return this.promesa;
  },
  async op(modo, fn) {
    const db = await this.abrir();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('fotos', modo);
      const req = fn(tx.objectStore('fotos'));
      tx.oncomplete = () => resolve(req?.result);
      tx.onerror = () => reject(tx.error);
    });
  },
  guardar(id, data) { return this.op('readwrite', (st) => st.put(data, id)); },
  borrar(id) { return this.op('readwrite', (st) => st.delete(id)); },
  async todas() {
    const db = await this.abrir();
    return new Promise((resolve, reject) => {
      const res = {};
      const req = db.transaction('fotos').objectStore('fotos').openCursor();
      req.onsuccess = () => {
        const c = req.result;
        if (!c) return resolve(res);
        res[c.key] = c.value;
        c.continue();
      };
      req.onerror = () => reject(req.error);
    });
  },
};

async function cargarFotos() {
  try {
    const todas = await fotosDB.todas();
    Object.entries(todas).forEach(([id, data]) => FOTOS.set(id, data));
    if (FOTOS.size) render();
  } catch {
    // Sin IndexedDB las fotos solo duran mientras la app esté abierta.
  }
}

async function ponerFoto(id, data) {
  if (data) FOTOS.set(id, data); else FOTOS.delete(id);
  try {
    await (data ? fotosDB.guardar(id, data) : fotosDB.borrar(id));
  } catch {
    toast('No se pudo guardar la foto en este dispositivo');
  }
}

// Reduce la foto para que ocupe poco (≈ 30–60 KB).
function comprimirFoto(file, lado = 480) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      const escala = Math.min(1, lado / Math.max(img.width, img.height));
      const c = document.createElement('canvas');
      c.width = Math.round(img.width * escala);
      c.height = Math.round(img.height * escala);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      URL.revokeObjectURL(url);
      resolve(c.toDataURL('image/jpeg', 0.75));
    };
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('imagen')); };
    img.src = url;
  });
}

let fotoDestino = null; // id del producto que recibirá la foto

function pedirFoto(id) {
  fotoDestino = id;
  const input = $('#camara');
  input.value = '';
  input.click();
}

async function recibirFoto(file) {
  const id = fotoDestino;
  fotoDestino = null;
  if (!file || !id) return;
  try {
    await ponerFoto(id, await comprimirFoto(file));
    render();
    if ($('#dlg').open) abrirProducto(id);
    toast('Foto guardada');
  } catch {
    toast('No se pudo leer la foto');
  }
}

// ---------- Cantidades ----------

const FRACCIONES = { '½': 0.5, '¼': 0.25, '¾': 0.75 };

// "3 kg" → { n: 3, u: 'kg' } · "1 bolsa (3 kg)" → { n: 1, u: 'bolsa' }
function leerCantidad(texto) {
  const t = String(texto || '').trim();
  const m = t.match(/^(\d+(?:[.,]\d+)?|[½¼¾])\s*(.*)$/);
  if (!m) return { n: 1, u: t ? t.replace(/\(.*?\)/g, '').trim() : 'pieza' };
  const n = FRACCIONES[m[1]] ?? parseFloat(m[1].replace(',', '.'));
  const u = m[2].replace(/\(.*?\)/g, '').replace(/\bpor semana\b/, '').trim() || 'pieza';
  return { n, u };
}

function paso(item) {
  return /^(kg|kilos?)$/i.test(item.unidad) ? 0.25 : 1;
}

function redondear(n) {
  return Math.round(n * 100) / 100;
}

function formatoCant(n) {
  const r = redondear(n);
  return Number.isInteger(r) ? String(r) : String(r).replace('.', ',');
}

function estadoInv(item) {
  if (item.cantidad <= 0) return 'agotado';
  const limite = Math.max(paso(item), (item.inicial || 0) * 0.25);
  return item.cantidad <= limite ? 'poco' : 'ok';
}

const ETIQUETA_ESTADO = { agotado: 'Se acabó', poco: 'Por acabarse', ok: '' };

// ---------- Inventario ----------

function itemDespensa(nombre) {
  return S.despensa.find((i) => norm(i.nombre) === norm(nombre));
}

// Suma al inventario y devuelve la cantidad agregada.
function sumarADespensa(nombre, cat, cantTexto) {
  const { n, u } = leerCantidad(cantTexto || buscarEnCatalogo(nombre)?.q);
  const existente = itemDespensa(nombre);
  if (existente) {
    existente.cantidad = redondear(Math.max(0, existente.cantidad) + n);
    existente.inicial = existente.cantidad;
  } else {
    S.despensa.push({ id: uid(), nombre, cat: cat || adivinarCategoria(nombre), cantidad: n, unidad: u, inicial: n });
  }
  return n;
}

function restarDeDespensa(nombre, n) {
  const it = itemDespensa(nombre);
  if (it) it.cantidad = redondear(Math.max(0, it.cantidad - n));
}

// Llamado al marcar o desmarcar un producto como comprado en el mercado.
function compraMarcada(itemMercado) {
  if (itemMercado.hecho) {
    itemMercado.sumado = sumarADespensa(itemMercado.nombre, itemMercado.cat, itemMercado.cant);
  } else if (itemMercado.sumado) {
    restarDeDespensa(itemMercado.nombre, itemMercado.sumado);
    itemMercado.sumado = 0;
  }
}

function cambiarCantidad(id, delta) {
  const it = S.despensa.find((i) => i.id === id);
  if (!it) return;
  const antes = it.cantidad;
  it.cantidad = redondear(Math.max(0, it.cantidad + delta));
  if (delta > 0 && it.cantidad > (it.inicial || 0)) it.inicial = it.cantidad;
  if (antes > 0 && it.cantidad === 0) avisarAgotado(it);
  guardar();
}

function avisarAgotado(it) {
  if (S.autoLista !== false && agregarAlMercado(it.nombre, it.cat)) {
    toast(`Se acabó ${it.nombre}: lo agregué a la lista del mercado`);
  } else {
    toast(`Se acabó ${it.nombre}`);
  }
}

function borrarDeDespensa(id) {
  S.despensa = S.despensa.filter((i) => i.id !== id);
  ponerFoto(id, null);
  guardar();
}

// ---------- Vista ----------

function miniatura(it, clase = 'foto') {
  const f = FOTOS.get(it.id);
  return f
    ? `<button class="${clase}" data-action="ver-producto" data-id="${it.id}" aria-label="Ver ${esc(it.nombre)}"><img src="${f}" alt=""></button>`
    : `<button class="${clase} vacia" data-action="tomar-foto" data-id="${it.id}" aria-label="Tomar foto de ${esc(it.nombre)}">📷</button>`;
}

function renderDespensa() {
  const todos = S.despensa;
  const cuenta = { ok: 0, poco: 0, agotado: 0 };
  todos.forEach((i) => cuenta[estadoInv(i)]++);
  const f = ui.filtroDespensa || 'todo';

  $('#tab-despensa').innerHTML = `
    <div class="row spread"><h2>Mi despensa</h2><span class="muted">${todos.length} producto(s)</span></div>
    <div class="row wrap" style="margin-bottom:10px">
      <span class="badge">✓ ${cuenta.ok} con existencia</span>
      <span class="badge warn">⚠️ ${cuenta.poco} por acabarse</span>
      <span class="badge danger">✕ ${cuenta.agotado} agotados</span>
    </div>
    <div class="segmented">
      ${[['todo', 'Todo'], ['poco', 'Por acabarse'], ['agotado', 'Agotado']].map(([k, t]) =>
        `<button data-action="filtro-despensa" data-f="${k}" class="${f === k ? 'active' : ''}">${t}</button>`).join('')}
    </div>
    <input type="search" placeholder="Buscar en mi despensa…" value="${esc(ui.buscarDespensa || '')}" data-input="buscar-despensa" style="width:100%;margin-bottom:10px">
    <div id="despensa-lista"></div>
    <details class="card" style="margin-top:12px">
      <summary><strong>➕ Agregar algo que ya tengo</strong></summary>
      <form data-form="despensa" style="margin-top:10px">
        <input name="nombre" placeholder="Producto" list="lista-catalogo-inv" autocomplete="off" required style="width:100%;margin-bottom:8px">
        <datalist id="lista-catalogo-inv">${catalogoPermitido().map((x) => `<option value="${esc(x.n)}"></option>`).join('')}</datalist>
        <div class="row">
          <input name="cantidad" type="number" step="any" min="0" placeholder="Cantidad" class="grow" required>
          <input name="unidad" placeholder="Unidad (kg, latas…)" class="grow">
          <button class="btn primary">+</button>
        </div>
      </form>
    </details>
    ${S.mercado.some((i) => i.hecho) ? '' : '<p class="muted">Tip: al marcar productos como comprados en el Mercado, se suman aquí solos.</p>'}`;
  renderListaDespensa();
}

function renderListaDespensa() {
  const f = ui.filtroDespensa || 'todo';
  const q = norm(ui.buscarDespensa || '');
  const items = S.despensa.filter((i) => (f === 'todo' || estadoInv(i) === f) && (!q || norm(i.nombre).includes(q)));
  const orden = { agotado: 0, poco: 1, ok: 2 };

  const grupos = Object.keys(CATEGORIAS).map((cat) => {
    const lista = items.filter((i) => i.cat === cat)
      .sort((a, b) => orden[estadoInv(a)] - orden[estadoInv(b)] || a.nombre.localeCompare(b.nombre));
    if (!lista.length) return '';
    return `
      <h3>${CATEGORIAS[cat].nombre}</h3>
      <div class="card">
        ${lista.map((i) => {
          const e = estadoInv(i);
          return `
          <div class="item inv ${e}">
            ${miniatura(i)}
            <div class="grow" data-action="ver-producto" data-id="${i.id}">
              <div class="name">${esc(i.nombre)}</div>
              <div class="muted">${formatoCant(i.cantidad)} ${esc(i.unidad)}${ETIQUETA_ESTADO[e] ? ` · <span class="estado">${ETIQUETA_ESTADO[e]}</span>` : ''}</div>
            </div>
            <button class="btn small" data-action="inv-restar" data-id="${i.id}" aria-label="Restar" ${i.cantidad <= 0 ? 'disabled' : ''}>−</button>
            <button class="btn small" data-action="inv-sumar" data-id="${i.id}" aria-label="Sumar">+</button>
          </div>`;
        }).join('')}
      </div>`;
  }).join('');

  const vacio = S.despensa.length
    ? '<p class="empty">Nada coincide con el filtro.</p>'
    : '<p class="empty">Tu despensa está vacía. Marca productos como comprados en el Mercado o agrégalos abajo.</p>';
  $('#despensa-lista').innerHTML = grupos || vacio;
}

function abrirProducto(id) {
  const it = S.despensa.find((i) => i.id === id);
  if (!it) return;
  const f = FOTOS.get(id);
  const dlg = $('#dlg');
  dlg.innerHTML = `
    <form method="dialog" data-form="producto" data-id="${id}">
      <div class="row spread"><strong>${esc(it.nombre)}</strong><button type="button" class="x" data-action="cerrar-dlg" aria-label="Cerrar">✕</button></div>
      ${f ? `<img class="foto-grande" src="${f}" alt="Foto de ${esc(it.nombre)}">` : '<div class="foto-grande vacia">Sin foto</div>'}
      <div class="row wrap" style="margin:8px 0">
        <button type="button" class="btn small" data-action="tomar-foto" data-id="${id}">📷 ${f ? 'Cambiar foto' : 'Tomar foto'}</button>
        ${f ? `<button type="button" class="btn small danger" data-action="quitar-foto" data-id="${id}">Quitar foto</button>` : ''}
      </div>
      <label class="muted">Cantidad que tengo</label>
      <div class="row" style="margin:4px 0 10px">
        <input name="cantidad" type="number" step="any" min="0" value="${redondear(it.cantidad)}" class="grow">
        <input name="unidad" value="${esc(it.unidad)}" class="grow">
      </div>
      <div class="row wrap">
        <button class="btn primary" value="guardar">Guardar</button>
        <button type="button" class="btn" data-action="inv-a-lista" data-id="${id}">🛒 A la lista</button>
        <button type="button" class="btn danger" data-action="inv-borrar" data-id="${id}">Eliminar</button>
      </div>
    </form>`;
  if (!dlg.open) dlg.showModal();
}

// ---------- Acciones (app.js las une a las suyas) ----------

const accionesDespensa = {
  'filtro-despensa': (d) => { ui.filtroDespensa = d.f; renderDespensa(); },
  'inv-restar': (d) => {
    const it = S.despensa.find((i) => i.id === d.id);
    if (it) cambiarCantidad(d.id, -Math.min(paso(it), it.cantidad));
  },
  'inv-sumar': (d) => {
    const it = S.despensa.find((i) => i.id === d.id);
    if (it) cambiarCantidad(d.id, paso(it));
  },
  'tomar-foto': (d) => pedirFoto(d.id),
  'cerrar-dlg': () => $('#dlg').close(),
  'quitar-foto': async (d) => { await ponerFoto(d.id, null); render(); abrirProducto(d.id); },
  'ver-producto': (d) => abrirProducto(d.id),
  'inv-a-lista': (d) => {
    const it = S.despensa.find((i) => i.id === d.id);
    if (!it) return;
    toast(agregarAlMercado(it.nombre, it.cat) ? `${it.nombre} agregado a la lista` : 'Ya está en la lista');
    guardar();
  },
  'inv-borrar': (d) => {
    if (!confirm('¿Eliminar este producto de tu despensa?')) return;
    $('#dlg').close();
    borrarDeDespensa(d.id);
  },
};

function enviarFormDespensa(form, v) {
  if (form.dataset.form === 'despensa') {
    const n = parseFloat(String(v.cantidad).replace(',', '.'));
    if (!v.nombre.trim() || !(n >= 0)) return toast('Escribe producto y cantidad');
    const unidad = v.unidad.trim() || leerCantidad(buscarEnCatalogo(v.nombre)?.q).u;
    sumarADespensa(v.nombre.trim(), null, `${n} ${unidad}`);
    guardar();
    toast(`${v.nombre.trim()} en tu despensa`);
    return true;
  }
  if (form.dataset.form === 'producto') {
    const it = S.despensa.find((i) => i.id === form.dataset.id);
    if (!it) return true;
    const n = parseFloat(String(v.cantidad).replace(',', '.'));
    if (n >= 0) {
      const antes = it.cantidad;
      it.cantidad = redondear(n);
      if (it.cantidad > (it.inicial || 0)) it.inicial = it.cantidad;
      if (antes > 0 && it.cantidad === 0) avisarAgotado(it);
    }
    if (v.unidad.trim()) it.unidad = v.unidad.trim();
    $('#dlg').close();
    guardar();
    return true;
  }
  return false;
}
