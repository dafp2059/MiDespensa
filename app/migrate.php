<?php
declare(strict_types=1);

/*
 * Actualiza la base de datos sola cuando se sube una versión nueva del código.
 * Se ejecuta al entrar al panel; no hace nada si ya está al día.
 */

const SCHEMA_VERSION = 2;

function ensure_schema(): void
{
    // Base de datos vacía: crear todas las tablas desde database/schema.sql.
    if (!query("SHOW TABLES LIKE 'users'")->fetch()) {
        $sql = (string) file_get_contents(dirname(APP_DIR) . '/database/schema.sql');
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (preg_split('/;\s*$/m', $sql) as $statement) {
            if (trim($statement) !== '') {
                db()->exec($statement);
            }
        }
    }

    db()->exec('CREATE TABLE IF NOT EXISTS settings (
        name VARCHAR(60) NOT NULL PRIMARY KEY,
        value MEDIUMTEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    if ((int) (settings(true)['schema_version'] ?? 0) >= SCHEMA_VERSION) {
        return;
    }

    db()->exec('ALTER TABLE settings MODIFY value MEDIUMTEXT NULL');

    db()->exec("CREATE TABLE IF NOT EXISTS slides (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        media_type ENUM('none','image','video','youtube') NOT NULL DEFAULT 'none',
        media VARCHAR(255) NULL,
        eyebrow VARCHAR(190) NULL,
        title VARCHAR(255) NULL,
        subtitle TEXT NULL,
        button_text VARCHAR(80) NULL,
        button_url VARCHAR(255) NULL,
        button2_text VARCHAR(80) NULL,
        button2_url VARCHAR(255) NULL,
        align ENUM('left','center') NOT NULL DEFAULT 'left',
        overlay TINYINT UNSIGNED NOT NULL DEFAULT 55,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(140) NOT NULL UNIQUE,
        title VARCHAR(190) NOT NULL,
        eyebrow VARCHAR(120) NULL,
        intro TEXT NULL,
        content MEDIUMTEXT NULL,
        image VARCHAR(255) NULL,
        meta_description VARCHAR(300) NULL,
        form ENUM('none','contacto','contacto_info') NOT NULL DEFAULT 'none',
        form_reason VARCHAR(30) NULL,
        form_title VARCHAR(190) NULL,
        locked TINYINT(1) NOT NULL DEFAULT 0,
        published TINYINT(1) NOT NULL DEFAULT 1,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!query('SELECT id FROM slides LIMIT 1')->fetch()) {
        query('INSERT INTO slides (eyebrow, title, subtitle, sort_order) VALUES (?, ?, ?, 0)', [
            'Inmobiliaria en {{ciudad}}',
            'Encuentra tu próximo hogar, o el comprador ideal para el tuyo',
            'Casas, apartamentos, fincas y locales en venta y arriendo. Te acompañamos desde la primera visita hasta la firma.',
        ]);
    }

    foreach (default_pages() as $page) {
        if (!query('SELECT id FROM pages WHERE slug = ?', [$page['slug']])->fetch()) {
            query('INSERT INTO pages (slug, title, eyebrow, intro, content, meta_description, form, form_reason, form_title, locked)
                VALUES (:slug, :title, :eyebrow, :intro, :content, :meta_description, :form, :form_reason, :form_title, :locked)', $page);
        }
    }

    save_setting('schema_version', (string) SCHEMA_VERSION);
    settings(true);
}

function default_pages(): array
{
    return [
        [
            'slug' => 'nosotros', 'title' => '{{empresa}}', 'eyebrow' => 'Nosotros', 'locked' => 0,
            'intro' => 'Donde cada meta inmobiliaria se convierte en realidad.',
            'meta_description' => 'Conoce a {{empresa}}, inmobiliaria en {{ciudad}}.',
            'form' => 'none', 'form_reason' => null, 'form_title' => null,
            'content' => '<h2>Quiénes somos</h2><p>Somos una inmobiliaria de {{ciudad}} dedicada a la compra, venta y arriendo de casas, apartamentos, fincas, lotes, oficinas, locales y bodegas. Acompañamos a familias, inversionistas y propietarios en cada paso, con información clara y trato cercano.</p>'
                . '<h2>Qué hacemos</h2><ul><li><strong>Venta de inmuebles:</strong> valoración, promoción y acompañamiento hasta la escritura.</li>'
                . '<li><strong>Arrendamientos:</strong> búsqueda y estudio de arrendatarios, contratos y recaudo del canon.</li>'
                . '<li><strong>Asesoría de inversión:</strong> te ayudamos a elegir dónde y en qué invertir.</li>'
                . '<li><strong>Proyectos:</strong> desarrollo y comercialización de proyectos inmobiliarios.</li></ul>'
                . '<h2>Visítanos</h2><p>{{direccion}}, {{ciudad}}<br>Teléfono: {{telefono}}<br>Correo: {{correo}}<br>{{horario}}</p>',
        ],
        [
            'slug' => 'propietarios', 'title' => 'Vende o arrienda tu inmueble sin complicaciones', 'eyebrow' => 'Propietarios', 'locked' => 0,
            'intro' => 'Te acompañamos en todo el proceso para que vendas o arriendes al precio justo y con tranquilidad.',
            'meta_description' => '¿Quieres vender o arrendar tu casa, apartamento, local o lote? {{empresa}} se encarga de la valoración, la promoción, las visitas y los trámites.',
            'form' => 'contacto', 'form_reason' => 'vender', 'form_title' => 'Cuéntanos de tu inmueble',
            'content' => '<h2>Qué hacemos por ti</h2><ul><li>Valoración comercial según el mercado de la zona.</li><li>Fotos y descripción profesional del inmueble.</li>'
                . '<li>Publicación en nuestra web, portales inmobiliarios y redes sociales.</li><li>Atención de interesados y visitas acompañadas.</li>'
                . '<li>Estudio de arrendatarios y contrato de arrendamiento.</li><li>Revisión de documentos y acompañamiento hasta la escritura o la entrega.</li></ul>'
                . '<h2>Cómo funciona</h2><ol><li><strong>Nos cuentas de tu inmueble</strong> con este formulario o por WhatsApp.</li>'
                . '<li><strong>Lo visitamos y lo valoramos.</strong> Acordamos contigo el precio y las condiciones.</li>'
                . '<li><strong>Lo promocionamos</strong> y te mantenemos informado de cada visita.</li>'
                . '<li><strong>Cerramos el negocio</strong> con todos los documentos en regla.</li></ol>',
        ],
        [
            'slug' => 'contacto', 'title' => 'Hablemos de tu próximo inmueble', 'eyebrow' => 'Contacto', 'locked' => 1,
            'intro' => 'Cuéntanos qué necesitas y te respondemos en menos de 24 horas hábiles.',
            'meta_description' => 'Escríbenos, llámanos o visítanos. {{direccion}}, {{ciudad}}.',
            'form' => 'contacto_info', 'form_reason' => null, 'form_title' => null, 'content' => '',
        ],
        [
            'slug' => 'politica-de-datos', 'title' => 'Política de tratamiento de datos personales', 'eyebrow' => 'Legal', 'locked' => 0,
            'intro' => null,
            'meta_description' => 'Política de tratamiento de datos personales de {{razon_social}} conforme a la Ley 1581 de 2012.',
            'form' => 'none', 'form_reason' => null, 'form_title' => null,
            'content' => '<h2>1. Responsable del tratamiento</h2><p><strong>{{razon_social}}</strong>, NIT {{nit}}, con domicilio en {{direccion}}, {{ciudad}}. Correo: {{correo}}. Teléfono: {{telefono}}.</p>'
                . '<h2>2. Marco legal</h2><p>Esta política se expide en cumplimiento de la Ley Estatutaria 1581 de 2012, el Decreto 1377 de 2013 (compilado en el Decreto 1074 de 2015) y demás normas que las modifiquen o complementen.</p>'
                . '<h2>3. Datos que recolectamos</h2><p>Nombre, número de documento, teléfono, correo electrónico, dirección e información relacionada con los inmuebles de su interés o de su propiedad, así como la información necesaria para procesar pagos (la información de tarjetas y cuentas bancarias la procesa directamente la pasarela de pagos y no es almacenada por nosotros).</p>'
                . '<h2>4. Finalidades</h2><ul><li>Responder solicitudes de información y agendar visitas a inmuebles.</li><li>Gestionar la promoción, venta y arrendamiento de inmuebles.</li>'
                . '<li>Realizar estudios de arrendatarios y celebrar contratos.</li><li>Procesar y conciliar pagos realizados por medios electrónicos.</li>'
                . '<li>Enviar información comercial sobre inmuebles y servicios, cuando el titular lo haya autorizado.</li><li>Cumplir obligaciones legales, contables y tributarias.</li></ul>'
                . '<h2>5. Derechos del titular</h2><p>Como titular de los datos usted puede: conocer, actualizar y rectificar sus datos; solicitar prueba de la autorización otorgada; ser informado sobre el uso que se ha dado a sus datos; presentar quejas ante la Superintendencia de Industria y Comercio; revocar la autorización o solicitar la supresión de sus datos cuando no exista un deber legal o contractual de conservarlos; y acceder gratuitamente a sus datos.</p>'
                . '<h2>6. Consultas y reclamos</h2><p>Puede ejercer sus derechos escribiendo a {{correo}} o en nuestra oficina. Las consultas se atenderán en un máximo de diez (10) días hábiles y los reclamos en un máximo de quince (15) días hábiles, prorrogables en los términos de la ley.</p>'
                . '<h2>7. Seguridad</h2><p>Adoptamos medidas técnicas, humanas y administrativas razonables para proteger los datos personales contra pérdida, consulta, uso o acceso no autorizado.</p>'
                . '<h2>8. Vigencia</h2><p>Esta política rige a partir de su publicación. Los datos se conservarán mientras sean necesarios para las finalidades descritas o mientras exista un deber legal de conservarlos.</p>',
        ],
    ];
}
