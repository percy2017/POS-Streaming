<?php
/**
 * Define los endpoints de la API REST específicos para el Módulo Streaming.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Registra las rutas API del módulo streaming.
 * Se engancha a 'pos_base_register_module_rest_routes'.
 *
 * @param string $namespace El namespace base (ej: 'pos-base/v1').
 */
function streaming_register_module_api_routes( $namespace ) {
    // Ruta para obtener perfiles disponibles
    register_rest_route( $namespace, '/streaming/available-profiles', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'streaming_api_get_available_profiles',
        'permission_callback' => 'pos_base_api_permissions_check', // Reutilizar el chequeo de permisos base
        // Podríamos añadir 'args' si necesitáramos filtrar por proveedor, etc.
    ) );

    // Registrar más rutas específicas del módulo aquí si es necesario
}
// El add_action se hará en pos-streaming-module.php


/**
 * Callback API: Obtener perfiles de streaming disponibles.
 * Realiza una WP_Query buscando pos_profile con estado 'available'.
 *
 * @param WP_REST_Request $request Objeto de la solicitud.
 * @return WP_REST_Response|WP_Error Lista de perfiles o error.
 */
function streaming_api_get_available_profiles( WP_REST_Request $request ) {
    error_log('[Streaming API DEBUG] Entrando en streaming_api_get_available_profiles.');

    $profiles_data = array();
    $args = array(
        'post_type'      => 'pos_profile',
        'post_status'    => 'publish', // Solo perfiles publicados
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_pos_profile_status',
                'value'   => 'available', // Buscar solo los disponibles
                'compare' => '=',
            ),
        ),
        'orderby'        => 'title', // Ordenar por nombre del perfil
        'order'          => 'ASC',
    );

    $profiles_query = new WP_Query( $args );

    if ( $profiles_query->have_posts() ) {
        while ( $profiles_query->have_posts() ) {
            $profiles_query->the_post();
            $profile_id = get_the_ID();
            $parent_account_id = get_post_meta( $profile_id, '_pos_profile_parent_account_id', true );
            $parent_account_title = $parent_account_id ? get_the_title( $parent_account_id ) : __( 'Cuenta Desconocida', 'pos-streaming' );

            $profiles_data[] = array(
                'id'            => $profile_id,
                'title'         => get_the_title(),
                'account_id'    => $parent_account_id ? (int) $parent_account_id : null,
                'account_title' => $parent_account_title,
                // Podríamos añadir más datos si fueran útiles para mostrar
            );
        }
        wp_reset_postdata();
    } else {
         error_log('[Streaming API DEBUG] No se encontraron perfiles disponibles.');
    }

    error_log('[Streaming API DEBUG] Devolviendo ' . count($profiles_data) . ' perfiles disponibles.');
    return new WP_REST_Response( $profiles_data, 200 );
}

?>
