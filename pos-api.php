<?php

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// =========================================================================
// 1. REGISTRO DE RUTAS API REST
// =========================================================================
/**
 * Registra todas las rutas de la API REST para POS Base.
 * Define argumentos para validación y sanitización.
 */
function pos_base_register_rest_routes() {
    error_log('POS Base DEBUG: Ejecutando pos_base_register_rest_routes...');
    $namespace = 'pos-base/v1';

    // --- RUTA PARA BUSCAR PRODUCTOS ---
    register_rest_route( $namespace, '/products', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_base_api_search_products',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
            'search' => array(
                'description'       => __( 'Término de búsqueda para nombre o SKU del producto.', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'page' => array(
                'description'       => __( 'Número de página actual de la colección.', 'pos-base' ),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
            ),
            'per_page' => array(
                'description'       => __( 'Número máximo de items a devolver por página.', 'pos-base' ),
                'type'              => 'integer',
                'default'           => 50, // Ajusta si tienes una constante diferente
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
            ),
            'featured' => array(
                'description'       => __( 'Limitar resultados solo a productos destacados.', 'pos-base' ),
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
            // Podrías añadir aquí 'context', 'orderby', 'order' si los necesitas
        ),
    ) );

    // --- RUTA PARA BUSCAR CLIENTES ---
    register_rest_route( $namespace, '/customers', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_base_api_search_customers',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
            'search' => array(
                'description'       => __( 'Término de búsqueda para nombre, email o teléfono del cliente.', 'pos-base' ),
                'type'              => 'string',
                'required'          => true, // La búsqueda requiere un término
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'per_page' => array(
                'description'       => __( 'Número máximo de clientes a devolver.', 'pos-base' ),
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
            ),
        ),
    ) );

    // --- RUTA PARA OBTENER UN CLIENTE ESPECÍFICO ---
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_base_api_get_customer',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
            'id' => array(
                'description'       => __( 'ID único del cliente.', 'pos-base' ),
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
            ),
        ),
    ) );

    // --- RUTA PARA CREAR UN CLIENTE ---
    register_rest_route( $namespace, '/customers', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_base_api_create_customer',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
            'email' => array(
                'description'       => __( 'Dirección de correo electrónico del cliente.', 'pos-base' ),
                'type'              => 'string',
                'format'            => 'email', // Valida formato email
                'sanitize_callback' => 'sanitize_email',
                // 'required'       => true, // Depende si quieres permitir clientes sin email
            ),
            'first_name' => array(
                'description'       => __( 'Nombre del cliente.', 'pos-base' ),
                'type'              => 'string',
                'required'          => true, // Generalmente requerido
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'last_name' => array(
                'description'       => __( 'Apellido del cliente.', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'phone' => array(
                'description'       => __( 'Número de teléfono del cliente.', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field', // O una sanitización más específica para teléfonos
            ),
            'meta_data' => array(
                'description'       => __( 'Metadatos adicionales del cliente (ej: avatar, nota).', 'pos-base' ),
                'type'              => 'array',
                'items'             => array(
                    'type'       => 'object',
                    'properties' => array(
                        'key' => array(
                            'description' => __( 'Clave del metadato.', 'pos-base' ),
                            'type'        => 'string',
                            'required'    => true,
                            'sanitize_callback' => 'sanitize_key',
                        ),
                        'value' => array(
                            'description' => __( 'Valor del metadato.', 'pos-base' ),
                            'type'        => ['string', 'integer', 'boolean', 'number'], // Permitir varios tipos
                            // 'sanitize_callback' => 'wp_kses_post', // O sanitizar según el tipo esperado
                        ),
                    ),
                ),
            ),
        ),
    ) );

    // --- RUTA PARA ACTUALIZAR UN CLIENTE ---
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::EDITABLE, // PUT, PATCH
        'callback'            => 'pos_base_api_update_customer',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
             'id' => array( // El ID viene de la URL pero lo definimos para claridad/validación
                'description'       => __( 'ID único del cliente a actualizar.', 'pos-base' ),
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) { return is_numeric( $param ) && $param > 0; },
            ),
            'email' => array(
                'description'       => __( 'Nueva dirección de correo electrónico.', 'pos-base' ),
                'type'              => 'string',
                'format'            => 'email',
                'sanitize_callback' => 'sanitize_email',
            ),
            'first_name' => array(
                'description'       => __( 'Nuevo nombre.', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'last_name' => array(
                'description'       => __( 'Nuevo apellido.', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'phone' => array(
                'description'       => __( 'Nuevo número de teléfono.', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'meta_data' => array( // Misma estructura que en la creación
                'description'       => __( 'Metadatos a actualizar.', 'pos-base' ),
                'type'              => 'array',
                'items'             => array( /* ... (igual que en create) ... */ ),
            ),
        ),
    ) );

    // --- RUTA PARA OBTENER PASARELAS DE PAGO ---
    register_rest_route( $namespace, '/payment-gateways', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_base_api_get_payment_gateways',
        'permission_callback' => 'pos_base_api_permissions_check',
        // No necesita 'args' ya que no toma parámetros de entrada
    ) );

    // --- RUTA PARA VALIDAR CUPONES ---
    register_rest_route( $namespace, '/coupons/validate', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_base_api_validate_coupon',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
            'code' => array(
                'description'       => __( 'Código del cupón a validar.', 'pos-base' ),
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // --- RUTA PARA CREAR PEDIDOS ---
    register_rest_route( $namespace, '/orders', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_base_api_create_order',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array(
            'customer_id' => array(
                'description'       => __( 'ID del cliente para el pedido.', 'pos-base' ),
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => function( $param ) { return is_numeric( $param ) && $param >= 0; }, // Permitir cliente invitado (ID 0)
            ),
            'line_items' => array(
                'description'       => __( 'Array de productos en el carrito.', 'pos-base' ),
                'type'              => 'array',
                'required'          => true,
                'items'             => array(
                    'type'       => 'object',
                    'properties' => array(
                        'product_id' => array( 'type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint' ),
                        'variation_id' => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
                        'quantity' => array( 'type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint', 'validate_callback' => function($p){ return $p > 0; } ),
                        'price' => array( 'type' => 'number', 'required' => true, /* sanitización/validación podría ser más compleja */ ),
                    ),
                ),
                'validate_callback' => function( $param ) { return ! empty( $param ) && is_array( $param ); }, // Asegurar que no esté vacío
            ),
            'payment_method' => array(
                'description'       => __( 'ID de la pasarela de pago.', 'pos-base' ),
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_key',
            ),
            'payment_method_title' => array(
                'description'       => __( 'Título de la pasarela de pago (si es manual).', 'pos-base' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'set_paid' => array(
                'description'       => __( 'Marcar el pedido como pagado.', 'pos-base' ),
                'type'              => 'boolean',
                'default'           => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
            'meta_data' => array( // Misma estructura que en cliente
                'description'       => __( 'Metadatos adicionales del pedido (ej: tipo venta, suscripción).', 'pos-base' ),
                'type'              => 'array',
                'items'             => array( /* ... (igual que en create customer) ... */ ),
            ),
             'coupon_lines' => array(
                'description'       => __( 'Array de cupones aplicados.', 'pos-base' ),
                'type'              => 'array',
                 'items'             => array(
                    'type'       => 'object',
                    'properties' => array(
                        'code' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
                    ),
                ),
            ),
            'pos_order_note' => array(
                 'description'       => __( 'Nota privada para el pedido añadida desde el POS.', 'pos-base' ),
                 'type'              => 'string',
                 'sanitize_callback' => 'sanitize_textarea_field',
            ),
        ),
    ) );

    // --- RUTA PARA OBTENER EVENTOS DEL CALENDARIO ---
    register_rest_route( $namespace, '/calendar-events', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_base_api_get_calendar_events',
        'permission_callback' => 'pos_base_api_permissions_check',
        // Podría tener args para 'start' y 'end' date si se implementa filtrado por fecha
    ) );

    // --- RUTA PARA DATATABLES DE VENTAS ---
    register_rest_route( $namespace, '/sales-datatable', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_base_api_get_sales_for_datatable',
        'permission_callback' => 'pos_base_api_permissions_check',
        'args'                => array( // Argumentos estándar de DataTables Server-Side
            'draw' => array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'required'          => true,
            ),
            'start' => array(
                'type'              => 'integer',
                'default'           => 0,
                'sanitize_callback' => 'absint',
            ),
            'length' => array(
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($p){ return $p > 0 || $p === -1; }, // Permitir -1 para "todos"
            ),
            'search' => array(
                'type'              => 'object', // O 'array' dependiendo de cómo lo envíe DT
                'properties'        => array(
                    'value' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
                    'regex' => array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ),
                ),
            ),
             'order' => array(
                'type'              => 'array',
                 'items'             => array(
                    'type'       => 'object',
                    'properties' => array(
                        'column' => array( 'type' => 'integer', 'sanitize_callback' => 'absint' ),
                        'dir'    => array( 'type' => 'string', 'enum' => ['asc', 'desc'] ),
                    ),
                ),
            ),
            // Podrías añadir 'columns' si necesitas info específica de columnas
        ),
    ) );

    // --- Hook para que los MÓDULOS registren sus propias rutas ---
    do_action( 'pos_base_register_module_rest_routes', $namespace );

}
add_action( 'rest_api_init', 'pos_base_register_rest_routes' ); // <-- Asegúrate que esta línea existe al final del archivo

// =========================================================================
// 2. FUNCIONES CALLBACK DE LA API
// =========================================================================

/**
 * Callback API: Buscar productos.
 * Utiliza los parámetros validados de la solicitud para construir WP_Query.
 */
function pos_base_api_search_products( WP_REST_Request $request ) {
    // Obtener parámetros validados y sanitizados por la definición de 'args' en register_rest_route
    $per_page = $request['per_page'];
    $page = $request['page'];
    $search_term = $request['search'];
    $is_featured = $request['featured']; // Esto será true/false gracias a rest_sanitize_boolean

    error_log('POS Base DEBUG: Entrando en pos_base_api_search_products. Search: "' . $search_term . '", Page: ' . $page . ', PerPage: ' . $per_page . ', Featured: ' . ($is_featured ? 'true' : 'false'));

    // --- Construir los argumentos para WP_Query ---
    $args = array(
        'post_type'      => 'product',       // Buscar solo productos
        'post_status'    => 'publish',     // Solo productos publicados
        'posts_per_page' => $per_page,      // Productos por página
        'paged'          => $page,          // Página actual
        'orderby'        => 'title',       // Ordenar por título por defecto
        'order'          => 'ASC',
        // Ignorar productos pegajosos (sticky posts) si los hubiera
        'ignore_sticky_posts' => 1,
    );

    // Añadir término de búsqueda si existe
    if ( ! empty( $search_term ) ) {
        $args['s'] = $search_term; // 's' busca en título, contenido, extracto (y SKU si está configurado)
    }

    // Añadir filtro para productos destacados si se solicitó Y no hay búsqueda
    if ( $is_featured && empty( $search_term ) ) {
        if ( ! isset( $args['tax_query'] ) ) {
            $args['tax_query'] = array( 'relation' => 'AND' );
        }
        $args['tax_query'][] = array(
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => 'featured',
            'operator' => 'IN',
        );
    }
    // --- Fin construcción de argumentos ---

    error_log('POS Base DEBUG: WP_Query args: ' . print_r($args, true));

    // Ejecutar la consulta
    $products_query = new WP_Query( $args );
    $products_data = array();

    // Procesar resultados
    if ( $products_query->have_posts() ) {
        while ( $products_query->have_posts() ) {
            $products_query->the_post();
            $product_post_id = get_the_ID();
            $product = wc_get_product( $product_post_id );

            if ( ! $product || $product->get_type() === 'variation' ) {
                continue;
            }

            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src();

            // Construir datos básicos del producto
            $product_item = array(
                'id'          => $product->get_id(),
                'name'        => $product->get_name(),
                'sku'         => $product->get_sku(),
                'price'       => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price'  => $product->get_sale_price(),
                'type'        => $product->get_type(),
                'stock_status'=> $product->get_stock_status(),
                'stock_quantity' => $product->get_stock_quantity(),
                'image_url'   => $image_url,
                'variations'  => array()
            );

            // Si es un producto variable, obtener sus variaciones
            if ( $product->is_type( 'variable' ) ) {
                $variations = $product->get_available_variations();
                foreach ( $variations as $variation_data ) {
                    $variation_obj = wc_get_product( $variation_data['variation_id'] );
                    if ( ! $variation_obj ) continue;

                    $variation_image_id = $variation_obj->get_image_id();
                    $variation_image_url = $variation_image_id ? wp_get_attachment_image_url( $variation_image_id, 'thumbnail' ) : $image_url;

                    $product_item['variations'][] = array(
                        'variation_id' => $variation_data['variation_id'],
                        'attributes'   => $variation_data['attributes'],
                        'display_price'=> $variation_data['display_price'],
                        'display_regular_price' => $variation_data['display_regular_price'],
                        'price'        => $variation_obj->get_price(),
                        'regular_price'=> $variation_obj->get_regular_price(),
                        'sale_price'   => $variation_obj->get_sale_price(),
                        'sku'          => $variation_obj->get_sku(),
                        'stock_status' => $variation_obj->get_stock_status(),
                        'stock_quantity' => $variation_obj->get_stock_quantity(),
                        'image_url'    => $variation_image_url,
                        'is_in_stock'  => $variation_obj->is_in_stock(),
                    );
                }
            }
            $products_data[] = $product_item;
        }
        wp_reset_postdata();
    } else {
        error_log('POS Base DEBUG: WP_Query no encontró productos.');
    }

    // Preparar la respuesta REST
    $response = new WP_REST_Response( $products_data, 200 );

    // Añadir cabeceras de paginación
    $total_products = $products_query->found_posts;
    $max_pages = $products_query->max_num_pages;
    $response->header( 'X-WP-Total', $total_products );
    $response->header( 'X-WP-TotalPages', $max_pages );

    error_log('POS Base DEBUG: pos_base_api_search_products devolviendo ' . count($products_data) . ' productos.');
    return $response;
}

/**
 * Callback API: Buscar clientes.
 * MODIFICADO: Prioriza búsqueda por teléfono si el término parece un número.
 */
function pos_base_api_search_customers( WP_REST_Request $request ) {
    // Obtener parámetros validados
    $search_term = $request['search'];
    $per_page = $request['per_page'];

    error_log('POS Base DEBUG: Entrando en pos_base_api_search_customers. Search: "' . $search_term . '", PerPage: ' . $per_page);

    // --- INICIO: LÓGICA CONDICIONAL DE BÚSQUEDA ---
    $args = array(
        'number' => $per_page, // Número de resultados
        'orderby' => 'display_name', // Ordenar por nombre visible
        'order' => 'ASC',
        // Podríamos añadir 'role' => 'customer' si solo queremos clientes
    );

    // Comprobar si el término de búsqueda parece un número de teléfono
    // (Permite dígitos, espacios, +, -, (, ) )
    if ( preg_match('/^[\d\s\+\(\)-]+$/', $search_term) ) {
        error_log('POS Base DEBUG: Search term looks like a phone number. Querying meta only.');
        // Si parece teléfono, buscar SOLO en el campo meta 'billing_phone'
        $args['meta_query'] = array(
            array(
                'key'     => 'billing_phone',
                'value'   => $search_term,
                'compare' => 'LIKE' // Usar LIKE para flexibilidad con espacios/formato
            ),
        );
    } else {
        error_log('POS Base DEBUG: Search term is general. Querying standard fields and meta.');
        // Si no parece teléfono, usar la búsqueda general original
        $args['search'] = '*' . esc_attr( $search_term ) . '*'; // Buscar en campos de usuario
        $args['search_columns'] = array( // Columnas estándar donde buscar
            'user_login',
            'user_nicename',
            'user_email',
            'display_name',
        );
        $args['meta_query'] = array( // Buscar también en metadatos (nombre, apellido, teléfono como fallback)
            'relation' => 'OR',
            array(
                'key'     => 'first_name',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
            array(
                'key'     => 'last_name',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
            // Mantenemos la búsqueda por teléfono aquí también por si alguien busca
            // un teléfono junto con texto, aunque la condición anterior lo prioriza.
            array(
                'key'     => 'billing_phone',
                'value'   => $search_term,
                'compare' => 'LIKE'
            ),
        );
    }
    // --- FIN: LÓGICA CONDICIONAL DE BÚSQUEDA ---

    error_log('POS Base DEBUG: WP_User_Query args: ' . print_r($args, true));

    $user_query = new WP_User_Query( $args );
    $customers_data = array();

    if ( ! empty( $user_query->get_results() ) ) {
        foreach ( $user_query->get_results() as $user ) {
            // Usar función auxiliar para formatear la respuesta
            $customers_data[] = pos_base_prepare_customer_data_for_response( $user->ID );
        }
    } else {
        error_log('POS Base DEBUG: WP_User_Query no encontró clientes.');
    }

    error_log('POS Base DEBUG: pos_base_api_search_customers devolviendo ' . count($customers_data) . ' clientes.');
    return new WP_REST_Response( $customers_data, 200 );
}

/**
 * Callback API: Obtener un cliente específico.
 */
function pos_base_api_get_customer( WP_REST_Request $request ) {
    // El ID ya está validado como entero > 0 por 'args'
    $customer_id = $request['id'];

    error_log('POS Base DEBUG: Entrando en pos_base_api_get_customer. ID: ' . $customer_id);

    $user = get_user_by( 'id', $customer_id );
    if ( ! $user ) {
        error_log('POS Base DEBUG: Cliente ID ' . $customer_id . ' no encontrado.');
        return new WP_Error( 'rest_customer_invalid_id', __( 'Cliente no encontrado.', 'pos-base' ), array( 'status' => 404 ) );
    }

    // Usar función auxiliar para formatear la respuesta
    $customer_data = pos_base_prepare_customer_data_for_response( $customer_id );

    if (empty($customer_data)) {
         error_log('POS Base DEBUG: pos_base_prepare_customer_data_for_response devolvió vacío para ID ' . $customer_id);
         return new WP_Error( 'rest_customer_data_error', __( 'No se pudieron obtener los datos del cliente.', 'pos-base' ), array( 'status' => 500 ) );
    }

    error_log('POS Base DEBUG: pos_base_api_get_customer devolviendo datos para ID ' . $customer_id);
    return new WP_REST_Response( $customer_data, 200 );
}

/**
 * Callback API: Crear un nuevo cliente.
 */
function pos_base_api_create_customer( WP_REST_Request $request ) {
    // Parámetros ya validados/sanitizados por 'args'
    $email = $request['email'];
    $first_name = $request['first_name'];
    $last_name = $request['last_name'];
    $phone = $request['phone'];
    $meta_data = $request['meta_data'] ?? []; // Asegurar que sea array

    error_log('POS Base DEBUG: Entrando en pos_base_api_create_customer. Email: ' . $email . ', Nombre: ' . $first_name);

    // Validaciones adicionales (ej: email único si se proporciona)
    if ( ! empty( $email ) && email_exists( $email ) ) {
        error_log('POS Base DEBUG: Error - Email ya existe: ' . $email);
        return new WP_Error( 'rest_customer_email_exists', __( 'Ya existe un cliente con esta dirección de correo electrónico.', 'pos-base' ), array( 'status' => 400 ) );
    }

    // Generar nombre de usuario y contraseña
    $username = ! empty( $email ) ? $email : sanitize_user( $first_name . $last_name . wp_rand( 100, 999 ), true );
    if ( username_exists( $username ) ) $username .= wp_rand( 10, 99 );
    $password = wp_generate_password();

    // Crear cliente usando función de WooCommerce
    $customer_id = wc_create_new_customer( $email, $username, $password );

    if ( is_wp_error( $customer_id ) ) {
        error_log('POS Base DEBUG: Error en wc_create_new_customer: ' . $customer_id->get_error_message());
        return new WP_Error( 'rest_customer_creation_failed', $customer_id->get_error_message(), array( 'status' => 500 ) );
    }

    error_log('POS Base DEBUG: Cliente creado con ID: ' . $customer_id);

    // Actualizar datos básicos que wc_create_new_customer no maneja directamente
    wp_update_user( array( 'ID' => $customer_id, 'first_name' => $first_name, 'last_name' => $last_name ) );
    if ( ! empty( $phone ) ) update_user_meta( $customer_id, 'billing_phone', $phone );

    // Guardar metadatos personalizados (avatar y nota)
    if ( ! empty( $meta_data ) ) {
        foreach ( $meta_data as $meta_item ) {
            // Los 'key' y 'value' ya deberían estar sanitizados por 'args' si se definieron bien
            if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                $key = $meta_item['key']; // Ya sanitizado como 'sanitize_key'
                $value = $meta_item['value']; // Sanitizar aquí si no se hizo en 'args'
                if ( $key === 'pos_customer_avatar_id' ) {
                    $avatar_id = absint($value);
                    if ($avatar_id > 0) {
                        update_user_meta( $customer_id, 'pos_customer_avatar_id', $avatar_id );
                        error_log('POS Base DEBUG: Avatar ID ' . $avatar_id . ' guardado para cliente ' . $customer_id);
                    } else {
                        delete_user_meta( $customer_id, 'pos_customer_avatar_id' );
                        error_log('POS Base DEBUG: Avatar eliminado para cliente ' . $customer_id);
                    }
                } elseif ( $key === '_pos_customer_note' ) {
                    update_user_meta( $customer_id, '_pos_customer_note', sanitize_textarea_field($value) );
                    error_log('POS Base DEBUG: Nota guardada para cliente ' . $customer_id);
                }
            }
        }
    }

    // Preparar y devolver respuesta
    $customer_data = pos_base_prepare_customer_data_for_response( $customer_id );
    $response = new WP_REST_Response( $customer_data, 201 ); // 201 Created
    error_log('POS Base DEBUG: pos_base_api_create_customer completado para ID ' . $customer_id);
    return $response;
}

/**
 * Callback API: Actualizar un cliente existente.
 */
function pos_base_api_update_customer( WP_REST_Request $request ) {
    // ID validado por 'args'
    $customer_id = $request['id'];

    error_log('POS Base DEBUG: Entrando en pos_base_api_update_customer. ID: ' . $customer_id);

    $user = get_user_by( 'id', $customer_id );
    if ( ! $user ) {
        error_log('POS Base DEBUG: Error - Cliente no encontrado para actualizar: ' . $customer_id);
        return new WP_Error( 'rest_customer_invalid_id', __( 'Cliente no encontrado para actualizar.', 'pos-base' ), array( 'status' => 404 ) );
    }

    $update_data = array( 'ID' => $customer_id );
    // Obtener todos los parámetros enviados (ya sanitizados por 'args')
    $params = $request->get_params();

    // Campos principales (solo actualizar si se enviaron)
    if ( isset( $params['first_name'] ) ) $update_data['first_name'] = $params['first_name'];
    if ( isset( $params['last_name'] ) ) $update_data['last_name'] = $params['last_name'];
    if ( isset( $params['email'] ) ) {
        $email = $params['email']; // Ya sanitizado
        // Validar unicidad si el email cambió
        if ( $email !== $user->user_email ) {
            $existing_user = email_exists( $email );
            if ( $existing_user && $existing_user !== $customer_id ) {
                 error_log('POS Base DEBUG: Error - Email ' . $email . ' ya existe para otro usuario.');
                return new WP_Error( 'rest_customer_email_exists', __( 'Ya existe otro cliente con esta dirección de correo electrónico.', 'pos-base' ), array( 'status' => 400 ) );
            }
        }
        $update_data['user_email'] = $email;
    }

    // Actualizar datos del usuario
    if (count($update_data) > 1) { // Solo si hay algo más que el ID
        $result = wp_update_user( $update_data );
        if ( is_wp_error( $result ) ) {
            error_log('POS Base DEBUG: Error en wp_update_user para ID ' . $customer_id . ': ' . $result->get_error_message());
            return new WP_Error( 'rest_customer_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
        }
         error_log('POS Base DEBUG: Datos de usuario actualizados para ID ' . $customer_id . ': ' . print_r($update_data, true));
    }


    // Teléfono (actualizar meta si se envió)
    if ( isset( $params['phone'] ) ) {
        update_user_meta( $customer_id, 'billing_phone', $params['phone'] ); // Ya sanitizado
        error_log('POS Base DEBUG: Teléfono actualizado para ID ' . $customer_id);
    }

    // Actualizar metadatos personalizados (avatar y nota)
    if ( isset( $params['meta_data'] ) && is_array( $params['meta_data'] ) ) {
        foreach ( $params['meta_data'] as $meta_item ) {
             if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                $key = $meta_item['key']; // Ya sanitizado
                $value = $meta_item['value']; // Sanitizar aquí si no se hizo en 'args'
                if ( $key === 'pos_customer_avatar_id' ) {
                    $avatar_id = absint($value);
                     if ($avatar_id > 0) {
                        update_user_meta( $customer_id, 'pos_customer_avatar_id', $avatar_id );
                        error_log('POS Base DEBUG: Avatar ID ' . $avatar_id . ' actualizado para cliente ' . $customer_id);
                    } else {
                        delete_user_meta( $customer_id, 'pos_customer_avatar_id' );
                        error_log('POS Base DEBUG: Avatar eliminado para cliente ' . $customer_id);
                    }
                } elseif ( $key === '_pos_customer_note' ) {
                    update_user_meta( $customer_id, '_pos_customer_note', sanitize_textarea_field($value) );
                    error_log('POS Base DEBUG: Nota actualizada para cliente ' . $customer_id);
                }
            }
        }
    }

    // Preparar y devolver respuesta
    $customer_data = pos_base_prepare_customer_data_for_response( $customer_id );
    error_log('POS Base DEBUG: pos_base_api_update_customer completado para ID ' . $customer_id);
    return new WP_REST_Response( $customer_data, 200 ); // 200 OK
}

/**
 * Callback API: Obtener las pasarelas de pago activas y añadir opción manual POS.
 */
function pos_base_api_get_payment_gateways( WP_REST_Request $request ) {
    error_log('POS Base DEBUG: Entrando en pos_base_api_get_payment_gateways.');
    $gateways_data = array();
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

    // Añadir pasarelas activas de WooCommerce
    if ( $available_gateways ) {
        foreach ( $available_gateways as $gateway ) {
            if ( $gateway->enabled === 'yes' ) {
                $gateways_data[] = array( 'id' => $gateway->id, 'title' => $gateway->get_title() );
            }
        }
    }

    // SIEMPRE añadir la opción manual para el POS
    $gateways_data[] = array( 'id' => 'pos_manual', 'title' => __('Efectivo / Manual (POS)', 'pos-base') );

    // Opcional: Eliminar duplicados si 'pos_manual' ya existiera por alguna razón
    $gateways_data = array_values(array_unique($gateways_data, SORT_REGULAR)); // array_values para reindexar

    error_log('POS Base DEBUG: pos_base_api_get_payment_gateways devolviendo: ' . print_r($gateways_data, true));
    return new WP_REST_Response( $gateways_data, 200 );
}

/**
 * Callback API: Validar un código de cupón.
 */
function pos_base_api_validate_coupon( WP_REST_Request $request ) {
    // Código ya validado/sanitizado por 'args'
    $coupon_code = $request['code'];

    error_log('POS Base DEBUG: Entrando en pos_base_api_validate_coupon. Code: ' . $coupon_code);

    if ( ! wc_coupons_enabled() ) {
        error_log('POS Base DEBUG: Error - Cupones deshabilitados.');
        return new WP_Error( 'rest_coupons_disabled', __( 'Los cupones están deshabilitados.', 'pos-base' ), array( 'status' => 400 ) );
    }

    $coupon = new WC_Coupon( $coupon_code );

    if ( ! $coupon->get_id() ) {
        error_log('POS Base DEBUG: Error - Cupón no existe: ' . $coupon_code);
        return new WP_Error( 'rest_coupon_invalid_code', sprintf( __( 'El cupón "%s" no existe.', 'pos-base' ), $coupon_code ), array( 'status' => 404 ) );
    }

    // Validar el cupón (considerando el carrito si fuera necesario, aunque aquí no tenemos carrito)
    $is_valid = $coupon->is_valid();

    if ( ! $is_valid ) {
        $error_message = '';
        // Intentar obtener un mensaje de error más específico si es posible
        // Nota: validate_coupon_usage necesita un objeto WC_Cart, que no tenemos aquí.
        // La validación básica de is_valid() suele cubrir fechas, límites de uso, etc.
        // Si necesitamos validar contra productos específicos, se complica.
        try {
            // Simulación simple de validación (podría no ser suficiente para todas las reglas)
            if ( $coupon->get_date_expires() && $coupon->get_date_expires()->get_timestamp() < time() ) {
                $error_message = __( 'Este cupón ha expirado.', 'pos-base' );
            } elseif ( $coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
                 $error_message = __( 'Se ha alcanzado el límite de usos para este cupón.', 'pos-base' );
            }
            // Añadir más validaciones si es necesario (ej: minimum_amount)
        } catch (Exception $e) { $error_message = $e->getMessage(); }

        if (empty($error_message)) $error_message = sprintf( __( 'El cupón "%s" no es válido.', 'pos-base' ), $coupon_code );

        error_log('POS Base DEBUG: Error - Cupón inválido: ' . $coupon_code . ' - Razón: ' . $error_message);
        return new WP_Error( 'rest_coupon_invalid', $error_message, array( 'status' => 400 ) );
    }

    // --- Construir datos del cupón para la respuesta ---
    $coupon_data = array(
        'id'                          => $coupon->get_id(),
        'code'                        => $coupon->get_code(),
        'amount'                      => wc_format_decimal( $coupon->get_amount(), wc_get_price_decimals() ), // Valor numérico
        'discount_type'               => $coupon->get_discount_type(), // 'fixed_cart', 'percent', 'fixed_product'
        'description'                 => $coupon->get_description(),
        'date_expires'                => $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d H:i:s' ) : null,
        'minimum_amount'              => wc_format_decimal( $coupon->get_minimum_amount(), wc_get_price_decimals() ),
        'maximum_amount'              => wc_format_decimal( $coupon->get_maximum_amount(), wc_get_price_decimals() ),
        'individual_use'              => $coupon->get_individual_use(),
        'exclude_sale_items'          => $coupon->get_exclude_sale_items(),
        'free_shipping'               => $coupon->get_free_shipping(),
        // Podríamos añadir product_ids, excluded_product_ids, etc. si son necesarios
    );
    // --- Fin construcción datos ---

    error_log('POS Base DEBUG: Cupón validado correctamente: ' . $coupon_code);
    return new WP_REST_Response( $coupon_data, 200 );
}

/**
 * Callback API: Crear un pedido.
 * Maneja la creación del pedido, asignación de items, precios, metadatos,
 * cupones, y la actualización del estado del perfil de streaming si aplica.
 * MODIFICADO: Añade validación de perfil solo si el módulo streaming está activo.
 * CORREGIDO: Ubicación del bloque set_date_created.
 */
function pos_base_api_create_order( WP_REST_Request $request ) {
    // Parámetros ya validados/sanitizados por 'args'
    $params = $request->get_params(); // Usar get_params() ya que 'args' los procesa
    $customer_id = $params['customer_id'];
    $line_items_data = $params['line_items'];
    $payment_method_id = $params['payment_method'];
    $payment_method_title = $params['payment_method_title'] ?? $payment_method_id; // Usar título si se envió
    $set_paid = $params['set_paid']; // Ya es booleano por 'args'
    $meta_data_input = $params['meta_data'] ?? [];
    $coupon_lines_input = $params['coupon_lines'] ?? [];
    $pos_order_note = $params['pos_order_note'] ?? ''; // Ya sanitizado
    $pos_sale_date_input = $params['pos_sale_date'] ?? null; // Obtener la fecha enviada

    error_log('POS Base DEBUG: Entrando en pos_base_api_create_order. Cliente ID: ' . $customer_id);

    // Validar cliente (aunque ID 0 es permitido por 'args')
    if ($customer_id > 0) {
        $customer = get_user_by( 'id', $customer_id );
        if ( ! $customer ) {
             error_log('POS Base DEBUG: Error - Cliente ID ' . $customer_id . ' no existe.');
             return new WP_Error( 'rest_invalid_customer', __( 'El cliente no existe.', 'pos-base' ), array( 'status' => 400 ) );
        }
    }

    $order = null;
    $order_id = 0; // Inicializar order_id
    $assigned_profile_id = null; // Inicializar variable para el ID del perfil asignado

    try {
        // --- Crear el pedido con estado pendiente inicial ---
        $order = wc_create_order( array( 'customer_id' => $customer_id, 'status' => 'wc-pending' ) );
        if ( is_wp_error( $order ) ) {
            throw new Exception( $order->get_error_message() );
        }
        $order_id = $order->get_id(); // Obtener el ID aquí
        error_log('POS Base DEBUG: Pedido creado con ID: ' . $order_id);

        // --- Establecer datos de facturación desde el cliente (si existe) ---
        $billing_details = array();
        if ($customer_id > 0 && $customer) {
            $billing_details = array(
                'first_name' => $customer->first_name ?: $customer->display_name,
                'last_name'  => $customer->last_name,
                'email'      => $customer->user_email,
                'phone'      => get_user_meta( $customer_id, 'billing_phone', true ),
                // Añadir más campos si son necesarios
            );
        } else {
             $billing_details = array(
                 'first_name' => __('Invitado', 'pos-base'),
                 'last_name' => 'POS',
             );
        }
        $order->set_address( $billing_details, 'billing' );
        // --- Fin datos facturación ---

        // Añadir nota automática del POS
        $order->add_order_note( __( 'Pedido creado desde POS Base.', 'pos-base' ), false, true ); // Nota pública

        // ******** UBICACIÓN CORRECTA DEL BLOQUE DE FECHA ********
        // --- INICIO: Establecer Fecha de Venta Personalizada ---
        if ( ! empty( $pos_sale_date_input ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $pos_sale_date_input ) ) {
            try {
                // Combinar la fecha del input con la hora actual del servidor (zona horaria WP)
                $current_time_str = current_time( 'H:i:s' );
                $datetime_string = $pos_sale_date_input . ' ' . $current_time_str;

                // Crear objeto DateTime para asegurar formato correcto y zona horaria
                // Usamos wp_timezone() para obtener la zona horaria de WP
                $datetime_obj = new DateTime( $datetime_string, wp_timezone() );

                // Establecer la fecha creada en el pedido (en formato GMT/UTC para WC)
                // AHORA $order SÍ EXISTE
                $order->set_date_created( $datetime_obj->getTimestamp() ); // WC espera timestamp o string Y-m-d H:i:s GMT

                error_log('POS Base DEBUG: Fecha de venta personalizada establecida para pedido ' . $order_id . ': ' . $datetime_obj->format('Y-m-d H:i:s P'));

            } catch ( Exception $e ) {
                error_log('POS Base DEBUG: Error al procesar fecha de venta personalizada: ' . $e->getMessage() . ' - Input: ' . $pos_sale_date_input);
                // No detener la creación del pedido, pero registrar el error. WC usará la fecha actual por defecto.
            }
        } else {
             error_log('POS Base DEBUG: No se recibió fecha de venta personalizada válida (Input: ' . print_r($pos_sale_date_input, true) . '). Se usará la fecha/hora actual.');
        }
        // --- FIN: Establecer Fecha de Venta Personalizada ---
        // ******** FIN DEL BLOQUE MOVIDO ********

        // Añadir la nota personalizada si existe
        if ( ! empty( $pos_order_note ) ) {
            $order->add_order_note( $pos_order_note, true, true ); // Nota privada para el cliente
            error_log('POS Base DEBUG: Nota privada añadida al pedido ' . $order_id);
        }

        // --- Añadir Items y Precio Personalizado ---
        $pos_calculated_subtotal = 0;
        foreach ( $line_items_data as $item_data ) {
            $product_id = $item_data['product_id'];
            $variation_id = $item_data['variation_id'] ?? 0;
            $quantity = $item_data['quantity'];
            $custom_unit_price = $item_data['price'];

            $product = wc_get_product( $variation_id ?: $product_id );
            if ( ! $product ) {
                throw new Exception( sprintf( __( 'Producto inválido ID %d.', 'pos-base' ), $variation_id ?: $product_id ) );
            }

            $item_id = $order->add_product( $product, $quantity );
            if (!$item_id) {
                throw new Exception( sprintf( __( 'No se pudo añadir producto ID %d al pedido.', 'pos-base' ), $variation_id ?: $product_id ) );
            }

            $item = $order->get_item( $item_id );
            if ( ! $item instanceof WC_Order_Item_Product ) {
                throw new Exception( sprintf( __( 'No se pudo obtener el item %d del pedido.', 'pos-base' ), $item_id ) );
            }

            $line_subtotal = $custom_unit_price * $quantity;
            $line_total = $custom_unit_price * $quantity;
            $pos_calculated_subtotal += $line_total;

            $item->set_subtotal( $line_subtotal );
            $item->set_total( $line_total );
            $item->save();
            error_log('POS Base DEBUG: Item añadido/actualizado en pedido ' . $order_id . ': Product ID ' . ($variation_id ?: $product_id) . ', Qty: ' . $quantity . ', Price: ' . $custom_unit_price);
        }
        // --- Fin añadir items ---

        // Método de Pago
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        if ( isset( $payment_gateways[ $payment_method_id ] ) ) {
            $order->set_payment_method( $payment_gateways[ $payment_method_id ] );
        } else {
            $order->set_payment_method( $payment_method_id );
            $order->set_payment_method_title( $payment_method_title );
        }
        error_log('POS Base DEBUG: Método de pago establecido para pedido ' . $order_id . ': ' . $payment_method_id);

        // --- Metadatos del Pedido (incluye los de suscripción base y captura de perfil) ---
        $sale_type_from_meta = null; // Variable para guardar el tipo de venta de los metadatos
        if ( ! empty( $meta_data_input ) ) {
            foreach ( $meta_data_input as $meta_item ) {
                if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                    $order->update_meta_data( $meta_item['key'], $meta_item['value'] );
                    error_log('POS Base DEBUG: Metadato añadido/actualizado en pedido ' . $order_id . ': ' . $meta_item['key'] . ' = ' . print_r($meta_item['value'], true));

                    // Guardar el tipo de venta si viene en los metadatos
                    if ( $meta_item['key'] === '_pos_sale_type' ) {
                        $sale_type_from_meta = $meta_item['value'];
                    }

                    // --- INICIO: CAPTURAR Y VALIDAR PROFILE ID ---
                    if ( $meta_item['key'] === '_pos_assigned_profile_id' ) {
                        $profile_id_temp = absint( $meta_item['value'] );
                        // Verificar si es un ID válido de post y si es del tipo 'pos_profile'
                        if ( $profile_id_temp > 0 && get_post_type( $profile_id_temp ) === 'pos_profile' ) {
                            // Verificar si el perfil está realmente disponible
                            $current_status = get_post_meta( $profile_id_temp, '_pos_profile_status', true );
                            if ( $current_status === 'available' ) {
                                $assigned_profile_id = $profile_id_temp; // Guardar ID válido y disponible
                                error_log('[Streaming Order DEBUG] Perfil ID ' . $assigned_profile_id . ' válido y disponible encontrado en meta.');
                            } else {
                                 error_log('[Streaming Order WARNING] Perfil ID ' . $profile_id_temp . ' encontrado pero su estado es "' . $current_status . '", no "available". No se asignará.');
                                 // No lanzar excepción aquí todavía, validar después del bucle
                            }
                        } else {
                             error_log('[Streaming Order WARNING] _pos_assigned_profile_id recibido (' . $meta_item['value'] . ') pero no es un ID de perfil válido.');
                        }
                    }
                    // --- FIN: CAPTURAR Y VALIDAR PROFILE ID ---
                }
            }
        }
        // --- Fin metadatos ---

        // --- INICIO: VALIDACIÓN DE PERFIL STREAMING (AHORA OPCIONAL) ---
        // Obtener si el módulo está activo
        $active_modules = get_option( 'pos_base_active_modules', [] );
        $is_streaming_active = ( is_array( $active_modules ) && in_array( 'streaming', $active_modules, true ) );

        // --- COMENTADO: Ya no es obligatorio seleccionar un perfil ---
        // // Validar SOLO si el módulo está activo Y es una venta de suscripción Y NO se encontró un perfil válido
        // if ( $is_streaming_active && $sale_type_from_meta === 'subscription' && empty( $assigned_profile_id ) ) {
        //     // Lanzar el error que detendrá la creación del pedido
        //     error_log('[Streaming Order ERROR] Venta de suscripción requiere perfil, pero no se proporcionó o no es válido/disponible. Módulo activo: ' . ($is_streaming_active ? 'Sí' : 'No'));
        //     // Devolver un WP_Error detiene la ejecución y envía el mensaje al JS
        //     return new WP_Error(
        //         'profile_required', // Código de error único
        //         __( 'Debes seleccionar un perfil disponible para la suscripción.', 'pos-streaming' ), // Mensaje de error
        //         array( 'status' => 400 ) // Código de estado HTTP (Bad Request)
        //     );
        // }
        // --- FIN: VALIDACIÓN REQUERIDA DE PERFIL STREAMING ---

        // Guardar pedido TEMPRANO para poder aplicar cupones y tener metadatos guardados
        $order->save();

        // --- Aplicar Cupones ---
        $coupon_discount_total = 0;
        if ( ! empty( $coupon_lines_input ) ) {
            error_log('POS Base DEBUG: Aplicando cupones al pedido ' . $order_id . ': ' . print_r($coupon_lines_input, true));
            foreach ( $coupon_lines_input as $coupon_line ) {
                if ( ! empty( $coupon_line['code'] ) ) {
                    $result = $order->apply_coupon( $coupon_line['code'] );
                    if (is_wp_error($result)) {
                         error_log('POS Base DEBUG: Error al aplicar cupón ' . $coupon_line['code'] . ': ' . $result->get_error_message());
                    } else {
                         error_log('POS Base DEBUG: Cupón ' . $coupon_line['code'] . ' aplicado correctamente.');
                    }
                }
            }
            // Recalcular totales DESPUÉS de aplicar cupones
            $order->calculate_totals(false); // false para no recalcular impuestos si no los manejamos
            $coupon_discount_total = $order->get_discount_total();
            error_log('POS Base DEBUG: Descuento total por cupones: ' . $coupon_discount_total);
        }
        // --- Fin cupones ---

        // Calcular y Forzar Total Final (Subtotal POS - Descuento Cupón)
        $final_total = max(0, $pos_calculated_subtotal - $coupon_discount_total);
        $order->set_total( $final_total );
        error_log('POS Base DEBUG: Total final calculado y forzado para pedido ' . $order_id . ': ' . $final_total);

        // --- INICIO: ACTUALIZAR ESTADO DEL PERFIL ASIGNADO ---
        // Solo actualizar si el módulo está activo Y se asignó un perfil
        if ( $is_streaming_active && $assigned_profile_id ) {
            // Actualizar el metadato '_pos_profile_status' del CPT 'pos_profile'
            $update_result = update_post_meta( $assigned_profile_id, '_pos_profile_status', 'assigned' );

            if ($update_result) {
                 error_log('[Streaming Order SUCCESS] Estado del Perfil ID ' . $assigned_profile_id . ' actualizado a "assigned".');
                 // Añadir una nota al pedido indicando el perfil asignado
                 $profile_title = get_the_title($assigned_profile_id);
                 $order->add_order_note( sprintf( __( 'Perfil Streaming asignado: %s (ID: %d)', 'pos-streaming' ), $profile_title, $assigned_profile_id ), false, true ); // Nota pública
            } else {
                 error_log('[Streaming Order ERROR] Falló al actualizar estado del Perfil ID ' . $assigned_profile_id . ' a "assigned".');
                 // Considerar qué hacer aquí: ¿revertir? ¿añadir nota de error?
                 $order->add_order_note( sprintf( __( 'ERROR: Falló al actualizar estado del Perfil Streaming ID %d a "asignado". Revisar manualmente.', 'pos-streaming' ), $assigned_profile_id ), false, true );
            }
        }
        // --- FIN: ACTUALIZAR ESTADO DEL PERFIL ASIGNADO ---


        // Estado Final y Pago
        // Usar $sale_type_from_meta que ya obtuvimos
        $final_status = ($sale_type_from_meta === 'credit') ? 'on-hold' : 'completed'; // Ejemplo: 'on-hold' para crédito

        $order->update_status( $final_status, __( 'Pedido procesado desde POS.', 'pos-base' ), false ); // No notificar al cliente por defecto
        error_log('POS Base DEBUG: Estado del pedido ' . $order_id . ' actualizado a: ' . $final_status);

        // Marcar como pagado si corresponde
        if ( $set_paid && $final_status === 'completed' ) {
            if ($order->get_total() > 0) {
                $order->payment_complete();
                error_log('POS Base DEBUG: Pedido ' . $order_id . ' marcado como pagado (payment_complete).');
            } elseif ($order->get_total() == 0) {
                 // Para pedidos gratuitos, payment_complete puede no cambiar el estado si ya es 'completed'
                 $order->set_date_paid( time() ); // Marcar fecha de pago manualmente
                 $order->save();
                 error_log('POS Base DEBUG: Pedido gratuito ' . $order_id . ' marcado como pagado (manualmente).');
            }
        } else {
            $order->save(); // Guardar cualquier cambio final
        }

        // Preparar Respuesta
        $response_data = array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'order_url' => $order->get_edit_order_url(), // Añadir URL para conveniencia
        );
        error_log('POS Base DEBUG: pos_base_api_create_order completado. Respuesta: ' . print_r($response_data, true));
        return new WP_REST_Response( $response_data, 201 ); // 201 Created

    } catch ( Exception $e ) {
        error_log('POS Base DEBUG: EXCEPCIÓN al crear pedido: ' . $e->getMessage());
        // Intentar eliminar el pedido parcialmente creado si falló
        if ( isset( $order ) && $order instanceof WC_Order && $order->get_id() > 0 ) {
            wp_delete_post( $order->get_id(), true );
            error_log('POS Base DEBUG: Pedido parcialmente creado ' . $order->get_id() . ' eliminado debido a error.');
        }
        // Devolver el mensaje de la excepción como WP_Error
        return new WP_Error( 'rest_order_creation_failed', $e->getMessage(), array( 'status' => 500 ) );
    }
} // Fin de pos_base_api_create_order


