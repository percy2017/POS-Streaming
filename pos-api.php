<?php

// Si este archivo es llamado directamente, abortar.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =========================================================================
// 1. REGISTRO DE RUTAS API REST
// =========================================================================
function pos_streaming_register_rest_routes() {
    $namespace = 'pos_streaming/v1'; // Namespace de nuestra API

    // --- RUTA PARA BUSCAR PRODUCTOS ---
    register_rest_route( $namespace, '/products', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_search_products',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'search' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'page' => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1 ),
            'per_page' => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 10 ),
            'featured' => array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ),
        ),
    ) );

    // --- RUTA PARA BUSCAR CLIENTES ---
    register_rest_route( $namespace, '/customers', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_search_customers',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'search' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'per_page' => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 10 ),
        ),
    ) );

    // --- RUTA PARA OBTENER UN CLIENTE ESPECÍFICO ---
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_customer',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'id' => array( 'validate_callback' => function($param, $request, $key) { return is_numeric( $param ); } ),
        ),
    ) );

    // --- RUTA PARA CREAR UN CLIENTE ---
    register_rest_route( $namespace, '/customers', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_streaming_api_create_customer',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'first_name' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'last_name'  => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'email'      => array( 'type' => 'string', 'format' => 'email', 'sanitize_callback' => 'sanitize_email' ),
            'phone'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'meta_data'  => array( // Ahora puede incluir avatar y nota
                'type' => 'array',
                'items' => array( 'type' => 'object' ),
                'properties' => array(
                    'key' => array('type' => 'string'),
                    'value' => array(), // Puede ser string (nota) o int (avatar_id)
                ),
            ),
        ),
    ) );

    // --- RUTA PARA ACTUALIZAR UN CLIENTE ---
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::EDITABLE, // PUT, PATCH
        'callback'            => 'pos_streaming_api_update_customer',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'id' => array( 'validate_callback' => function($param, $request, $key) { return is_numeric( $param ); } ),
            'first_name' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'last_name'  => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'email'      => array( 'type' => 'string', 'format' => 'email', 'sanitize_callback' => 'sanitize_email' ),
            'phone'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'meta_data'  => array( // Ahora puede incluir avatar y nota
                 'type' => 'array',
                 'items' => array( 'type' => 'object' ),
                 'properties' => array(
                     'key' => array('type' => 'string'),
                     'value' => array(),
                 ),
            ),
        ),
    ) );

    // --- RUTA PARA OBTENER PASARELAS DE PAGO ---
    register_rest_route( $namespace, '/payment-gateways', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_payment_gateways',
        'permission_callback' => 'pos_streaming_api_permissions_check',
    ) );

    // --- RUTA PARA VALIDAR CUPONES ---
    register_rest_route( $namespace, '/coupons/validate', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_streaming_api_validate_coupon',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'code' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // --- RUTA PARA CREAR PEDIDOS ---
    register_rest_route( $namespace, '/orders', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_streaming_api_create_order',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'customer_id' => array(
                'required'          => true,
                'validate_callback' => function($param) { return is_numeric($param) && $param > 0; },
                'sanitize_callback' => 'absint',
            ),
            'payment_method' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_key',
            ),
             'payment_method_title' => array( // Añadido para el título
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'line_items' => array(
                'required'          => true,
                'type'              => 'array',
                'items'             => array('type' => 'object'),
                    'validate_callback' => function($items) {
                    if (!is_array($items) || empty($items)) return false;
                    foreach ($items as $item) {
                        if (empty($item['product_id']) || !isset($item['quantity']) || !isset($item['price'])) return false;
                    }
                    return true;
                    }
            ),
            'meta_data' => array(
                'type'              => 'array',
                'items'             => array('type' => 'object'),
            ),
            'coupon_lines' => array(
                'type'              => 'array',
                'items'             => array('type' => 'object'),
            ),
            'set_paid' => array(
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ),
            'pos_order_note' => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_textarea_field', // Permite saltos de línea
                'default'           => '',
            ),
        ),
    ) );

    // --- RUTA PARA OBTENER EVENTOS DEL CALENDARIO ---
    register_rest_route( $namespace, '/calendar-events', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_calendar_events',
        'permission_callback' => 'pos_streaming_api_permissions_check',
    ) );
    
    // --- RUTA PARA DATATABLES DE VENTAS ---
    register_rest_route( $namespace, '/sales-datatable', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_sales_for_datatable',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        // Los argumentos serán leídos directamente del request en el callback
    ) );
}
add_action( 'rest_api_init', 'pos_streaming_register_rest_routes' );

// =========================================================================
// 2. FUNCIONES CALLBACK DE LA API
// =========================================================================

/**
 * Callback API: Buscar productos.
 */
