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
        'permission_callback' => 'pos_streaming_api_permissions_check', // Usar función de permisos
        'args'                => array(
            'search' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'page' => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1 ),
            'per_page' => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 10 ),
            'featured' => array( 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean' ),
        ),
    ) );

    // --- **NUEVO:** RUTA PARA BUSCAR CLIENTES ---
    register_rest_route( $namespace, '/customers', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_search_customers',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'search' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'per_page' => array( 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 10 ),
        ),
    ) );

    // --- **NUEVO:** RUTA PARA OBTENER UN CLIENTE ESPECÍFICO ---
    register_rest_route( $namespace, '/customers/(?P<id>\d+)', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_customer',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'id' => array( 'validate_callback' => function($param, $request, $key) { return is_numeric( $param ); } ),
        ),
    ) );

    // RUTA PARA CREAR UN CLIENTE ---
    register_rest_route( $namespace, '/customers', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_streaming_api_create_customer',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'first_name' => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'last_name'  => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            'email'      => array( 'type' => 'string', 'format' => 'email', 'sanitize_callback' => 'sanitize_email' ),
            'phone'      => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ), // Se espera E.164
            'meta_data'  => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ), // Para el avatar_id
        ),
    ) );

    //  RUTA PARA ACTUALIZAR UN CLIENTE ---
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
            'meta_data'  => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
        ),
    ) );

    // RUTA PARA OBTENER PASARELAS DE PAGO ---
    register_rest_route( $namespace, '/payment-gateways', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_payment_gateways',
        'permission_callback' => 'pos_streaming_api_permissions_check', // Reutilizar permisos
    ) );


    register_rest_route( $namespace, '/coupons/validate', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST, ya que enviamos el código
        'callback'            => 'pos_streaming_api_validate_coupon',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array(
            'code' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'description'       => __( 'El código del cupón a validar.', 'pos-streaming' ),
            ),
            // Opcional: Podrías pasar el contexto del carrito (items, subtotal) para validaciones más complejas
            // 'cart_context' => array( 'type' => 'object' ),
        ),
    ) );


    // RUTA PARA CREAR PEDIDOS (POST /orders) ---
    register_rest_route( $namespace, '/orders', array(
        'methods'             => WP_REST_Server::CREATABLE, // POST
        'callback'            => 'pos_streaming_api_create_order', // Implementación abajo
        'permission_callback' => 'pos_streaming_api_permissions_check',
        'args'                => array( // Definir argumentos esperados para validación/sanitización
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
            // Añadir más args si es necesario (billing, shipping, etc.)
        ),
    ) );

    // RUTA PARA OBTENER EVENTOS DEL CALENDARIO ---
    register_rest_route( $namespace, '/calendar-events', array(
        'methods'             => WP_REST_Server::READABLE, // GET
        'callback'            => 'pos_streaming_api_get_calendar_events',
        'permission_callback' => 'pos_streaming_api_permissions_check',
        // Podríamos añadir args para filtrar por fecha (start, end) si es necesario
        // 'args' => array( 'start' => ..., 'end' => ... ),
    ) );

}
add_action( 'rest_api_init', 'pos_streaming_register_rest_routes' );

// =========================================================================
// 2. FUNCIONES CALLBACK DE LA API
// =========================================================================

/**
 * Callback API: Buscar productos.
 * (Sin cambios respecto a la versión anterior)
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
 * **NUEVO:** Callback API: Buscar clientes.
 * Busca usuarios con rol 'customer' o sin rol específico por nombre, email o teléfono.
 */