/**
 * Callback API: Obtener eventos para FullCalendar.
 * Incluye Vencimientos de Suscripciones (desde Pedidos) y Vencimientos de Cuentas Streaming (desde CPT pos_account).
 */
function pos_base_api_get_calendar_events( WP_REST_Request $request ) {
    error_log('POS Base DEBUG: Entrando en pos_base_api_get_calendar_events.');
    $all_events = array(); // Array para combinar todos los eventos

    // --- 1. Obtener Vencimientos de Suscripciones Vendidas (desde Pedidos) ---
    $order_args = array(
        'post_type'   => 'shop_order',
        'post_status' => array('wc-processing', 'wc-completed', 'wc-on-hold'), // Estados relevantes
        'limit'       => -1,
        'meta_query'  => array(
            'relation' => 'AND',
            array( 'key' => '_pos_sale_type', 'value' => 'subscription', 'compare' => '=' ),
            array( 'key' => '_pos_subscription_expiry_date', 'compare' => 'EXISTS' ),
            array( 'key' => '_pos_subscription_expiry_date', 'value' => '', 'compare' => '!=' ),
        ),
        'orderby'     => 'meta_value',
        'meta_key'    => '_pos_subscription_expiry_date',
        'order'       => 'ASC',
        'return'      => 'ids',
    );

    $order_ids = wc_get_orders( $order_args );

    if ( ! empty( $order_ids ) ) {
        error_log('POS Base DEBUG: Encontrados ' . count($order_ids) . ' pedidos de suscripción con fecha de vencimiento.');
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) continue;

            $title = $order->get_meta( '_pos_subscription_title' );
            $expiry_date = $order->get_meta( '_pos_subscription_expiry_date' );
            $color = $order->get_meta( '_pos_subscription_color' );

            if ( empty( $title ) ) {
                $title = sprintf( __( 'Vence Suscripción: %s', 'pos-base' ), $order->get_formatted_billing_full_name() ?: ('#' . $order_id) );
            }
            if ( empty( $expiry_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expiry_date ) ) continue;

            $all_events[] = array(
                'id'            => 'pos_sub_exp_' . $order_id,
                'title'         => $title,
                'start'         => $expiry_date,
                'color'         => ! empty( $color ) ? $color : '#3a87ad', // Azul por defecto para suscripciones
                'allDay'        => true,
                'extendedProps' => array(
                    'type'        => 'subscription_expiry',
                    'order_id'    => $order_id,
                    'customer_id' => $order->get_customer_id(),
                    'order_url'   => $order->get_edit_order_url(),
                )
            );
        }
    } else {
         error_log('POS Base DEBUG: No se encontraron pedidos de suscripción con fecha de vencimiento.');
    }
    // --- Fin Vencimientos de Suscripciones ---


    // --- 2. Obtener Vencimientos de Cuentas Streaming (desde CPT pos_account) ---
    $account_args = array(
        'post_type'      => 'pos_account',
        'post_status'    => 'publish', // Solo cuentas publicadas
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_pos_account_expiry_date', // El metadato de la cuenta
                'compare' => 'EXISTS',
            ),
             array(
                'key'     => '_pos_account_expiry_date',
                'value'   => '',
                'compare' => '!=',
            ),
        ),
        'orderby'        => 'meta_value', // Ordenar por fecha de vencimiento
        'meta_key'       => '_pos_account_expiry_date',
        'order'          => 'ASC',
    );

    $accounts_query = new WP_Query( $account_args );

    if ( $accounts_query->have_posts() ) {
        error_log('POS Base DEBUG: Encontradas ' . $accounts_query->post_count . ' cuentas streaming con fecha de vencimiento.');
        while ( $accounts_query->have_posts() ) {
            $accounts_query->the_post();
            $account_id = get_the_ID();
            $account_title = get_the_title();
            $expiry_date = get_post_meta( $account_id, '_pos_account_expiry_date', true );

            if ( empty( $expiry_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expiry_date ) ) continue;

            // Formatear título del evento
            $event_title = sprintf( __( 'Vence Cuenta: %s', 'pos-streaming' ), $account_title );

            // Añadir el evento al array general
            $all_events[] = array(
                'id'            => 'pos_acc_exp_' . $account_id, // ID único para este tipo de evento
                'title'         => $event_title,
                'start'         => $expiry_date,
                'color'         => '#dc3545', // Rojo por defecto para vencimiento de cuentas (diferente a suscripciones)
                'allDay'        => true,
                'extendedProps' => array(
                    'type'        => 'account_expiry', // Nuevo tipo para identificarlo en JS si es necesario
                    'account_id'  => $account_id,
                    'account_url' => get_edit_post_link( $account_id ), // Enlace para editar la cuenta
                )
            );
        }
        wp_reset_postdata(); // Importante después de un loop WP_Query con the_post()
    } else {
         error_log('POS Base DEBUG: No se encontraron cuentas streaming con fecha de vencimiento.');
    }
    // --- Fin Vencimientos de Cuentas ---


    // --- Devolver todos los eventos combinados ---
    error_log('POS Base DEBUG: pos_base_api_get_calendar_events devolviendo ' . count($all_events) . ' eventos en total.');
    return new WP_REST_Response( $all_events, 200 );
}

