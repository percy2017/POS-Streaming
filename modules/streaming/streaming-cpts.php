<?php
/**
 * Registra los Custom Post Types para el Módulo Streaming.
 * Define 'pos_account' y 'pos_profile'.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Registra los CPTs 'pos_account' y 'pos_profile'.
 * Esta función será llamada por el hook 'init' desde el archivo principal del módulo.
 */
function streaming_register_cpts() {

    // --- CPT: Cuenta POS (Proveedor de Streaming) ---
    $account_labels = array(
        'name'                  => _x( 'Cuentas Streaming', 'Post type general name', 'pos-streaming' ), // Text domain específico del módulo
        'singular_name'         => _x( 'Cuenta Streaming', 'Post type singular name', 'pos-streaming' ),
        'menu_name'             => _x( 'Cuentas Streaming', 'Admin Menu text', 'pos-streaming' ),
        'name_admin_bar'        => _x( 'Cuenta Streaming', 'Add New on Toolbar', 'pos-streaming' ),
        'add_new'               => __( 'Añadir Nueva', 'pos-streaming' ),
        'add_new_item'          => __( 'Añadir Nueva Cuenta', 'pos-streaming' ),
        'new_item'              => __( 'Nueva Cuenta', 'pos-streaming' ),
        'edit_item'             => __( 'Editar Cuenta', 'pos-streaming' ),
        'view_item'             => __( 'Ver Cuenta', 'pos-streaming' ),
        'all_items'             => __( 'Todas las Cuentas', 'pos-streaming' ),
        'search_items'          => __( 'Buscar Cuentas', 'pos-streaming' ),
        'parent_item_colon'     => __( 'Cuenta Padre:', 'pos-streaming' ), // No aplica
        'not_found'             => __( 'No se encontraron cuentas.', 'pos-streaming' ),
        'not_found_in_trash'    => __( 'No se encontraron cuentas en la papelera.', 'pos-streaming' ),
        // 'featured_image'        => _x( 'Logo Proveedor', 'Overrides the “Featured Image” phrase', 'pos-streaming' ), // Eliminado soporte thumbnail
        // 'set_featured_image'    => _x( 'Establecer logo', 'Overrides the “Set featured image” phrase', 'pos-streaming' ), // Eliminado soporte thumbnail
        // 'remove_featured_image' => _x( 'Quitar logo', 'Overrides the “Remove featured image” phrase', 'pos-streaming' ), // Eliminado soporte thumbnail
        // 'use_featured_image'    => _x( 'Usar como logo', 'Overrides the “Use as featured image” phrase', 'pos-streaming' ), // Eliminado soporte thumbnail
        'archives'              => _x( 'Archivo de Cuentas', 'post type archive label', 'pos-streaming' ),
        'insert_into_item'      => _x( 'Insertar en cuenta', 'insert media into post', 'pos-streaming' ),
        'uploaded_to_this_item' => _x( 'Subido a esta cuenta', 'media attached to post', 'pos-streaming' ),
        'filter_items_list'     => _x( 'Filtrar lista de cuentas', 'Screen reader text filter links', 'pos-streaming' ),
        'items_list_navigation' => _x( 'Navegación lista de cuentas', 'Screen reader text pagination', 'pos-streaming' ),
        'items_list'            => _x( 'Lista de cuentas', 'Screen reader text items list', 'pos-streaming' ),
    );
    $account_args = array(
        'labels'             => $account_labels,
        'public'             => false, // No visible en el frontend por defecto
        'publicly_queryable' => false, // No consultable directamente
        'show_ui'            => true,  // Mostrar en el panel de administración
        'show_in_menu'       => false, // Mostrar bajo el menú principal de POS Base
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post', // Usar permisos estándar
        // 'capabilities'    => array(...) // Podríamos definir capacidades personalizadas si fuera necesario
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 25, // Posición relativa bajo el menú padre (ajustar según necesidad)
        'menu_icon'          => 'dashicons-cloud', // Icono representativo
        'supports'           => array( 'title', 'custom-fields' ), // Título (Nombre Proveedor), Custom Fields (para metaboxes). Se quitó 'thumbnail'.
        'show_in_rest'       => true, // Habilitar API REST para este CPT
        'rest_base'          => 'streaming-accounts', // Slug para la API REST
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    );
    register_post_type( 'pos_account', $account_args ); // Slug del CPT: pos_account

    // --- CPT: Perfil POS (Dentro de una cuenta) ---
    $profile_labels = array(
        'name'                  => _x( 'Perfiles Streaming', 'Post type general name', 'pos-streaming' ),
        'singular_name'         => _x( 'Perfil Streaming', 'Post type singular name', 'pos-streaming' ),
        'menu_name'             => _x( 'Perfiles Streaming', 'Admin Menu text', 'pos-streaming' ),
        'name_admin_bar'        => _x( 'Perfil Streaming', 'Add New on Toolbar', 'pos-streaming' ),
        'add_new'               => __( 'Añadir Nuevo', 'pos-streaming' ),
        'add_new_item'          => __( 'Añadir Nuevo Perfil', 'pos-streaming' ),
        'new_item'              => __( 'Nuevo Perfil', 'pos-streaming' ),
        'edit_item'             => __( 'Editar Perfil', 'pos-streaming' ),
        'view_item'             => __( 'Ver Perfil', 'pos-streaming' ),
        'all_items'             => __( 'Todos los Perfiles', 'pos-streaming' ),
        'search_items'          => __( 'Buscar Perfiles', 'pos-streaming' ),
        'parent_item_colon'     => __( 'Perfil Padre:', 'pos-streaming' ), // No aplica
        'not_found'             => __( 'No se encontraron perfiles.', 'pos-streaming' ),
        'not_found_in_trash'    => __( 'No se encontraron perfiles en la papelera.', 'pos-streaming' ),
        'archives'              => _x( 'Archivo de Perfiles', 'post type archive label', 'pos-streaming' ),
        'insert_into_item'      => _x( 'Insertar en perfil', 'insert media into post', 'pos-streaming' ),
        'uploaded_to_this_item' => _x( 'Subido a este perfil', 'media attached to post', 'pos-streaming' ),
        'filter_items_list'     => _x( 'Filtrar lista de perfiles', 'Screen reader text filter links', 'pos-streaming' ),
        'items_list_navigation' => _x( 'Navegación lista de perfiles', 'Screen reader text pagination', 'pos-streaming' ),
        'items_list'            => _x( 'Lista de perfiles', 'Screen reader text items list', 'pos-streaming' ),
    );
    $profile_args = array(
        'labels'             => $profile_labels,
        'public'             => false, // No visible en el frontend
        'show_ui'            => true,  // Mostrar en admin
        'show_in_menu'       => false, // Mostrar bajo el menú principal de POS Base
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 26, // Justo debajo de Cuentas
        'menu_icon'          => 'dashicons-admin-users', // Icono
        'supports'           => array( 'title', 'custom-fields' ), // Título (Nombre Perfil), Custom Fields (para metaboxes que indiquen a qué cuenta pertenece, estado, etc.)
        'show_in_rest'       => true, // Habilitar API REST
        'rest_base'          => 'streaming-profiles', // Slug para la API REST
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    );
    register_post_type( 'pos_profile', $profile_args ); // Slug del CPT: pos_profile

    // --- Taxonomía: Proveedor de Streaming ---
    $provider_labels = array(
        'name'              => _x( 'Proveedores de Streaming', 'taxonomy general name', 'pos-streaming' ),
        'singular_name'     => _x( 'Proveedor de Streaming', 'taxonomy singular name', 'pos-streaming' ),
        'search_items'      => __( 'Buscar Proveedores', 'pos-streaming' ),
        'all_items'         => __( 'Todos los Proveedores', 'pos-streaming' ),
        'parent_item'       => __( 'Proveedor Padre', 'pos-streaming' ), // No jerárquico
        'parent_item_colon' => __( 'Proveedor Padre:', 'pos-streaming' ), // No jerárquico
        'edit_item'         => __( 'Editar Proveedor', 'pos-streaming' ),
        'update_item'       => __( 'Actualizar Proveedor', 'pos-streaming' ),
        'add_new_item'      => __( 'Añadir Nuevo Proveedor', 'pos-streaming' ),
        'new_item_name'     => __( 'Nombre del Nuevo Proveedor', 'pos-streaming' ),
        'menu_name'         => __( 'Proveedores', 'pos-streaming' ),
        'not_found'         => __( 'No se encontraron proveedores.', 'pos-streaming' ),
        'no_terms'          => __( 'No hay proveedores', 'pos-streaming' ),
        'items_list'        => __( 'Lista de proveedores', 'pos-streaming' ),
        'items_list_navigation' => __( 'Navegación lista de proveedores', 'pos-streaming' ),
    );
    $provider_args = array(
        'labels'            => $provider_labels,
        'hierarchical'      => false, // Como etiquetas, no como categorías
        'public'            => false, // No visible en el frontend por defecto
        'show_ui'           => true,  // Mostrar en el panel de administración
        // 'show_in_menu'      => 'pos-base', // No queremos que aparezca en el menú lateral
        'show_in_menu'      => false, // Aseguramos que no se muestre en el menú lateral
        // 'meta_box_cb'       => true, // No mostrar el metabox de taxonomía por defecto (usamos el nuestro)
        'show_admin_column' => true,  // Mostrar columna en la tabla de Cuentas
        'query_var'         => false, // No necesario para consultas directas
        'rewrite'           => false, // Sin reescritura de URL
        'show_in_nav_menus' => false, // No mostrar en menús de navegación
        'show_tagcloud'     => false, // No mostrar en nube de etiquetas
        'show_in_rest'      => true,  // Habilitar en la API REST
        'rest_base'         => 'streaming-providers', // Slug para la API REST
        'rest_controller_class' => 'WP_REST_Terms_Controller',
    );
    register_taxonomy(
        'streaming_provider', // Slug de la taxonomía
        array( 'pos_account' ), // Asociar SOLO con el CPT 'pos_account'
        $provider_args
    );


}

// NOTA IMPORTANTE: El add_action('init', 'streaming_register_cpts');
// NO se pone aquí. Se añadirá en 'pos-streaming-module.php'.

?>