function pos_streaming_api_search_customers( WP_REST_Request $request ) {
    $search_term = $request['search'];
    $per_page = $request['per_page'];

    $args = array(
        'role__in'    => array( 'customer' ), // Busca principalmente clientes de WC
        'search'      => '*' . esc_attr( $search_term ) . '*', // Búsqueda wildcard
        'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
        'number'      => $per_page,
        'orderby'     => 'display_name',
        'order'       => 'ASC',
        'meta_query'  => array(
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
            array(
                'key'     => 'billing_phone', // Buscar también en el teléfono de facturación de WC
                'value'   => $search_term,
                'compare' => 'LIKE'
            )
        )
    );

    $user_query = new WP_User_Query( $args );
    $customers_data = array();

    if ( ! empty( $user_query->get_results() ) ) {
        foreach ( $user_query->get_results() as $user ) {
            $customers_data[] = pos_streaming_prepare_customer_data_for_response( $user->ID );
        }
    }

    // Podríamos añadir una segunda query para usuarios sin rol si la primera no da resultados,
    // pero por simplicidad, nos centramos en clientes WC por ahora.

    return new WP_REST_Response( $customers_data, 200 );
}

/**
 * **NUEVO:** Callback API: Obtener un cliente específico.
 */
function pos_streaming_api_get_customer( WP_REST_Request $request ) {
    $customer_id = (int) $request['id'];
    $user = get_user_by( 'id', $customer_id );

    if ( ! $user ) {
        return new WP_Error( 'rest_customer_invalid_id', __( 'Cliente no encontrado.', 'pos-streaming' ), array( 'status' => 404 ) );
    }

    // Podrías añadir una comprobación de rol si solo quieres permitir obtener 'customers'
    // if (!in_array('customer', $user->roles)) { ... }

    $customer_data = pos_streaming_prepare_customer_data_for_response( $customer_id );

    return new WP_REST_Response( $customer_data, 200 );
}

/**
 * Callback API: Crear un nuevo cliente.
 * Usa wc_create_new_customer para asegurar que se crea como cliente de WooCommerce.
 */
function pos_streaming_api_create_customer( WP_REST_Request $request ) {
    $email = $request['email'];
    $first_name = $request['first_name'];
    $last_name = $request['last_name'];
    $phone = $request['phone'];
    $meta_data = $request['meta_data']; // Array de objetos {key: '...', value: '...'}

    // Validar email si se proporciona
    if ( ! empty( $email ) && ! is_email( $email ) ) {
        return new WP_Error( 'rest_customer_invalid_email', __( 'La dirección de correo electrónico no es válida.', 'pos-streaming' ), array( 'status' => 400 ) );
    }
    if ( ! empty( $email ) && email_exists( $email ) ) {
        return new WP_Error( 'rest_customer_email_exists', __( 'Ya existe un cliente con esta dirección de correo electrónico.', 'pos-streaming' ), array( 'status' => 400 ) );
    }

    // Generar un nombre de usuario si no se proporciona email (o usar email)
    $username = ! empty( $email ) ? $email : sanitize_user( $first_name . $last_name . wp_rand( 100, 999 ), true );
    if ( username_exists( $username ) ) {
        $username .= wp_rand( 10, 99 ); // Añadir números extra si ya existe
    }

    // Generar contraseña segura
    $password = wp_generate_password();

    // Crear el cliente usando la función de WooCommerce
    $customer_id = wc_create_new_customer( $email, $username, $password );

    if ( is_wp_error( $customer_id ) ) {
        return new WP_Error( 'rest_customer_creation_failed', $customer_id->get_error_message(), array( 'status' => 500 ) );
    }

    // Actualizar datos adicionales (nombre, apellido, teléfono, metadatos)
    $update_data = array(
        'ID'         => $customer_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
    );
    wp_update_user( $update_data );

    // Guardar teléfono en billing_phone
    if ( ! empty( $phone ) ) {
        update_user_meta( $customer_id, 'billing_phone', $phone );
    }

    // Guardar metadatos (ej: avatar_id)
    if ( ! empty( $meta_data ) && is_array( $meta_data ) ) {
        foreach ( $meta_data as $meta_item ) {
            if ( isset( $meta_item['key'] ) && $meta_item['key'] === 'pos_customer_avatar_id' ) {
                $avatar_id = absint( $meta_item['value'] );
                if ( $avatar_id > 0 && 'attachment' === get_post_type( $avatar_id ) ) {
                    update_user_meta( $customer_id, 'pos_customer_avatar_id', $avatar_id );
                } else {
                    delete_user_meta( $customer_id, 'pos_customer_avatar_id' ); // Borrar si no es válido
                }
                break; // Solo nos interesa este metadato por ahora
            }
        }
    }

    // Preparar y devolver los datos del cliente creado
    $customer_data = pos_streaming_prepare_customer_data_for_response( $customer_id );
    $response = new WP_REST_Response( $customer_data, 201 ); // 201 Created
    // Opcional: Añadir cabecera Location
    // $response->header( 'Location', rest_url( sprintf( '%s/customers/%d', $request->get_route(), $customer_id ) ) );

    return $response;
}