function pos_streaming_api_search_products( WP_REST_Request $request ) {
    $per_page = $request['per_page'];
    $page = $request['page'];
    $search_term = $request['search'];
    $is_featured = $request['featured'];

    $args = array(
        'post_type'      => array('product', 'product_variation'),
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    if ( ! empty( $search_term ) ) {
        $args['s'] = $search_term;
    }

    if ( $is_featured ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            ),
        );
        $args['post_type'] = 'product';
        $args['post_parent'] = 0;
    }

    $products_query = new WP_Query( $args );
    $products_data = array();

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

            $product_item = array(
                'id'          => $product->get_id(),
                'name'        => $product->get_name(),
                'sku'         => $product->get_sku(),
                'price'       => $product->get_price(),
                'price_html'  => $product->get_price_html(),
                'type'        => $product->get_type(),
                'stock_status'=> $product->get_stock_status(),
                'image_url'   => $image_url,
                'variations'  => array(),
            );

            if ( $product->is_type( 'variable' ) ) {
                $variations = $product->get_available_variations();
                foreach ( $variations as $variation_data ) {
                    $variation_obj = wc_get_product( $variation_data['variation_id'] );
                    if ( ! $variation_obj ) continue;

                    $variation_image_id = $variation_obj->get_image_id();
                    $variation_image_url = $variation_image_id ? wp_get_attachment_image_url( $variation_image_id, 'thumbnail' ) : $image_url;
                    $product_item['variations'][] = array(
                        'variation_id'   => $variation_obj->get_id(),
                        'variation_name' => wc_get_formatted_variation( $variation_obj, true, false ),
                        'sku'            => $variation_obj->get_sku(),
                        'price'          => $variation_obj->get_price(),
                        'price_html'     => $variation_obj->get_price_html(),
                        'stock_status'   => $variation_obj->get_stock_status(),
                        'stock_quantity' => $variation_obj->get_stock_quantity(),
                        'image_url'      => $variation_image_url,
                    );
                }
            }
            $products_data[] = $product_item;
        }
        wp_reset_postdata();
    }

    $response = new WP_REST_Response( $products_data, 200 );
    $total_products = $products_query->found_posts;
    $max_pages = $products_query->max_num_pages;
    $response->header( 'X-WP-Total', $total_products );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

/**
 * Callback API: Buscar clientes.
 */
function pos_streaming_api_search_customers( WP_REST_Request $request ) {
    $search_term = $request['search'];
    $per_page = $request['per_page'];

    $args = array(
        'role__in'    => array( 'customer' ),
        'search'      => '*' . esc_attr( $search_term ) . '*',
        'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
        'number'      => $per_page,
        'orderby'     => 'display_name',
        'order'       => 'ASC',
        'meta_query'  => array(
            'relation' => 'OR',
            array( 'key' => 'first_name', 'value' => $search_term, 'compare' => 'LIKE' ),
            array( 'key' => 'last_name', 'value' => $search_term, 'compare' => 'LIKE' ),
            array( 'key' => 'billing_phone', 'value' => $search_term, 'compare' => 'LIKE' )
        )
    );

    $user_query = new WP_User_Query( $args );
    $customers_data = array();

    if ( ! empty( $user_query->get_results() ) ) {
        foreach ( $user_query->get_results() as $user ) {
            $customers_data[] = pos_streaming_prepare_customer_data_for_response( $user->ID );
        }
    }

    return new WP_REST_Response( $customers_data, 200 );
}

/**
 * Callback API: Obtener un cliente específico.
 */
function pos_streaming_api_get_customer( WP_REST_Request $request ) {
    $customer_id = (int) $request['id'];
    $user = get_user_by( 'id', $customer_id );

    if ( ! $user ) {
        return new WP_Error( 'rest_customer_invalid_id', __( 'Cliente no encontrado.', 'pos-streaming' ), array( 'status' => 404 ) );
    }

    $customer_data = pos_streaming_prepare_customer_data_for_response( $customer_id );
    return new WP_REST_Response( $customer_data, 200 );
}

/**
 * Callback API: Crear un nuevo cliente.
 */
