# Mi Despensa

App para iPhone (React Native + Expo) para organizar la comida de una persona que vive sola y tiene unos 15 minutos para cocinar. El código está en la carpeta [`MiDespensa/`](MiDespensa/).

## Qué hace

- **Menú semanal:** genera un menú de lunes a domingo con recetas de 15 minutos o menos, sin repetir platillos. También puedes tocar cada día para elegir la receta a mano.
- **Recetas:** cada receta trae ingredientes, pasos, tiempo y calorías aproximadas. Puedes agregar recetas propias.
- **Lista del mercado:** pasa los ingredientes del menú a la lista con un botón. Incluye aseo personal y limpieza con productos sugeridos, filtros (Todo / Comida / Aseo) y opción para compartir por WhatsApp, Notas, etc.
- **Calorías:** meta diaria, registro rápido desde recetas, antojos frecuentes o texto libre, navegación por días y gráfica de los últimos 7 días.
- **Alimentos excluidos:** de entrada no usa pollo, huevo, camarón ni frijoles. Se cambia en Ajustes.
- **Respaldo:** exporta e importa tus datos como texto.

Los datos se guardan solo en el iPhone (AsyncStorage). No hay cuenta ni servidor.

## Abrirla en el iPhone (sin Mac)

1. Instala **Expo Go** desde la App Store en tu iPhone.
2. En una computadora (Windows, Linux o Mac) con [Node.js](https://nodejs.org) instalado:

   ```bash
   git clone https://github.com/dafp2059/Solufidge.git
   cd Solufidge/MiDespensa
   npm install
   npx expo start
   ```

3. Escanea el código QR que aparece con la **cámara del iPhone**. Se abre en Expo Go.
   - El iPhone y la computadora deben estar en la misma red Wi-Fi. Si no conecta, usa `npx expo start --tunnel`.

## Instalarla como app propia (opcional)

Para tenerla con su propio ícono sin depender de Expo Go se usa EAS Build, que compila en la nube (no necesita Mac) pero requiere una cuenta de Apple Developer (99 USD al año):

```bash
npm install -g eas-cli
eas login
eas build --platform ios
```

## Estructura

| Archivo | Contenido |
|---|---|
| `src/app/_layout.js` | Pestañas inferiores y avisos |
| `src/app/index.js` | Menú de la semana |
| `src/app/recetas.js` | Recetas y formulario de receta propia |
| `src/app/mercado.js` | Lista del mercado y aseo |
| `src/app/calorias.js` | Control de calorías |
| `src/app/ajustes.js` | Meta, exclusiones y respaldo |
| `src/store.js` | Estado y lógica, guardado en el teléfono |
| `src/data.js` | Recetas, categorías, productos de aseo y antojos |
| `src/ui.js` | Colores (modo claro y oscuro) y componentes |

Para cambiar o agregar recetas que vienen incluidas, edita `RECETAS_BASE` en `src/data.js`.