/**
 *Callback API: Actualizar un cliente existente.
 */
function pos_streaming_api_update_customer( WP_REST_Request $request ) {
    $customer_id = (int) $request['id'];
    $user = get_user_by( 'id', $customer_id );

    if ( ! $user ) {
        return new WP_Error( 'rest_customer_invalid_id', __( 'Cliente no encontrado para actualizar.', 'pos-streaming' ), array( 'status' => 404 ) );
    }

    $update_data = array( 'ID' => $customer_id );
    $params = $request->get_params();

    // Campos de usuario principales
    if ( isset( $params['first_name'] ) ) $update_data['first_name'] = $params['first_name'];
    if ( isset( $params['last_name'] ) ) $update_data['last_name'] = $params['last_name'];
    if ( isset( $params['email'] ) ) {
        $email = $params['email'];
        if ( ! empty( $email ) && ! is_email( $email ) ) {
            return new WP_Error( 'rest_customer_invalid_email', __( 'La dirección de correo electrónico no es válida.', 'pos-streaming' ), array( 'status' => 400 ) );
        }
        // Comprobar si el email ya existe PARA OTRO usuario
        $existing_user = email_exists( $email );
        if ( $existing_user && $existing_user !== $customer_id ) {
            return new WP_Error( 'rest_customer_email_exists', __( 'Ya existe otro cliente con esta dirección de correo electrónico.', 'pos-streaming' ), array( 'status' => 400 ) );
        }
        $update_data['user_email'] = $email;
    }

    // Actualizar datos de usuario
    $result = wp_update_user( $update_data );
    if ( is_wp_error( $result ) ) {
        return new WP_Error( 'rest_customer_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
    }

    // Actualizar teléfono (billing_phone)
    if ( isset( $params['phone'] ) ) {
        update_user_meta( $customer_id, 'billing_phone', $params['phone'] );
    }

    // Actualizar metadatos (ej: avatar_id)
    if ( isset( $params['meta_data'] ) && is_array( $params['meta_data'] ) ) {
        foreach ( $params['meta_data'] as $meta_item ) {
            if ( isset( $meta_item['key'] ) && $meta_item['key'] === 'pos_customer_avatar_id' ) {
                $avatar_id = absint( $meta_item['value'] );
                 if ( $avatar_id > 0 && 'attachment' === get_post_type( $avatar_id ) ) {
                    update_user_meta( $customer_id, 'pos_customer_avatar_id', $avatar_id );
                } else {
                    delete_user_meta( $customer_id, 'pos_customer_avatar_id' ); // Borrar si no es válido o está vacío
                }
                break;
            }
        }
    }

    // Preparar y devolver los datos actualizados
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
            // Solo incluir pasarelas que estén habilitadas
            if ( $gateway->enabled === 'yes' ) {
                $gateways_data[] = array(
                    'id'    => $gateway->id,
                    'title' => $gateway->get_title(),
                    // 'icon'  => $gateway->get_icon(), // Opcional: si quieres mostrar iconos
                );
            }
        }
    }

    // Añadir opción manual/efectivo si no existe una específica para POS
    // Puedes personalizar esto o añadir una pasarela específica para POS en WC
    $has_cash_option = false;
    foreach ($gateways_data as $gw) {
        if (in_array($gw['id'], ['cod', 'bacs', 'cheque', 'pos_cash'])) { // IDs comunes o uno personalizado
            $has_cash_option = true;
            break;
        }
    }
    if (!$has_cash_option) {
         $gateways_data[] = array(
             'id' => 'pos_manual', // ID genérico para el POS
             'title' => __('Efectivo / Manual (POS)', 'pos-streaming')
         );
    }


    return new WP_REST_Response( $gateways_data, 200 );
}

