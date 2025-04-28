<?php
 /**
  * Module Name: Streaming
  * Description: Gestiona Cuentas y Perfiles de Streaming, permitiendo asignarlos a ventas de suscripción desde el POS.
  * Author: Ing. Percy Alvarez
  * Version: 1.0.0
  */
 
 
// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

// --- Constantes del Módulo ---
define( 'POS_STREAMING_MODULE_VERSION', '1.0.0' ); // Versión del módulo
define( 'POS_STREAMING_MODULE_DIR', plugin_dir_path( __FILE__ ) );
define( 'POS_STREAMING_MODULE_URL', plugin_dir_url( __FILE__ ) );
define( 'POS_STREAMING_TEXT_DOMAIN', 'pos-streaming' ); // Text domain para este módulo

// --- Inclusión de Archivos del Módulo ---

// 1. Cargar archivo de registro de CPTs
// Contiene la función streaming_register_cpts()
require_once POS_STREAMING_MODULE_DIR . 'streaming-cpts.php';

// 2. Cargar archivo de metaboxes para CPTs (y quizás pedidos)
// Contendrá la función para añadir los campos personalizados
require_once POS_STREAMING_MODULE_DIR . 'streaming-metaboxes.php'; // Descomentar cuando se cree

// 3. Cargar archivo de API específica del módulo
// Contendrá la función para registrar rutas API del módulo
require_once POS_STREAMING_MODULE_DIR . 'streaming-api.php'; // Descomentar cuando se cree

// 4. Cargar archivo de hooks generales (frontend, checkout, etc.)
// Contendrá add_action/add_filter para integrar con POS Base y WC
require_once POS_STREAMING_MODULE_DIR . 'streaming-hooks.php'; // Descomentar cuando se cree

// 5. Cargar archivo para encolar assets del módulo (JS/CSS)
// Contendrá la función para encolar assets específicos del módulo
require_once POS_STREAMING_MODULE_DIR . 'streaming-assets.php'; // Descomentar cuando se cree


// --- Registro de Hooks Iniciales ---

// Enganchar la función de registro de CPTs al hook 'init' de WordPress
// Esto asegura que los CPTs se registren en el momento adecuado.
add_action( 'init', 'streaming_register_cpts' );

// Enganchar la función para añadir metaboxes (cuando exista)
add_action( 'add_meta_boxes', 'streaming_add_metaboxes' ); // Descomentar cuando se cree streaming-metaboxes.php y la función

// Enganchar la función para guardar los datos del metabox
add_action( 'save_post_pos_account', 'streaming_save_metabox_data', 10, 2 ); // <-- AÑADIR ESTA LÍNEA (el 10 y 2 son importantes)
add_action( 'save_post_pos_profile', 'streaming_save_metabox_data', 10, 2 ); // <-- AÑADIR ESTA LÍNEA

// Enganchar la función para registrar rutas API del módulo (cuando exista)
add_action( 'pos_base_register_module_rest_routes', 'streaming_register_module_api_routes' ); // Descomentar cuando se cree streaming-api.php y la función

// Enganchar la función para encolar assets del módulo (cuando exista)
add_action( 'pos_base_enqueue_module_scripts', 'streaming_enqueue_module_assets' ); // Descomentar cuando se cree streaming-assets.php y la función


// --- Carga del Text Domain del Módulo ---
/**
 * Carga el text domain para las traducciones del módulo streaming.
 */
function streaming_load_textdomain() {
    load_plugin_textdomain(
        POS_STREAMING_TEXT_DOMAIN,
        false,
        basename( dirname( __FILE__ ) ) . '/languages/' // Ruta relativa: streaming/languages/
    );
}
// Ejecutar después de que el plugin base cargue el suyo (prioridad > 10)
add_action( 'plugins_loaded', 'streaming_load_textdomain', 15 );

function streaming_add_admin_submenus() {
    // Añadir submenú para Cuentas Streaming
    add_submenu_page(
        'pos-base',                          // Slug del menú padre (el menú principal de POS Base)
        __( 'Cuentas Streaming', 'pos-streaming' ), // Título de la página
        __( 'Cuentas Streaming', 'pos-streaming' ), // Título del menú
        'edit_posts',                        // Capacidad requerida (ajustar si es necesario, ej: 'manage_options')
        'edit.php?post_type=pos_account',    // Slug/URL: apunta directamente a la lista de Cuentas
        '',                                  // Sin función de callback directa (usa la página estándar de WP)
        25                                   // Posición (opcional, debe coincidir o ser mayor que la del CPT)
    );

    // Añadir submenú para Perfiles Streaming
    add_submenu_page(
        'pos-base',                          // Slug del menú padre
        __( 'Perfiles Streaming', 'pos-streaming' ), // Título de la página
        __( 'Perfiles Streaming', 'pos-streaming' ), // Título del menú
        'edit_posts',                        // Capacidad requerida
        'edit.php?post_type=pos_profile',    // Slug/URL: apunta directamente a la lista de Perfiles
        '',                                  // Sin función de callback directa
        26                                   // Posición (opcional)
    );
}
add_action( 'admin_menu', 'streaming_add_admin_submenus', 20 );

?>
