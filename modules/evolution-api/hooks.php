<?php
/**
 * hooks.php
 *
 * Contiene los manejadores AJAX para la gestión de la instancia
 * del módulo Evolution API.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

// --- Manejadores AJAX para la Gestión de Instancia ---

/**
 * Función auxiliar interna para verificar nonce, permisos y configuración de API
 * en los manejadores AJAX de gestión de instancia.
 * Devuelve el nombre de la instancia gestionada actual o un WP_Error.
 *
 * @return string|WP_Error Nombre de la instancia gestionada o WP_Error si falla la verificación.
 */
function _pos_evolution_api_ajax_verify_request() {
    // 1. Verificar Nonce
    if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ), 'evolution_api_ajax_nonce' ) ) {
        $error_msg = __( 'Error de seguridad (Nonce inválido). Por favor, recarga la página e inténtalo de nuevo.', 'pos-base' );
        error_log( "[EVO_API_AJAX_VERIFY] Nonce verification failed. Received: " . print_r($_POST['_ajax_nonce'] ?? 'N/A', true) );
        return new WP_Error( 'security_fail', $error_msg, ['status' => 403] );
    }

    // 2. Verificar Permisos
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        $error_msg = __( 'No tienes permisos suficientes para realizar esta acción.', 'pos-base' );
        error_log( "[EVO_API_AJAX_VERIFY] Permission check failed for user ID: " . get_current_user_id() );
        return new WP_Error( 'permission_fail', $error_msg, ['status' => 403] );
    }

    // 3. Verificar configuración de API (URL/Token)
    if ( ! class_exists( 'Evolution_API_Client' ) ) {
         $error_msg = __( 'Error interno: No se pudo cargar el cliente API.', 'pos-base' );
         error_log( "[EVO_API_AJAX_VERIFY] Evolution_API_Client class not found." );
         return new WP_Error( 'internal_error', $error_msg, ['status' => 500] );
    }
    $api_client = new Evolution_API_Client();
    if ( ! $api_client->is_configured() ) {
         $error_msg = __( 'La configuración de la API (URL/Token) está incompleta. Ve a los ajustes generales de POS Base.', 'pos-base' );
         error_log( "[EVO_API_AJAX_VERIFY] API client is not configured (URL/Token missing)." );
         return new WP_Error( 'config_fail', $error_msg, ['status' => 503] );
    }

    // 4. Obtener nombre de instancia gestionada
    $settings = pos_evolution_api_get_settings(); // Función de settings.php
    $instance_name = $settings['managed_instance_name'] ?? '';
    error_log( "[EVO_API_AJAX_VERIFY] Verification successful. Managed instance name: '{$instance_name}'" );

    // Devolver el nombre (puede ser vacío) si todo está bien
    return $instance_name;
}


/**
 * Manejador AJAX: Crear Instancia.
 * Espera 'instance_name_to_create' en $_POST.
 */
