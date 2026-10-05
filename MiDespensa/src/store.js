// Estado de Mi Despensa: menú, recetas propias, lista del mercado y calorías.
// Se guarda en el teléfono con AsyncStorage.

import AsyncStorage from '@react-native-async-storage/async-storage';
import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { CATEGORIAS, DIAS, EXCLUSIONES_INICIALES, RECETAS_BASE } from './data';

const STORAGE_KEY = 'mi-despensa-v1';

// ---------- Utilidades ----------

export function norm(s) {
  return String(s ?? '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
}

export function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
}

export function fechaKey(d = new Date()) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export function sumarDias(key, n) {
  const d = new Date(key + 'T12:00:00');
  d.setDate(d.getDate() + n);
  return fechaKey(d);
}

export function indiceDia(key = fechaKey()) {
  return (new Date(key + 'T12:00:00').getDay() + 6) % 7; // 0 = lunes
}

// Adivina la categoría de un producto por palabras clave.
const PALABRAS_CATEGORIA = {
  aseoPersonal: ['papel higienico', 'jabon de bano', 'shampoo', 'champu', 'pasta dental', 'cepillo', 'desodorante', 'rastrillo', 'crema corporal', 'hilo dental', 'toalla femenina'],
  limpieza: ['detergente', 'cloro', 'lavatrastes', 'limpiador', 'bolsa', 'esponja', 'suavizante', 'servilleta', 'escoba', 'trapeador', 'fibra', 'jabon'],
  carnes: ['carne', 'bistec', 'chuleta', 'chorizo', 'jamon', 'salchicha', 'res', 'cerdo', 'tocino', 'pescado', 'filete'],
  salsas: ['salsa', 'soya', 'chipotle', 'mayonesa', 'catsup', 'mostaza', 'sal de mesa', 'pimienta', 'consome', 'vinagre'],
  verduras: ['papa', 'cebolla', 'jitomate', 'tomate', 'lechuga', 'aguacate', 'limon', 'pepino', 'zanahoria', 'cilantro', 'champinon', 'manzana', 'platano', 'naranja', 'chile', 'ajo', 'fruta', 'verdura', 'nopal', 'calabaza', 'espinaca'],
  lacteos: ['queso', 'leche', 'yogur', 'crema', 'mantequilla', 'panela'],
  panaderia: ['tortilla', 'pan', 'tostada', 'bolillo'],
  congelados: ['congelad', 'helado', 'hielo'],
  alacena: ['arroz', 'atun', 'pasta', 'aceite', 'azucar', 'cafe', 'galleta', 'cereal', 'sardina', 'lata', 'avena', 'harina', 'lenteja', 'garbanzo'],
};

export function adivinarCategoria(nombre) {
  const n = norm(nombre);
  // El orden importa: aseo primero para que "pasta dental" no caiga en alacena.
  for (const [cat, palabras] of Object.entries(PALABRAS_CATEGORIA)) {
    if (palabras.some((p) => n.includes(p))) return cat;
  }
  return 'otros';
}

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

// Copia profunda del estado (solo datos JSON).
const clonar = (o) => JSON.parse(JSON.stringify(o));