function pos_base_api_get_sales_for_datatable( WP_REST_Request $request ) {
    // Parámetros (sin cambios)
    $params = $request->get_params();
    $draw = $params['draw'];
    $start = $params['start'];
    $length = $params['length'];
    $search_value = $params['search']['value'] ?? '';

    error_log("Dato completo recivido - ".json_encode( $params));
    
    $args = array(
        'return'    => 'ids',
        'paginate'  => true,
        'limit'     => $length,
        'paged'     => $length > 0 ? ( $start / $length ) + 1 : 1,
        'orderby'   => 'date_created',
        'order'     => 'DESC'
    );
   
    // Añadir búsqueda si existe
    if ( ! empty( $search_value ) ) {
        $args['s'] = $search_value;
    }

    error_log('POS Base DEBUG: WC_Order_Query args: ' . print_r($args, true));

    // Ejecutar la consulta para obtener IDs paginados y filtrados
    $order_query = new WC_Order_Query( $args );
    $result_object = $order_query->get_orders();
    $order_ids = $result_object->orders ?? [];

    // Obtener el total de registros FILTRADOS (desde el objeto paginado)
    $records_filtered_raw = $result_object->total ?? 0;
    // Asegurarnos de que sea un entero
    $records_filtered = is_int($records_filtered_raw) ? $records_filtered_raw : 0;
    error_log('POS Base DEBUG: records_filtered_raw: ' . print_r($records_filtered_raw, true) . ', records_filtered (after check): ' . $records_filtered);


    // Obtener el total de registros SIN filtrar (para DataTables)
    // Usamos un query separado solo para contar, sin filtros de búsqueda ni paginación
    $total_args = array('return' => 'count');
    $total_query = new WC_Order_Query( $total_args );
    $records_total_raw = $total_query->get_orders(); // Obtener el resultado crudo

    // Asegurarnos de que sea un entero, si no, usar 0
    $records_total = is_int($records_total_raw) ? $records_total_raw : 0;
    error_log('POS Base DEBUG: records_total_raw: ' . print_r($records_total_raw, true) . ', records_total (after check): ' . $records_total);


    // --- Preparar datos para la respuesta DataTables ---
    $data = array();
    if ( ! empty( $order_ids ) ) {
        $orders = array_filter( array_map( 'wc_get_order', $order_ids ) );
        $active_modules = get_option( 'pos_base_active_modules', [] );
        $is_streaming_active = ( is_array( $active_modules ) && in_array( 'streaming', $active_modules, true ) );

        foreach ( $orders as $order ) {
            // --- Obtener TODOS los datos individuales necesarios (sin cambios) ---
            $order_id = $order->get_id();
            $order_url = $order->get_edit_order_url();
            $customer_name = $order->get_formatted_billing_full_name() ?: __( 'Invitado', 'pos-base' );
            $user_id = $order->get_customer_id();
            $customer_link = $user_id ? get_edit_user_link( $user_id ) : '';
            $date_created = $order->get_date_created();
            $formatted_date = $date_created ? $date_created->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '-';
            $phone = $order->get_billing_phone();
            $sale_type = $order->get_meta( '_pos_sale_type', true );
            $sale_type_label = $sale_type ? esc_html( ucfirst( $sale_type ) ) : 'N/A';
            $sub_expiry = $order->get_meta( '_pos_subscription_expiry_date', true );
            $sub_title = $order->get_meta( '_pos_subscription_title', true );
            $profile_id = $order->get_meta( '_pos_assigned_profile_id', true );
            $avatar_url = get_avatar_url( $user_id ?: $order->get_billing_email(), ['size' => 32, 'default' => 'mystery'] );
            $custom_avatar_id = $user_id ? get_user_meta( $user_id, 'pos_customer_avatar_id', true ) : null;
            if ($custom_avatar_id && $url = wp_get_attachment_image_url($custom_avatar_id, 'thumbnail')) {
                $avatar_url = $url;
            }
            $customer_note_content = '';
            $note_args = array('order_id' => $order_id, 'type' => 'customer', 'number' => 1, 'orderby' => 'comment_date_gmt', 'order' => 'DESC');
            $notes = wc_get_order_notes( $note_args );
            if ( ! empty( $notes ) ) {
                $latest_note = reset( $notes );
                $customer_note_content = $latest_note->content;
            }

            // --- Construir HTML para las 5 columnas AGRUPADAS ---

            // Columna 1: Pedido / Fecha / Tipo
            $col1_html = '<a href="' . esc_url( $order_url ) . '"><strong>#' . $order_id . '</strong></a>';
            $col1_html .= '<br><small>' . $formatted_date . '</small>';
            $col1_html .= '<br><span class="pos-sale-type-badge pos-type-' . esc_attr($sale_type ?: 'direct') . '">' . $sale_type_label . '</span>';
            $col1_html .= '<div class="row-actions">';
            $col1_html .= '<span class="view"><a href="' . esc_url( $order_url ) . '">' . __('Ver Pedido', 'pos-base') . '</a></span>';

            // --- INICIO: Acción y Modal SMS ---
            $modal_id = 'pos-sms-modal-content-' . $order_id;
            $thickbox_url = '#TB_inline?width=450&height=350&inlineId=' . $modal_id; // Ajusta tamaño si es necesario
            // Solo mostrar enlace si hay teléfono
            if ($phone) {
                $col1_html .= ' | <span class="sms"><a href="' . esc_url($thickbox_url) . '" class="thickbox send-sms-action" data-order-id="' . $order_id . '" data-phone="' . esc_attr($phone) . '" title="' . esc_attr__('Enviar Mensaje (WhatsApp/SMS)', 'pos-base') . '">' . __('Enviar Mensaje', 'pos-base') . '</a></span>';

                // Contenido oculto del modal para ESTA fila
                $col1_html .= '<div id="' . esc_attr($modal_id) . '" style="display:none;">';
                $col1_html .= '<div class="pos-sms-modal-wrapper">'; // Contenedor para estilos
                $col1_html .= '<h3>' . sprintf(esc_html__('Enviar Mensaje a %s', 'pos-base'), esc_html($customer_name)) . '</h3>';
                $col1_html .= '<p><strong>' . esc_html__('Teléfono:', 'pos-base') . '</strong> ' . esc_html($phone) . '</p>';
                $col1_html .= '<p><label for="pos-sms-message-' . $order_id . '">' . esc_html__('Mensaje:', 'pos-base') . '</label><br>';
                $col1_html .= '<textarea id="pos-sms-message-' . $order_id . '" class="pos-sms-message-input" rows="5" style="width: 98%;"></textarea></p>';
                $col1_html .= '<p class="submit">';
                $col1_html .= '<button type="button" class="button button-primary pos-send-sms-button" data-order-id="' . $order_id . '" data-phone="' . esc_attr($phone) . '">' . esc_html__('Enviar Mensaje', 'pos-base') . '</button>';
                $col1_html .= '<span class="spinner" style="float: none; vertical-align: middle; margin-left: 5px;"></span>'; // Spinner
                $col1_html .= '</p>';
                $col1_html .= '<div class="pos-sms-feedback" style="margin-top: 10px;"></div>'; // Para mensajes de estado
                $col1_html .= '</div>'; // fin .pos-sms-modal-wrapper
                $col1_html .= '</div>'; // fin #modal_id
            }
            // --- FIN: Acción y Modal SMS ---
            
            $col1_html .= '</div>'; // Fin .row-actions
         


            // Columna 2: Cliente / Contacto
            $col2_html = '<div style="display: flex; align-items: center;">';
            $col2_html .= '<img src="' . esc_url($avatar_url) . '" width="50" height="50" style="border-radius: 50%; margin-right: 8px;" loading="lazy">';
            $col2_html .= '<div>';
            $col2_html .= $customer_link ? '<a href="' . esc_url( $customer_link ) . '">' . esc_html( $customer_name ) . '</a>' : esc_html( $customer_name );
            if ($phone) {
                $col2_html .= '<br><small><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></small>';
            }
            $col2_html .= '</div></div>';


            // Columna 3: Producto(s)
            $col3_html = '';
            $items = $order->get_items();
            $item_list = [];
            if (!empty($items)) {
                foreach ($items as $item) {
                    $product_name = $item->get_name();
                    $quantity = $item->get_quantity();
                    $item_list[] = esc_html($product_name) . ' (x' . $quantity . ')';
                }
                if (count($item_list) > 2) {
                     $col3_html = implode('<br>', array_slice($item_list, 0, 2));
                     $col3_html .= '<br><small>... (' . sprintf(__('%d más', 'pos-base'), count($item_list) - 2) . ')</small>';
                } else {
                     $col3_html = implode('<br>', $item_list);
                }
            }
            if (empty($col3_html)) {
                $col3_html = '-';
            }


            // Columna 4: Vencimiento / Historial (Estadísticas)
            $col4_html = '-';
            $vencimiento_html = '';
            if ( ! empty( $sub_expiry ) && $sale_type === 'subscription' ) {
                $formatted_expiry = '-';
                $human_time_diff = '';
                try {
                    $expiry_obj = new DateTime( $sub_expiry );
                    $formatted_expiry = $expiry_obj->format( get_option( 'date_format' ) );
                    $human_time_diff = human_time_diff( $expiry_obj->getTimestamp(), current_time( 'timestamp' ) );
                    $is_past = $expiry_obj->getTimestamp() < current_time( 'timestamp' );
                    $human_time_diff = $is_past ? sprintf(__('%s atrás', 'pos-base'), $human_time_diff) : sprintf(__('en %s', 'pos-base'), $human_time_diff);
                } catch (Exception $e) { $formatted_expiry = $sub_expiry; }
                $vencimiento_html = esc_html( $formatted_expiry );
                $vencimiento_html .= '<br><small>' . esc_html( $human_time_diff ) . '</small>';
            }
            $stats_html = '';
            if ($user_id) {
                $order_count = wc_get_customer_order_count($user_id);
                $total_spent = wc_get_customer_total_spent($user_id);
                $aov = ($order_count > 0) ? $total_spent / $order_count : 0;
                $stats_html .= '<div class="pos-customer-stats" style="font-size: 0.9em; margin-top: 5px; padding-top: 5px; border-top: 1px dashed #eee;">';
                $stats_html .= sprintf( '%s: %d<br>', __('Pedidos', 'pos-base'), $order_count );
                $stats_html .= sprintf( '%s: %s<br>', __('Total Gastado', 'pos-base'), wc_price($total_spent) );
                $stats_html .= sprintf( '%s: %s', __('Valor Medio', 'pos-base'), wc_price($aov) );
                $stats_html .= '</div>';
            }
            if (!empty($vencimiento_html) || !empty($stats_html)) {
                $col4_html = $vencimiento_html . $stats_html;
            }


            // Columna 5: Notas / Detalles
            $col5_html = '';
            if (!empty($customer_note_content)) {
                $col5_html .= '<div class="pos-order-note-display"><strong>' . __('Nota Cliente:', 'pos-base') . '</strong> ' . wp_kses_post( wp_trim_words( $customer_note_content, 25, '...' ) ) . '</div>';
            }
            $details_parts = [];
            if ( $sale_type === 'subscription' && !empty($sub_title)) {
                 $details_parts[] = '<strong>' . esc_html__( 'Susc:', 'pos-base' ) . '</strong> ' . esc_html( $sub_title );
            }
            if ( $is_streaming_active && $profile_id && $profile_title = get_the_title( $profile_id ) ) {
                 $profile_url = get_edit_post_link( $profile_id );
                 $details_parts[] = '<strong>' . esc_html__( 'Perfil:', 'pos-streaming' ) . '</strong> ' . ($profile_url ? '<a href="'.esc_url($profile_url).'" target="_blank">' : '') . esc_html( $profile_title ) . ($profile_url ? '</a>' : '');
            }
            if (!empty($details_parts)) {
                 $col5_html .= (!empty($col5_html) ? '<hr style="margin: 5px 0; border-style: dashed;">' : '') . implode( '<br>', $details_parts );
            }
            if (empty($col5_html)) {
                $col5_html = '-';
            }


            // --- Construir array de datos para la fila (AHORA CON 5 ELEMENTOS) ---
            $data[] = array(
                $col1_html, // Columna 1
                $col2_html, // Columna 2
                $col3_html, // Columna 3 (Productos)
                $col4_html, // Columna 4 (Vencimiento/Historial)
                $col5_html, // Columna 5 (Notas/Detalles)
            );
            // --- Fin construcción fila ---
        }
    }
    // --- Fin preparación datos ---

    // --- Respuesta JSON---
    $response_data = array(
        "draw"            => $draw,
        "recordsTotal"    => $records_total,
        "recordsFiltered" => $records_filtered,
        "data"            => $data,
    );
    // --- Fin respuesta ---

    error_log('POS Base DEBUG: pos_base_api_get_sales_for_datatable (v5 - Agrupado 5 Col) devolviendo respuesta para Draw ' . $draw);
    return new WP_REST_Response( $response_data, 200 );
}



