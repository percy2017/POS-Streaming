<?php
/**
 * Plugin Name:       POS 2025
 * Plugin URI:        https://percyalvarez.com/plugins-wordpress
 * Description:       Plugin base para Punto de Venta (POS) en WordPress/WooCommerce, con soporte para módulos extensibles.
 * Version:           1.1.0
 * Author:            Ing. Percy Alvarez
 * Author URI:        https://percyalvarez.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pos-base
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   8.x
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );


// --- Constantes del Plugin ---
define( 'POS_BASE_VERSION', '1.1.0' ); // Versión del plugin base
define( 'POS_BASE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POS_BASE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'POS_BASE_PLUGIN_FILE', __FILE__ );
define( 'POS_BASE_ASSETS_URL', POS_BASE_PLUGIN_URL . 'assets/' );

// Declarar compatibilidad con HPOS
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', POS_BASE_PLUGIN_FILE, true );
    }
} );

/**
 * Función principal de inicialización del plugin base.
 * Se ejecuta después de que todos los plugins estén cargados.
 */
function pos_base_init() {

    // --- Comprobación de WooCommerce ---
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'pos_base_woocommerce_inactive_notice' );
        return;
    }

    // --- Inclusión de Archivos Principales (Sólo si WC está activo) ---
    require_once POS_BASE_PLUGIN_DIR . 'pos-page.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-api.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-metabox.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-setting.php';
    require_once POS_BASE_PLUGIN_DIR . 'pos-tour.php';

    // --- Carga Dinámica de Módulos Activos ---
    $active_modules = get_option( 'pos_base_active_modules', [] );
    if ( ! empty( $active_modules ) && is_array( $active_modules ) ) {
        foreach ( $active_modules as $module_slug ) {
            $module_slug = sanitize_key( $module_slug );
            // Asumimos un nombre estándar para el archivo principal del módulo
            $module_file = POS_BASE_PLUGIN_DIR . 'modules/' . $module_slug . '/pos-' . $module_slug . '-module.php';
            if ( file_exists( $module_file ) && is_readable( $module_file ) ) {
                require_once $module_file;
            } else {
                 error_log( "POS Base WARNING: Módulo activo '" . $module_slug . "' no encontrado o no legible en: " . $module_file );
            }
        }
    }
    // --- Fin Carga Dinámica de Módulos ---


    // --- Carga del Text Domain para Traducciones del Plugin Base ---
    load_plugin_textdomain(
        'pos-base',
        false,
        dirname( plugin_basename( POS_BASE_PLUGIN_FILE ) ) . '/languages/'
    );

    // --- Encolar Scripts y Estilos Base ---
    add_action( 'admin_enqueue_scripts', 'pos_base_enqueue_assets' );

    // --- Filtro para Avatar Personalizado (Funcionalidad BASE) ---
    add_filter( 'get_avatar', 'pos_base_get_custom_avatar', 10, 5 );

    // --- Hook para Metabox de Pedido (Funcionalidad BASE) ---
    add_action( 'add_meta_boxes', 'pos_base_add_order_metabox', 10, 2 );

    // --- Hooks para que los módulos se registren (se añadirán en los módulos) ---

}
add_action( 'plugins_loaded', 'pos_base_init' );


/**
 * Muestra un aviso en el admin si WooCommerce no está activo.
 */
if ( ! function_exists( 'pos_base_woocommerce_inactive_notice' ) ) {
    function pos_base_woocommerce_inactive_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'El plugin "POS Base" requiere que WooCommerce esté activo y funcionando.', 'pos-base' ); ?></p>
        </div>
        <?php
    }
}


// --- Hooks de Activación / Desactivación ---
function pos_base_activate() {
    // Código de activación del plugin base
    flush_rewrite_rules(); // Buena idea si se añaden CPTs/rutas API
}
register_activation_hook( POS_BASE_PLUGIN_FILE, 'pos_base_activate' );

function pos_base_deactivate() {
    // Código de desactivación del plugin base
    flush_rewrite_rules();
}
register_deactivation_hook( POS_BASE_PLUGIN_FILE, 'pos_base_deactivate' );


/**
 * Enqueue scripts and styles for the admin area (POS Page).
 */
