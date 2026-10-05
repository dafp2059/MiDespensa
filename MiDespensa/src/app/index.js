// Menú de la semana.

import { useState } from 'react';
import { FlatList, Modal, Pressable, ScrollView, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { DIAS } from '../data';
import { indiceDia, useStore } from '../store';
import { Badge, Boton, Card, Fila, s, T, Titulo, useColores } from '../ui';

function SelectorReceta({ dia, onCerrar }) {
  const { permitidas, asignarDia, S } = useStore();
  const c = useColores();
  const elegir = (id) => { asignarDia(dia, id); onCerrar(); };
  return (
    <Modal visible animationType="slide" presentationStyle="pageSheet" onRequestClose={onCerrar}>
      <SafeAreaView style={{ flex: 1, backgroundColor: c.bg }}>
        <Fila style={{ justifyContent: 'space-between', padding: 16 }}>
          <Titulo style={{ marginBottom: 0 }}>{DIAS[dia]}</Titulo>
          <Boton titulo="Cerrar" chico onPress={onCerrar} />
        </Fila>
        <FlatList
          contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 40 }}
          data={[{ id: null, nombre: '— Sin plan —' }, ...permitidas]}
          keyExtractor={(r) => r.id ?? 'ninguna'}
          renderItem={({ item: r }) => (
            <Pressable onPress={() => elegir(r.id)}>
              <Card destacada={S.menu[dia] === r.id}>
                <Fila>
                  <T style={{ flex: 1 }}>{r.nombre}</T>
                  {r.kcal ? <Badge texto={`${r.minutos} min`} /> : null}
                  {r.kcal ? <Badge texto={`${r.kcal} kcal`} /> : null}
                </Fila>
              </Card>
            </Pressable>
          )}
        />
      </SafeAreaView>
    </Modal>
  );
}

export default function Menu() {
  const { S, receta, generarMenu, limpiarMenu, ingredientesAlMercado, registrar } = useStore();
  const [eligiendo, setEligiendo] = useState(null);
  const hoy = indiceDia();
  const totalSemana = S.menu.reduce((t, id) => t + (receta(id)?.kcal || 0), 0);

  return (
    <ScrollView contentContainerStyle={s.pantalla}>
      <Fila style={{ justifyContent: 'space-between', marginBottom: 10 }}>
        <Titulo style={{ marginBottom: 0 }}>Menú de la semana</Titulo>
        <T muted>{totalSemana} kcal</T>
      </Fila>
      <Fila wrap style={{ marginBottom: 12 }}>
        <Boton primario titulo="🎲 Generar" onPress={generarMenu} />
        <Boton titulo="🛒 Pasar al mercado" onPress={() => ingredientesAlMercado(S.menu.map(receta))} />
        <Boton titulo="Limpiar" onPress={limpiarMenu} />
      </Fila>

      {DIAS.map((dia, i) => {
        const r = receta(S.menu[i]);
        return (
          <Pressable key={dia} onPress={() => setEligiendo(i)}>
            <Card destacada={i === hoy}>
              <Fila style={{ alignItems: 'flex-start' }}>
                <View style={{ width: 92 }}>
                  <T style={{ fontWeight: '600' }}>{dia}</T>
                  {i === hoy && <T muted>Hoy</T>}
                </View>
                <View style={{ flex: 1, gap: 6 }}>
                  <T muted={!r}>{r ? r.nombre : 'Toca para elegir'}</T>
                  {r && (
                    <Fila wrap>
                      <Badge texto={`${r.minutos} min`} />
                      <Badge texto={`${r.kcal} kcal`} />
                      {i === hoy && <Boton chico titulo="Lo comí ✓" onPress={() => registrar(r.nombre, r.kcal)} />}
                    </Fila>
                  )}
                </View>
              </Fila>
            </Card>
          </Pressable>
        );
      })}

      <T muted>Excluido: {S.exclusiones.join(', ') || 'nada'}. Cámbialo en Ajustes.</T>
      {eligiendo !== null && <SelectorReceta dia={eligiendo} onCerrar={() => setEligiendo(null)} />}
    </ScrollView>
  );
}
