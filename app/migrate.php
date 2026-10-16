<?php
declare(strict_types=1);

/*
 * Actualiza la base de datos sola cuando se sube una versión nueva del código.
 * Se ejecuta al entrar al panel; no hace nada si ya está al día.
 */

const SCHEMA_VERSION = 3;

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

    $version = (int) (settings(true)['schema_version'] ?? 0);
    if ($version >= SCHEMA_VERSION) {
        return;
    }
    if ($version < 2) {
        migrate_v2();
    }
    if ($version < 3) {
        migrate_v3();
    }
    save_setting('schema_version', (string) SCHEMA_VERSION);
    settings(true);
}

function column_exists(string $table, string $column): bool
{
    return (bool) query('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $column])->fetch();
}

/** Versión 2: banner, páginas y ajustes editables. */
function migrate_v2(): void
{
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

}

/** Versión 3: clientes, contratos, cuotas y portal "Mi cuenta". */
function migrate_v3(): void
{
    if (!column_exists('properties', 'address')) {
        db()->exec('ALTER TABLE properties ADD address VARCHAR(190) NULL AFTER neighborhood, ADD registry_number VARCHAR(60) NULL AFTER address');
    }
    if (!column_exists('payments', 'charge_id')) {
        db()->exec('ALTER TABLE payments ADD client_id INT UNSIGNED NULL, ADD charge_id INT UNSIGNED NULL, ADD KEY idx_charge (charge_id)');
    }

    db()->exec("CREATE TABLE IF NOT EXISTS clients (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        document_type VARCHAR(5) NOT NULL DEFAULT 'CC',
        document VARCHAR(30) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        phone VARCHAR(40) NULL,
        address VARCHAR(190) NULL,
        notes TEXT NULL,
        password_hash VARCHAR(255) NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->exec("CREATE TABLE IF NOT EXISTS client_tokens (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id INT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        KEY idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->exec("CREATE TABLE IF NOT EXISTS contract_templates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type ENUM('arriendo','venta') NOT NULL,
        name VARCHAR(150) NOT NULL,
        body MEDIUMTEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->exec("CREATE TABLE IF NOT EXISTS contracts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        type ENUM('arriendo','venta') NOT NULL,
        status ENUM('activo','terminado','cancelado') NOT NULL DEFAULT 'activo',
        property_id INT UNSIGNED NULL,
        client_id INT UNSIGNED NOT NULL,
        template_id INT UNSIGNED NULL,
        owner_name VARCHAR(150) NULL,
        owner_document VARCHAR(40) NULL,
        owner_phone VARCHAR(40) NULL,
        cosigner_name VARCHAR(150) NULL,
        cosigner_document VARCHAR(40) NULL,
        cosigner_phone VARCHAR(40) NULL,
        cosigner_address VARCHAR(190) NULL,
        amount BIGINT UNSIGNED NOT NULL DEFAULT 0,
        admin_fee BIGINT UNSIGNED NOT NULL DEFAULT 0,
        due_day TINYINT UNSIGNED NOT NULL DEFAULT 5,
        signed_at DATE NULL,
        start_date DATE NULL,
        duration_months SMALLINT UNSIGNED NULL,
        end_date DATE NULL,
        deed_date DATE NULL,
        notary VARCHAR(120) NULL,
        body MEDIUMTEXT NULL,
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_client (client_id),
        KEY idx_property (property_id),
        CONSTRAINT fk_contract_client FOREIGN KEY (client_id) REFERENCES clients(id),
        CONSTRAINT fk_contract_property FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    db()->exec("CREATE TABLE IF NOT EXISTS charges (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        contract_id INT UNSIGNED NOT NULL,
        concept VARCHAR(30) NOT NULL,
        description VARCHAR(190) NOT NULL,
        amount BIGINT UNSIGNED NOT NULL,
        due_date DATE NOT NULL,
        status ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente',
        paid_at DATETIME NULL,
        paid_method VARCHAR(40) NULL,
        payment_id INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_contract (contract_id, due_date),
        KEY idx_status (status, due_date),
        CONSTRAINT fk_charge_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!query('SELECT id FROM contract_templates LIMIT 1')->fetch()) {
        foreach (default_contract_templates() as $t) {
            query('INSERT INTO contract_templates (type, name, body) VALUES (?, ?, ?)', [$t['type'], $t['name'], $t['body']]);
        }
    }

    // "Mi cuenta" en la barra superior, si el menú aún no se ha editado.
    if (!array_key_exists('menu.superior', settings(true))) {
        save_setting('menu.superior', json_encode([
            ['label' => 'Pagos en línea', 'url' => '/pagos', 'new_tab' => 0],
            ['label' => 'Mi cuenta', 'url' => '/mi-cuenta/', 'new_tab' => 0],
        ], JSON_UNESCAPED_UNICODE));
    }
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

/** Plantillas de referencia. Deben revisarse con un abogado antes de usarse. */
function default_contract_templates(): array
{
    $firmas = '<p><br></p><p>Para constancia se firma en {{ciudad}}, el {{fecha_contrato}}.</p><p><br></p>';
    return [
        [
            'type' => 'arriendo',
            'name' => 'Arrendamiento de vivienda urbana (Ley 820 de 2003)',
            'body' => '<h2>CONTRATO DE ARRENDAMIENTO DE VIVIENDA URBANA N.º {{contrato_codigo}}</h2>'
                . '<p><strong>ARRENDADOR:</strong> {{razon_social}}, NIT {{nit}}, quien actúa en nombre y por cuenta del propietario {{propietario_nombre}}, identificado(a) con {{propietario_documento}}.</p>'
                . '<p><strong>ARRENDATARIO:</strong> {{cliente_nombre}}, identificado(a) con {{cliente_documento}}, teléfono {{cliente_telefono}}, correo {{cliente_correo}}.</p>'
                . '<p><strong>DEUDOR SOLIDARIO:</strong> {{codeudor_nombre}}, identificado(a) con {{codeudor_documento}}, teléfono {{codeudor_telefono}}, dirección {{codeudor_direccion}}.</p>'
                . '<p>Las partes celebran el presente contrato de arrendamiento, que se regirá por la Ley 820 de 2003 y por las siguientes cláusulas:</p>'
                . '<p><strong>PRIMERA. OBJETO.</strong> El ARRENDADOR entrega al ARRENDATARIO, a título de arrendamiento, el {{inmueble_tipo}} ubicado en {{inmueble_direccion}}, identificado(a) con el folio de matrícula inmobiliaria n.º {{inmueble_matricula}}, junto con los bienes y el estado descritos en el inventario que firman las partes y que hace parte de este contrato.</p>'
                . '<p><strong>SEGUNDA. DESTINACIÓN.</strong> El inmueble se destinará exclusivamente a vivienda del ARRENDATARIO y su familia. El ARRENDATARIO no podrá cederlo ni subarrendarlo, total o parcialmente, sin autorización previa y escrita del ARRENDADOR.</p>'
                . '<p><strong>TERCERA. CANON.</strong> El canon mensual es de {{valor_letras}} ({{valor}}), que el ARRENDATARIO pagará por mensualidades anticipadas a más tardar el día {{dia_pago}} de cada mes, mediante el portal de pagos {{portal}} o por otro medio que el ARRENDADOR autorice por escrito. La cuota de administración de la propiedad horizontal es de {{administracion}} y estará a cargo del ARRENDATARIO.</p>'
                . '<p><strong>CUARTA. REAJUSTE.</strong> Cada doce (12) meses de ejecución del contrato el canon podrá reajustarse en una proporción que no supere el ciento por ciento (100 %) del incremento del índice de precios al consumidor (IPC) del año calendario inmediatamente anterior, conforme al artículo 20 de la Ley 820 de 2003, previa comunicación escrita al ARRENDATARIO.</p>'
                . '<p><strong>QUINTA. TÉRMINO.</strong> El término del contrato es de {{duracion_meses}} meses, contados desde el {{fecha_inicio}} hasta el {{fecha_fin}}. Vencido este término, el contrato se prorrogará en los términos del artículo 6 de la Ley 820 de 2003.</p>'
                . '<p><strong>SEXTA. SERVICIOS PÚBLICOS.</strong> El pago de los servicios públicos domiciliarios y demás servicios que se causen durante el arrendamiento estará a cargo del ARRENDATARIO.</p>'
                . '<p><strong>SÉPTIMA. REPARACIONES Y MEJORAS.</strong> Las reparaciones locativas estarán a cargo del ARRENDATARIO y las necesarias a cargo del ARRENDADOR. El ARRENDATARIO no podrá hacer mejoras ni modificaciones sin autorización escrita del ARRENDADOR.</p>'
                . '<p><strong>OCTAVA. OBLIGACIONES DEL ARRENDATARIO.</strong> Pagar el canon en la forma y fecha acordadas; cuidar el inmueble; cumplir el reglamento de propiedad horizontal; permitir las visitas de inspección previamente acordadas; y restituir el inmueble al terminar el contrato en el estado en que lo recibió, salvo el deterioro natural por su uso legítimo.</p>'
                . '<p><strong>NOVENA. TERMINACIÓN.</strong> El contrato podrá terminarse por mutuo acuerdo o por las causales previstas en los artículos 22 a 25 de la Ley 820 de 2003. La mora en el pago del canon dará derecho al ARRENDADOR a terminar el contrato y exigir la restitución del inmueble.</p>'
                . '<p><strong>DÉCIMA. DEUDOR SOLIDARIO.</strong> El DEUDOR SOLIDARIO se obliga de forma solidaria con el ARRENDATARIO por el cumplimiento de todas las obligaciones de este contrato y sus prórrogas.</p>'
                . '<p><strong>UNDÉCIMA. MÉRITO EJECUTIVO.</strong> Este contrato presta mérito ejecutivo para el cobro de los cánones y demás obligaciones a cargo del ARRENDATARIO, conforme al artículo 14 de la Ley 820 de 2003.</p>'
                . '<p><strong>DUODÉCIMA. DEPÓSITOS.</strong> Conforme al artículo 16 de la Ley 820 de 2003, no se exige depósito en dinero ni otra clase de caución real para garantizar el cumplimiento de este contrato.</p>'
                . '<p><strong>DÉCIMA TERCERA. NOTIFICACIONES.</strong> Las partes recibirán notificaciones en las direcciones y correos indicados en este contrato.</p>'
                . $firmas
                . '<p>_______________________________<br>ARRENDADOR<br>{{razon_social}} · NIT {{nit}}</p><p><br></p>'
                . '<p>_______________________________<br>ARRENDATARIO<br>{{cliente_nombre}} · {{cliente_documento}}</p><p><br></p>'
                . '<p>_______________________________<br>DEUDOR SOLIDARIO<br>{{codeudor_nombre}} · {{codeudor_documento}}</p>',
        ],
        [
            'type' => 'venta',
            'name' => 'Promesa de compraventa de inmueble',
            'body' => '<h2>PROMESA DE COMPRAVENTA DE INMUEBLE N.º {{contrato_codigo}}</h2>'
                . '<p><strong>PROMITENTE VENDEDOR:</strong> {{propietario_nombre}}, identificado(a) con {{propietario_documento}}, teléfono {{propietario_telefono}}.</p>'
                . '<p><strong>PROMITENTE COMPRADOR:</strong> {{cliente_nombre}}, identificado(a) con {{cliente_documento}}, teléfono {{cliente_telefono}}, correo {{cliente_correo}}.</p>'
                . '<p>Las partes celebran el presente contrato de promesa de compraventa, que se regirá por las siguientes cláusulas:</p>'
                . '<p><strong>PRIMERA. OBJETO.</strong> El PROMITENTE VENDEDOR se obliga a vender y el PROMITENTE COMPRADOR a comprar el {{inmueble_tipo}} ubicado en {{inmueble_direccion}}, identificado(a) con el folio de matrícula inmobiliaria n.º {{inmueble_matricula}}, cuyos linderos y demás especificaciones constan en el título de adquisición.</p>'
                . '<p><strong>SEGUNDA. PRECIO Y FORMA DE PAGO.</strong> El precio es de {{valor_letras}} ({{valor}}), que el PROMITENTE COMPRADOR pagará así:</p>'
                . '<p>{{plan_pagos}}</p>'
                . '<p>Los pagos podrán hacerse en el portal {{portal}} o por el medio que las partes acuerden por escrito.</p>'
                . '<p><strong>TERCERA. ESCRITURA PÚBLICA.</strong> La escritura pública de compraventa se otorgará en la {{notaria}} el día {{fecha_escritura}}, siempre que el PROMITENTE COMPRADOR esté al día en los pagos previstos.</p>'
                . '<p><strong>CUARTA. ENTREGA.</strong> El inmueble se entregará materialmente a paz y salvo por concepto de impuesto predial, valorización, servicios públicos y cuotas de administración, en la fecha de la firma de la escritura o en la que las partes acuerden por escrito.</p>'
                . '<p><strong>QUINTA. LIBERTAD Y SANEAMIENTO.</strong> El PROMITENTE VENDEDOR garantiza que el inmueble es de su exclusiva propiedad y que está libre de embargos, hipotecas, demandas y limitaciones al dominio, y saldrá al saneamiento en los casos de ley.</p>'
                . '<p><strong>SEXTA. GASTOS.</strong> Los gastos notariales se pagarán por partes iguales. La retención en la fuente estará a cargo del PROMITENTE VENDEDOR, y el impuesto y los derechos de registro, a cargo del PROMITENTE COMPRADOR.</p>'
                . '<p><strong>SÉPTIMA. CLÁUSULA PENAL.</strong> La parte que incumpla cualquiera de las obligaciones de esta promesa pagará a la parte cumplida, a título de pena, el diez por ciento (10 %) del precio, sin perjuicio de que esta pueda exigir el cumplimiento del contrato.</p>'
                . '<p><strong>OCTAVA. INTERMEDIACIÓN.</strong> {{razon_social}}, NIT {{nit}}, actúa como intermediario en este negocio.</p>'
                . $firmas
                . '<p>_______________________________<br>PROMITENTE VENDEDOR<br>{{propietario_nombre}} · {{propietario_documento}}</p><p><br></p>'
                . '<p>_______________________________<br>PROMITENTE COMPRADOR<br>{{cliente_nombre}} · {{cliente_documento}}</p>',
        ],
    ];
}