function pos_streaming_api_create_customer( WP_REST_Request $request ) {
    $email = $request['email'];
    $first_name = $request['first_name'];
    $last_name = $request['last_name'];
    $phone = $request['phone'];
    $meta_data = $request['meta_data']; // Array de objetos {key: '...', value: '...'}

    // Validaciones (sin cambios)
    if ( ! empty( $email ) && ! is_email( $email ) ) {
        return new WP_Error( 'rest_customer_invalid_email', __( 'La dirección de correo electrónico no es válida.', 'pos-streaming' ), array( 'status' => 400 ) );
    }
    if ( ! empty( $email ) && email_exists( $email ) ) {
        return new WP_Error( 'rest_customer_email_exists', __( 'Ya existe un cliente con esta dirección de correo electrónico.', 'pos-streaming' ), array( 'status' => 400 ) );
    }
    $username = ! empty( $email ) ? $email : sanitize_user( $first_name . $last_name . wp_rand( 100, 999 ), true );
    if ( username_exists( $username ) ) {
        $username .= wp_rand( 10, 99 );
    }
    $password = wp_generate_password();

    // Crear cliente WC (sin cambios)
    $customer_id = wc_create_new_customer( $email, $username, $password );
    if ( is_wp_error( $customer_id ) ) {
        return new WP_Error( 'rest_customer_creation_failed', $customer_id->get_error_message(), array( 'status' => 500 ) );
    }

    // Actualizar datos básicos (sin cambios)
    wp_update_user( array( 'ID' => $customer_id, 'first_name' => $first_name, 'last_name' => $last_name ) );
    if ( ! empty( $phone ) ) {
        update_user_meta( $customer_id, 'billing_phone', $phone );
    }

    // **MODIFICADO:** Guardar metadatos (avatar y nota)
    if ( ! empty( $meta_data ) && is_array( $meta_data ) ) {
        foreach ( $meta_data as $meta_item ) {
            if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                $key = sanitize_key( $meta_item['key'] );
                $value = $meta_item['value']; // Sanitizar según la clave

                if ( $key === 'pos_customer_avatar_id' ) {
                    $avatar_id = absint( $value );
                    if ( $avatar_id > 0 && 'attachment' === get_post_type( $avatar_id ) ) {
                        update_user_meta( $customer_id, 'pos_customer_avatar_id', $avatar_id );
                    } else {
                        delete_user_meta( $customer_id, 'pos_customer_avatar_id' );
                    }
                } elseif ( $key === '_pos_customer_note' ) { // <-- GUARDAR NOTA
                    update_user_meta( $customer_id, '_pos_customer_note', sanitize_textarea_field( $value ) );
                }
                // Añadir más 'elseif' para otros metadatos si es necesario
            }
        }
    }

    // Preparar y devolver respuesta (sin cambios)
    $customer_data = pos_streaming_prepare_customer_data_for_response( $customer_id );
    $response = new WP_REST_Response( $customer_data, 201 );
    return $response;
}

/**
 * Callback API: Actualizar un cliente existente.
 */
function pos_streaming_api_update_customer( WP_REST_Request $request ) {
    $customer_id = (int) $request['id'];
    $user = get_user_by( 'id', $customer_id );

    if ( ! $user ) {
        return new WP_Error( 'rest_customer_invalid_id', __( 'Cliente no encontrado para actualizar.', 'pos-streaming' ), array( 'status' => 404 ) );
    }

    $update_data = array( 'ID' => $customer_id );
    $params = $request->get_params();

    // Campos principales (sin cambios)
    if ( isset( $params['first_name'] ) ) $update_data['first_name'] = $params['first_name'];
    if ( isset( $params['last_name'] ) ) $update_data['last_name'] = $params['last_name'];
    if ( isset( $params['email'] ) ) {
        $email = $params['email'];
        if ( ! empty( $email ) && ! is_email( $email ) ) {
            return new WP_Error( 'rest_customer_invalid_email', __( 'La dirección de correo electrónico no es válida.', 'pos-streaming' ), array( 'status' => 400 ) );
        }
        $existing_user = email_exists( $email );
        if ( $existing_user && $existing_user !== $customer_id ) {
            return new WP_Error( 'rest_customer_email_exists', __( 'Ya existe otro cliente con esta dirección de correo electrónico.', 'pos-streaming' ), array( 'status' => 400 ) );
        }
        $update_data['user_email'] = $email;
    }
    $result = wp_update_user( $update_data );
    if ( is_wp_error( $result ) ) {
        return new WP_Error( 'rest_customer_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
    }

    // Teléfono (sin cambios)
    if ( isset( $params['phone'] ) ) {
        update_user_meta( $customer_id, 'billing_phone', $params['phone'] );
    }

    // **MODIFICADO:** Actualizar metadatos (avatar y nota)
    if ( isset( $params['meta_data'] ) && is_array( $params['meta_data'] ) ) {
        foreach ( $params['meta_data'] as $meta_item ) {
             if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                $key = sanitize_key( $meta_item['key'] );
                $value = $meta_item['value']; // Sanitizar según la clave

                if ( $key === 'pos_customer_avatar_id' ) {
                    $avatar_id = absint( $value );
                    if ( $avatar_id > 0 && 'attachment' === get_post_type( $avatar_id ) ) {
                        update_user_meta( $customer_id, 'pos_customer_avatar_id', $avatar_id );
                    } else {
                        delete_user_meta( $customer_id, 'pos_customer_avatar_id' );
                    }
                } elseif ( $key === '_pos_customer_note' ) { // <-- GUARDAR NOTA
                    update_user_meta( $customer_id, '_pos_customer_note', sanitize_textarea_field( $value ) );
                }
                 // Añadir más 'elseif' para otros metadatos si es necesario
            }
        }
    }

    // Preparar y devolver respuesta (sin cambios)
    $customer_data = pos_streaming_prepare_customer_data_for_response( $customer_id );
    return new WP_REST_Response( $customer_data, 200 );
}

/**
 * Callback API: Obtener las pasarelas de pago activas.
 */
