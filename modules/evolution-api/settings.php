<?php
/**
 * settings.php
 *
 * Maneja la integración de los ajustes del módulo Evolution API
 * en la página de configuración general de POS Base.
 * Incluye campos para URL, Token (con toggle) y gestiona internamente
 * el nombre de la instancia creada.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Registra los ajustes, sección y campos para Evolution API.
 * Se engancha a 'admin_init'.
 */
function pos_evolution_api_register_settings_fields() {

    // 1. Registrar el ajuste principal para este módulo
    //    Guardará un array con 'api_url', 'token', y 'managed_instance_name'.
    register_setting(
        'pos_base_options_group',           // Grupo de opciones de POS Base
        'pos_evolution_api_settings',       // Nombre de la opción en wp_options
        [
            'type'              => 'array',
            'description'       => __( 'Configuración para la conexión con Evolution API.', 'pos-base' ),
            'sanitize_callback' => 'pos_evolution_api_sanitize_settings', // Función para limpiar los datos
            'default'           => pos_evolution_api_get_defaults(),      // Valores por defecto
        ]
    );

    // 2. Añadir la sección visual en la página de ajustes de POS Base
    add_settings_section(
        'pos_evolution_api_section',        // ID único para la sección
        __( 'Configuración Evolution API', 'pos-base' ), // Título visible de la sección
        'pos_evolution_api_section_callback', // Función que muestra el <hr> y texto introductorio
        'pos-base-settings'                 // Slug de la página donde se mostrará ('admin.php?page=pos-base-settings')
    );

    // 3. Añadir el campo para la URL de la API
    add_settings_field(
        'evolution_api_url_field',          // ID único del campo
        __( 'URL de la API', 'pos-base' ),  // Etiqueta visible
        'pos_evolution_api_render_field',   // Función que renderiza el input genérico
        'pos-base-settings',                // Slug de la página
        'pos_evolution_api_section',        // Sección a la que pertenece
        [                                   // Argumentos para la función de renderizado
            'label_for'   => 'evolution_api_url', // ID del input para la etiqueta <label>
            'option_name' => 'pos_evolution_api_settings', // Nombre de la opción general
            'key'         => 'api_url',             // Clave dentro del array de la opción
            'type'        => 'url',                 // Tipo de input HTML
            'description' => __('Ej: http://localhost:8080', 'pos-base') // Texto de ayuda
        ]
    );

    // 4. Añadir el campo para el Token con botón de mostrar/ocultar
    add_settings_field(
        'evolution_api_token_field',        // ID único del campo
        __( 'Token', 'pos-base' ),          // Etiqueta visible
        'pos_evolution_api_render_token_field', // Función específica para renderizar este campo
        'pos-base-settings',                // Slug de la página
        'pos_evolution_api_section',        // Sección a la que pertenece
        [                                   // Argumentos para la función de renderizado
            'label_for'   => 'evolution_api_token', // ID del input para la etiqueta <label>
            'option_name' => 'pos_evolution_api_settings', // Nombre de la opción general
            'key'         => 'token',               // Clave dentro del array de la opción
            'description' => __('El Token de autenticación para tu instancia de Evolution API.', 'pos-base') // Texto de ayuda
        ]
    );

    // NOTA: No añadimos un campo visible para 'managed_instance_name' aquí.
    // Se gestionará desde la página de administración de la instancia ('admin-page.php')
    // y se guardará/actualizará programáticamente en la opción 'pos_evolution_api_settings'.

}
// Enganchar la función de registro al hook 'admin_init'.
add_action( 'admin_init', 'pos_evolution_api_register_settings_fields' );

/**
 * Muestra un separador <hr> y texto introductorio para la sección de Evolution API.
 * Es la función callback definida en add_settings_section().
 */
function pos_evolution_api_section_callback() {
    // Añadir separador visual antes de la sección
    echo '<hr style="margin: 25px 0 15px 0; border: 0; border-top: 1px solid #ccd0d4;">';
    // Añadir texto descriptivo
    echo '<p>' . esc_html__( 'Introduce los detalles para conectar con tu instancia de Evolution API.', 'pos-base' ) . '</p>';
}

/**
 * Renderiza un campo de input genérico (usado para la URL).
 * Es una de las funciones callback definidas en add_settings_field().
 *
 * @param array $args Argumentos pasados desde add_settings_field.
 */
function pos_evolution_api_render_field( $args ) {
    $option_name = $args['option_name'];
    $key = $args['key'];
    $type = $args['type'] ?? 'text'; // Default a 'text' si no se especifica
    $description = $args['description'] ?? '';

    // Obtener el array completo de ajustes para esta opción
    $options = get_option( $option_name, [] );
    // Obtener el valor específico para este campo, o vacío si no existe
    $value = $options[$key] ?? '';

    // Sanitizar el valor antes de mostrarlo en el atributo 'value' del input
    $sanitized_value = '';
    if ( $type === 'url' ) {
        $sanitized_value = esc_url( $value );
    } else {
        $sanitized_value = esc_attr( $value );
    }

    // Construir el atributo 'name' del input: option_name[key] (ej: pos_evolution_api_settings[api_url])
    $input_name = sprintf( '%s[%s]', esc_attr( $option_name ), esc_attr( $key ) );
    // Usar el 'label_for' como ID del input
    $input_id = esc_attr( $args['label_for'] );

    // Imprimir el HTML del input
    printf(
        '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />',
        esc_attr( $type ),
        $input_id,
        $input_name,
        $sanitized_value
    );

    // Mostrar descripción si existe
    if ( ! empty( $description ) ) {
        printf( '<p class="description">%s</p>', esc_html( $description ) );
    }
}