/**
 *Callback API: Validar un código de cupón.
 */
function pos_streaming_api_validate_coupon( WP_REST_Request $request ) {
    // Verificar si los cupones están habilitados en WooCommerce
    if ( ! wc_coupons_enabled() ) {
        return new WP_Error(
            'rest_coupons_disabled',
            __( 'Los cupones están deshabilitados en la tienda.', 'pos-streaming' ),
            array( 'status' => 400 ) // Bad Request
        );
    }

    $coupon_code = $request['code'];

    if ( empty( $coupon_code ) ) {
         return new WP_Error(
            'rest_coupon_code_required',
            __( 'El código de cupón es requerido.', 'pos-streaming' ),
            array( 'status' => 400 )
        );
    }

    // Obtener el objeto del cupón
    $coupon = new WC_Coupon( $coupon_code );

    // Verificar si el cupón existe
    if ( ! $coupon->get_id() ) {
        return new WP_Error(
            'rest_coupon_invalid_code',
            sprintf( __( 'El código de cupón "%s" no existe.', 'pos-streaming' ), $coupon_code ),
            array( 'status' => 404 ) // Not Found
        );
    }

    // Validar el cupón (fechas, límites de uso, etc.)
    // Nota: is_valid() puede necesitar el contexto del carrito para restricciones más complejas.
    // Por ahora, hacemos una validación básica.
    $is_valid = $coupon->is_valid();

    // Si no es válido, intentar obtener el mensaje de error específico
    if ( ! $is_valid ) {
        $error_message = '';
        // Intentar obtener el error de validación (puede requerir contexto de carrito)
        try {
            // Simular un carrito vacío para obtener algunos errores básicos
            $validation_error = $coupon->validate_coupon_usage( WC()->cart );
            if (is_wp_error($validation_error)) {
                 $error_message = $validation_error->get_error_message();
            }
        } catch (Exception $e) {
             $error_message = $e->getMessage();
        }

        // Mensaje genérico si no se pudo obtener uno específico
        if (empty($error_message)) {
             $error_message = sprintf( __( 'El cupón "%s" no es válido.', 'pos-streaming' ), $coupon_code );
        }

        return new WP_Error(
            'rest_coupon_invalid',
            $error_message,
            array( 'status' => 400 ) // Bad Request
        );
    }

    // Si el cupón es válido, preparar la respuesta
    $coupon_data = array(
        'id'            => $coupon->get_id(),
        'code'          => $coupon->get_code(),
        'amount'        => wc_format_decimal( $coupon->get_amount(), wc_get_price_decimals() ), // Formatear el monto
        'discount_type' => $coupon->get_discount_type(), // 'percent', 'fixed_cart', 'fixed_product'
        // Podrías añadir más detalles si son necesarios para el frontend
        // 'description'   => $coupon->get_description(),
        // 'date_expires'  => $coupon->get_date_expires() ? $coupon->get_date_expires()->getTimestamp() : null,
    );

    return new WP_REST_Response( $coupon_data, 200 );
}

/**
 * **FINAL v4:** Callback API: Crear un pedido.
 * Usando error_log() para depuración.
 */
