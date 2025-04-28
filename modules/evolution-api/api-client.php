<?php
/**
 * api-client.php
 *
 * Contiene la clase Evolution_API_Client para interactuar con la Evolution API.
 * Proporciona métodos para realizar peticiones HTTP a los diferentes endpoints de la API.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Clase Evolution_API_Client
 *
 * Encapsula la lógica para comunicarse con la Evolution API.
 */
class Evolution_API_Client {

    /**
     * URL base de la API de Evolution.
     * @var string
     */
    private $api_url;

    /**
     * Token (API Key) para autenticación.
     * @var string
     */
    private $api_key;

    /**
     * Constructor. Obtiene la configuración desde los ajustes de WordPress.
     */
    public function __construct() {
        // Obtener los ajustes guardados usando la función de settings.php
        $settings = pos_evolution_api_get_settings();
        // Asegurarse de que la URL termine con una barra
        $this->api_url = trailingslashit( $settings['api_url'] ?? '' );
        $this->api_key = $settings['token'] ?? ''; // La clave en settings es 'token'
    }

    /**
     * Verifica si la URL y el Token están configurados.
     *
     * @return bool True si está configurado, False en caso contrario.
     */
    public function is_configured() {
        return ! empty( $this->api_url ) && ! empty( $this->api_key );
    }

