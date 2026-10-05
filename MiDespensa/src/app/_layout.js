import Ionicons from '@expo/vector-icons/Ionicons';
import { Tabs } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { StyleSheet, Text, View } from 'react-native';
import { StoreProvider, useStore } from '../store';
import { useColores } from '../ui';

const PESTANAS = [
  ['index', 'Menú', 'calendar'],
  ['recetas', 'Recetas', 'restaurant'],
  ['mercado', 'Mercado', 'cart'],
  ['calorias', 'Calorías', 'flame'],
  ['ajustes', 'Ajustes', 'settings'],
];

function Aviso() {
  const { mensaje } = useStore();
  const c = useColores();
  if (!mensaje) return null;
  return (
    <View pointerEvents="none" style={estilos.aviso}>
      <Text style={[estilos.avisoTexto, { backgroundColor: c.text, color: c.bg }]}>{mensaje}</Text>
    </View>
  );
}

export default function Layout() {
  const c = useColores();
  return (
    <StoreProvider>
      <StatusBar style="auto" />
      <View style={{ flex: 1, backgroundColor: c.bg }}>
        <Tabs
          screenOptions={{
            tabBarActiveTintColor: c.accent,
            tabBarInactiveTintColor: c.muted,
            tabBarStyle: { backgroundColor: c.card, borderTopColor: c.border },
            headerStyle: { backgroundColor: c.bg },
            headerTintColor: c.text,
            headerShadowVisible: false,
            sceneStyle: { backgroundColor: c.bg },
          }}
        >
          {PESTANAS.map(([name, title, icon]) => (
            <Tabs.Screen
              key={name}
              name={name}
              options={{
                title,
                headerTitle: name === 'index' ? 'Mi Despensa' : title,
                tabBarIcon: ({ color, size }) => <Ionicons name={icon} size={size} color={color} />,
              }}
            />
          ))}
        </Tabs>
        <Aviso />
      </View>
    </StoreProvider>
  );
}

const estilos = StyleSheet.create({
  aviso: { position: 'absolute', left: 16, right: 16, bottom: 100, alignItems: 'center' },
  avisoTexto: { paddingVertical: 9, paddingHorizontal: 16, borderRadius: 999, overflow: 'hidden', fontSize: 14 },
});
