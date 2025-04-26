<?php
/**
 * Registro de Custom Post Types para Cuentas y Perfiles de POS Streaming. 
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Registra los CPTs al iniciar WordPress.
 */
function pos_streaming_register_cpts() {

    // --- CPT: Cuenta (Proveedor) ---
    $account_labels = array(
        'name'                  => _x( 'Cuentas POS', 'Post Type General Name', 'pos-streaming' ),
        'singular_name'         => _x( 'Cuenta POS', 'Post Type Singular Name', 'pos-streaming' ),
        'menu_name'             => __( 'Cuentas POS', 'pos-streaming' ), // Nombre del menú principal
        'name_admin_bar'        => __( 'Cuenta POS', 'pos-streaming' ),
        'archives'              => __( 'Archivo de Cuentas', 'pos-streaming' ),
        'attributes'            => __( 'Atributos de Cuenta', 'pos-streaming' ),
        'parent_item_colon'     => __( 'Cuenta Padre:', 'pos-streaming' ),
        'all_items'             => __( 'Todas las Cuentas', 'pos-streaming' ), // Nombre del submenú de listado
        'add_new_item'          => __( 'Añadir Nueva Cuenta', 'pos-streaming' ),
        'add_new'               => __( 'Añadir Nueva', 'pos-streaming' ), // Nombre del submenú de añadir
        'new_item'              => __( 'Nueva Cuenta', 'pos-streaming' ),
        'edit_item'             => __( 'Editar Cuenta', 'pos-streaming' ),
        'update_item'           => __( 'Actualizar Cuenta', 'pos-streaming' ),
        'view_item'             => __( 'Ver Cuenta', 'pos-streaming' ),
        'view_items'            => __( 'Ver Cuentas', 'pos-streaming' ),
        'search_items'          => __( 'Buscar Cuenta', 'pos-streaming' ),
        'not_found'             => __( 'No encontrada', 'pos-streaming' ),
        'not_found_in_trash'    => __( 'No encontrada en Papelera', 'pos-streaming' ),
        'featured_image'        => __( 'Imagen Destacada', 'pos-streaming' ),
        'set_featured_image'    => __( 'Establecer Imagen Destacada', 'pos-streaming' ),
        'remove_featured_image' => __( 'Quitar Imagen Destacada', 'pos-streaming' ),
        'use_featured_image'    => __( 'Usar como Imagen Destacada', 'pos-streaming' ),
        'insert_into_item'      => __( 'Insertar en Cuenta', 'pos-streaming' ),
        'uploaded_to_this_item' => __( 'Subido a esta Cuenta', 'pos-streaming' ),
        'items_list'            => __( 'Lista de Cuentas', 'pos-streaming' ),
        'items_list_navigation' => __( 'Navegación Lista de Cuentas', 'pos-streaming' ),
        'filter_items_list'     => __( 'Filtrar Lista de Cuentas', 'pos-streaming' ),
    );
    $account_args = array(
        'label'                 => __( 'Cuenta POS', 'pos-streaming' ),
        'description'           => __( 'Cuentas principales de servicios streaming (proveedores).', 'pos-streaming' ),
        'labels'                => $account_labels,
        'supports'              => array( 'title', 'custom-fields' ), // Esencial para título y metaboxes
        'hierarchical'          => false,
        'public'                => false, // No visible en el frontend
        'show_ui'               => true,  // Mostrar interfaz en admin
        'show_in_menu'          => true,  // <-- CORREGIDO: Crear su propio menú principal
        'menu_position'         => 59,    // Posición en el menú (después de POS Streaming)
        'menu_icon'             => 'dashicons-id-alt', // Icono para el menú principal
        'show_in_admin_bar'     => true,  // Mostrar en la barra de admin (opcional)
        'show_in_nav_menus'     => false, // No mostrar en menús de navegación del sitio
        'can_export'            => true,  // Permitir exportar
        'has_archive'           => false, // No necesita página de archivo
        'exclude_from_search'   => true,  // No incluir en búsquedas del sitio
        'publicly_queryable'    => false, // No accesible por URL directa
        'capability_type'       => 'post',// Usar permisos base de 'post'
        'map_meta_cap'          => true,  // Necesario para permisos de metadatos
        'show_in_rest'          => false, // No exponer en API REST por defecto
        'rewrite'               => false, // No necesita URLs amigables
    );
    register_post_type( 'pos_account', $account_args ); // <-- REGISTRA 'pos_account'

    // --- CPT: Perfil ---
    $profile_labels = array(
        'name'                  => _x( 'Perfiles POS', 'Post Type General Name', 'pos-streaming' ),
        'singular_name'         => _x( 'Perfil POS', 'Post Type Singular Name', 'pos-streaming' ),
        'menu_name'             => __( 'Perfiles POS', 'pos-streaming' ), // Nombre para el menú
        'name_admin_bar'        => __( 'Perfil POS', 'pos-streaming' ),
        'parent_item_colon'     => __( 'Cuenta Padre:', 'pos-streaming' ),
        'all_items'             => __( 'Todos los Perfiles', 'pos-streaming' ), // Submenú de listado
        'add_new_item'          => __( 'Añadir Nuevo Perfil', 'pos-streaming' ),
        'add_new'               => __( 'Añadir Nuevo', 'pos-streaming' ), // Submenú de añadir
        'new_item'              => __( 'Nuevo Perfil', 'pos-streaming' ),
        'edit_item'             => __( 'Editar Perfil', 'pos-streaming' ),
        'update_item'           => __( 'Actualizar Perfil', 'pos-streaming' ),
        'view_item'             => __( 'Ver Perfil', 'pos-streaming' ),
        'search_items'          => __( 'Buscar Perfil', 'pos-streaming' ),
        'not_found'             => __( 'No encontrado', 'pos-streaming' ),
        'not_found_in_trash'    => __( 'No encontrado en Papelera', 'pos-streaming' ),
        'items_list'            => __( 'Lista de Perfiles', 'pos-streaming' ),
        'items_list_navigation' => __( 'Navegación Lista de Perfiles', 'pos-streaming' ),
        'filter_items_list'     => __( 'Filtrar Lista de Perfiles', 'pos-streaming' ),
    );
    $profile_args = array(
        'label'                 => __( 'Perfil POS', 'pos-streaming' ),
        'description'           => __( 'Perfiles individuales dentro de una cuenta POS.', 'pos-streaming' ),
        'labels'                => $profile_labels,
        'supports'              => array( 'title', 'custom-fields' ), // Esencial para título y metaboxes
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        // Mostrar como submenú del NUEVO menú principal 'Cuentas POS'
        'show_in_menu'          => 'edit.php?post_type=pos_account', // <-- Correcto: bajo el menú de Cuentas
        'menu_position'         => 10, // Posición dentro del submenú
        // 'menu_icon'          => 'dashicons-admin-users', // No necesita icono propio si es submenú
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
        'show_in_rest'          => false,
        'rewrite'               => false,
    );
    register_post_type( 'pos_profile', $profile_args ); // <-- REGISTRA 'pos_profile'

}
// ¡IMPORTANTE! Enganchar la función al hook 'init'
add_action( 'init', 'pos_streaming_register_cpts' );

?>
