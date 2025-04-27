<?php
/**
 * Encola los assets (CSS/JS) específicos del Módulo Streaming.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Encola los scripts y estilos del módulo streaming.
 * Se engancha a 'pos_base_enqueue_module_scripts'.
 *
 * @param string $hook_suffix El sufijo de la página actual.
 */
function streaming_enqueue_module_assets( $hook_suffix ) {
    // Asegurarse de que estamos en la página del POS Base
    $pos_page_hook = 'toplevel_page_pos-base';
    if ( $hook_suffix !== $pos_page_hook ) {
        return;
    }

    // Encolar CSS del módulo (si existe y es necesario)
    $css_file_path = POS_STREAMING_MODULE_DIR . 'assets/css/streaming-style.css';
    if ( file_exists( $css_file_path ) ) {
        wp_enqueue_style(
            'pos-streaming-style',
            POS_STREAMING_MODULE_URL . 'assets/css/streaming-style.css',
            array('pos-base-style'), // Depende del estilo base
            filemtime( $css_file_path )
        );
    }

    // Encolar JS del módulo
    $js_file_path = POS_STREAMING_MODULE_DIR . 'assets/js/streaming-app.js';
    if ( file_exists( $js_file_path ) ) {
        wp_enqueue_script(
            'pos-streaming-app',
            POS_STREAMING_MODULE_URL . 'assets/js/streaming-app.js',
            array('pos-base-app', 'jquery', 'wp-util'), // Depende del script base y jQuery/wp-util
            filemtime( $js_file_path ),
            true // Cargar en el footer
        );

        // Podríamos pasar datos específicos del módulo con wp_localize_script si fuera necesario
        /*
        wp_localize_script('pos-streaming-app', 'posStreamingParams', [
            'api_url' => esc_url_raw( rest_url( 'pos-base/v1/streaming/' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'i18n'    => [
                'select_profile' => __('Selecciona un perfil', 'pos-streaming'),
                // ... más traducciones
            ]
        ]);
        */
    }
}
// El add_action se hará en pos-streaming-module.php
