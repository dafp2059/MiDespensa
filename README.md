# Solufidge

App web para organizar la comida de una persona que vive sola y tiene unos 15 minutos para cocinar.

## Qué hace

- **Menú semanal:** genera un menú de lunes a domingo con recetas de 15 minutos o menos, sin repetir platillos. También puedes elegir cada día a mano.
- **Recetas:** cada receta trae ingredientes, pasos, tiempo y calorías aproximadas. Puedes agregar recetas propias.
- **Lista del mercado:** pasa los ingredientes del menú a la lista con un botón. Incluye una sección de aseo personal y limpieza con productos sugeridos, filtros (Todo / Comida / Aseo) y opción para compartir o copiar la lista.
- **Calorías:** meta diaria, registro rápido desde recetas, antojos frecuentes o texto libre, y una gráfica de los últimos 7 días.
- **Alimentos excluidos:** de entrada no usa pollo, huevo, camarón ni frijoles. Se cambia en Ajustes.
- **Respaldo:** exporta e importa tus datos en JSON.

Los datos se guardan solo en el dispositivo (`localStorage`). No hay cuenta ni servidor.

## Cómo usarla

No necesita instalar nada ni compilar. Basta con servir la carpeta:

```bash
python3 -m http.server 8000
# abrir http://localhost:8000
```

Para tenerla en el celular, publícala en un hosting estático (por ejemplo GitHub Pages). Luego ábrela en el navegador del teléfono y elige **"Agregar a pantalla de inicio"**. Funciona sin internet.

## Archivos

| Archivo | Contenido |
|---|---|
| `index.html` | Estructura y navegación |
| `styles.css` | Estilos, con modo claro y oscuro |
| `data.js` | Recetas, categorías, productos de aseo y antojos |
| `app.js` | Lógica de la app |
| `sw.js`, `manifest.webmanifest` | Instalación como app y uso sin conexión |

Para agregar o cambiar recetas que vienen incluidas, edita `RECETAS_BASE` en `data.js`.