/**
 * Renderiza el campo de input para el TOKEN con un botón de mostrar/ocultar.
 * Es la función callback específica para el campo del token.
 *
 * @param array $args Argumentos pasados desde add_settings_field.
 */
function pos_evolution_api_render_token_field( $args ) {
    $option_name = $args['option_name'];
    $key = $args['key'];
    $description = $args['description'] ?? '';

    $options = get_option( $option_name, [] );
    $value = $options[$key] ?? '';

    $sanitized_value = esc_attr( $value );
    $input_name = sprintf( '%s[%s]', esc_attr( $option_name ), esc_attr( $key ) );
    $input_id = esc_attr( $args['label_for'] );

    ?>
    <div style="position: relative; display: inline-block; max-width: 25em;"> <?php // Contenedor para posicionar el botón ?>
        <input type="password" id="<?php echo $input_id; ?>" name="<?php echo $input_name; ?>" value="<?php echo $sanitized_value; ?>" class="regular-text" autocomplete="new-password" style="padding-right: 40px; width: 100%; box-sizing: border-box;" />
        <button type="button" class="button button-secondary" onclick="togglePasswordVisibilityEvoApi('<?php echo esc_js( $input_id ); ?>', this)" title="<?php esc_attr_e('Mostrar/Ocultar Token', 'pos-base'); ?>" style="position: absolute; right: 1px; top: 1px; bottom: 1px; margin: 0; height: auto; width: 32px; cursor: pointer; border-left: 1px solid #ccc;">
            <span class="dashicons dashicons-visibility" style="line-height: inherit;"></span> <?php // Icono inicial ?>
        </button>
    </div>
    <?php if ( ! empty( $description ) ) : ?>
        <p class="description"><?php echo esc_html( $description ); ?></p>
    <?php endif; ?>
    <script>
        // Asegurarse de que la función tenga un nombre único para evitar conflictos
        function togglePasswordVisibilityEvoApi(inputId, button) {
            var input = document.getElementById(inputId);
            var icon = button.querySelector('.dashicons');
            if (!input || !icon) return; // Salir si los elementos no existen

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('dashicons-visibility');
                icon.classList.add('dashicons-hidden');
            } else {
                input.type = 'password';
                icon.classList.remove('dashicons-hidden');
                icon.classList.add('dashicons-visibility');
            }
        }
        // Opcional: Resetear a password si se deja el campo
        // var tokenInput = document.getElementById('<?php echo esc_js($input_id); ?>');
        // if(tokenInput) {
        //     tokenInput.addEventListener('blur', function() {
        //         var icon = this.nextElementSibling.querySelector('.dashicons');
        //         this.type = 'password';
        //         icon.classList.remove('dashicons-hidden');
        //         icon.classList.add('dashicons-visibility');
        //     });
        // }
    </script>
    <?php
}


/**
 * Sanitiza el array de ajustes de Evolution API antes de guardarlo en la BD.
 * Es la función callback definida en register_setting().
 *
 * @param array|mixed $input Los datos enviados desde el formulario o desde update_option().
 * @return array El array sanitizado y listo para guardar.
 */
function pos_evolution_api_sanitize_settings( $input ) {
    $sanitized_output = [];
    $defaults = pos_evolution_api_get_defaults();
    // Obtener los valores actuales para preservar 'managed_instance_name' si no se está actualizando
    $current_settings = get_option( 'pos_evolution_api_settings', $defaults );

    // Asegurarse de que $input sea un array
    if ( ! is_array( $input ) ) {
        $input = []; // Si no es array, tratarlo como vacío para usar defaults/current
    }

    // Sanitizar URL: usar esc_url_raw para guardar en BD, trim para quitar espacios
    $sanitized_output['api_url'] = isset( $input['api_url'] ) ? esc_url_raw( trim( $input['api_url'] ) ) : $defaults['api_url'];

    // Sanitizar Token: usar sanitize_text_field para strings simples, trim
    $sanitized_output['token'] = isset( $input['token'] ) ? sanitize_text_field( trim( $input['token'] ) ) : $defaults['token'];

    // Gestionar managed_instance_name:
    // Si se está actualizando explícitamente (ej: desde AJAX), sanitizar el nuevo valor.
    if ( isset( $input['managed_instance_name'] ) ) {
        // Usar sanitize_key es buena opción para nombres de instancia (letras, números, -, _)
        $sanitized_output['managed_instance_name'] = sanitize_key( trim( $input['managed_instance_name'] ) );
    } else {
        // Si no viene en el input (ej: guardando desde el formulario principal),
        // mantener el valor que ya estaba guardado en la base de datos.
        $sanitized_output['managed_instance_name'] = $current_settings['managed_instance_name'] ?? $defaults['managed_instance_name'];
    }

    return $sanitized_output;
}

/**
 * Define los valores por defecto para la opción 'pos_evolution_api_settings'.
 *
 * @return array Array con las claves y sus valores por defecto.
 */
function pos_evolution_api_get_defaults() {
    return [
        'api_url'               => '', // URL de la API vacía por defecto
        'token'                 => '', // Token vacío por defecto
        'managed_instance_name' => '', // Nombre de la instancia gestionada vacío por defecto
    ];
}

/**
 * Función auxiliar para obtener los ajustes guardados de Evolution API.
 * Combina los valores guardados con los valores por defecto para asegurar
 * que todas las claves esperadas estén presentes.
 *
 * @return array Los ajustes completos del módulo.
 */
function pos_evolution_api_get_settings() {
    $defaults = pos_evolution_api_get_defaults();
    $saved_settings = get_option( 'pos_evolution_api_settings', $defaults );

    // wp_parse_args es ideal para fusionar arrays asegurando que las claves por defecto existan
    return wp_parse_args( (array) $saved_settings, $defaults );
}

?>
