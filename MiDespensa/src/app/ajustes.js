// Ajustes: meta de calorías, alimentos excluidos y respaldo.

import { useState } from 'react';
import { Alert, ScrollView, Share, View } from 'react-native';
import { useStore } from '../store';
import { Boton, Campo, Card, Chip, Fila, s, Subtitulo, T, Titulo } from '../ui';

export default function Ajustes() {
  const { S, guardarMeta, agregarExclusion, quitarExclusion, exportar, importar, reiniciar } = useStore();
  const [meta, setMeta] = useState(null); // null = mostrar la meta guardada
  const [ex, setEx] = useState('');
  const [respaldo, setRespaldo] = useState('');

  const borrarTodo = () => Alert.alert('Borrar todo', 'Se borrarán menú, lista, recetas propias y registro de calorías.', [
    { text: 'Cancelar', style: 'cancel' },
    { text: 'Borrar', style: 'destructive', onPress: reiniciar },
  ]);

  return (
    <ScrollView contentContainerStyle={s.pantalla} keyboardShouldPersistTaps="handled">
      <Titulo>Ajustes</Titulo>

      <Subtitulo>Meta diaria de calorías</Subtitulo>
      <Fila>
        <Campo style={{ flex: 1 }} keyboardType="number-pad" value={meta ?? String(S.meta)} onChangeText={setMeta} />
        <Boton primario titulo="Guardar" onPress={() => { guardarMeta(meta ?? S.meta); setMeta(null); }} />
      </Fila>

      <Subtitulo>Alimentos que no como</Subtitulo>
      <Card>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', marginBottom: 8 }}>
          {S.exclusiones.length === 0 && <T muted>Ninguno</T>}
          {S.exclusiones.map((e, k) => <Chip key={e} texto={`${e}  ✕`} onPress={() => quitarExclusion(k)} />)}
        </View>
        <Fila>
          <Campo style={{ flex: 1 }} placeholder="Ej. cerdo, lenteja…" value={ex} onChangeText={setEx}
            onSubmitEditing={() => { agregarExclusion(ex); setEx(''); }} />
          <Boton primario titulo="Agregar" onPress={() => { agregarExclusion(ex); setEx(''); }} />
        </Fila>
        <T muted style={{ marginTop: 8 }}>Las recetas con estos ingredientes no aparecen en el menú.</T>
      </Card>

      <Subtitulo>Respaldo</Subtitulo>
      <Card style={{ gap: 8 }}>
        <T muted>Tus datos se guardan solo en este iPhone. Exporta un respaldo (por ejemplo a Notas) de vez en cuando.</T>
        <Boton titulo="⬇️ Exportar respaldo" onPress={() => Share.share({ message: exportar() }).catch(() => {})} />
        <Campo multiline style={{ minHeight: 70 }} placeholder="Pega aquí un respaldo para importarlo" value={respaldo} onChangeText={setRespaldo} />
        <Boton titulo="⬆️ Importar" onPress={() => { if (importar(respaldo)) setRespaldo(''); }} />
        <Boton peligro titulo="Borrar todo" onPress={borrarTodo} />
      </Card>
    </ScrollView>
  );
}