function pos_streaming_api_get_payment_gateways( WP_REST_Request $request ) {
    $gateways_data = array();
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

    if ( $available_gateways ) {
        foreach ( $available_gateways as $gateway ) {
            if ( $gateway->enabled === 'yes' ) {
                $gateways_data[] = array( 'id' => $gateway->id, 'title' => $gateway->get_title() );
            }
        }
    }
    $has_cash_option = false;
    foreach ($gateways_data as $gw) {
        if (in_array($gw['id'], ['cod', 'bacs', 'cheque', 'pos_cash'])) {
            $has_cash_option = true; break;
        }
    }
    if (!$has_cash_option) {
         $gateways_data[] = array( 'id' => 'pos_manual', 'title' => __('Efectivo / Manual (POS)', 'pos-streaming') );
    }
    return new WP_REST_Response( $gateways_data, 200 );
}

/**
 * Callback API: Validar un código de cupón.
 */
function pos_streaming_api_validate_coupon( WP_REST_Request $request ) {
    if ( ! wc_coupons_enabled() ) {
        return new WP_Error( 'rest_coupons_disabled', __( 'Los cupones están deshabilitados.', 'pos-streaming' ), array( 'status' => 400 ) );
    }
    $coupon_code = $request['code'];
    if ( empty( $coupon_code ) ) {
         return new WP_Error( 'rest_coupon_code_required', __( 'El código de cupón es requerido.', 'pos-streaming' ), array( 'status' => 400 ) );
    }
    $coupon = new WC_Coupon( $coupon_code );
    if ( ! $coupon->get_id() ) {
        return new WP_Error( 'rest_coupon_invalid_code', sprintf( __( 'El cupón "%s" no existe.', 'pos-streaming' ), $coupon_code ), array( 'status' => 404 ) );
    }
    $is_valid = $coupon->is_valid();
    if ( ! $is_valid ) {
        $error_message = '';
        try {
            $validation_error = $coupon->validate_coupon_usage( WC()->cart ); // Simular carrito vacío
            if (is_wp_error($validation_error)) $error_message = $validation_error->get_error_message();
        } catch (Exception $e) { $error_message = $e->getMessage(); }
        if (empty($error_message)) $error_message = sprintf( __( 'El cupón "%s" no es válido.', 'pos-streaming' ), $coupon_code );
        return new WP_Error( 'rest_coupon_invalid', $error_message, array( 'status' => 400 ) );
    }
    $coupon_data = array(
        'id'            => $coupon->get_id(),
        'code'          => $coupon->get_code(),
        'amount'        => wc_format_decimal( $coupon->get_amount(), wc_get_price_decimals() ),
        'discount_type' => $coupon->get_discount_type(),
    );
    return new WP_REST_Response( $coupon_data, 200 );
}

/**
 * Callback API: Crear un pedido.
 */