function pos_evolution_ajax_create_instance() {
    error_log( "[EVO_API_AJAX_CREATE] Received request. POST data: " . print_r($_POST, true) );
    $verification_result = _pos_evolution_api_ajax_verify_request();
    if ( is_wp_error( $verification_result ) ) {
        error_log( "[EVO_API_AJAX_CREATE] Verification failed: " . $verification_result->get_error_message() );
        wp_send_json_error( $verification_result->get_error_message(), $verification_result->get_error_data()['status'] ?? 400 );
    }
    $current_instance_name = $verification_result;

    // Verificar que no haya ya una instancia gestionada
    if ( ! empty( $current_instance_name ) ) {
        $error_msg = __( 'Ya existe una instancia gestionada por este plugin. Elimínala primero si deseas crear una nueva a través de esta interfaz.', 'pos-base' );
        error_log( "[EVO_API_AJAX_CREATE] Conflict: Instance '{$current_instance_name}' already managed." );
        wp_send_json_error( $error_msg, 409 ); // 409 Conflict
    }

    // Obtener y sanitizar el nombre de la nueva instancia desde POST
    if ( ! isset( $_POST['instance_name_to_create'] ) || empty( $_POST['instance_name_to_create'] ) ) {
        $error_msg = __( 'Se requiere un nombre para la nueva instancia.', 'pos-base' );
        error_log( "[EVO_API_AJAX_CREATE] Bad Request: Missing 'instance_name_to_create'." );
        wp_send_json_error( $error_msg, 400 );
    }
    $new_instance_name = sanitize_key( $_POST['instance_name_to_create'] );
    if ( empty( $new_instance_name ) ) {
         $error_msg = __( 'El nombre proporcionado para la instancia no es válido.', 'pos-base' );
         error_log( "[EVO_API_AJAX_CREATE] Bad Request: Invalid instance name after sanitization: '{$new_instance_name}'." );
         wp_send_json_error( $error_msg, 400 );
    }

    // Llamar a la API para crear
    error_log( "[EVO_API_AJAX_CREATE] Attempting to create instance '{$new_instance_name}' via API." );
    $api_client = new Evolution_API_Client();
    $options = ['qrcode' => true]; // Pedir QR al crear
    $result = $api_client->create_instance( $new_instance_name, $options );

    // Procesar resultado de la API
    if ( is_wp_error( $result ) ) {
        $error_code = $result->get_error_code();
        $status_code = $result->get_error_data()['status'] ?? 500;
        $error_message = $result->get_error_message();
        error_log( "[EVO_API_AJAX_CREATE] API Error creating instance '{$new_instance_name}': [{$status_code}] {$error_message}" );

        // Mensaje específico si la instancia ya existe en la API
        if ( $status_code === 409 || strpos( strtolower($error_message), 'instance already exists' ) !== false ) {
             $error_message = sprintf(__('La instancia "%s" ya existe en el servidor Evolution API. Elige otro nombre o gestiona la existente manualmente.', 'pos-base'), esc_html($new_instance_name));
             $status_code = 409;
        }
        wp_send_json_error( $error_message, $status_code );
    } else {
        // Éxito de la API
        error_log( "[EVO_API_AJAX_CREATE] API Success creating instance '{$new_instance_name}'. API Response: " . print_r($result, true) );
        // Guardar el nuevo nombre en las opciones de WP
        $settings = pos_evolution_api_get_settings();
        $settings['managed_instance_name'] = $new_instance_name;
        $update_result = update_option( 'pos_evolution_api_settings', $settings );
        error_log( "[EVO_API_AJAX_CREATE] Updated WP option 'pos_evolution_api_settings'. Update result: " . ($update_result ? 'Success' : 'Failed/No Change') );

        // Preparar datos para el *interior* de 'data' en la respuesta JSON
        $response_inner_data = [
            'message' => __( 'Instancia creada exitosamente.', 'pos-base' ),
            'instance_name' => $new_instance_name, // Devolver el nombre creado
            'qr_base64' => null, // Iniciar como null
        ];

        // Extraer QR si la API lo devolvió (puede estar en 'qrcode.base64' o solo 'base64')
        $qr_key_path = null;
        if (isset($result['qrcode']['base64'])) {
            $qr_key_path = $result['qrcode']['base64'];
            error_log( "[EVO_API_AJAX_CREATE] QR code found in API response at ['qrcode']['base64']." );
        } elseif (isset($result['base64'])) {
            $qr_key_path = $result['base64'];
            error_log( "[EVO_API_AJAX_CREATE] QR code found in API response at ['base64']." );
        }

        if ($qr_key_path && !empty($qr_key_path)) {
            $response_inner_data['qr_base64'] = $qr_key_path;
            $response_inner_data['message'] = __( 'Instancia creada. Escanea el código QR para conectar.', 'pos-base' );
        } elseif (isset($result['instance']['state']) && $result['instance']['state'] === 'open') {
             $response_inner_data['message'] = __( 'Instancia creada y parece estar ya conectada (no se generó QR).', 'pos-base' );
             error_log( "[EVO_API_AJAX_CREATE] Instance created, state is 'open', no QR code in response." );
        } else {
            error_log( "[EVO_API_AJAX_CREATE] Instance created, but no QR code and state not 'open'. Response: " . print_r($result, true) );
        }

        // Enviar siempre la estructura {success: true, data: {...}}
        error_log( "[EVO_API_AJAX_CREATE] Sending success JSON response with data wrapper: " . print_r($response_inner_data, true) );
        wp_send_json_success( $response_inner_data, 201 ); // 201 Created
    }
}
add_action( 'wp_ajax_pos_evolution_create_instance', 'pos_evolution_ajax_create_instance' );