function pos_streaming_api_create_order( WP_REST_Request $request ) {
    $params = $request->get_json_params();
    error_log("POS API: create_order - Parámetros recibidos: " . print_r($params, true)); // LOG PARAMS

    // --- Validación básica ---
    if ( empty( $params['customer_id'] ) || empty( $params['line_items'] ) || empty( $params['payment_method'] ) ) {
        error_log("POS API: create_order - Error: Faltan parámetros.");
        return new WP_Error( 'rest_missing_params', __( 'Faltan parámetros requeridos para crear el pedido.', 'pos-streaming' ), array( 'status' => 400 ) );
    }

    $customer_id = absint( $params['customer_id'] );
    $line_items_data = $params['line_items'];
    $payment_method_id = sanitize_key( $params['payment_method'] );
    $payment_method_title = isset($params['payment_method_title']) ? sanitize_text_field($params['payment_method_title']) : $payment_method_id;
    $set_paid = isset( $params['set_paid'] ) ? (bool) $params['set_paid'] : true;
    $meta_data_input = isset( $params['meta_data'] ) ? $params['meta_data'] : array();
    $coupon_lines_input = isset( $params['coupon_lines'] ) ? $params['coupon_lines'] : array();

    // Verificar cliente
    if ( ! get_user_by( 'id', $customer_id ) ) {
         error_log("POS API: create_order - Error: Cliente ID {$customer_id} no existe.");
         return new WP_Error( 'rest_invalid_customer', __( 'El cliente especificado no existe.', 'pos-streaming' ), array( 'status' => 400 ) );
    }

    // --- Crear el Pedido ---
    $order = null;
    try {
        $order = wc_create_order( array(
            'customer_id' => $customer_id,
            'status'      => 'wc-pending',
        ) );

        if ( is_wp_error( $order ) ) {
            throw new Exception( $order->get_error_message() );
        }
        $order_id = $order->get_id();
        error_log("POS API: create_order - Pedido {$order_id} iniciado.");

        // --- Añadir Items y Establecer Precio Personalizado ---
        $pos_calculated_subtotal = 0;
        foreach ( $line_items_data as $item_data ) {
            $product_id = absint( $item_data['product_id'] );
            $variation_id = isset( $item_data['variation_id'] ) ? absint( $item_data['variation_id'] ) : 0;
            $quantity = absint( $item_data['quantity'] );
            $custom_unit_price = isset( $item_data['price'] ) ? floatval( $item_data['price'] ) : null;

            if ($custom_unit_price === null || $custom_unit_price < 0) {
                 throw new Exception( sprintf( __( 'Precio inválido para producto ID %d.', 'pos-streaming' ), $variation_id ?: $product_id ) );
            }

            $product = wc_get_product( $variation_id ?: $product_id );
            if ( ! $product ) {
                throw new Exception( sprintf( __( 'Producto inválido ID %d.', 'pos-streaming' ), $variation_id ?: $product_id ) );
            }

            // 1. Añadir producto
            $item_id = $order->add_product( $product, $quantity );
            if (!$item_id) {
                 throw new Exception( sprintf( __( 'No se pudo añadir el producto ID %d al pedido.', 'pos-streaming' ), $variation_id ?: $product_id ) );
            }

            // 2. Obtener item
            $item = $order->get_item( $item_id );
            if ( ! $item instanceof WC_Order_Item_Product ) {
                 throw new Exception( sprintf( __( 'No se pudo obtener el item %d del pedido.', 'pos-streaming' ), $item_id ) );
            }

            // 3. Calcular totales de línea
            $line_subtotal = $custom_unit_price * $quantity;
            $line_total = $custom_unit_price * $quantity;
            $pos_calculated_subtotal += $line_total;

            // 4. Establecer totales en el item
            $item->set_subtotal( $line_subtotal );
            $item->set_total( $line_total );
            error_log("POS API: create_order - Pedido {$order_id}, Item {$item_id}: Intentando set_total a {$line_total}");

            // 5. Guardar item
            $item->save();
            error_log("POS API: create_order - Pedido {$order_id}, Item {$item_id}: Item guardado.");
        }

        // --- Establecer Método de Pago ---
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        if ( isset( $payment_gateways[ $payment_method_id ] ) ) {
            $order->set_payment_method( $payment_gateways[ $payment_method_id ] );
        } else {
             $order->set_payment_method( $payment_method_id );
             $order->set_payment_method_title( $payment_method_title );
        }
         error_log("POS API: create_order - Pedido {$order_id}: Método de pago establecido a {$payment_method_id}");

        // --- Añadir Metadatos (ANTES de calcular totales y guardar) ---
        if ( ! empty( $meta_data_input ) ) {
             error_log("POS API: create_order - Pedido {$order_id}: Procesando metadatos: " . print_r($meta_data_input, true));
            foreach ( $meta_data_input as $meta_item ) {
                if ( isset( $meta_item['key'] ) && isset( $meta_item['value'] ) ) {
                    $key = sanitize_key( $meta_item['key'] );
                    $value = wp_kses_post( $meta_item['value'] );
                    error_log("POS API: create_order - Pedido {$order_id}: Guardando Meta -> Key: {$key}, Value: {$value}");
                    $order->update_meta_data( $key, $value );
                } else {
                     error_log("POS API: create_order - Pedido {$order_id}: Metadato inválido recibido: " . print_r($meta_item, true));
                }
            }
        } else {
             error_log("POS API: create_order - Pedido {$order_id}: No hay metadatos adicionales.");
        }
        $order->add_order_note( __( 'Pedido creado desde POS Streaming.', 'pos-streaming' ) );

        // --- Guardar el pedido TEMPRANO para persistir metadatos ---
        $order->save();
        error_log("POS API: create_order - Pedido {$order_id}: Guardado (1ra vez) para persistir metadatos.");
        // Verificar si los metadatos se guardaron
        $test_meta_title = $order->get_meta('_pos_subscription_title');
        error_log("POS API: create_order - Pedido {$order_id}: Verificación Meta '_pos_subscription_title' DESPUÉS del 1er save: " . $test_meta_title);


        // --- Añadir Cupones (DESPUÉS de guardar metadatos y items) ---
        $coupon_discount_total = 0;
        if ( ! empty( $coupon_lines_input ) ) {
             error_log("POS API: create_order - Pedido {$order_id}: Aplicando cupones: " . print_r($coupon_lines_input, true));
            foreach ( $coupon_lines_input as $coupon_line ) {
                if ( ! empty( $coupon_line['code'] ) ) {
                    $coupon_code = sanitize_text_field( $coupon_line['code'] );
                    $result = $order->apply_coupon( $coupon_code );
                    if ( is_wp_error( $result ) ) {
                        error_log("POS API: create_order - Pedido {$order_id}: Error aplicando cupón {$coupon_code}: " . $result->get_error_message());
                    } else {
                         error_log("POS API: create_order - Pedido {$order_id}: Cupón {$coupon_code} aplicado.");
                    }
                }
            }
            // Recalcular para que los descuentos de cupón se reflejen internamente
             $order->calculate_totals(false); // false = no guardar aún
             $coupon_discount_total = $order->get_discount_total();
             error_log("POS API: create_order - Pedido {$order_id}: Descuento total por cupones: {$coupon_discount_total}");
        }


        // --- Calcular y Forzar el Total Final del Pedido ---
        $final_total = $pos_calculated_subtotal - $coupon_discount_total;
        $final_total = max(0, $final_total);

        error_log("POS API: create_order - Pedido {$order_id}: Calculando total final. Subtotal POS: {$pos_calculated_subtotal}, Descuento Cupón: {$coupon_discount_total}, Total Final: {$final_total}");

        // Establecer el total calculado manualmente
        $order->set_total( $final_total );
        error_log("POS API: create_order - Pedido {$order_id}: Total forzado a {$final_total}");

        // --- Establecer Estado Final y Pago ---
        $final_status = 'completed';
        $order->update_status( $final_status, __( 'Pedido completado desde POS.', 'pos-streaming' ), false ); // false = no guardar aún
        error_log("POS API: create_order - Pedido {$order_id}: Estado actualizado a {$final_status} (sin guardar aún).");

        if ( $set_paid && $order->get_total() > 0 ) {
            $order->payment_complete();
             error_log("POS API: create_order - Pedido {$order_id}: Marcado como pagado vía payment_complete().");
        } elseif ($order->get_total() == 0) {
             $order->payment_complete();
             error_log("POS API: create_order - Pedido {$order_id} (total 0): Marcado como pagado vía payment_complete().");
        } else {
            // Si no se marca como pagado, guardar el cambio de estado y el total forzado
            $order->save();
             error_log("POS API: create_order - Pedido {$order_id}: Guardado (final - no pagado). Total Forzado: " . $order->get_total());
        }

        // Verificar total y metadatos ANTES de la respuesta
        $final_saved_total = $order->get_total();
        $final_saved_meta_title = $order->get_meta('_pos_subscription_title');
        error_log("POS API: create_order - Pedido {$order_id}: Verificación FINAL - Total: {$final_saved_total}, Meta Title: {$final_saved_meta_title}");


        // --- Preparar Respuesta ---
        $response_data = array(
            'id'      => $order->get_id(),
            'status'  => $order->get_status(),
            'total'   => $order->get_total(),
        );
        return new WP_REST_Response( $response_data, 201 );

    } catch ( Exception $e ) {
        error_log("POS API: create_order - *** EXCEPCIÓN ***: " . $e->getMessage());
        if ( isset( $order ) && $order instanceof WC_Order && $order->get_id() > 0 ) {
             wp_delete_post( $order->get_id(), true );
             error_log("POS API: create_order - Pedido {$order_id} eliminado debido a excepción.");
        }
        return new WP_Error( 'rest_order_creation_failed', $e->getMessage(), array( 'status' => 500 ) );
    }
}