function pos_streaming_api_create_order( WP_REST_Request $request ) {
    $params = $request->get_json_params();
    // error_log("POS API: create_order - Parámetros recibidos: " . print_r($params, true));

    // Validación básica (sin cambios)
    if ( empty( $params['customer_id'] ) || empty( $params['line_items'] ) || empty( $params['payment_method'] ) ) {
        return new WP_Error( 'rest_missing_params', __( 'Faltan parámetros requeridos.', 'pos-streaming' ), array( 'status' => 400 ) );
    }

    $customer_id = absint( $params['customer_id'] );
    $line_items_data = $params['line_items'];
    $payment_method_id = sanitize_key( $params['payment_method'] );
    $payment_method_title = isset($params['payment_method_title']) ? sanitize_text_field($params['payment_method_title']) : $payment_method_id;
    $set_paid = isset( $params['set_paid'] ) ? (bool) $params['set_paid'] : true;
    $meta_data_input = isset( $params['meta_data'] ) ? $params['meta_data'] : array();
    $coupon_lines_input = isset( $params['coupon_lines'] ) ? $params['coupon_lines'] : array();

    // Verificar cliente (sin cambios)
    $customer = get_user_by( 'id', $customer_id );
    if ( ! $customer ) {
         return new WP_Error( 'rest_invalid_customer', __( 'El cliente no existe.', 'pos-streaming' ), array( 'status' => 400 ) );
    }

    // --- Crear el Pedido ---
    $order = null;
    try {
        $order = wc_create_order( array(
            'customer_id' => $customer_id,
            'status'      => 'wc-pending', // Empezar como pendiente
        ) );

        if ( is_wp_error( $order ) ) throw new Exception( $order->get_error_message() );
        $order_id = $order->get_id();
        // error_log("POS API: create_order - Pedido {$order_id} iniciado.");

        // **NUEVO:** Establecer datos de facturación del pedido
        $billing_details = array(
            'first_name' => $customer->first_name,
            'last_name'  => $customer->last_name,
            'email'      => $customer->user_email,
            'phone'      => get_user_meta( $customer_id, 'billing_phone', true ),
            // Podrías añadir dirección, ciudad, etc., si los recopilas
            // 'address_1'  => get_user_meta( $customer_id, 'billing_address_1', true ),
            // 'city'       => get_user_meta( $customer_id, 'billing_city', true ),
            // 'postcode'   => get_user_meta( $customer_id, 'billing_postcode', true ),
            // 'country'    => get_user_meta( $customer_id, 'billing_country', true ),
            // 'state'      => get_user_meta( $customer_id, 'billing_state', true ),
        );
        $order->set_address( $billing_details, 'billing' );
        // error_log("POS API: create_order - Pedido {$order_id}: Datos de facturación establecidos.");
        // ----------------------------------------------------

            // ... (Obtener otros parámetros: customer_id, line_items, etc.) ...
            $pos_order_note = isset( $params['pos_order_note'] ) ? $params['pos_order_note'] : ''; // Obtener la nota
        
        
            // Añadir nota automática del POS
            $order->add_order_note( __( 'Pedido creado desde POS Streaming.', 'pos-streaming' ), false ); // false = Nota privada
        
            // Añadir la nota personalizada si existe
            if ( ! empty( $pos_order_note ) ) {
                // Añadir como nota PRIVADA (solo visible en admin)
                $order->add_order_note( $pos_order_note, true );
                // Si quisieras que fuera una NOTA AL CLIENTE (visible por él y enviada por email):
                // $order->add_order_note( $pos_order_note, true );
            }
    
        // --- Añadir Items y Precio Personalizado ---
        $pos_calculated_subtotal = 0;
        foreach ( $line_items_data as $item_data ) {
            $product_id = absint( $item_data['product_id'] );
            $variation_id = isset( $item_data['variation_id'] ) ? absint( $item_data['variation_id'] ) : 0;
            $quantity = absint( $item_data['quantity'] );
            $custom_unit_price = isset( $item_data['price'] ) ? floatval( $item_data['price'] ) : null;

            if ($custom_unit_price === null || $custom_unit_price < 0) throw new Exception( sprintf( __( 'Precio inválido para producto ID %d.', 'pos-streaming' ), $variation_id ?: $product_id ) );
            $product = wc_get_product( $variation_id ?: $product_id );
            if ( ! $product ) throw new Exception( sprintf( __( 'Producto inválido ID %d.', 'pos-streaming' ), $variation_id ?: $product_id ) );

            $item_id = $order->add_product( $product, $quantity );
            if (!$item_id) throw new Exception( sprintf( __( 'No se pudo añadir producto ID %d.', 'pos-streaming' ), $variation_id ?: $product_id ) );
            $item = $order->get_item( $item_id );
            if ( ! $item instanceof WC_Order_Item_Product ) throw new Exception( sprintf( __( 'No se pudo obtener item %d.', 'pos-streaming' ), $item_id ) );

            $line_subtotal = $custom_unit_price * $quantity;
            $line_total = $custom_unit_price * $quantity;
            $pos_calculated_subtotal += $line_total;

            $item->set_subtotal( $line_subtotal );
            $item->set_total( $line_total );
            $item->save();
            // error_log("POS API: create_order - Pedido {$order_id}, Item {$item_id}: Total {$line_total} guardado.");
        }

        // --- Método de Pago ---
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        if ( isset( $payment_gateways[ $payment_method_id ] ) ) {
            $order->set_payment_method( $payment_gateways[ $payment_method_id ] );
        } else {
             $order->set_payment_method( $payment_method_id );
             $order->set_payment_method_title( $payment_method_title );
        }
        // error_log("POS API: create_order - Pedido {$order_id}: Método pago {$payment_method_id}.");

        // --- Metadatos ---
        if ( ! empty( $meta_data_input ) ) {
            foreach ( $meta_data_input as $meta_item ) {
                if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                    $order->update_meta_data( sanitize_key( $meta_item['key'] ), wp_kses_post( $meta_item['value'] ) );
                }
            }
        }
        $order->add_order_note( __( 'Pedido creado desde POS Streaming.', 'pos-streaming' ) );

        // --- Guardar pedido TEMPRANO ---
        $order->save();
        // error_log("POS API: create_order - Pedido {$order_id}: Guardado (1ra vez).");

        // --- Cupones ---
        $coupon_discount_total = 0;
        if ( ! empty( $coupon_lines_input ) ) {
            foreach ( $coupon_lines_input as $coupon_line ) {
                if ( ! empty( $coupon_line['code'] ) ) {
                    $result = $order->apply_coupon( sanitize_text_field( $coupon_line['code'] ) );
                    // if ( is_wp_error( $result ) ) error_log("POS API: create_order - Pedido {$order_id}: Error cupón {$coupon_line['code']}: " . $result->get_error_message());
                }
            }
            $order->calculate_totals(false);
            $coupon_discount_total = $order->get_discount_total();
            // error_log("POS API: create_order - Pedido {$order_id}: Descuento cupones: {$coupon_discount_total}");
        }

        // --- Calcular y Forzar Total Final ---
        $final_total = max(0, $pos_calculated_subtotal - $coupon_discount_total);
        $order->set_total( $final_total );
        // error_log("POS API: create_order - Pedido {$order_id}: Total forzado a {$final_total}");

        // --- Estado Final y Pago ---
        $final_status = 'completed'; // O 'processing' si prefieres
        $order->update_status( $final_status, __( 'Pedido completado desde POS.', 'pos-streaming' ), false );

        if ( $set_paid && $order->get_total() > 0 ) {
            $order->payment_complete();
            // error_log("POS API: create_order - Pedido {$order_id}: Marcado pagado.");
        } elseif ($order->get_total() == 0) {
             $order->payment_complete(); // Marcar pagado si es gratis
             // error_log("POS API: create_order - Pedido {$order_id} (gratis): Marcado pagado.");
        } else {
            $order->save(); // Guardar si no se marcó como pagado
            // error_log("POS API: create_order - Pedido {$order_id}: Guardado final (no pagado).");
        }

        // Preparar Respuesta (sin cambios)
        $response_data = array( 'id' => $order->get_id(), 'status' => $order->get_status(), 'total' => $order->get_total() );
        return new WP_REST_Response( $response_data, 201 );

    } catch ( Exception $e ) {
        // error_log("POS API: create_order - *** EXCEPCIÓN ***: " . $e->getMessage());
        if ( isset( $order ) && $order instanceof WC_Order && $order->get_id() > 0 ) {
             wp_delete_post( $order->get_id(), true ); // Limpiar pedido fallido
        }
        return new WP_Error( 'rest_order_creation_failed', $e->getMessage(), array( 'status' => 500 ) );
    }
}

