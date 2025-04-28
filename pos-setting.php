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
            // 'label_for' no es ideal aquí porque son múltiples checkboxes
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
 */
function pos_base_render_modules_field() {
    $active_modules = get_option( 'pos_base_active_modules', [] );
    $modules_dir = POS_BASE_PLUGIN_DIR . 'modules/';

    if ( ! is_dir( $modules_dir ) || ! is_readable( $modules_dir ) ) {
        // ... (código de manejo de error de directorio sin cambios) ...
        return;
    }

    $potential_modules = scandir( $modules_dir );
    $available_modules = [];

    if ( $potential_modules ) {
        foreach ( $potential_modules as $item ) {
            if ( $item === '.' || $item === '..' || ! is_dir( $modules_dir . $item ) ) {
                continue;
            }
            $available_modules[ $item ] = $item;
        }
    }

    if ( empty( $available_modules ) ) {
        // ... (código si no hay módulos sin cambios) ...
    } else {
        // --- INICIO MODIFICACIÓN ---
        echo '<fieldset class="pos-base-module-checkboxes">'; // Añadida clase contenedora
        foreach ( $available_modules as $slug => $name ) {
            $is_checked = in_array( $slug, (array) $active_modules, true );
            $checkbox_id = 'pos-module-' . esc_attr( $slug );
            ?>
            <div class="pos-module-option"> <?php // Wrapper para cada opción ?>
                <input
                    type="checkbox"
                    id="<?php echo $checkbox_id; ?>"
                    name="pos_base_active_modules[]"
                    value="<?php echo esc_attr( $slug ); ?>"
                    <?php checked( $is_checked ); ?>
                    class="pos-module-checkbox-input" <?php // Clase para ocultar ?>
                >
                <label for="<?php echo $checkbox_id; ?>" class="pos-module-checkbox-label"> <?php // Label estilizable ?>
                    <?php echo esc_html( ucfirst( $name ) ); ?>
                </label>
            </div>
            <?php
        }
        echo '</fieldset>';
        // --- FIN MODIFICACIÓN ---
        echo '<p class="description">' . esc_html__( 'Marca los módulos que deseas activar. Los cambios surtirán efecto después de guardar.', 'pos-base' ) . '</p>';
    }
}

/**
 * Sanitiza el array de módulos activos antes de guardarlo en la BD.
 */
function pos_base_sanitize_active_modules( $input ) {
    // ... (código de sanitización sin cambios) ...
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

/**
 * Añade CSS inline para estilizar los checkboxes en la página de configuración.
 *
 * @param string $hook_suffix El sufijo del hook de la página actual.
 */
function pos_base_enqueue_settings_styles( $hook_suffix ) {
    // Obtener el hook suffix esperado para la página de configuración
    // (Asegúrate que 'pos-base-settings' es el slug correcto de tu submenú)
    $settings_page_hook = 'pos-base_page_pos-base-settings'; // Ajusta si es diferente

    // Solo aplicar en nuestra página de configuración
    if ( $hook_suffix !== $settings_page_hook ) {
        return;
    }

    // CSS para los checkboxes personalizados
    $custom_css = "
        .pos-base-module-checkboxes .pos-module-option {
            margin-bottom: 8px; /* Espacio entre opciones */
        }
        .pos-module-checkbox-input {
            /* Ocultar el checkbox original de forma accesible */
            opacity: 0;
            position: absolute;
            left: -9999px;
        }
        .pos-module-checkbox-label {
            position: relative;
            padding-left: 30px; /* Espacio para el checkbox falso */
            cursor: pointer;
            line-height: 20px; /* Alinear verticalmente */
            display: inline-block;
            color: #2c3338; /* Color de texto admin */
        }
        /* El 'cuadrado' del checkbox falso */
        .pos-module-checkbox-label::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 18px; /* Tamaño del cuadrado */
            height: 18px;
            border: 1px solid #8c8f94; /* Borde gris admin */
            background-color: #fff;
            border-radius: 3px;
            transition: background-color 0.1s ease-in-out, border-color 0.1s ease-in-out;
        }
        /* Estilo al pasar el ratón */
        .pos-module-checkbox-label:hover::before {
            border-color: #2271b1; /* Azul primario admin */
        }
        /* El 'check' (marca) */
        .pos-module-checkbox-label::after {
            content: '';
            position: absolute;
            left: 6px; /* Posición del check dentro del cuadrado */
            top: 3px;
            width: 6px;
            height: 10px;
            border: solid #fff; /* Color del check */
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
            opacity: 0; /* Oculto por defecto */
            transition: opacity 0.1s ease-in-out;
        }
        /* Estilos cuando el checkbox original está marcado */
        .pos-module-checkbox-input:checked + .pos-module-checkbox-label::before {
            background-color: #2271b1; /* Fondo azul */
            border-color: #2271b1; /* Borde azul */
        }
        .pos-module-checkbox-input:checked + .pos-module-checkbox-label::after {
            opacity: 1; /* Mostrar el check */
        }
        /* Estilo focus para accesibilidad */
        .pos-module-checkbox-input:focus + .pos-module-checkbox-label::before {
            box-shadow: 0 0 0 1px #2271b1, 0 0 2px 1px rgba(34, 113, 177, 0.8);
            border-color: #2271b1;
            outline: none;
        }
    ";

    // Añadir el CSS inline asociado a un handle existente (ej. 'wp-admin')
    wp_add_inline_style( 'wp-admin', $custom_css );
}
add_action( 'admin_enqueue_scripts', 'pos_base_enqueue_settings_styles' );

?>