// =========================================================================
// 3. FUNCIONES AUXILIARES Y DE PERMISOS
// =========================================================================

/**
 * Prepara los datos del cliente para la respuesta de la API.
 * Incluye metadatos relevantes como avatar y nota.
 */
function pos_base_prepare_customer_data_for_response( $customer_id ) {
    $user_info = get_userdata( $customer_id );
    if ( ! $user_info ) return array(); // Devolver array vacío si el usuario no existe

    // Obtener Avatar Personalizado o Gravatar
    $avatar_id = get_user_meta( $customer_id, 'pos_customer_avatar_id', true );
    $avatar_url = '';
    if ( $avatar_id && $url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) ) {
        $avatar_url = $url;
    } else {
        // Si no hay avatar personalizado, usar Gravatar
        $avatar_url = get_avatar_url( $customer_id, array( 'size' => 96, 'default' => 'mystery' ) );
        $avatar_id = ''; // Indicar que no es un ID de adjunto
    }

    // Obtener Nota del Cliente
    $customer_note = get_user_meta( $customer_id, '_pos_customer_note', true );

    // --- Construir array de datos del cliente ---
    return array(
        'id'         => $customer_id,
        'email'      => $user_info->user_email,
        'first_name' => $user_info->first_name,
        'last_name'  => $user_info->last_name,
        'display_name' => $user_info->display_name,
        'phone'      => get_user_meta( $customer_id, 'billing_phone', true ), // Obtener teléfono de facturación
        'avatar_url' => $avatar_url,
        'meta_data'  => array( // Incluir metadatos relevantes
            array( 'key' => 'pos_customer_avatar_id', 'value' => $avatar_id ), // Devolver el ID si existe
            array( 'key' => '_pos_customer_note', 'value' => $customer_note ),
            // Podríamos añadir más metadatos si fueran necesarios
        ),
    );
    // --- Fin construcción ---
}