    /**
     * Realiza una petición HTTP a un endpoint de la API.
     * Maneja la construcción de la URL, cabeceras, cuerpo y errores.
     *
     * @param string $endpoint El endpoint específico de la API (ej: 'instance/create').
     * @param string $method   El método HTTP ('GET', 'POST', 'DELETE', etc.). Por defecto 'GET'.
     * @param array|null $body El cuerpo de la petición (para POST/PUT). Se codificará como JSON.
     * @param bool $decode_json Si se debe decodificar la respuesta JSON a un array PHP. Por defecto true.
     * @return array|string|WP_Error El cuerpo de la respuesta decodificado, el cuerpo crudo o un WP_Error.
     */
    private function make_request( $endpoint, $method = 'GET', $body = null, $decode_json = true ) {
        // Verificar configuración antes de proceder
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'api_not_configured', __( 'La URL de la API o el Token no están configurados.', 'pos-base' ) );
        }

        // Construir URL completa
        $url = $this->api_url . $endpoint;

        // Cabeceras estándar
        $headers = [
            'apikey' => $this->api_key,
            'Content-Type' => 'application/json', // Asumimos JSON para el cuerpo
        ];

        // Argumentos para wp_remote_request
        $args = [
            'method'  => strtoupper( $method ), // Asegurar método en mayúsculas
            'headers' => $headers,
            'timeout' => 30, // Timeout razonable (en segundos)
            // 'sslverify' => false, // Descomentar SOLO si tienes problemas de SSL y sabes lo que haces
        ];

        // Añadir cuerpo si es necesario (para POST, PUT, etc.)
        if ( $body !== null && ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') ) {
            $args['body'] = wp_json_encode( $body );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                 error_log( "Evolution API Client Error: Fallo al codificar JSON para el cuerpo. Error: " . json_last_error_msg() );
                 return new WP_Error('json_encode_error', __('Error interno al preparar la petición.', 'pos-base'));
            }
        }

        // Log de la petición saliente
        error_log( "Evolution API Request: {$args['method']} {$url} | Body: " . ($args['body'] ?? 'None') );

        // Realizar la petición usando la API HTTP de WordPress
        $response = wp_remote_request( $url, $args );

        // Manejar errores de conexión de WordPress (ej: cURL error)
        if ( is_wp_error( $response ) ) {
            error_log( "Evolution API WP_Error during request: " . $response->get_error_message() );
            return $response; // Devolver el WP_Error directamente
        }

        // Obtener código de estado y cuerpo de la respuesta
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // Log de la respuesta recibida
        error_log( "Evolution API Response: Code={$response_code} | Body: {$response_body}" );

        // Manejar códigos de error HTTP (4xx, 5xx)
        if ( $response_code >= 300 ) {
            // Intentar decodificar el cuerpo por si contiene un mensaje de error JSON
            $error_data = json_decode( $response_body, true );
            $error_message = $response_body; // Mensaje por defecto es el cuerpo crudo
            if ( $error_data !== null ) {
                // Intentar extraer mensaje de error de estructuras comunes
                $error_message = $error_data['response']['message'] ?? $error_data['error'] ?? $error_data['message'] ?? $response_body;
            }
            return new WP_Error( 'api_error_' . $response_code, esc_html( $error_message ), ['status' => $response_code] );
        }

        // Si se espera una respuesta JSON, decodificarla
        if ( $decode_json ) {
            $decoded_body = json_decode( $response_body, true ); // true para obtener array asociativo

            // Verificar si hubo error al decodificar JSON
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                 error_log( "Evolution API JSON Decode Error: " . json_last_error_msg() . " | Body received: " . $response_body );
                 return new WP_Error('json_decode_error', __('Error al decodificar la respuesta JSON de la API.', 'pos-base'), ['body' => $response_body]);
            }
            return $decoded_body; // Devolver el array PHP
        }

        // Si no se esperaba JSON, devolver el cuerpo crudo
        return $response_body;
    }

    // --- Métodos Específicos para Endpoints de Instancia ---

    /**
     * Crea una nueva instancia en el servidor Evolution API.
     *
     * @param string $instance_name Nombre deseado para la nueva instancia.
     * @param array $options Opciones adicionales (ej: ['qrcode' => true]).
     * @return array|WP_Error Respuesta de la API o WP_Error.
     */
    public function create_instance( $instance_name, $options = [] ) {
        $body = array_merge(['instanceName' => $instance_name], $options);
        return $this->make_request( 'instance/create', 'POST', $body );
    }

    /**
     * Solicita la conexión o el QR para una instancia existente.
     * Llama a GET /instance/connect/{instanceName}.
     *
     * @param string $instance_name Nombre de la instancia.
     * @return array|WP_Error Respuesta de la API (puede contener QR) o WP_Error.
     */
    public function connect_instance( $instance_name ) {
        return $this->make_request( 'instance/connect/' . rawurlencode($instance_name), 'GET' );
    }

    /**
     * Obtiene los detalles completos de una instancia específica.
     * Llama a GET /instance/fetchInstances y filtra por nombre.
     *
     * @param string $instance_name Nombre de la instancia a buscar.
     * @return array|WP_Error Objeto 'instance' con los detalles o WP_Error.
     */
    public function get_instance_details( $instance_name ) {
        // Llama al endpoint que devuelve la lista de todas las instancias
        $all_instances_data = $this->make_request( 'instance/fetchInstances', 'GET' );

        if ( is_wp_error( $all_instances_data ) ) {
            return $all_instances_data; // Propagar el error
        }

        // Verificar si la respuesta es un array
        if ( ! is_array( $all_instances_data ) ) {
             error_log("[EVO_API_CLIENT] Unexpected response format from fetchInstances: " . print_r($all_instances_data, true));
             return new WP_Error('api_format_error', __('Formato inesperado en la respuesta de fetchInstances.', 'pos-base'));
        }

        // Buscar la instancia específica por nombre en el array devuelto
        foreach ( $all_instances_data as $instance_data ) {
            // La estructura parece ser un array de objetos, cada uno con una clave 'instance'
            if ( isset( $instance_data['instance']['instanceName'] ) && $instance_data['instance']['instanceName'] === $instance_name ) {
                // Encontrada, devolver el objeto 'instance' completo
                error_log("[EVO_API_CLIENT] Instance '{$instance_name}' found in fetchInstances response.");
                // Devolver solo el contenido del objeto 'instance' para mantener consistencia
                return $instance_data['instance'];
            }
        }

        // Si no se encontró la instancia en la lista
        error_log("[EVO_API_CLIENT] Instance '{$instance_name}' not found in fetchInstances response.");
        return new WP_Error( 'instance_not_found', __( 'Instancia no encontrada en la lista del servidor.', 'pos-base' ), ['status' => 404] );
    }

    /**
     * Desconecta (logout) una instancia del servidor Evolution API.
     * Llama a DELETE /instance/logout/{instanceName}.
     *
     * @param string $instance_name Nombre de la instancia.
     * @return string|WP_Error Cuerpo de la respuesta (generalmente vacío o simple) o WP_Error.
     */
    public function disconnect_instance( $instance_name ) {
        // El logout a menudo no devuelve un JSON complejo, así que $decode_json = false
        return $this->make_request( 'instance/logout/' . rawurlencode($instance_name), 'DELETE', null, false );
    }

    /**
     * Elimina permanentemente una instancia del servidor Evolution API.
     * Llama a DELETE /instance/delete/{instanceName}.
     *
     * @param string $instance_name Nombre de la instancia.
     * @return array|WP_Error Respuesta de la API o WP_Error.
     */
    public function delete_instance( $instance_name ) {
        return $this->make_request( 'instance/delete/' . rawurlencode($instance_name), 'DELETE' );
    }

    // --- Métodos de Mensajería (Ejemplo Básico) ---

    /**
     * Envía un mensaje de texto simple.
     *
     * @param string $number Número de teléfono del destinatario (con código de país, ej: 519xxxxxxxx).
     * @param string $message El mensaje de texto a enviar.
     * @param string $instance_name La instancia desde la cual enviar el mensaje.
     * @return array|WP_Error Respuesta de la API o WP_Error.
     */
    public function send_text_message( $number, $message, $instance_name ) {
        $body = [
            'number' => $number,
            'options' => [
                'delay' => 1200,        // Pequeño retraso simulado
                'presence' => 'composing', // Simular "escribiendo"
            ],
            'textMessage' => [
                'text' => $message,
            ],
        ];
        return $this->make_request( 'message/sendText/' . rawurlencode($instance_name), 'POST', $body );
    }

    // --- Puedes añadir más métodos aquí para otros endpoints ---
    // Ej: get_profile_picture, send_image, etc.

} // Fin de la clase Evolution_API_Client
?>
