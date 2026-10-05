// Colores y componentes compartidos.

import { Pressable, StyleSheet, Text, TextInput, useColorScheme, View } from 'react-native';

const CLARO = {
  bg: '#f6f7f5',
  card: '#ffffff',
  text: '#1d2420',
  muted: '#66706a',
  border: '#e1e5e2',
  accent: '#2f7d5b',
  accentSoft: '#e3f1ea',
  warn: '#c0612b',
  danger: '#b3261e',
};

const OSCURO = {
  bg: '#121614',
  card: '#1b211e',
  text: '#e7ece9',
  muted: '#9aa59f',
  border: '#2c3430',
  accent: '#5cc095',
  accentSoft: '#1f3329',
  warn: '#e59563',
  danger: '#f2827a',
};

export function useColores() {
  return useColorScheme() === 'dark' ? OSCURO : CLARO;
}

export function Card({ style, children, destacada }) {
  const c = useColores();
  return (
    <View style={[s.card, { backgroundColor: c.card, borderColor: destacada ? c.accent : c.border, borderWidth: destacada ? 2 : 1 }, style]}>
      {children}
    </View>
  );
}

export function T({ style, muted, children, ...props }) {
  const c = useColores();
  return <Text style={[{ color: muted ? c.muted : c.text, fontSize: muted ? 14 : 16 }, style]} {...props}>{children}</Text>;
}

export function Titulo({ children, style }) {
  return <T style={[s.titulo, style]}>{children}</T>;
}

export function Subtitulo({ children }) {
  const c = useColores();
  return <Text style={[s.subtitulo, { color: c.muted }]}>{children}</Text>;
}

export function Boton({ titulo, onPress, primario, peligro, chico, style }) {
  const c = useColores();
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      style={({ pressed }) => [
        s.boton,
        chico && s.botonChico,
        { backgroundColor: primario ? c.accent : c.card, borderColor: primario ? c.accent : c.border, opacity: pressed ? 0.7 : 1 },
        style,
      ]}
    >
      <Text style={{ color: primario ? '#fff' : peligro ? c.danger : c.text, fontSize: chico ? 14 : 16, fontWeight: primario ? '600' : '400' }}>
        {titulo}
      </Text>
    </Pressable>
  );
}

export function Chip({ texto, onPress }) {
  const c = useColores();
  return (
    <Pressable onPress={onPress} style={({ pressed }) => [s.chip, { borderColor: c.border, backgroundColor: c.card, opacity: pressed ? 0.6 : 1 }]}>
      <Text style={{ color: c.text, fontSize: 14 }}>{texto}</Text>
    </Pressable>
  );
}

export function Badge({ texto }) {
  const c = useColores();
  return (
    <View style={[s.badge, { backgroundColor: c.accentSoft }]}>
      <Text style={{ color: c.accent, fontSize: 12 }}>{texto}</Text>
    </View>
  );
}

export function Campo({ style, ...props }) {
  const c = useColores();
  return (
    <TextInput
      placeholderTextColor={c.muted}
      style={[s.campo, { borderColor: c.border, backgroundColor: c.card, color: c.text }, style]}
      {...props}
    />
  );
}

export function Fila({ children, style, wrap }) {
  return <View style={[s.fila, wrap && { flexWrap: 'wrap' }, style]}>{children}</View>;
}

export const s = StyleSheet.create({
  pantalla: { padding: 16, paddingBottom: 40 },
  card: { borderRadius: 14, padding: 14, marginBottom: 10 },
  titulo: { fontSize: 20, fontWeight: '700', marginBottom: 10 },
  subtitulo: { fontSize: 13, fontWeight: '600', textTransform: 'uppercase', letterSpacing: 0.5, marginTop: 14, marginBottom: 6 },
  boton: { borderWidth: 1, borderRadius: 10, paddingVertical: 10, paddingHorizontal: 14, alignItems: 'center' },
  botonChico: { paddingVertical: 6, paddingHorizontal: 10 },
  chip: { borderWidth: 1, borderRadius: 999, paddingVertical: 6, paddingHorizontal: 12, marginRight: 6, marginBottom: 6 },
  badge: { borderRadius: 999, paddingVertical: 2, paddingHorizontal: 8 },
  campo: { borderWidth: 1, borderRadius: 10, paddingVertical: 10, paddingHorizontal: 12, fontSize: 16 },
  fila: { flexDirection: 'row', alignItems: 'center', gap: 8 },
});