/**
 * **NUEVO:** Callback API: Obtener eventos para FullCalendar.
 * Busca pedidos completados/procesando marcados como suscripción POS
 * y devuelve los datos en formato de evento FullCalendar.
 */
function pos_streaming_api_get_calendar_events( WP_REST_Request $request ) {
    $events = array();

    // Argumentos para buscar pedidos relevantes
    $args = array(
        'limit'      => -1, // Obtener todos (-1). Considerar paginación si hay muchos.
        'status'     => array('wc-completed', 'wc-processing'), // Pedidos activos
        'meta_query' => array(
            'relation' => 'AND', // Todas las condiciones deben cumplirse
            array(
                'key'     => '_pos_sale_type',
                'value'   => 'subscription',
                'compare' => '=',
            ),
            array(
                'key'     => '_pos_subscription_expiry_date', // Asegurarse de que la fecha exista
                'compare' => 'EXISTS',
            ),
             array(
                'key'     => '_pos_subscription_expiry_date', // Y no esté vacía
                'value'   => '',
                'compare' => '!=',
            ),
        ),
        'orderby'    => 'date', // Ordenar por fecha de pedido
        'order'      => 'DESC',
    );

    // Opcional: Filtrar por rango de fechas si FullCalendar lo envía
    // $start_param = $request->get_param('start'); // Formato YYYY-MM-DDThh:mm:ss
    // $end_param = $request->get_param('end');
    // if ($start_param && $end_param) {
    //     $args['date_query'] = array(
    //         array(
    //             'column' => '_pos_subscription_expiry_date', // ¡OJO! date_query no funciona bien con meta_values directamente
    //             'after'  => gmdate('Y-m-d', strtotime($start_param)), // Adaptar según formato
    //             'before' => gmdate('Y-m-d', strtotime($end_param)),
    //             'inclusive' => true,
    //         ),
    //     );
    //     // Alternativa: Filtrar después de obtener todos los pedidos
    // }


    $orders = wc_get_orders( $args );

    if ( $orders ) {
        foreach ( $orders as $order ) {
            $title = $order->get_meta('_pos_subscription_title');
            $expiry_date = $order->get_meta('_pos_subscription_expiry_date'); // Formato YYYY-MM-DD
            $color = $order->get_meta('_pos_subscription_color');
            $order_id = $order->get_id();

            // Validar datos esenciales
            if ( empty($title) || empty($expiry_date) ) {
                continue; // Saltar si falta título o fecha
            }

            // Validar formato de fecha (básico)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiry_date)) {
                 error_log("POS Calendar: Fecha inválida encontrada para pedido {$order_id}: {$expiry_date}");
                 continue; // Saltar si la fecha no es YYYY-MM-DD
            }

            // Crear objeto de evento para FullCalendar
            $events[] = array(
                'id'            => 'pos_order_' . $order_id, // ID único para el evento
                'title'         => $title,
                'start'         => $expiry_date, // Fecha de vencimiento
                'color'         => !empty($color) ? $color : '#3a87ad', // Color guardado o por defecto
                'allDay'        => true, // Marcar como evento de día completo
                'extendedProps' => array( // Propiedades extra para usar en JS (ej: al hacer clic)
                    'order_id' => $order_id,
                    'customer_id' => $order->get_customer_id(),
                    'order_url' => $order->get_edit_order_url(), // URL para editar el pedido
                )
            );
        }
    }

    return new WP_REST_Response( $events, 200 );
}

