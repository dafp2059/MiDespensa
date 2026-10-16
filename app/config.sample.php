<?php
// Copia este archivo como app/config.php y completa tus datos.
// app/config.php NO se sube al repositorio (tiene contraseñas).

return [
    // Dirección pública de la web, sin barra final.
    'base_url' => 'https://ingesproinmobiliaria.com',

    // true solo mientras pruebas: muestra los errores de PHP en pantalla.
    'debug' => false,

    // Código que pide /admin/instalar.php para crear el primer administrador.
    // Evita que otra persona lo cree antes que tú. Puedes borrarlo después.
    'setup_code' => '',

    // true en la copia de pruebas (subdominio) para que Google no la muestre.
    'noindex' => false,

    // Base de datos (cPanel > Bases de datos MySQL).
    'db' => [
        'host' => 'localhost',
        'name' => 'usuario_ingespro',
        'user' => 'usuario_ingespro',
        'pass' => 'CAMBIAR',
    ],

    // Datos de la empresa que aparecen en toda la web.
    'site' => [
        'name' => 'INGESPRO Inmobiliaria',
        'legal_name' => 'INGESPRO INMOBILIARIA', // razón social exacta, como aparece en el RUT
        'nit' => '', // NIT de la empresa, aparece en la política de datos
        'tagline' => 'Donde cada meta inmobiliaria se convierte en realidad.',
        'phone' => '3042579430',
        'whatsapp' => '573042579430', // con indicativo de país, sin + ni espacios
        'email' => 'contacto@ingesproinmobiliaria.com',
        'address' => 'Cra. 45A #66A - 116',
        'city' => 'Medellín, Antioquia',
        'instagram' => 'https://www.instagram.com/ingesprohogar/',
        'facebook' => '',
        'hours' => 'Lunes a viernes 8:00 a. m. – 6:00 p. m. · Sábados 9:00 a. m. – 1:00 p. m.',
    ],

    // Correo que recibe los mensajes de los formularios.
    'notify_email' => 'contacto@ingesproinmobiliaria.com',

    // Wompi (comercios.wompi.co > Desarrolladores). Usa las llaves de prueba
    // (pub_test_..., test_integrity_..., test_events_...) hasta verificar que todo funciona.
    'wompi' => [
        'public_key' => 'pub_test_CAMBIAR',
        'integrity_secret' => 'test_integrity_CAMBIAR',
        'events_secret' => 'test_events_CAMBIAR',
    ],
];
