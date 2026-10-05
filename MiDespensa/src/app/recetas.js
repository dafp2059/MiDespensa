// Recetas: detalle, enviar al mercado y agregar recetas propias.

import { useState } from 'react';
import { Alert, KeyboardAvoidingView, Pressable, ScrollView } from 'react-native';
import { useStore } from '../store';
import { Badge, Boton, Campo, Card, Fila, s, Subtitulo, T, Titulo } from '../ui';

function TarjetaReceta({ r }) {
  const { ingredientesAlMercado, registrar, borrarReceta } = useStore();
  const [abierta, setAbierta] = useState(false);
  const eliminar = () => Alert.alert('Eliminar receta', `¿Eliminar “${r.nombre}”?`, [
    { text: 'Cancelar', style: 'cancel' },
    { text: 'Eliminar', style: 'destructive', onPress: () => borrarReceta(r.id) },
  ]);

  return (
    <Card>
      <Pressable onPress={() => setAbierta(!abierta)}>
        <Fila>
          <T style={{ flex: 1, fontWeight: '600' }}>{r.nombre}</T>
          <Badge texto={`${r.minutos} min`} />
          <Badge texto={`${r.kcal} kcal`} />
        </Fila>
      </Pressable>
      {abierta && (
        <>
          <Subtitulo>Ingredientes</Subtitulo>
          {r.ingredientes.map((i) => <T key={i.nombre}>• {i.nombre}</T>)}
          {r.pasos?.length > 0 && (
            <>
              <Subtitulo>Pasos</Subtitulo>
              {r.pasos.map((p, k) => <T key={k}>{k + 1}. {p}</T>)}
            </>
          )}
          <Fila wrap style={{ marginTop: 12 }}>
            <Boton chico titulo="🛒 Al mercado" onPress={() => ingredientesAlMercado([r])} />
            <Boton chico titulo="🔥 Lo comí hoy" onPress={() => registrar(r.nombre, r.kcal)} />
            {r.propia && <Boton chico peligro titulo="Eliminar" onPress={eliminar} />}
          </Fila>
        </>
      )}
    </Card>
  );
}

const VACIA = { nombre: '', minutos: '', kcal: '', ingredientes: '', pasos: '' };

export default function Recetas() {
  const { permitidas, todasRecetas, guardarReceta } = useStore();
  const [f, setF] = useState(VACIA);
  const ocultas = todasRecetas.length - permitidas.length;
  const set = (k) => (v) => setF({ ...f, [k]: v });

  return (
    <KeyboardAvoidingView behavior="padding" style={{ flex: 1 }} keyboardVerticalOffset={100}>
      <ScrollView contentContainerStyle={s.pantalla} keyboardShouldPersistTaps="handled">
        <Titulo>Recetas de 15 minutos o menos</Titulo>
        {permitidas.map((r) => <TarjetaReceta key={r.id} r={r} />)}
        {ocultas > 0 && <T muted>{ocultas} receta(s) ocultas por tus exclusiones.</T>}

        <Titulo style={{ marginTop: 20 }}>Agregar mi receta</Titulo>
        <Card style={{ gap: 8 }}>
          <Campo placeholder="Nombre" value={f.nombre} onChangeText={set('nombre')} />
          <Fila>
            <Campo style={{ flex: 1 }} placeholder="Minutos" keyboardType="number-pad" value={f.minutos} onChangeText={set('minutos')} />
            <Campo style={{ flex: 1 }} placeholder="kcal por porción" keyboardType="number-pad" value={f.kcal} onChangeText={set('kcal')} />
          </Fila>
          <Campo multiline style={{ minHeight: 80 }} placeholder="Ingredientes (uno por línea)" value={f.ingredientes} onChangeText={set('ingredientes')} />
          <Campo multiline style={{ minHeight: 80 }} placeholder="Pasos (uno por línea, opcional)" value={f.pasos} onChangeText={set('pasos')} />
          <Boton primario titulo="Guardar receta" onPress={() => { if (guardarReceta(f)) setF(VACIA); }} />
        </Card>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
