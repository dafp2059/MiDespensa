# Mi Despensa

App web instalable (PWA) para organizar la comida de una persona que vive sola y tiene unos 15 minutos para cocinar. Se instala en el iPhone desde Safari, sin computadora y sin App Store. El código está en [`MiDespensa/`](MiDespensa/).

## Qué hace

- **Menú semanal:** genera un menú de lunes a domingo con recetas de 15 minutos o menos, sin repetir platillos. También puedes elegir cada día a mano.
- **Recetas:** cada receta trae ingredientes, pasos, tiempo y calorías aproximadas. Puedes agregar recetas propias.
- **Lista del mercado:** pasa los ingredientes del menú a la lista con un botón. Incluye aseo personal y limpieza con productos sugeridos, filtros (Todo / Comida / Aseo) y opción para compartir por WhatsApp, Notas, etc.
- **Calorías:** meta diaria, registro rápido desde recetas, antojos frecuentes o texto libre, y gráfica de los últimos 7 días.
- **Alimentos excluidos:** de entrada no usa pollo, huevo, camarón ni frijoles. Se cambia en Ajustes.
- **Respaldo:** exporta e importa tus datos (en iPhone se pueden guardar en Archivos o iCloud).

Los datos se guardan solo en el teléfono. No hay cuenta ni servidor. Funciona sin internet una vez instalada.

## Publicarla e instalarla desde el iPhone

La app se publica sola en GitHub Pages con el flujo `.github/workflows/pages.yml` cada vez que hay cambios en `master`.

1. En Safari, abre el repositorio en github.com e inicia sesión.
2. **Settings → General → Danger Zone → Change visibility → Public** (ya hecho). GitHub Pages gratis solo funciona con repositorios públicos. El código no contiene datos personales: tus datos viven solo en tu teléfono.
3. **Settings → Pages → Source: GitHub Actions.**
4. **Actions → Publicar Mi Despensa → Run workflow** (o espera al siguiente cambio).
5. Abre `https://dafp2059.github.io/MiDespensa/` en **Safari**, toca **Compartir → Agregar a pantalla de inicio**.

## Estructura

| Archivo | Contenido |
|---|---|
| `MiDespensa/index.html` | Estructura y navegación |
| `MiDespensa/styles.css` | Estilos, con modo claro y oscuro |
| `MiDespensa/data.js` | Recetas, categorías, productos de aseo y antojos |
| `MiDespensa/app.js` | Lógica de la app |
| `MiDespensa/sw.js`, `manifest.webmanifest`, `icons/` | Instalación como app y uso sin conexión |

Para cambiar o agregar recetas que vienen incluidas, edita `RECETAS_BASE` en `MiDespensa/data.js`.
