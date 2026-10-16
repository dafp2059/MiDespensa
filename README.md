# INGESPRO Inmobiliaria: web en PHP + MySQL

Web de la inmobiliaria hecha desde cero, sin WordPress. Usa PHP 8, MySQL, HTML, CSS y un poco de JavaScript, y funciona en cualquier hosting con cPanel.

## Qué incluye

**Web pública**
- **Inicio:** buscador, inmuebles destacados, zonas, sección para propietarios, proyectos y contacto.
- **Listado de inmuebles** con filtros por operación, tipo, zona, precio, habitaciones y palabra clave, orden y paginación.
- **Ficha de cada inmueble:** galería, características, video de YouTube, mapa, botón de WhatsApp con mensaje prellenado y formulario para agendar visita.
- **Proyectos**, **Vende o arrienda** (captación de propietarios), **Nosotros**, **Contacto** y **Política de tratamiento de datos** (Ley 1581).
- **Pagos en línea con Wompi** (canon, administración, separación…), con página de resultado y webhook.
- **SEO:** URLs amigables (`/inmueble/casa-campestre-el-retiro`), títulos y descripciones por página, datos estructurados, `sitemap.xml` y redirecciones desde las direcciones viejas de WordPress.
- Botón flotante de WhatsApp y diseño adaptado a celulares.

**Panel de administración** (`/admin/`). Todo se cambia desde aquí, sin tocar código.

*Contenido*
- **Inmuebles:** crear, editar, ocultar, destacar y eliminar, con fotos (se reducen solas a 1800 px), portada, estado, precio y coordenadas.
- **Proyectos**, **Zonas** (con foto), **Mensajes** de los formularios (con botón para responder por WhatsApp) y **Pagos** de Wompi (exportables a Excel).

*Diseño*
- **Banner:** varias diapositivas que cambian solas, cada una con foto, video subido (MP4/WEBM) o video de YouTube, título, texto y dos botones. Se eligen los segundos por diapositiva, el alto y si se muestra el buscador.
- **Inicio:** mostrar, ocultar y reordenar las secciones, y editar todos sus textos, la franja "Por qué elegirnos", los pasos para propietarios, un bloque de texto libre y el título y la descripción para Google.
- **Menús:** menú principal, barra superior y las dos columnas del pie de página (texto, enlace, orden y si abre en pestaña nueva), además de los textos del pie.
- **Páginas:** Nosotros, Contacto, Vende o arrienda, Política de datos y todas las páginas nuevas que quieras, con editor de texto, foto de encabezado y formulario de contacto opcional.
- **Ajustes:** logo, versión para fondo oscuro, ícono de la pestaña, colores, tipografía, botones de WhatsApp y los datos de la empresa (teléfono, dirección, horario, redes, NIT, razón social).
- **Usuarios** del equipo.

Al subir una versión nueva del código, la base de datos se actualiza sola la primera vez que alguien abre la web.

## Estructura

```
app/            Código interno. Va FUERA de public_html.
  config.php    Tus datos y contraseñas (se crea a partir de config.sample.php).
  views/        Plantillas: cabecera, pie, tarjetas, formularios, panel.
public_html/    Lo que se ve en internet.
  admin/        Panel de administración.
  assets/       Estilos, JavaScript, logo.
  uploads/      Fotos que sube el equipo.
database/schema.sql   Tablas de la base de datos.
```

## Instalación en cPanel

1. **Copia de seguridad del WordPress actual.** En cPanel > Copias de seguridad, descarga una copia completa antes de tocar nada.
2. **Base de datos.** En cPanel > Bases de datos MySQL, crea una base de datos y un usuario, y dale todos los privilegios. Luego, en phpMyAdmin, selecciona la base y usa **Importar** con `database/schema.sql`.
3. **Archivos.** Con el Administrador de archivos o FTP:
   - Sube la carpeta `app/` a tu carpeta de inicio (al lado de `public_html`, **no dentro**).
   - Sube el contenido de `public_html/` dentro de `public_html` (después de mover el WordPress a otra carpeta o eliminarlo).
4. **Configuración.** Copia `app/config.sample.php` como `app/config.php` y completa los datos de la base de datos, de la empresa (NIT y razón social para la política de datos) y las llaves de Wompi.
5. **Versión de PHP.** En cPanel > Seleccionar versión de PHP, elige 8.1 o superior y activa las extensiones `pdo_mysql`, `gd`, `curl` y `mbstring`.
6. **Primer usuario.** Entra a `https://ingesproinmobiliaria.com/admin/`. La primera vez te pide crear el administrador.
7. **Permisos.** La carpeta `public_html/uploads` debe poder escribirse (755).

### Wompi

1. En [comercios.wompi.co](https://comercios.wompi.co) > Desarrolladores, copia la **llave pública**, el **secreto de integridad** y el **secreto de eventos** en `app/config.php`.
2. En la misma sección, cambia la **URL de eventos** a `https://ingesproinmobiliaria.com/wompi-webhook.php`. Así los pagos se actualizan solos aunque el cliente cierre el navegador.
3. Prueba primero con las llaves de prueba (`pub_test_…`). La página de pagos muestra un aviso mientras estés en modo de pruebas.

## Verla sin computador (GitHub Codespaces)

Desde el navegador del celular abre:

**https://codespaces.new/dafp2059/MiDespensa?ref=claude/ingespro-web**

Toca **Create codespace** y espera unos 5 minutos la primera vez. Cuando termine, ve a la pestaña **Puertos** (Ports) y abre la dirección del puerto **8000**: ahí está la web funcionando. El panel está en esa misma dirección agregando `/admin`. La primera vez te pide crear el usuario administrador.

Es una copia de pruebas: lo que cargues ahí no pasa al hosting. Cuando termines, detén el codespace en github.com/codespaces para no gastar las horas gratis del mes.

## Probarlo en tu computador

Con PHP 8 y MySQL instalados:

```bash
mysql -u root -e "CREATE DATABASE ingespro CHARACTER SET utf8mb4"
mysql -u root ingespro < database/schema.sql
cp app/config.sample.php app/config.php   # y edita base_url = http://localhost:8000 y los datos de MySQL
php -S localhost:8000 -t public_html tools/servidor-local.php
```

Abre http://localhost:8000. El archivo `tools/servidor-local.php` imita las reglas de `.htaccess`, que el servidor de pruebas de PHP no lee.

## Textos para revisar antes de publicar

- La franja "Por qué elegirnos" (en **Diseño > Inicio**) y la página **Nosotros** (en **Diseño > Páginas**): confirma que los servicios descritos son los que ofrecen.
- **Horario de atención**, **NIT** y **razón social**: se cambian en el panel, en **Ajustes**.
- El **logo**: súbelo en el panel, en **Ajustes**. Mientras tanto se muestra uno provisional.