/**
 * Manejador AJAX: Obtener QR.
 * Usa la instancia definida en 'managed_instance_name'.
 */
function pos_evolution_ajax_get_qr() {
    // Log: Inicio de la función y datos recibidos
    error_log( "[EVO_API_AJAX_GET_QR] Received request. POST data: " . print_r($_POST, true) );

    // Verificar seguridad, permisos y configuración
    $verification_result = _pos_evolution_api_ajax_verify_request();
    if ( is_wp_error( $verification_result ) ) {
         error_log( "[EVO_API_AJAX_GET_QR] Verification failed: " . $verification_result->get_error_message() );
        wp_send_json_error( $verification_result->get_error_message(), $verification_result->get_error_data()['status'] ?? 400 );
    }
    $instance_name = $verification_result; // Nombre de la instancia gestionada

    if ( empty( $instance_name ) ) {
        $error_msg = __( 'No hay ninguna instancia gestionada configurada para obtener el QR.', 'pos-base' );
        error_log( "[EVO_API_AJAX_GET_QR] Not Found: No managed instance name set." );
        wp_send_json_error( $error_msg, 404 );
    }

    error_log( "[EVO_API_AJAX_GET_QR] Attempting to get QR for instance '{$instance_name}' via API." );
    $api_client = new Evolution_API_Client();
    $result = $api_client->connect_instance( $instance_name ); // Llama a GET /instance/connect/{instanceName}

    if ( is_wp_error( $result ) ) {
        $error_message = $result->get_error_message();
        $status_code = $result->get_error_data()['status'] ?? 500;
        error_log( "[EVO_API_AJAX_GET_QR] API Error getting QR for '{$instance_name}': [{$status_code}] {$error_message}" );
        wp_send_json_error( $error_message, $status_code );
    } else {
         error_log( "[EVO_API_AJAX_GET_QR] API Success getting QR for '{$instance_name}'. API Response: " . print_r($result, true) );

        // Preparar datos iniciales para el *interior* de 'data' en la respuesta JSON
        $response_inner_data = [
            'message' => __( 'Solicitud de QR procesada.', 'pos-base' ),
            'qr_base64' => null, // Iniciar como null
        ];

        // Verificar si la respuesta de la API contiene la clave 'base64' (directamente)
        if ( isset( $result['base64'] ) && !empty($result['base64']) ) {
            // Si existe y no está vacía, actualizar los datos
            $response_inner_data['qr_base64'] = $result['base64'];
            $response_inner_data['message'] = __( 'Código QR obtenido. Escanéalo con WhatsApp para (re)conectar.', 'pos-base' );
            error_log( "[EVO_API_AJAX_GET_QR] QR code found in API response (using result['base64'])." );
        } else {
             // Si no se encontró el QR en la respuesta esperada
             $response_inner_data['message'] = __( 'No se pudo obtener el código QR. La instancia podría estar ya conectada, apagada o en un estado inválido.', 'pos-base' );
             error_log( "[EVO_API_AJAX_GET_QR] No QR code found in API response (checked result['base64']). Attempting to get state." );

             // Intentar obtener el estado actual para dar más contexto al usuario
             $state_result = $api_client->get_instance_state($instance_name);
             if (!is_wp_error($state_result) && isset($state_result['instance']['state'])) {
                 $current_state = strtoupper($state_result['instance']['state']);
                 $response_inner_data['message'] .= ' ' . sprintf(__('Estado actual reportado: %s', 'pos-base'), '<strong>' . esc_html($current_state) . '</strong>');
                 error_log( "[EVO_API_AJAX_GET_QR] Current state reported: {$current_state}" );
             } else {
                 error_log( "[EVO_API_AJAX_GET_QR] Failed to get current state after no QR was found." );
             }
        }

        // Enviar siempre la estructura {success: true, data: {...}}
        error_log( "[EVO_API_AJAX_GET_QR] Sending success JSON response with data wrapper: " . print_r($response_inner_data, true) );
        wp_send_json_success( $response_inner_data );
    }
}
add_action( 'wp_ajax_pos_evolution_get_qr', 'pos_evolution_ajax_get_qr' );


