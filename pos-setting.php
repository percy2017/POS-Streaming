<?php
/**
 * Manejo de la página de configuración y la API de Ajustes para POS Base.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

/**
 * Registra los ajustes, secciones y campos para la página de configuración.
 * Se engancha a 'admin_init'.
 */
function pos_base_register_settings() {

    // 1. Registrar el ajuste principal (la opción que guarda los módulos activos)
    register_setting(
        'pos_base_options_group',           // Nombre del grupo de opciones (usado en settings_fields())
        'pos_base_active_modules',          // Nombre de la opción en la BD (wp_options)
        [
            'type'              => 'array', // Especificamos que esperamos un array
            'description'       => __( 'Almacena los slugs de los módulos POS Base activos.', 'pos-base' ),
            'sanitize_callback' => 'pos_base_sanitize_active_modules', // Función para limpiar los datos antes de guardar
            'default'           => [],      // Valor por defecto (un array vacío)
            // 'show_in_rest'   => false, // Podríamos exponerlo a la API REST si fuera necesario
        ]
    );

    // 2. Añadir la sección de configuración para los módulos
    add_settings_section(
        'pos_base_modules_section',         // ID único para la sección
        __( 'Gestión de Módulos', 'pos-base' ), // Título visible de la sección
        'pos_base_modules_section_callback', // Función que muestra texto introductorio (opcional)
        'pos-base-settings'                 // Slug de la página donde se mostrará (el mismo que en add_submenu_page)
    );

    // 3. Añadir el campo para activar/desactivar módulos dentro de la sección anterior
    add_settings_field(
        'pos_base_modules_field',           // ID único para el campo
        __( 'Módulos Disponibles', 'pos-base' ), // Etiqueta visible para el campo
        'pos_base_render_modules_field',    // Función que renderiza el HTML del campo (los checkboxes)
        'pos-base-settings',                // Slug de la página
        'pos_base_modules_section',         // ID de la sección a la que pertenece
        [
            'label_for' => 'pos_base_active_modules' // Asociar con la opción para accesibilidad (aunque tengamos múltiples checkboxes)
        ]
    );
}
add_action( 'admin_init', 'pos_base_register_settings' );

/**
 * Muestra texto introductorio para la sección de módulos (opcional).
 * Callback para add_settings_section.
 */
function pos_base_modules_section_callback() {
    echo '<p>' . esc_html__( 'Activa o desactiva los módulos de funcionalidad extendida para el POS.', 'pos-base' ) . '</p>';
}

/**
 * Detecta módulos en la carpeta /modules y renderiza los checkboxes.
 * Callback para add_settings_field.
 */
function pos_base_render_modules_field() {
    // Obtener los módulos que están actualmente activos (guardados en la BD)
    $active_modules = get_option( 'pos_base_active_modules', [] ); // Devuelve array vacío si no existe

    // Ruta a la carpeta de módulos
    $modules_dir = POS_BASE_PLUGIN_DIR . 'modules/';

    // Verificar si el directorio de módulos existe
    if ( ! is_dir( $modules_dir ) || ! is_readable( $modules_dir ) ) {
        echo '<p><em>' . esc_html__( 'La carpeta de módulos no existe o no se puede leer.', 'pos-base' ) . '</em></p>';
        echo '<p><em>' . sprintf( esc_html__( 'Ruta esperada: %s', 'pos-base' ), '<code>' . esc_html( $modules_dir ) . '</code>' ) . '</em></p>';
        // Crear la carpeta si no existe? Podría ser una opción.
        if ( ! is_dir( $modules_dir ) ) {
            wp_mkdir_p( $modules_dir );
             echo '<p style="color: green;">' . esc_html__( 'Se ha creado la carpeta de módulos.', 'pos-base' ) . '</p>';
        }
        return;
    }

    // Escanear el directorio de módulos
    $potential_modules = scandir( $modules_dir );
    $available_modules = [];

    if ( $potential_modules ) {
        foreach ( $potential_modules as $item ) {
            // Ignorar . y .. y archivos que no sean directorios
            if ( $item === '.' || $item === '..' || ! is_dir( $modules_dir . $item ) ) {
                continue;
            }
            // Asumimos que cada directorio es un módulo potencial
            // Podríamos añadir una comprobación extra: buscar un archivo específico dentro (ej. 'module-info.json' o 'main-module-file.php')
            $available_modules[ $item ] = $item; // Usamos el nombre del directorio como slug y como nombre (podríamos mejorarlo)
        }
    }

    // Mostrar checkboxes si se encontraron módulos
    if ( empty( $available_modules ) ) {
        echo '<p><em>' . esc_html__( 'No se encontraron módulos en la carpeta.', 'pos-base' ) . '</em></p>';
         echo '<p><em>' . sprintf( esc_html__( 'Añade subdirectorios a %s para que aparezcan aquí.', 'pos-base' ), '<code>' . esc_html( $modules_dir ) . '</code>' ) . '</em></p>';
    } else {
        echo '<fieldset>'; // Agrupa los checkboxes
        foreach ( $available_modules as $slug => $name ) {
            // Comprobar si este módulo está en la lista de activos
            $is_checked = in_array( $slug, (array) $active_modules, true );
            ?>
            <label for="pos-module-<?php echo esc_attr( $slug ); ?>">
                <input
                    type="checkbox"
                    id="pos-module-<?php echo esc_attr( $slug ); ?>"
                    name="pos_base_active_modules[]" <?php // IMPORTANTE: usar [] para que PHP lo trate como array ?>
                    value="<?php echo esc_attr( $slug ); ?>"
                    <?php checked( $is_checked ); // Función de WP para añadir 'checked="checked"' si es true ?>
                >
                <?php echo esc_html( ucfirst( $name ) ); // Muestra el nombre del directorio capitalizado ?>
            </label><br>
            <?php
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__( 'Marca los módulos que deseas activar. Los cambios surtirán efecto después de guardar.', 'pos-base' ) . '</p>';
    }
}

/**
 * Sanitiza el array de módulos activos antes de guardarlo en la BD.
 * Callback para register_setting.
 *
 * @param array|mixed $input El valor enviado desde el formulario.
 * @return array El array sanitizado de slugs de módulos activos.
 */
function pos_base_sanitize_active_modules( $input ) {
    $sanitized_output = [];
    $submitted_modules = (array) $input; // Asegurarse de que es un array

    // Volver a obtener la lista de módulos realmente disponibles para validar
    $modules_dir = POS_BASE_PLUGIN_DIR . 'modules/';
    $available_slugs = [];
    if ( is_dir( $modules_dir ) && is_readable( $modules_dir ) ) {
        $potential_modules = scandir( $modules_dir );
        if ( $potential_modules ) {
            foreach ( $potential_modules as $item ) {
                if ( $item !== '.' && $item !== '..' && is_dir( $modules_dir . $item ) ) {
                    $available_slugs[] = $item;
                }
            }
        }
    }

    // Filtrar los módulos enviados: solo guardar los que realmente existen
    foreach ( $submitted_modules as $slug ) {
        if ( is_string( $slug ) && ! empty( $slug ) && in_array( $slug, $available_slugs, true ) ) {
            $sanitized_output[] = sanitize_key( $slug ); // sanitize_key es bueno para slugs
        }
    }

    // Asegurarse de que no haya duplicados (aunque el checkbox no debería permitirlo)
    $sanitized_output = array_unique( $sanitized_output );

    return $sanitized_output;
}

?>