/**
 * Callback API: Obtener eventos para FullCalendar.
 * MODIFICADO: Incluye vencimientos de perfiles Y vencimientos de cuentas.
 */
function pos_streaming_api_get_calendar_events( WP_REST_Request $request ) {
    $all_events = array(); // Array para todos los eventos

    // --- 1. Obtener Vencimientos de Perfiles (Suscripciones Vendidas) ---
    $profile_args = array(
        'post_type'  => 'pos_profile', // Buscar perfiles
        'post_status'=> 'any', // Considerar cualquier estado de perfil
        'limit'      => -1,
        'meta_query' => array(
            'relation' => 'AND',
            // Que esté asignado a un pedido
            array( 'key' => '_pos_assigned_order_id', 'compare' => 'EXISTS' ),
            array( 'key' => '_pos_assigned_order_id', 'value' => '0', 'compare' => '!=' ),
            array( 'key' => '_pos_assigned_order_id', 'value' => '', 'compare' => '!=' ),
            // Y que tenga una fecha de expiración de suscripción válida
            array( 'key' => '_pos_subscription_expiry_date', 'compare' => 'EXISTS' ),
            array( 'key' => '_pos_subscription_expiry_date', 'value' => '', 'compare' => '!=' ),
            // Podríamos añadir filtro por estado del pedido asociado si quisiéramos
        ),
        'orderby'    => 'meta_value', // Ordenar por fecha de expiración
        'meta_key'   => '_pos_subscription_expiry_date',
        'order'      => 'ASC',
    );

    // Usar WP_Query para CPTs
    $profile_query = new WP_Query( $profile_args );

    if ( $profile_query->have_posts() ) {
        while ( $profile_query->have_posts() ) {
            $profile_query->the_post();
            $profile_id = get_the_ID();
            $order_id = get_post_meta( $profile_id, '_pos_assigned_order_id', true );
            $order = wc_get_order($order_id); // Necesitamos el pedido para obtener el título/color

            if (!$order) continue; // Si el pedido no existe, saltar

            $title = $order->get_meta('_pos_subscription_title'); // Título guardado en el pedido
            $expiry_date = $order->get_meta('_pos_subscription_expiry_date'); // Fecha guardada en el pedido
            $color = $order->get_meta('_pos_subscription_color');

            // Usar título del perfil si no hay título específico en el pedido
            if ( empty($title) ) {
                $title = get_the_title();
            }
             // Validar fecha
            if ( empty($expiry_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date) ) continue;

            $all_events[] = array(
                'id'            => 'pos_sub_exp_' . $profile_id, // ID único para evento de suscripción
                'title'         => $title,
                'start'         => $expiry_date,
                'color'         => !empty($color) ? $color : '#3a87ad', // Color azul por defecto
                'allDay'        => true,
                'extendedProps' => array(
                    'type'        => 'subscription_expiry', // Tipo de evento
                    'order_id'    => $order_id,
                    'profile_id'  => $profile_id,
                    'customer_id' => $order->get_customer_id(),
                    'order_url'   => $order->get_edit_order_url(),
                    'profile_url' => get_edit_post_link($profile_id),
                )
            );
        }
        wp_reset_postdata(); // Restaurar datos de post
    }

    // --- 2. Obtener Vencimientos de Cuentas (Proveedores) ---
    $account_args = array(
        'post_type'  => 'pos_account', // Buscar cuentas
        'post_status'=> 'publish', // O 'any' si quieres ver borradores, etc.
        'limit'      => -1,
        'meta_query' => array(
            'relation' => 'AND',
            // Que tengan fecha de vencimiento
            array( 'key' => '_pos_account_expiry_date', 'compare' => 'EXISTS' ),
            array( 'key' => '_pos_account_expiry_date', 'value' => '', 'compare' => '!=' ),
            // Opcional: Solo cuentas activas
            // array( 'key' => '_pos_account_status', 'value' => 'active', 'compare' => '=' ),
        ),
        'orderby'    => 'meta_value', // Ordenar por fecha de expiración
        'meta_key'   => '_pos_account_expiry_date',
        'order'      => 'ASC',
    );

    $account_query = new WP_Query( $account_args );

    if ( $account_query->have_posts() ) {
        while ( $account_query->have_posts() ) {
            $account_query->the_post();
            $account_id = get_the_ID();
            $account_title = get_the_title();
            $expiry_date = get_post_meta( $account_id, '_pos_account_expiry_date', true );

            // Validar fecha
            if ( empty($expiry_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date) ) continue;

            $all_events[] = array(
                'id'            => 'pos_acc_exp_' . $account_id, // ID único para evento de cuenta
                'title'         => sprintf( __('Vence Cuenta: %s', 'pos-streaming'), $account_title ), // Título distintivo
                'start'         => $expiry_date,
                'color'         => '#dc3545', // Color ROJO por defecto para vencimiento de cuenta
                'allDay'        => true,
                'extendedProps' => array(
                    'type'        => 'account_expiry', // Tipo de evento
                    'account_id'  => $account_id,
                    'account_url' => get_edit_post_link($account_id),
                )
            );
        }
        wp_reset_postdata(); // Restaurar datos de post
    }

    // --- Devolver todos los eventos combinados ---
    return new WP_REST_Response( $all_events, 200 );
}