/**
 * Manejador AJAX: Obtener Estado y Detalles.
 * Usa la instancia definida en 'managed_instance_name'.
 * Llama al método get_instance_details que usa /fetchInstances.
 * **CORREGIDO para determinar el estado basado en la presencia de detalles.**
 */
function pos_evolution_ajax_get_status() {
    // Log: Inicio de la función y datos recibidos
    error_log( "[EVO_API_AJAX_GET_STATUS] Received request. POST data: " . print_r($_POST, true) );

    // Verificar seguridad, permisos y configuración
    $verification_result = _pos_evolution_api_ajax_verify_request();
    if ( is_wp_error( $verification_result ) ) {
        error_log( "[EVO_API_AJAX_GET_STATUS] Verification failed: " . $verification_result->get_error_message() );
        wp_send_json_error( $verification_result->get_error_message(), $verification_result->get_error_data()['status'] ?? 400 );
    }
    $instance_name = $verification_result; // Nombre de la instancia gestionada

    if ( empty( $instance_name ) ) {
        $error_msg = __( 'No hay ninguna instancia gestionada configurada para consultar el estado.', 'pos-base' );
        error_log( "[EVO_API_AJAX_GET_STATUS] Not Found: No managed instance name set." );
        wp_send_json_error( $error_msg, 404 );
    }

    // Log: Intentando llamar a la API usando el nuevo método
    error_log( "[EVO_API_AJAX_GET_STATUS] Attempting to get DETAILS for instance '{$instance_name}' via API (using get_instance_details -> fetchInstances)." );
    $api_client = new Evolution_API_Client();
    // *** LLAMAR AL NUEVO MÉTODO get_instance_details ***
    $instance_details = $api_client->get_instance_details( $instance_name );

    // Procesar resultado
    if ( is_wp_error( $instance_details ) ) {
        // Si get_instance_details devuelve un error (ej: no encontrada en fetchInstances)
        $error_message = $instance_details->get_error_message();
        $status_code = $instance_details->get_error_data()['status'] ?? 500;
        error_log( "[EVO_API_AJAX_GET_STATUS] API Error getting details for '{$instance_name}': [{$status_code}] {$error_message}" );
        wp_send_json_error( $error_message, $status_code );
    } else {
        // Éxito, $instance_details contiene el objeto 'instance' encontrado por get_instance_details
        error_log( "[EVO_API_AJAX_GET_STATUS] API Success getting details for '{$instance_name}'. Details from fetchInstances: " . print_r($instance_details, true) );

        // *** LÓGICA DE ESTADO CORREGIDA ***
        $state = 'unknown'; // Estado por defecto

        // Si tenemos 'owner' o 'wid' con valor, consideramos que está CONECTADO,
        // independientemente de lo que diga la clave 'status' o 'state' de este endpoint.
        if ( ! empty( $instance_details['owner'] ) ) {
            $state = 'CONNECTED';
            error_log("[EVO_API_AJAX_GET_STATUS] Determined state as CONNECTED based on non-empty 'owner'.");
        } elseif ( ! empty( $instance_details['wid'] ) ) { // Fallback por si 'owner' no siempre está
             $state = 'CONNECTED';
             error_log("[EVO_API_AJAX_GET_STATUS] Determined state as CONNECTED based on non-empty 'wid'.");
        } else {
            // Si no hay owner ni wid, entonces sí usamos 'state' o 'status' como fallback.
            $state = $instance_details['state'] ?? $instance_details['status'] ?? 'unknown';
            error_log("[EVO_API_AJAX_GET_STATUS] Determined state as '{$state}' from 'state'/'status' key (owner/wid missing or empty).");
        }
        // *** FIN LÓGICA DE ESTADO CORREGIDA ***

        // Mensaje simple para log
        $message_for_log = sprintf( 'Detalles obtenidos para instancia "%s": %s', esc_html($instance_name), strtoupper($state) );

        // Preparar datos para el *interior* de 'data' en la respuesta JSON
        // El JS espera los detalles completos dentro de data.details.instance
        $response_inner_data = [
            'message' => $message_for_log,
            'state'   => $state, // <-- Usar el estado corregido
            'details' => [
                'instance' => $instance_details // Pasar detalles completos
            ]
        ];

        // Log: Enviando respuesta JSON exitosa
        error_log( "[EVO_API_AJAX_GET_STATUS] Sending success JSON response with data wrapper: " . print_r($response_inner_data, true) );
        wp_send_json_success( $response_inner_data );
    }
}
// Asegúrate de que esta línea esté presente y descomentada en tu archivo hooks.php
add_action( 'wp_ajax_pos_evolution_get_status', 'pos_evolution_ajax_get_status' );


