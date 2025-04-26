<?php
/**
 * Plugin Name:       POS Streaming
 * Plugin URI:        https://percyalvarez.com/plugins-wordpress
 * Description:       Punto de Venta (POS) full responsive para gestionar ventas de cuentas y perfiles de servicios streaming sobre WooCommerce.
 * Version:           1.0.0
 * Author:            Ing. Percy Alvarez
 * Author URI:        https://percyalvarez.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pos-streaming
 * Domain Path:       /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:   8.x
 */

// Evitar acceso directo al archivo
defined( 'ABSPATH' ) or die( '¡No tienes permiso para acceder aquí!' );


// --- Constantes del Plugin ---
define( 'POS_STREAMING_VERSION', '1.1.0' );
define( 'POS_STREAMING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POS_STREAMING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'POS_STREAMING_PLUGIN_FILE', __FILE__ );
define( 'POS_STREAMING_ASSETS_URL', POS_STREAMING_PLUGIN_URL . 'assets/' );

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', POS_STREAMING_PLUGIN_FILE, true ); // true indica que es compatible
    }
} );

/**
 * Función principal de inicialización del plugin.
 * Se ejecuta después de que todos los plugins estén cargados.
 */
function pos_streaming_init() {

    // --- Comprobación de WooCommerce ---
    if ( ! class_exists( 'WooCommerce' ) ) {
        // Mostrar aviso si WooCommerce no está activo
        add_action( 'admin_notices', 'pos_streaming_woocommerce_inactive_notice' );
        // No continuar cargando el resto del plugin si WC no está
        return;
    }

    // --- Inclusión de Archivos Principales (Sólo si WC está activo) ---
    require_once POS_STREAMING_PLUGIN_DIR . 'pos-page.php';
    require_once POS_STREAMING_PLUGIN_DIR . 'pos-api.php';
    require_once POS_STREAMING_PLUGIN_DIR . 'pos-metabox.php';
    // Podrías añadir más archivos aquí si tu lógica crece
    error_log('POS Streaming DEBUG: pos_streaming_init() - pos-metabox.php incluido. ¿Existe función? ' . function_exists('pos_streaming_add_order_metabox'));


    // --- Carga del Text Domain para Traducciones ---
    // Es correcto cargarlo aquí o en un hook 'plugins_loaded' separado con prioridad baja.
    load_plugin_textdomain(
        'pos-streaming',
        false,
        dirname( plugin_basename( POS_STREAMING_PLUGIN_FILE ) ) . '/languages/'
    );

    // --- Encolar Scripts y Estilos ---
    // El hook 'admin_enqueue_scripts' se dispara más tarde, por lo que está bien añadirlo aquí.
    add_action( 'admin_enqueue_scripts', 'pos_streaming_enqueue_assets' );

    add_filter( 'get_avatar', 'pos_streaming_get_custom_avatar', 10, 5 );

    // --- **MODIFICADO:** Usar hook general add_meta_boxes ---
    error_log('POS Streaming DEBUG: pos_streaming_init() - Añadiendo acción add_meta_boxes.');
    add_action( 'add_meta_boxes', 'pos_streaming_add_order_metabox', 10, 2 ); // Añadir prioridad y aceptar 2 argumentos
    
  
}
// Enganchar la inicialización al hook 'plugins_loaded'
add_action( 'plugins_loaded', 'pos_streaming_init' );


/**
 * Muestra un aviso en el admin si WooCommerce no está activo.
 * Esta función se define fuera de pos_streaming_init para que esté disponible
 * incluso si la inicialización principal se detiene.
 */
if ( ! function_exists( 'pos_streaming_woocommerce_inactive_notice' ) ) {
    function pos_streaming_woocommerce_inactive_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'El plugin "POS Streaming" requiere que WooCommerce esté activo y funcionando.', 'pos-streaming' ); ?></p>
        </div>
        <?php
    }
}