// =========================================================================
// 3. FUNCIONES AUXILIARES Y DE PERMISOS
// =========================================================================

/**
 * **NUEVO:** Prepara los datos del cliente para la respuesta de la API.
 * Incluye ID, nombre, apellido, email, teléfono y URL del avatar.
 *
 * @param int $customer_id ID del usuario/cliente.
 * @return array Datos formateados del cliente.
 */
function pos_streaming_prepare_customer_data_for_response( $customer_id ) {
    $user_info = get_userdata( $customer_id );
    if ( ! $user_info ) {
        return array(); // Devuelve vacío si el usuario no existe
    }

    $avatar_id = get_user_meta( $customer_id, 'pos_customer_avatar_id', true );
    $avatar_url = '';

    if ( $avatar_id && $url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) ) {
        $avatar_url = $url;
    } else {
        // Fallback al avatar de Gravatar/WordPress
        $avatar_url = get_avatar_url( $customer_id, array( 'size' => 96, 'default' => 'mystery' ) );
        $avatar_id = ''; // Asegurarse de que no se envíe un ID inválido
    }

    return array(
        'id'         => $user_info->ID,
        'first_name' => $user_info->first_name,
        'last_name'  => $user_info->last_name,
        'email'      => $user_info->user_email,
        'phone'      => get_user_meta( $customer_id, 'billing_phone', true ), // Obtener de billing_phone
        'avatar_id'  => $avatar_id, // ID del adjunto si existe
        'avatar_url' => $avatar_url, // URL del avatar (personalizado o por defecto)
    );
}