/**
 * Manejador AJAX: Desconectar Instancia (Logout).
 */
function pos_evolution_ajax_disconnect() {
    error_log( "[EVO_API_AJAX_DISCONNECT] Received request. POST data: " . print_r($_POST, true) );
    $verification_result = _pos_evolution_api_ajax_verify_request();
    if ( is_wp_error( $verification_result ) ) {
        error_log( "[EVO_API_AJAX_DISCONNECT] Verification failed: " . $verification_result->get_error_message() );
        wp_send_json_error( $verification_result->get_error_message(), $verification_result->get_error_data()['status'] ?? 400 );
    }
    $instance_name = $verification_result;

    if ( empty( $instance_name ) ) {
        $error_msg = __( 'No hay ninguna instancia gestionada configurada para desconectar.', 'pos-base' );
        error_log( "[EVO_API_AJAX_DISCONNECT] Not Found: No managed instance name set." );
        wp_send_json_error( $error_msg, 404 );
    }

    error_log( "[EVO_API_AJAX_DISCONNECT] Attempting to disconnect instance '{$instance_name}' via API." );
    $api_client = new Evolution_API_Client();
    $result = $api_client->disconnect_instance( $instance_name ); // Llama a DELETE /instance/logout/{instanceName}

    if ( is_wp_error( $result ) ) {
        $error_message = $result->get_error_message();
        $status_code = $result->get_error_data()['status'] ?? 500;
        error_log( "[EVO_API_AJAX_DISCONNECT] API Error disconnecting '{$instance_name}': [{$status_code}] {$error_message}" );
        wp_send_json_error( $error_message, $status_code );
    } else {
        error_log( "[EVO_API_AJAX_DISCONNECT] API Success disconnecting '{$instance_name}'. API Response: " . print_r($result, true) );
        // La API puede devolver un mensaje o simplemente un 200 OK
        $message = isset($result['message']) && is_string($result['message'])
                   ? $result['message']
                   : __( 'Instancia desconectada exitosamente. Puede tardar unos momentos en reflejarse.', 'pos-base' );
        $response_inner_data = ['message' => $message];
        error_log( "[EVO_API_AJAX_DISCONNECT] Sending success JSON response with data wrapper: " . print_r($response_inner_data, true) );
        wp_send_json_success( $response_inner_data );
    }
}
add_action( 'wp_ajax_pos_evolution_disconnect', 'pos_evolution_ajax_disconnect' );


