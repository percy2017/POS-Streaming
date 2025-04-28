<?php
/**
 * Module Name: Evolution API
 * Description: Gestiona la conexión y envío de mensajes a través de una instancia de Evolution API.
 * Author: Tu Nombre/Empresa
 * Version: 1.0.0
 */

// Salir si se accede directamente.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- Constantes del Módulo ---

// Define la ruta del directorio del módulo.
if ( ! defined( 'EVOLUTION_API_MODULE_DIR' ) ) {
    define( 'EVOLUTION_API_MODULE_DIR', plugin_dir_path( __FILE__ ) );
}

// Define la URL del directorio del módulo.
if ( ! defined( 'EVOLUTION_API_MODULE_URL' ) ) {
    define( 'EVOLUTION_API_MODULE_URL', plugin_dir_url( __FILE__ ) );
}

// --- Inclusión de Archivos del Módulo ---
// Incluye los archivos necesarios que estarán en la raíz de este módulo.

$required_files = [
    'settings.php',    // Gestionará los ajustes del módulo.
    'api-client.php',  // Contendrá la clase o funciones para interactuar con la API.
    'hooks.php',       // Contendrá los hooks para integrar con WP/WC/POS Base.
    'cron.php',        // Gestiona las tareas programadas.
    'admin-page.php',  // Renderizará la página de gestión de instancia.

    // Añade aquí otros archivos PHP principales si son necesarios.
];

foreach ( $required_files as $file ) {
    $file_path = EVOLUTION_API_MODULE_DIR . $file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    } else {
        // Opcional: Registrar un error si un archivo esencial no se encuentra.
        error_log( "POS Base - Módulo Evolution API: Archivo requerido no encontrado - " . $file_path );
    }
}

// --- Añadir Submenú de Gestión de Instancia ---
/**
 * Añade la página de submenú para gestionar la instancia de Evolution API.
 * Se ejecuta solo si este módulo está activo.
 */
function pos_evolution_api_add_submenu() {
    add_submenu_page(
        'pos-base',                             // Slug del menú padre (POS Base)
        __( 'Gestionar Instancia Evolution API', 'pos-base' ), // Título de la página
        __( 'Evolution API', 'pos-base' ),      // Título del menú
        'manage_woocommerce',                   // Capacidad requerida (la misma que para el POS)
        'pos-evolution-api-instance',           // Slug único para esta página de submenú
        'pos_evolution_api_render_instance_page' // Función callback para renderizar la página (definida en admin-page.php)
    );
}
// Enganchar la función al hook admin_menu.
// Se registrará solo cuando este archivo sea incluido por POS Base (módulo activo).
add_action( 'admin_menu', 'pos_evolution_api_add_submenu' );


// --- Inicialización del Módulo ---
// (Código de inicialización adicional si es necesario)

?>
