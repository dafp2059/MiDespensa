// Control de calorías: meta diaria, registro y últimos 7 días.

import Ionicons from '@expo/vector-icons/Ionicons';
import { useState } from 'react';
import { Pressable, ScrollView, View } from 'react-native';
import { DIAS, SNACKS } from '../data';
import { fechaKey, indiceDia, sumarDias, useStore } from '../store';
import { Boton, Campo, Card, Chip, Fila, s, Subtitulo, T, useColores } from '../ui';

function nombreFecha(key) {
  const hoy = fechaKey();
  if (key === hoy) return 'Hoy';
  if (key === sumarDias(hoy, -1)) return 'Ayer';
  const [, m, d] = key.split('-');
  return `${DIAS[indiceDia(key)]} ${Number(d)}/${Number(m)}`;
}

export default function Calorias() {
  const { S, permitidas, registrar, borrarRegistro, totalDia } = useStore();
  const [fecha, setFecha] = useState(fechaKey());
  const [nombre, setNombre] = useState('');
  const [kcal, setKcal] = useState('');
  const c = useColores();

  const total = totalDia(fecha);
  const restante = S.meta - total;
  const pasado = total > S.meta;
  const entradas = S.registro.filter((r) => r.fecha === fecha);

  const semana = [6, 5, 4, 3, 2, 1, 0].map((n) => {
    const key = sumarDias(fecha, -n);
    return { key, etiqueta: DIAS[indiceDia(key)].slice(0, 2), total: totalDia(key) };
  });
  const max = Math.max(S.meta, ...semana.map((d) => d.total));
  const conDatos = semana.filter((d) => d.total > 0);
  const promedio = conDatos.length ? Math.round(conDatos.reduce((t, d) => t + d.total, 0) / conDatos.length) : 0;
  const esHoy = fecha === fechaKey();

  const agregarLibre = () => {
    registrar(nombre, kcal, fecha);
    if (nombre.trim() && Number(kcal) > 0) { setNombre(''); setKcal(''); }
  };

  return (
    <ScrollView contentContainerStyle={s.pantalla} keyboardShouldPersistTaps="handled">
      <Fila style={{ justifyContent: 'space-between', marginBottom: 10 }}>
        <Pressable onPress={() => setFecha(sumarDias(fecha, -1))} hitSlop={10} accessibilityLabel="Día anterior">
          <Ionicons name="chevron-back" size={26} color={c.accent} />
        </Pressable>
        <T style={{ fontSize: 18, fontWeight: '700' }}>{nombreFecha(fecha)}</T>
        <Pressable onPress={() => !esHoy && setFecha(sumarDias(fecha, 1))} hitSlop={10} accessibilityLabel="Día siguiente">
          <Ionicons name="chevron-forward" size={26} color={esHoy ? c.border : c.accent} />
        </Pressable>
      </Fila>

      <Card>
        <Fila style={{ justifyContent: 'space-between' }}>
          <T style={{ fontSize: 30, fontWeight: '700' }}>{total}</T>
          <T muted>meta {S.meta} kcal</T>
        </Fila>
        <View style={{ height: 14, backgroundColor: c.border, borderRadius: 999, overflow: 'hidden', marginVertical: 8 }}>
          <View style={{ height: '100%', width: `${Math.min(100, (total / S.meta) * 100)}%`, backgroundColor: pasado ? c.warn : c.accent }} />
        </View>
        <T muted>{restante >= 0 ? `Te quedan ${restante} kcal` : `Te pasaste ${-restante} kcal`}</T>
      </Card>

      <Subtitulo>Mis recetas</Subtitulo>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap' }}>
        {permitidas.map((r) => <Chip key={r.id} texto={`${r.nombre} · ${r.kcal}`} onPress={() => registrar(r.nombre, r.kcal, fecha)} />)}
      </View>

      <Subtitulo>Antojos y extras</Subtitulo>
      <View style={{ flexDirection: 'row', flexWrap: 'wrap' }}>
        {SNACKS.map((x) => <Chip key={x.nombre} texto={`${x.nombre} · ${x.kcal}`} onPress={() => registrar(x.nombre, x.kcal, fecha)} />)}
      </View>

      <Subtitulo>Otra cosa</Subtitulo>
      <Fila>
        <Campo style={{ flex: 1 }} placeholder="¿Qué comiste?" value={nombre} onChangeText={setNombre} />
        <Campo style={{ width: 80 }} placeholder="kcal" keyboardType="number-pad" value={kcal} onChangeText={setKcal} />
        <Boton primario titulo="+" onPress={agregarLibre} />
      </Fila>

      <Subtitulo>Registro del día</Subtitulo>
      <Card style={{ paddingVertical: 4 }}>
        {entradas.length === 0 && <T muted style={{ textAlign: 'center', paddingVertical: 12 }}>Nada registrado este día.</T>}
        {entradas.map((e) => (
          <Fila key={e.id} style={{ paddingVertical: 8 }}>
            <T style={{ flex: 1 }}>{e.nombre}</T>
            <T muted>{e.kcal} kcal</T>
            <Pressable onPress={() => borrarRegistro(e.id)} hitSlop={8} accessibilityLabel="Quitar">
              <Ionicons name="close" size={20} color={c.muted} />
            </Pressable>
          </Fila>
        ))}
      </Card>

      <Subtitulo>Últimos 7 días</Subtitulo>
      <Card>
        <Fila style={{ alignItems: 'flex-end', height: 120, gap: 6 }}>
          {semana.map((d) => (
            <View key={d.key} style={{ flex: 1, alignItems: 'center', justifyContent: 'flex-end', height: '100%' }}>
              <View style={{ width: '100%', minHeight: 2, height: `${(d.total / max) * 85}%`, borderTopLeftRadius: 6, borderTopRightRadius: 6, backgroundColor: d.total > S.meta ? c.warn : c.accent }} />
              <T muted style={{ fontSize: 11, marginTop: 2 }}>{d.etiqueta}</T>
            </View>
          ))}
        </Fila>
        <T muted style={{ marginTop: 8 }}>Promedio de los días con registro: {promedio} kcal</T>
      </Card>
      <T muted>Las calorías de las recetas son aproximadas por porción.</T>
    </ScrollView>
  );
}