/**
 * **MODIFICADO** Callback API: Obtener datos de ventas para DataTables.
 * Ahora incluye los detalles de suscripción en la columna 'Meta'.
 */
function pos_streaming_api_get_sales_for_datatable( WP_REST_Request $request ) {
    $params = $request->get_params();

    // Parámetros de DataTables
    $draw = isset( $params['draw'] ) ? absint( $params['draw'] ) : 0;
    $start = isset( $params['start'] ) ? absint( $params['start'] ) : 0;
    $length = isset( $params['length'] ) ? absint( $params['length'] ) : 10;
    $search_value = isset( $params['search']['value'] ) ? sanitize_text_field( $params['search']['value'] ) : '';

    // Mapeo de columnas
    $column_map = array(
        0 => 'ID', 1 => 'date', 2 => 'customer', 3 => 'total',
        4 => '_pos_sale_type', 5 => 'note', 6 => 'meta',
    );

    $order_column_index = isset( $params['order'][0]['column'] ) ? absint( $params['order'][0]['column'] ) : 0;
    $order_dir = isset( $params['order'][0]['dir'] ) && strtolower( $params['order'][0]['dir'] ) === 'asc' ? 'ASC' : 'DESC';
    $orderby = $column_map[ $order_column_index ] ?? 'ID';

    // Argumentos base para WC_Order_Query
    $args = array(
        'limit'    => $length,
        'offset'   => $start,
        'orderby'  => $orderby,
        'order'    => $order_dir,
        'paginate' => true,
        'return'   => 'ids',
    );

    // Manejo de ordenación por metadatos
    if ( in_array( $orderby, ['_pos_sale_type'] ) ) {
        $args['orderby'] = 'meta_value';
        $args['meta_key'] = $orderby;
    }

    // Manejo de Búsqueda
    if ( ! empty( $search_value ) ) {
        $args['s'] = $search_value;
    }

    // Consulta para obtener IDs y totales
    $order_query = new WC_Order_Query( $args );
    $result_object = $order_query->get_orders();
    $order_ids = $result_object->orders ?? [];
    $records_filtered = $result_object->total ?? 0;

    // Consulta para obtener el total SIN filtrar
    $total_args = array( 'return' => 'ids', 'limit' => -1 );
    $total_query = new WC_Order_Query( $total_args );
    $records_total = count($total_query->get_orders());

    // Preparar datos para la respuesta
    $data = array();
    if ( ! empty( $order_ids ) ) {
        $orders = array_map( 'wc_get_order', $order_ids );
        $orders = array_filter( $orders );

        foreach ( $orders as $order ) {
            $order_id = $order->get_id();
            $order_url = $order->get_edit_order_url();
            $customer_name = $order->get_formatted_billing_full_name() ?: __( 'Invitado', 'pos-streaming' );
            $user_id = $order->get_customer_id();
            $customer_link = $user_id ? get_edit_user_link( $user_id ) : '';
            $date_created = $order->get_date_created();

            // Obtener notas
            $notes = wc_get_order_notes( array(
                'order_id' => $order_id, 'type' => 'internal', 'orderby' => 'date_created', 'order' => 'DESC',
            ) );
            $notes_html = '';
            if (!empty($notes)) {
                $latest_note = reset($notes);
                // Usar wp_kses_post para permitir el <br> pero escapar otro HTML potencialmente peligroso
                $notes_html = wp_kses_post( wp_trim_words( $latest_note->content, 15, '...' ) );
            }

            // Obtener tipo de venta POS
            $sale_type = $order->get_meta( '_pos_sale_type', true );
            $sale_type_label = $sale_type ? esc_html( ucfirst( $sale_type ) ) : 'N/A';

            // --- **NUEVO:** Construir contenido para la columna 'Meta' ---
            $meta_display = '-'; // Valor por defecto
            if ( $sale_type === 'subscription' ) {
                $sub_title = $order->get_meta( '_pos_subscription_title', true );
                $sub_expiry = $order->get_meta( '_pos_subscription_expiry_date', true );
                $sub_color = $order->get_meta( '_pos_subscription_color', true );

                $meta_parts = [];
                if ( ! empty( $sub_title ) ) {
                    $meta_parts[] = '<strong>' . esc_html__( 'Título:', 'pos-streaming' ) . '</strong> ' . esc_html( $sub_title );
                }
                if ( ! empty( $sub_expiry ) ) {
                    $formatted_date = $sub_expiry; // Valor por defecto
                    try {
                        // Intentar formatear la fecha usando el formato de WP
                        $date_obj = new DateTime($sub_expiry);
                        $formatted_date = $date_obj->format(get_option('date_format'));
                    } catch (Exception $e) { /* Mantener valor original si falla */ }
                    $meta_parts[] = '<strong>' . esc_html__( 'Vence:', 'pos-streaming' ) . '</strong> ' . esc_html( $formatted_date );
                }
                if ( ! empty( $sub_color ) ) {
                    // Generar el HTML para el swatch de color y el código
                    $meta_parts[] = '<strong>' . esc_html__( 'Color:', 'pos-streaming' ) . '</strong> '
                                   . '<span style="display:inline-block; width: 12px; height: 12px; background-color:' . esc_attr( $sub_color ) . '; border: 1px solid #ccc; vertical-align: middle; margin-right: 3px;"></span>'
                                   . '<code>' . esc_html( $sub_color ) . '</code>';
                }

                if ( ! empty( $meta_parts ) ) {
                    $meta_display = implode( '<br>', $meta_parts ); // Unir con saltos de línea HTML
                } else {
                    // Mensaje si es suscripción pero no hay detalles
                    $meta_display = '<em>' . esc_html__( 'Detalles suscripción no encontrados', 'pos-streaming' ) . '</em>';
                }
            }
            // --- Fin construcción columna 'Meta' ---

            $data[] = array(
                // Columnas en el orden del HTML/JS
                sprintf( '<a href="%s"><strong>#%s</strong></a>', esc_url( $order_url ), $order_id ), // 0: Pedido #
                $date_created instanceof WC_DateTime ? $date_created->date_i18n( get_option( 'date_format' ) ) : 'N/A', // 1: Fecha
                $customer_link ? sprintf( '<a href="%s">%s</a>', esc_url( $customer_link ), esc_html( $customer_name ) ) : esc_html( $customer_name ), // 2: Cliente
                $order->get_formatted_order_total(), // 3: Total
                $sale_type_label, // 4: Tipo (POS)
                $notes_html, // 5: Notas
                $meta_display, // 6: Meta <-- Usar la variable construida
            );
        }
    }

    // Respuesta JSON para DataTables
    $response_data = array(
        'draw'            => $draw,
        'recordsTotal'    => $records_total,
        'recordsFiltered' => $records_filtered,
        'data'            => $data,
    );

    return new WP_REST_Response( $response_data, 200 );
}