// --- Hooks de Activación / Desactivación ---
// Estos hooks se ejecutan fuera del flujo normal de carga, por lo que están bien aquí.
function pos_streaming_activate() {
    // Tu código de activación...
    // flush_rewrite_rules();
}
register_activation_hook( POS_STREAMING_PLUGIN_FILE, 'pos_streaming_activate' );

function pos_streaming_deactivate() {
    // Tu código de desactivación...
    // flush_rewrite_rules();
}
register_deactivation_hook( POS_STREAMING_PLUGIN_FILE, 'pos_streaming_deactivate' );


// --- Función para Encolar Assets ---
// La definición de la función puede permanecer aquí, pero la llamada (add_action)
// se movió dentro de pos_streaming_init.
function pos_streaming_enqueue_assets( $hook_suffix ) {

    echo "<!-- DEBUG: Hook Suffix Actual: " . esc_html( $hook_suffix ) . " -->";

    // El hook que ESPERAMOS (basado en add_menu_page con slug 'pos-streaming')
    $pos_page_hook = 'toplevel_page_pos-streaming';
    
    if ( $hook_suffix !== $pos_page_hook ) {
        return;
    }

    // soporte para Thickbox (modal nativo)
    add_thickbox();

    // Encolar scripts y estilos del Media Uploader
    wp_enqueue_media();

    // Estilos
    wp_enqueue_style( 'sweetalert2', POS_STREAMING_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.min.css', array(), '11.10.6' );
    wp_enqueue_style( 'intl-tel-input-css', POS_STREAMING_ASSETS_URL . 'vendor/intl-tel-input/css/intlTelInput.min.css', array(), '17.0.15' );
    wp_enqueue_style( 'pos-streaming-style', POS_STREAMING_ASSETS_URL . 'style.css', array('sweetalert2', 'intl-tel-input-css', 'thickbox'), POS_STREAMING_VERSION ); // Añadida dependencia

    // Scripts
    wp_enqueue_script( 'sweetalert2', POS_STREAMING_ASSETS_URL . 'vendor/sweetalert2/sweetalert2.all.min.js', array(), '11.10.6', true );
    wp_enqueue_script( 'fullcalendar', POS_STREAMING_ASSETS_URL . 'vendor/fullcalendar/index.global.min.js', array(), '6.1.11', true );
    wp_enqueue_script( 'intl-tel-input-js', POS_STREAMING_ASSETS_URL . 'vendor/intl-tel-input/js/intlTelInputWithUtils.min.js', array('jquery'), '17.0.15', true );
    wp_enqueue_script( 'pos-streaming-app', POS_STREAMING_ASSETS_URL . 'app.js', array('jquery', 'wp-util', 'sweetalert2', 'fullcalendar', 'intl-tel-input-js', 'thickbox'), POS_STREAMING_VERSION, true );


    // Localize
    wp_localize_script(
        'pos-streaming-app',
        'posStreamingParams',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'rest_url' => rest_url( 'pos_streaming/v1/' ),
            'admin_url' => admin_url( '/' ),
            'intlTelInputFlagsUrl' => POS_STREAMING_ASSETS_URL . 'vendor/intl-tel-input/img/flags.webp',
            'default_avatar_url' => get_avatar_url( 0, ['size' => 96, 'default' => 'mystery'] ),
            'i18n'     => array(
                'error_general' => __( 'Ocurrió un error inesperado.', 'pos-streaming' ),
                'confirm_accion' => __( '¿Estás seguro?', 'pos-streaming' ),
                'loading' => __( 'Cargando...', 'pos-streaming' ),
                'no_products_found' => __( 'No se encontraron productos.', 'pos-streaming' ),
                'search_placeholder' => __( 'Buscar producto por nombre o SKU...', 'pos-streaming' ),
                'add_customer' => __( 'Añadir Nuevo Cliente', 'pos-streaming' ),
                'edit_customer' => __( 'Editar Cliente', 'pos-streaming' ),
                'save_customer' => __( 'Guardar Cliente', 'pos-streaming' ),
                'cancel' => __( 'Cancelar', 'pos-streaming' ),
                'customer_first_name' => __( 'Nombre:', 'pos-streaming' ),
                'customer_last_name' => __( 'Apellido:', 'pos-streaming' ),
                'customer_email' => __( 'Email:', 'pos-streaming' ),
                'customer_phone' => __( 'Teléfono:', 'pos-streaming' ),
                'search_customer_placeholder' => __( 'Nombre, email o teléfono...', 'pos-streaming' ),
                'customer_required_fields' => __( 'Por favor, completa los campos requeridos.', 'pos-streaming' ),
                'customer_saved_success' => __( 'Cliente guardado correctamente.', 'pos-streaming' ),
                'customer_saved_error' => __( 'Error al guardar el cliente.', 'pos-streaming' ),
                'change_avatar' => __( 'Cambiar Imagen', 'pos-streaming' ),
                'remove_avatar' => __( 'Quitar Imagen', 'pos-streaming' ),
                'select_avatar_title' => __( 'Seleccionar o Subir Avatar', 'pos-streaming' ),
                'use_this_avatar' => __( 'Usar esta imagen', 'pos-streaming' ),
                'add_to_cart' => __( 'Añadir', 'pos-streaming' ), // Añadido
                'select_variation' => __( 'Selecciona opción:', 'pos-streaming' ), // Añadido
                'instock' => __( 'En stock', 'pos-streaming' ), // Añadido
                'outofstock' => __( 'Agotado', 'pos-streaming' ), // Añadido
                'cart_empty' => __( 'El carrito está vacío.', 'pos-streaming' ), // Añadido
                'anonymous' => __( 'Invitado', 'pos-streaming' ), 
                'loading_payment_methods' => __( 'Cargando métodos...', 'pos-streaming' ),
                'select_payment_method' => __( '-- Selecciona Método --', 'pos-streaming' ),
                'no_payment_methods' => __( 'No hay métodos', 'pos-streaming' ),
                'error_loading_payment_methods' => __( 'Error al cargar', 'pos-streaming' ),
               
            )
        )
    );
}