/**
 * Manejador AJAX: Eliminar Instancia.
 */
function pos_evolution_ajax_delete_instance() {
    error_log( "[EVO_API_AJAX_DELETE] Received request. POST data: " . print_r($_POST, true) );
    $verification_result = _pos_evolution_api_ajax_verify_request();
    if ( is_wp_error( $verification_result ) ) {
        error_log( "[EVO_API_AJAX_DELETE] Verification failed: " . $verification_result->get_error_message() );
        wp_send_json_error( $verification_result->get_error_message(), $verification_result->get_error_data()['status'] ?? 400 );
    }
    $instance_name = $verification_result;

    if ( empty( $instance_name ) ) {
        $error_msg = __( 'No hay ninguna instancia gestionada configurada para eliminar.', 'pos-base' );
        error_log( "[EVO_API_AJAX_DELETE] Not Found: No managed instance name set." );
        wp_send_json_error( $error_msg, 404 );
    }

    error_log( "[EVO_API_AJAX_DELETE] Attempting to delete instance '{$instance_name}' via API." );
    $api_client = new Evolution_API_Client();
    $result = $api_client->delete_instance( $instance_name ); // Llama a DELETE /instance/delete/{instanceName}

    if ( is_wp_error( $result ) ) {
        $status_code = $result->get_error_data()['status'] ?? 500;
        $error_message = $result->get_error_message();
        error_log( "[EVO_API_AJAX_DELETE] API Error deleting '{$instance_name}': [{$status_code}] {$error_message}" );

        // Si la API dice que no existe (404), la eliminamos de WP igualmente
        if ($status_code === 404) {
             error_log( "[EVO_API_AJAX_DELETE] Instance '{$instance_name}' not found on API server (404). Clearing from WP options anyway." );
             $settings = pos_evolution_api_get_settings();
             $settings['managed_instance_name'] = '';
             $update_result = update_option( 'pos_evolution_api_settings', $settings );
             error_log( "[EVO_API_AJAX_DELETE] Cleared WP option 'pos_evolution_api_settings'. Update result: " . ($update_result ? 'Success' : 'Failed/No Change') );
             // Enviar éxito porque el resultado final es el deseado (instancia no existe)
             wp_send_json_success( ['message' => __( 'La instancia ya no existía en el servidor API. Se ha eliminado de la configuración del plugin.', 'pos-base' )] );
        } else {
            // Otro tipo de error
            wp_send_json_error( $error_message, $status_code );
        }
    } else {
        // Éxito de la API al eliminar
        error_log( "[EVO_API_AJAX_DELETE] API Success deleting '{$instance_name}'. API Response: " . print_r($result, true) );
        // Limpiar el nombre de la instancia en las opciones de WP
        $settings = pos_evolution_api_get_settings();
        $settings['managed_instance_name'] = '';
        $update_result = update_option( 'pos_evolution_api_settings', $settings );
        error_log( "[EVO_API_AJAX_DELETE] Cleared WP option 'pos_evolution_api_settings'. Update result: " . ($update_result ? 'Success' : 'Failed/No Change') );

        $message = isset($result['message']) && is_string($result['message'])
                   ? $result['message']
                   : __( 'Instancia eliminada exitosamente del servidor API y de la configuración del plugin.', 'pos-base' );
        $response_inner_data = ['message' => $message];
        error_log( "[EVO_API_AJAX_DELETE] Sending success JSON response with data wrapper: " . print_r($response_inner_data, true) );
        wp_send_json_success( $response_inner_data );
    }
}
add_action( 'wp_ajax_pos_evolution_delete_instance', 'pos_evolution_ajax_delete_instance' );

?>