/**
 * Comprueba los permisos para acceder a los endpoints de la API.
 * Verifica capacidad y nonce.
 */
function pos_base_api_permissions_check( WP_REST_Request $request ) {
    error_log('POS Base DEBUG: Entrando en pos_base_api_permissions_check.');
    $can_manage = current_user_can('manage_woocommerce');
    error_log('POS Base DEBUG: current_user_can(manage_woocommerce) = ' . ($can_manage ? 'true' : 'false'));

    if ( ! $can_manage ) {
        error_log('POS Base DEBUG: Error - Permiso denegado (manage_woocommerce).');
        return new WP_Error( 'rest_forbidden_capability', __( 'No tienes permiso.', 'pos-base' ), array( 'status' => rest_authorization_required_code() ) ); // 401 o 403
    }

    // Verificar Nonce (esencial para peticiones desde el admin)
    $nonce = $request->get_header('X-WP-Nonce') ?: $request->get_param('_wpnonce');
    error_log('POS Base DEBUG: Nonce recibido: ' . $nonce);
    $nonce_verified = wp_verify_nonce( $nonce, 'wp_rest' );
    error_log('POS Base DEBUG: wp_verify_nonce(wp_rest) = ' . ($nonce_verified ? 'true' : 'false'));

    if ( empty($nonce) || ! $nonce_verified ) {
         error_log('POS Base DEBUG: Error - Nonce inválido o faltante.');
         return new WP_Error( 'rest_forbidden_nonce', __( 'Nonce inválido.', 'pos-base' ), array( 'status' => 403 ) ); // 403 Forbidden
    }

    error_log('POS Base DEBUG: Permisos OK en pos_base_api_permissions_check.');
    return true; // Permiso concedido
}
?>