function pos_base_enqueue_assets( $hook_suffix ) {

    // Solo cargar en la página principal del POS
    $pos_page_hook = 'toplevel_page_pos-base';
    if ( $hook_suffix !== $pos_page_hook ) {
        return;
    }

    // Dependencias Nativas
    add_thickbox();
    wp_enqueue_media();

    // --- INICIO: Encolar Select2 (Bundled) ---
    $select2_css_path = POS_BASE_PLUGIN_DIR . 'assets/vendor/select2/css/select2.min.css';
    $select2_js_path = POS_BASE_PLUGIN_DIR . 'assets/vendor/select2/js/select2.full.min.js';

    if ( file_exists( $select2_css_path ) && file_exists( $select2_js_path ) ) {
        // Usar un handle propio para evitar conflictos
        wp_enqueue_style(
            'pos-base-select2', // Handle propio
            POS_BASE_ASSETS_URL . 'vendor/select2/css/select2.min.css',
            array(), // Sin dependencias CSS directas aquí
            filemtime( $select2_css_path ) // Versionado
        );
        wp_enqueue_script(
            'pos-base-select2', // Handle propio
            POS_BASE_ASSETS_URL . 'vendor/select2/js/select2.full.min.js',
            array('jquery'), // Depende de jQuery
            filemtime( $select2_js_path ), // Versionado
            true // Cargar en footer
        );
        error_log('[DEBUG Select2 Enqueue] Bundled Select2 CSS and JS enqueued.');
    } else {
        error_log('[DEBUG Select2 Enqueue] ERROR: Bundled Select2 files not found in plugin vendor directory.');
    }
    // --- FIN: Encolar Select2 (Bundled) ---

    // Estilos Vendor (comunes)
    wp_enqueue_style( 'pos-base-datatables', POS_BASE_ASSETS_URL . 'vendor/datatables/datatables.min.css', array(), '1.13.8' );
    wp_enqueue_style( 'pos-base-sweetalert2', POS_BASE_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.min.css', array(), '11.10.6' );
    wp_enqueue_style( 'pos-base-intl-tel-input', POS_BASE_ASSETS_URL . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '18.2.1' );

    // Estilo Base del POS
    wp_enqueue_style(
        'pos-base-style',
        POS_BASE_ASSETS_URL . 'style.css',
        array('pos-base-datatables', 'pos-base-sweetalert2', 'pos-base-intl-tel-input', 'thickbox','pos-base-select2'), // Dependencias vendor
        filemtime( POS_BASE_PLUGIN_DIR . 'assets/style.css' )
    );

    // Scripts Vendor (comunes)
    wp_enqueue_script( 'pos-base-datatables', POS_BASE_ASSETS_URL . 'vendor/datatables/datatables.min.js', array('jquery'), '1.13.8', true );
    wp_enqueue_script( 'pos-base-sweetalert2', POS_BASE_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.all.min.js', array(), '11.10.6', true );
    wp_enqueue_script( 'pos-base-fullcalendar', POS_BASE_ASSETS_URL . 'vendor/fullcalendar/index.global.min.js', array(), '6.1.11', true );
    wp_enqueue_script( 'pos-base-intl-tel-input', POS_BASE_ASSETS_URL . 'vendor/intl-tel-input/js/intlTelInputWithUtils.min.js', array('jquery'), '18.2.1', true );

    // Script Base de la Aplicación POS
    wp_enqueue_script(
        'pos-base-app',
        POS_BASE_ASSETS_URL . 'app.js',
        array('jquery', 'wp-util', 'thickbox', 'pos-base-datatables', 'pos-base-sweetalert2', 'pos-base-fullcalendar', 'pos-base-intl-tel-input', 'pos-base-select2'), // Dependencias vendor y WP
        filemtime( POS_BASE_PLUGIN_DIR . 'assets/app.js' ),
        true
    );

    /* --- INICIO: BLOQUE DEL TOUR ELIMINADO ---
    // --- ENCOLAR SCRIPT DEL TOUR Y PASAR DATOS ---
    // if ( $load_tour ) {
    //     wp_enqueue_script(
    //         'pos-base-tour',
    //         POS_BASE_ASSETS_URL . 'js/pos-tour.js',
    //         array( 'jquery', 'wp-pointer' ),
    //         filemtime( POS_BASE_PLUGIN_DIR . 'assets/js/pos-tour.js' ),
    //         true
    //     );
    //
    //     // Obtener los datos de los pointers definidos en pos-tour.php
    //     // if ( function_exists('pos_base_get_tour_pointers') && ! empty( $tour_pointers = pos_base_get_tour_pointers() ) ) {
    //     //     wp_localize_script(
    //     //         'pos-base-tour',
    //     //         'posBaseTourData',
    //     //         array(
    //     //             'pointers' => $tour_pointers,
    //     //         )
    //     //     );
    //     // }
    // }
    // --- FIN SCRIPT DEL TOUR ---
    */ // --- FIN: BLOQUE DEL TOUR ELIMINADO ---


    // Localize Script Base
    wp_localize_script(
        'pos-base-app',
        'posBaseParams',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'rest_url' => esc_url_raw( rest_url( 'pos-base/v1/' ) ), // Namespace correcto
            'admin_url' => admin_url(),
            'intlTelInputUtilsScript' => esc_url( POS_BASE_ASSETS_URL . 'vendor/intl-tel-input/js/utils.js' ),
            'default_avatar_url' => get_avatar_url( 0, ['size' => 96, 'default' => 'mystery'] ),
            'i18n' => array(
                // Usar text domain 'pos-base'
                'loading' => __( 'Cargando...', 'pos-base' ),
                'saving' => __( 'Guardando...', 'pos-base' ),
                'processing' => __( 'Procesando...', 'pos-base' ),
                'error_general' => __( 'Ocurrió un error inesperado.', 'pos-base' ),
                'search_placeholder' => __( 'Buscar producto por nombre o SKU...', 'pos-base' ),
                'search_customer_placeholder' => __( 'Buscar cliente por nombre, email o teléfono...', 'pos-base' ),
                'no_products_found' => __( 'No se encontraron productos.', 'pos-base' ),
                'no_featured_products_found' => __( 'No hay productos destacados.', 'pos-base' ),
                'add_to_cart' => __( 'Añadir', 'pos-base' ),
                'select_variation' => __( 'Selecciona opción:', 'pos-base' ),
                'instock' => __( 'En stock', 'pos-base' ),
                'outofstock' => __( 'Agotado', 'pos-base' ),
                'cart_empty' => __( 'El carrito está vacío.', 'pos-base' ),
                'add_customer' => __( 'Añadir Nuevo Cliente', 'pos-base' ),
                'edit_customer' => __( 'Editar Cliente', 'pos-base' ),
                'customer_required_fields' => __( 'Por favor, completa los campos requeridos.', 'pos-base' ),
                'customer_saved_success' => __( 'Cliente guardado correctamente.', 'pos-base' ),
                'customer_saved_error' => __( 'Error al guardar el cliente.', 'pos-base' ),
                'loading_customer_data' => __( 'Cargando datos del cliente...', 'pos-base' ),
                'searching' => __( 'Buscando...', 'pos-base' ),
                'search_error' => __( 'Error al buscar.', 'pos-base' ),
                'no_customers_found' => __( 'No se encontraron clientes.', 'pos-base' ),
                'anonymous' => __( 'Invitado', 'pos-base' ),
                'select_avatar_title' => __( 'Seleccionar o Subir Avatar', 'pos-base' ),
                'use_this_avatar' => __( 'Usar esta imagen', 'pos-base' ),
                'loading_payment_methods' => __( 'Cargando métodos de pago...', 'pos-base' ),
                'select_payment_method' => __( '-- Selecciona Método de Pago --', 'pos-base' ),
                'no_payment_methods' => __( 'No hay métodos de pago activos.', 'pos-base' ),
                'error_loading_payment_methods' => __( 'Error al cargar métodos.', 'pos-base' ),
                'complete_sale' => __( 'Completar Venta', 'pos-base' ),
                'creating_order' => __( 'Creando pedido...', 'pos-base' ),
                'order_created_success' => __( '¡Pedido Creado!', 'pos-base' ),
                'order_created_error' => __( 'Error al crear el pedido.', 'pos-base' ),
                'coupon_code_required' => __( 'Por favor, ingresa un código de cupón.', 'pos-base' ),
                'apply' => __( 'Aplicar', 'pos-base' ),
                'validating' => __( 'Validando...', 'pos-base' ),
                'coupon_applied_success' => __( 'Cupón "%s" aplicado correctamente.', 'pos-base' ),
                'coupon_invalid' => __( 'El código de cupón no es válido.', 'pos-base' ),
                'remove_coupon' => __( 'Quitar cupón', 'pos-base' ),

                // DataTables
                'dt_processing' => __( 'Procesando...', 'pos-base' ),
                'dt_search' => __( 'Buscar:', 'pos-base' ),
                'dt_lengthMenu' => __( 'Mostrar _MENU_ registros', 'pos-base' ),
                'dt_info' => __( 'Mostrando _START_ a _END_ de _TOTAL_ registros', 'pos-base' ),
                'dt_infoEmpty' => __( 'Mostrando 0 a 0 de 0 registros', 'pos-base' ),
                'dt_infoFiltered' => __( '(filtrado de _MAX_ registros totales)', 'pos-base' ),
                'dt_loadingRecords' => __( 'Cargando...', 'pos-base' ),
                'dt_zeroRecords' => __( 'No se encontraron registros coincidentes', 'pos-base' ),
                'dt_emptyTable' => __( 'No hay datos disponibles en la tabla', 'pos-base' ),
                'dt_paginate_first' => __( 'Primero', 'pos-base' ),
                'dt_paginate_previous' => __( 'Anterior', 'pos-base' ),
                'dt_paginate_next' => __( 'Siguiente', 'pos-base' ),
                'dt_paginate_last' => __( 'Último', 'pos-base' ),
                'dt_aria_sortAscending' => __( ': activar para ordenar la columna ascendente', 'pos-base' ),
                'dt_aria_sortDescending' => __( ': activar para ordenar la columna descendente', 'pos-base' )
            )
        )
    );

    // --- Hook para que los módulos encolen sus propios assets ---
    do_action( 'pos_base_enqueue_module_scripts', $hook_suffix );
}

/**
 * Filtra la salida de get_avatar para usar la imagen personalizada si existe.
 */
function pos_base_get_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
    $user_id = false;

    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
        $user_id = (int) $id_or_email->user_id;
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if ( $user ) {
            $user_id = $user->ID;
        }
    } elseif ( $id_or_email instanceof WP_User ) {
         $user_id = $id_or_email->ID;
    } elseif ( $id_or_email instanceof WP_Comment ) {
         if ( ! empty( $id_or_email->user_id ) ) {
             $user_id = (int) $id_or_email->user_id;
         } elseif ( ! empty( $id_or_email->comment_author_email ) ) {
             $user = get_user_by( 'email', $id_or_email->comment_author_email );
             if ( $user ) {
                 $user_id = $user->ID;
             }
         }
    }

    if ( ! $user_id ) {
        return $avatar;
    }

    $custom_avatar_id = get_user_meta( $user_id, 'pos_customer_avatar_id', true );

    if ( $custom_avatar_id ) {
        $image_url = wp_get_attachment_image_url( $custom_avatar_id, array( $size, $size ), false );

        if ( $image_url ) {
            $avatar = sprintf(
                '<img alt="%s" src="%s" class="avatar avatar-%d photo pos-custom-avatar" height="%d" width="%d" loading="lazy" decoding="async">',
                esc_attr( $alt ),
                esc_url( $image_url ),
                (int) $size,
                (int) $size,
                (int) $size
            );
        }
    }

    return $avatar;
}

/**
 * Añade un enlace de "Configuración" a la fila del plugin en la página de plugins.
 */
function pos_base_add_settings_link( $actions, $plugin_file ) {
    if ( plugin_basename( POS_BASE_PLUGIN_FILE ) === $plugin_file ) {
        $settings_url = admin_url( 'admin.php?page=pos-base-settings' );
        $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configuración', 'pos-base' ) . '</a>';
        array_unshift( $actions, $settings_link );
    }
    return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( POS_BASE_PLUGIN_FILE ), 'pos_base_add_settings_link', 10, 2 );

?>