/**
 * **MODIFICADO:** Comprueba los permisos para acceder a los endpoints de la API.
 * Verifica capacidad 'manage_woocommerce' Y el nonce 'wp_rest'.
 *
 * @param WP_REST_Request $request Objeto de la solicitud.
 * @return bool|WP_Error True si tiene permiso, WP_Error si no.
 */
function pos_streaming_api_permissions_check( WP_REST_Request $request ) {
    // 1. Verificar capacidad del usuario
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        error_log('POS API Permission Check FAILED: User cannot manage_woocommerce.'); // Log
        return new WP_Error(
            'rest_forbidden_capability',
            __( 'No tienes permiso para realizar esta acción.', 'pos-streaming' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    // 2. Verificar Nonce (buscar en cabecera o parámetro _wpnonce)
    $nonce = $request->get_header('X-WP-Nonce');
    if ( empty($nonce) ) {
        $nonce = $request->get_param('_wpnonce'); // Obtener de parámetro URL
    }

    if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
         error_log('POS API Permission Check FAILED: Invalid or missing nonce.'); // Log
         return new WP_Error(
            'rest_forbidden_nonce',
            __( 'Verificación de seguridad (nonce) fallida.', 'pos-streaming' ),
            array( 'status' => 403 ) // 403 Forbidden es más apropiado que 401 aquí
        );
    }

    // Si ambas verificaciones pasan
    error_log('POS API Permission Check PASSED.'); // Log
    return true;
}