// =========================================================================
// 3. FUNCIONES AUXILIARES Y DE PERMISOS
// =========================================================================

/**
 * Prepara los datos del cliente para la respuesta de la API.
 */
function pos_streaming_prepare_customer_data_for_response( $customer_id ) {
    $user_info = get_userdata( $customer_id );
    if ( ! $user_info ) return array();

    $avatar_id = get_user_meta( $customer_id, 'pos_customer_avatar_id', true );
    $avatar_url = '';
    if ( $avatar_id && $url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) ) {
        $avatar_url = $url;
    } else {
        $avatar_url = get_avatar_url( $customer_id, array( 'size' => 96, 'default' => 'mystery' ) );
        $avatar_id = '';
    }

    // **NUEVO:** Obtener la nota del cliente
    $customer_note = get_user_meta( $customer_id, '_pos_customer_note', true );

    return array(
        'id'         => $user_info->ID,
        'first_name' => $user_info->first_name,
        'last_name'  => $user_info->last_name,
        'email'      => $user_info->user_email,
        'phone'      => get_user_meta( $customer_id, 'billing_phone', true ),
        'avatar_id'  => $avatar_id,
        'avatar_url' => $avatar_url,
        'note'       => $customer_note, // <-- AÑADIDO: Incluir la nota
    );
}

/**
 * Comprueba los permisos para acceder a los endpoints de la API.
 */
function pos_streaming_api_permissions_check( WP_REST_Request $request ) {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        // error_log('POS API Permission Check FAILED: User cannot manage_woocommerce.');
        return new WP_Error( 'rest_forbidden_capability', __( 'No tienes permiso.', 'pos-streaming' ), array( 'status' => rest_authorization_required_code() ) );
    }
    $nonce = $request->get_header('X-WP-Nonce') ?: $request->get_param('_wpnonce');
    if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
         // error_log('POS API Permission Check FAILED: Invalid or missing nonce.');
         return new WP_Error( 'rest_forbidden_nonce', __( 'Nonce inválido.', 'pos-streaming' ), array( 'status' => 403 ) );
    }
    // error_log('POS API Permission Check PASSED.');
    return true;
}
?>