/**
 * Filtra la salida de get_avatar para usar la imagen personalizada si existe.
 *
 * @param string $avatar      HTML para mostrar el avatar.
 * @param mixed  $id_or_email El identificador del usuario (ID, email, objeto WP_User, etc.).
 * @param int    $size        Tamaño del avatar solicitado.
 * @param string $default     URL de la imagen por defecto o tipo ('mystery', 'blank', etc.).
 * @param string $alt         Texto alternativo para la imagen.
 *
 * @return string HTML modificado del avatar.
 */
function pos_streaming_get_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
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
    }

    if ( ! $user_id ) {
        return $avatar; // No se pudo identificar al usuario, devolver el avatar original (Gravatar)
    }

    // Obtener el ID del avatar personalizado desde el metadato
    $custom_avatar_id = get_user_meta( $user_id, 'pos_customer_avatar_id', true );

    if ( $custom_avatar_id ) {
        // Obtener la URL de la imagen adjunta en el tamaño solicitado
        $image_url = wp_get_attachment_image_url( $custom_avatar_id, array( $size, $size ) );

        if ( $image_url ) {
            // Construir la etiqueta <img> personalizada
            $avatar = sprintf(
                '<img alt="%s" src="%s" class="avatar avatar-%d photo" height="%d" width="%d" loading="lazy" decoding="async">',
                esc_attr( $alt ),
                esc_url( $image_url ),
                (int) $size,
                (int) $size,
                (int) $size
            );
        }
        // Si no se encuentra la URL (imagen borrada?), se devolverá el $avatar original (Gravatar)
    }

    return $avatar; // Devuelve el avatar personalizado o el original si no hay uno personalizado
}
