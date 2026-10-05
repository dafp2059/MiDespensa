// Lista del mercado: comida, aseo personal y limpieza.

import Ionicons from '@expo/vector-icons/Ionicons';
import { useState } from 'react';
import { Alert, Pressable, ScrollView, Share, View } from 'react-native';
import { ASEO_SUGERIDOS, CATEGORIAS } from '../data';
import { useStore } from '../store';
import { Boton, Campo, Card, Chip, Fila, s, Subtitulo, T, Titulo, useColores } from '../ui';

const FILTROS = [['todo', 'Todo'], ['comida', 'Comida'], ['aseo', 'Aseo']];

function Segmentado({ valor, onCambio }) {
  const c = useColores();
  return (
    <Fila style={{ borderWidth: 1, borderColor: c.border, borderRadius: 10, overflow: 'hidden', gap: 0, marginBottom: 10 }}>
      {FILTROS.map(([k, t]) => (
        <Pressable key={k} onPress={() => onCambio(k)} style={{ flex: 1, paddingVertical: 8, alignItems: 'center', backgroundColor: valor === k ? c.accent : c.card }}>
          <T style={{ color: valor === k ? '#fff' : c.text }}>{t}</T>
        </Pressable>
      ))}
    </Fila>
  );
}

function Producto({ item }) {
  const { toggleProducto, borrarProducto } = useStore();
  const c = useColores();
  return (
    <Fila style={{ paddingVertical: 8 }}>
      <Pressable onPress={() => toggleProducto(item.id)} hitSlop={8} accessibilityRole="checkbox" accessibilityState={{ checked: item.hecho }}>
        <Ionicons name={item.hecho ? 'checkbox' : 'square-outline'} size={24} color={c.accent} />
      </Pressable>
      <T onPress={() => toggleProducto(item.id)} style={{ flex: 1, textDecorationLine: item.hecho ? 'line-through' : 'none', color: item.hecho ? c.muted : c.text }}>
        {item.nombre}
      </T>
      <Pressable onPress={() => borrarProducto(item.id)} hitSlop={8} accessibilityLabel="Quitar">
        <Ionicons name="close" size={20} color={c.muted} />
      </Pressable>
    </Fila>
  );
}

export default function Mercado() {
  const { S, agregarProducto, borrarComprados, vaciarLista, textoLista, toast } = useStore();
  const [filtro, setFiltro] = useState('todo');
  const [nombre, setNombre] = useState('');
  const [cat, setCat] = useState('');
  const c = useColores();

  const visible = (k) => filtro === 'todo' || CATEGORIAS[k]?.tipo === filtro || (filtro === 'comida' && CATEGORIAS[k]?.tipo === 'otro');
  const items = S.mercado.filter((i) => visible(i.cat));
  const pendientes = items.filter((i) => !i.hecho).length;

  const agregar = () => {
    if (!nombre.trim()) return;
    agregarProducto(nombre, cat);
    setNombre('');
  };

  const compartir = () => {
    const texto = textoLista();
    if (!texto) return toast('No hay pendientes');
    Share.share({ message: `Lista del mercado\n\n${texto}` }).catch(() => {});
  };

  const vaciar = () => Alert.alert('Vaciar lista', '¿Borrar todos los productos?', [
    { text: 'Cancelar', style: 'cancel' },
    { text: 'Vaciar', style: 'destructive', onPress: vaciarLista },
  ]);

  return (
    <ScrollView contentContainerStyle={s.pantalla} keyboardShouldPersistTaps="handled">
      <Fila style={{ justifyContent: 'space-between' }}>
        <Titulo>Lista del mercado</Titulo>
        <T muted>{pendientes} pendiente(s)</T>
      </Fila>
      <Segmentado valor={filtro} onCambio={setFiltro} />

      <Fila>
        <Campo style={{ flex: 1 }} placeholder="Agregar producto…" value={nombre} onChangeText={setNombre} onSubmitEditing={agregar} returnKeyType="done" />
        <Boton primario titulo="+" onPress={agregar} />
      </Fila>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginTop: 8 }} keyboardShouldPersistTaps="handled">
        {[['', 'Auto'], ...Object.entries(CATEGORIAS).map(([k, v]) => [k, v.nombre])].map(([k, t]) => (
          <Pressable key={k || 'auto'} onPress={() => setCat(k)}
            style={[s.chip, { borderColor: cat === k ? c.accent : c.border, backgroundColor: cat === k ? c.accentSoft : c.card }]}>
            <T style={{ fontSize: 13, color: cat === k ? c.accent : c.text }}>{t}</T>
          </Pressable>
        ))}
      </ScrollView>

      {filtro !== 'comida' && (
        <>
          <Subtitulo>Aseo y limpieza rápidos</Subtitulo>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap' }}>
            {ASEO_SUGERIDOS.map((a) => <Chip key={a.nombre} texto={`+ ${a.nombre}`} onPress={() => agregarProducto(a.nombre, a.cat)} />)}
          </View>
        </>
      )}

      {items.length === 0 && (
        <T muted style={{ textAlign: 'center', marginTop: 20 }}>
          La lista está vacía. Agrega productos o usa “Pasar al mercado” en el Menú.
        </T>
      )}

      {Object.keys(CATEGORIAS).map((k) => {
        const lista = items.filter((i) => i.cat === k).sort((a, b) => a.hecho - b.hecho);
        if (!lista.length) return null;
        return (
          <View key={k}>
            <Subtitulo>{CATEGORIAS[k].nombre}</Subtitulo>
            <Card style={{ paddingVertical: 4 }}>
              {lista.map((i) => <Producto key={i.id} item={i} />)}
            </Card>
          </View>
        );
      })}

      {S.mercado.length > 0 && (
        <Fila wrap style={{ marginTop: 12 }}>
          <Boton titulo="📤 Compartir" onPress={compartir} />
          <Boton titulo="Borrar comprados" onPress={borrarComprados} />
          <Boton peligro titulo="Vaciar" onPress={vaciar} />
        </Fila>
      )}
    </ScrollView>
  );
}