function mezclar(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

// ---------- Contexto ----------

const StoreContext = createContext(null);

export function StoreProvider({ children }) {
  const [S, setS] = useState(estadoInicial);
  const [listo, setListo] = useState(false);
  const [mensaje, setMensaje] = useState(null);
  const timer = useRef(null);

  useEffect(() => {
    AsyncStorage.getItem(STORAGE_KEY)
      .then((raw) => { if (raw) setS({ ...estadoInicial(), ...JSON.parse(raw) }); })
      .catch(() => {})
      .finally(() => setListo(true));
  }, []);

  useEffect(() => {
    if (listo) AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(S)).catch(() => {});
  }, [S, listo]);

  const toast = useCallback((msg) => {
    setMensaje(msg);
    clearTimeout(timer.current);
    timer.current = setTimeout(() => setMensaje(null), 2000);
  }, []);

  // Aplica un cambio sobre una copia del estado.
  const cambiar = (fn) => setS((prev) => {
    const copia = clonar(prev);
    fn(copia);
    return copia;
  });

  const todasRecetas = [...RECETAS_BASE, ...S.recetasPropias];
  const receta = (id) => todasRecetas.find((r) => r.id === id);

  const exclusionDe = (r, exclusiones = S.exclusiones) => {
    const textos = [r.nombre, ...(r.tags || []), ...r.ingredientes.map((i) => i.nombre)].map(norm);
    return exclusiones.find((ex) => textos.some((t) => t.includes(norm(ex))));
  };

  const permitidas = todasRecetas.filter((r) => !exclusionDe(r));

  const totalDia = (fecha) => S.registro.filter((r) => r.fecha === fecha).reduce((s, r) => s + r.kcal, 0);

  // Devuelve true si el producto se agregó (o se volvió a marcar como pendiente).
  const agregarEn = (estado, nombre, cat) => {
    nombre = nombre.trim();
    if (!nombre) return false;
    const existente = estado.mercado.find((i) => norm(i.nombre) === norm(nombre));
    if (existente) {
      if (!existente.hecho) return false;
      existente.hecho = false;
      return true;
    }
    estado.mercado.push({ id: uid(), nombre, cat: cat || adivinarCategoria(nombre), hecho: false });
    return true;
  };

  const acciones = {
    generarMenu() {
      if (!permitidas.length) return toast('No hay recetas permitidas. Revisa Ajustes.');
      let bolsa = [];
      cambiar((s) => {
        s.menu = DIAS.map(() => {
          if (!bolsa.length) bolsa = mezclar(permitidas);
          return bolsa.pop().id;
        });
      });
      toast('Menú generado');
    },
    asignarDia(i, id) {
      cambiar((s) => { s.menu[i] = id; });
    },
    limpiarMenu() {
      cambiar((s) => { s.menu = Array(7).fill(null); });
    },
    ingredientesAlMercado(recetas) {
      recetas = recetas.filter(Boolean);
      if (!recetas.length) return toast('Primero arma el menú');
      const copia = clonar(S);
      let n = 0;
      recetas.forEach((r) => r.ingredientes.forEach((i) => { if (agregarEn(copia, i.nombre, i.cat)) n++; }));
      setS(copia);
      toast(n ? `${n} producto(s) agregados al mercado` : 'Todo ya estaba en la lista');
    },
    agregarProducto(nombre, cat) {
      const copia = clonar(S);
      const ok = agregarEn(copia, nombre, cat);
      setS(copia);
      toast(ok ? `${nombre.trim()} agregado` : 'Ya está en la lista');
    },
    toggleProducto(id) {
      cambiar((s) => { const it = s.mercado.find((i) => i.id === id); if (it) it.hecho = !it.hecho; });
    },
    borrarProducto(id) {
      cambiar((s) => { s.mercado = s.mercado.filter((i) => i.id !== id); });
    },
    borrarComprados() {
      cambiar((s) => { s.mercado = s.mercado.filter((i) => !i.hecho); });
    },
    vaciarLista() {
      cambiar((s) => { s.mercado = []; });
    },
    textoLista() {
      const grupos = {};
      S.mercado.filter((i) => !i.hecho).forEach((i) => (grupos[i.cat] ||= []).push(i.nombre));
      return Object.keys(CATEGORIAS)
        .filter((c) => grupos[c])
        .map((c) => `${CATEGORIAS[c].nombre}:\n${grupos[c].map((n) => `- ${n}`).join('\n')}`)
        .join('\n\n');
    },
    registrar(nombre, kcal, fecha = fechaKey()) {
      kcal = Math.round(Number(kcal));
      if (!nombre?.trim() || !(kcal > 0)) return toast('Escribe nombre y calorías');
      cambiar((s) => { s.registro.push({ id: uid(), fecha, nombre: nombre.trim(), kcal }); });
      toast(`+${kcal} kcal: ${nombre.trim()}`);
    },
    borrarRegistro(id) {
      cambiar((s) => { s.registro = s.registro.filter((r) => r.id !== id); });
    },
    guardarReceta({ nombre, minutos, kcal, ingredientes, pasos }) {
      const lineas = (t) => String(t || '').split('\n').map((x) => x.trim()).filter(Boolean);
      const ings = lineas(ingredientes);
      if (!nombre?.trim() || !ings.length || !(Number(kcal) > 0)) {
        toast('Falta nombre, calorías o ingredientes');
        return false;
      }
      cambiar((s) => {
        s.recetasPropias.push({
          id: 'propia-' + uid(),
          propia: true,
          nombre: nombre.trim(),
          minutos: Number(minutos) || 15,
          kcal: Number(kcal),
          ingredientes: ings.map((n) => ({ nombre: n, cat: adivinarCategoria(n) })),
          pasos: lineas(pasos),
        });
      });
      toast('Receta guardada');
      return true;
    },
    borrarReceta(id) {
      cambiar((s) => {
        s.recetasPropias = s.recetasPropias.filter((r) => r.id !== id);
        s.menu = s.menu.map((x) => (x === id ? null : x));
      });
    },
    guardarMeta(meta) {
      cambiar((s) => { s.meta = Math.max(800, Math.min(6000, Number(meta) || 2000)); });
      toast('Meta guardada');
    },
    agregarExclusion(ex) {
      ex = ex.trim().toLowerCase();
      if (!ex) return;
      cambiar((s) => {
        if (!s.exclusiones.map(norm).includes(norm(ex))) s.exclusiones.push(ex);
        // Quita del menú lo que ya no está permitido.
        s.menu = s.menu.map((id) => (id && receta(id) && exclusionDe(receta(id), s.exclusiones) ? null : id));
      });
    },
    quitarExclusion(k) {
      cambiar((s) => { s.exclusiones.splice(k, 1); });
    },
    exportar() {
      return JSON.stringify(S);
    },
    importar(texto) {
      try {
        const datos = JSON.parse(texto);
        if (!datos || typeof datos !== 'object' || !Array.isArray(datos.mercado)) throw new Error('formato');
        setS({ ...estadoInicial(), ...datos });
        toast('Datos importados');
        return true;
      } catch {
        toast('El texto no es un respaldo válido');
        return false;
      }
    },
    reiniciar() {
      setS(estadoInicial());
    },
  };

  const valor = { S, listo, mensaje, toast, receta, permitidas, todasRecetas, totalDia, ...acciones };
  return <StoreContext.Provider value={valor}>{children}</StoreContext.Provider>;
}

export function useStore() {
  return useContext(StoreContext);
}
