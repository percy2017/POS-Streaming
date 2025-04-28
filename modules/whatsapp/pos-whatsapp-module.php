<?php
/**
 * Plugin Name: POS Base - WhatsApp Module (Settings Only)
 * Description: Añade la configuración para el módulo WhatsApp a POS Base.
 * Version: 2.0 (Reset)
 * Author: Tu Nombre
 * Text Domain: pos-whatsapp
 * Domain Path: /languages
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

// Definir constantes útiles (opcional, pero bueno tenerlas)
define( 'POS_WHATSAPP_MODULE_DIR', plugin_dir_path( __FILE__ ) );
define( 'POS_WHATSAPP_MODULE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Carga el textdomain para traducciones del módulo.
 */
function pos_whatsapp_load_textdomain_v2() { // Renombrada por si acaso
    load_plugin_textdomain(
        'pos-whatsapp',
        false,
        basename( dirname( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'pos_whatsapp_load_textdomain_v2', 5 );


// ===========================================
// AJUSTES DEL MÓDULO WHATSAPP (Settings API)
// ===========================================

/**
 * Registra los ajustes, la sección y los campos para el módulo WhatsApp.
 * Se engancha a 'admin_init'.
 */
function pos_whatsapp_register_settings_v2() { // Renombrada por si acaso

    // 1. Registrar el grupo de ajustes para guardar los datos de WhatsApp
    //    USA EL MISMO GRUPO que el plugin base para que se guarden juntos.
    register_setting(
        'pos_base_options_group',           // <-- Grupo del plugin base (de pos-setting.php)
        'pos_whatsapp_settings',            // <-- Nombre de NUESTRA opción (guardará un array)
        'pos_whatsapp_sanitize_settings_v2' // <-- Nuestra función de sanitización
    );

    // 2. Añadir la sección de ajustes a la página de configuración del plugin base
    add_settings_section(
        'pos_whatsapp_settings_section',    // ID único para la sección
        __( 'Configuración del Módulo WhatsApp', 'pos-whatsapp' ), // Título visible
        'pos_whatsapp_section_callback_v2', // Función de descripción (opcional)
        'pos-base-settings'                 // <-- Slug de la página de ajustes del plugin base
    );

    // 3. Añadir los campos DENTRO de nuestra sección

    // Campo: Número de Teléfono
    add_settings_field(
        'pos_whatsapp_target_phone',            // ID del campo
        __( 'Número WhatsApp Destino', 'pos-whatsapp' ), // Etiqueta
        'pos_whatsapp_render_phone_field_v2',   // Función que dibuja el input
        'pos-base-settings',                    // Slug de la página
        'pos_whatsapp_settings_section'         // ID de la sección
        // No necesitamos 'label_for' aquí porque la etiqueta está en la función render
    );

    // Campo: Mensaje Introductorio
    add_settings_field(
        'pos_whatsapp_default_intro',
        __( 'Mensaje Introductorio (Formulario)', 'pos-whatsapp' ),
        'pos_whatsapp_render_intro_field_v2',
        'pos-base-settings',
        'pos_whatsapp_settings_section'
    );

    // Campo: Tooltip del Botón
    add_settings_field(
        'pos_whatsapp_button_tooltip',
        __( 'Texto Flotante (Tooltip) Botón', 'pos-whatsapp' ),
        'pos_whatsapp_render_tooltip_field_v2',
        'pos-base-settings',
        'pos_whatsapp_settings_section'
    );

    // Campo: Título del Formulario
     add_settings_field(
        'pos_whatsapp_form_title',
        __( 'Título del Formulario Popup', 'pos-whatsapp' ),
        'pos_whatsapp_render_form_title_field_v2',
        'pos-base-settings',
        'pos_whatsapp_settings_section'
    );
}
add_action( 'admin_init', 'pos_whatsapp_register_settings_v2' );

/**
 * Muestra texto introductorio para la sección de WhatsApp (opcional).
 */
function pos_whatsapp_section_callback_v2() {
    echo '<p>' . esc_html__( 'Introduce aquí los datos para el botón de contacto por WhatsApp.', 'pos-whatsapp' ) . '</p>';
}

/**
 * Renderiza el campo para el número de teléfono (input simple).
 */
function pos_whatsapp_render_phone_field_v2() {
    $options = get_option( 'pos_whatsapp_settings' ); // Obtiene el array guardado
    // Busca el valor específico o usa un string vacío si no existe
    $value = isset( $options['target_phone_number'] ) ? $options['target_phone_number'] : '';
    ?>
    <input type='tel'
           id='pos_whatsapp_target_phone'
           name='pos_whatsapp_settings[target_phone_number]' <?php // Nombre crucial: opcion[clave] ?>
           value='<?php echo esc_attr( $value ); ?>'
           class='regular-text'>
    <p class="description"><?php esc_html_e( 'Introduce el número completo con código de país, sin espacios ni símbolos (ej: 51987654321).', 'pos-whatsapp' ); ?></p>
    <?php
}

/**
 * Renderiza el campo para el mensaje introductorio (textarea).
 */
function pos_whatsapp_render_intro_field_v2() {
    $options = get_option( 'pos_whatsapp_settings' );
    $value = isset( $options['default_intro_message'] ) ? $options['default_intro_message'] : __( 'Hola, necesito información sobre...', 'pos-whatsapp' );
    ?>
    <textarea id='pos_whatsapp_default_intro'
              name='pos_whatsapp_settings[default_intro_message]'
              rows='3'
              class='large-text'><?php echo esc_textarea( $value ); ?></textarea>
    <p class="description"><?php esc_html_e( 'Este mensaje aparecerá prellenado en el formulario del popup.', 'pos-whatsapp' ); ?></p>
    <?php
}

/**
 * Renderiza el campo para el tooltip del botón (input text).
 */
function pos_whatsapp_render_tooltip_field_v2() {
    $options = get_option( 'pos_whatsapp_settings' );
    $value = isset( $options['button_tooltip'] ) ? $options['button_tooltip'] : __( 'Contactar por WhatsApp', 'pos-whatsapp' );
    ?>
    <input type='text'
           id='pos_whatsapp_button_tooltip'
           name='pos_whatsapp_settings[button_tooltip]'
           value='<?php echo esc_attr( $value ); ?>'
           class='regular-text'>
    <p class="description"><?php esc_html_e( 'Texto que aparece al pasar el ratón sobre el botón flotante.', 'pos-whatsapp' ); ?></p>
    <?php
}

/**
 * Renderiza el campo para el título del formulario (input text).
 */
function pos_whatsapp_render_form_title_field_v2() {
    $options = get_option( 'pos_whatsapp_settings' );
    $value = isset( $options['form_title'] ) ? $options['form_title'] : __( 'Enviar mensaje por WhatsApp', 'pos-whatsapp' );
    ?>
    <input type='text'
           id='pos_whatsapp_form_title'
           name='pos_whatsapp_settings[form_title]'
           value='<?php echo esc_attr( $value ); ?>'
           class='regular-text'>
    <p class="description"><?php esc_html_e( 'Título que se muestra en la cabecera del formulario popup.', 'pos-whatsapp' ); ?></p>
    <?php
}


/**
 * Sanitiza los ajustes del módulo WhatsApp antes de guardarlos.
 *
 * @param array|mixed $input Los datos enviados desde el formulario (esperamos un array).
 * @return array El array de datos sanitizados.
 */
function pos_whatsapp_sanitize_settings_v2( $input ) {
    // Inicializar array de salida
    $output = array();

    // Asegurarse de que $input es un array
    if ( ! is_array( $input ) ) {
        // Si no es un array, devolver un array vacío o los valores por defecto
        // Esto previene errores si algo va mal en el envío del formulario
        return $output;
    }

    // Sanitizar cada campo esperado
    if ( isset( $input['target_phone_number'] ) ) {
        // Limpieza simple: quitar todo excepto dígitos y el signo '+'
        $phone = preg_replace( '/[^\d+]/', '', $input['target_phone_number'] );
        // Asegurarse que el '+' solo esté al principio (opcional)
        if (strpos($phone, '+') !== false && strpos($phone, '+') !== 0) {
            $phone = preg_replace( '/[^\d]/', '', $phone ); // Quitar '+' si no está al inicio
        }
        $output['target_phone_number'] = $phone;
    }

    if ( isset( $input['default_intro_message'] ) ) {
        $output['default_intro_message'] = sanitize_textarea_field( $input['default_intro_message'] );
    }

    if ( isset( $input['button_tooltip'] ) ) {
        $output['button_tooltip'] = sanitize_text_field( $input['button_tooltip'] );
    }

     if ( isset( $input['form_title'] ) ) {
        $output['form_title'] = sanitize_text_field( $input['form_title'] );
    }

    // Devolver el array sanitizado
    return $output;
}


// ===========================================
// CÓDIGO FRONTEND (Botón Flotante)
// ===========================================

/**
 * Añade el HTML del botón flotante y el formulario al pie de página.
 * Lee la configuración desde la opción 'pos_whatsapp_settings'.
 */
function pos_whatsapp_add_floating_button_html_v2() { // Renombrada por si acaso
    // --- Obtener configuración guardada ---
    $options = get_option( 'pos_whatsapp_settings' );

    // Valores por defecto si no están guardados o están vacíos
    $target_phone_number = isset( $options['target_phone_number'] ) ? trim( $options['target_phone_number'] ) : '';
    $default_intro_message = isset( $options['default_intro_message'] ) && trim( $options['default_intro_message'] ) !== '' ? $options['default_intro_message'] : __( 'Hola, necesito información sobre...', 'pos-whatsapp' );
    $button_tooltip = isset( $options['button_tooltip'] ) && trim( $options['button_tooltip'] ) !== '' ? $options['button_tooltip'] : __( 'Contactar por WhatsApp', 'pos-whatsapp' );
    $form_title = isset( $options['form_title'] ) && trim( $options['form_title'] ) !== '' ? $options['form_title'] : __( 'Enviar mensaje por WhatsApp', 'pos-whatsapp' );
    // --- Fin Configuración ---

    // No mostrar si no hay número configurado
    if ( empty( $target_phone_number ) ) {
        error_log('POS WhatsApp Frontend: No se muestra el botón flotante porque falta el número de teléfono en los ajustes.');
        return; // No imprimir nada si no hay número
    }

    // Limpiar número (solo dígitos) - Aunque ya debería estar sanitizado
    $cleaned_phone = preg_replace( '/\D+/', '', $target_phone_number );

    // Imprimir el HTML
    ?>
    <div id="pos-whatsapp-fab-container">
        <button id="pos-whatsapp-fab-button" aria-label="<?php echo esc_attr( $button_tooltip ); ?>" title="<?php echo esc_attr( $button_tooltip ); ?>">
            <!-- Icono SVG WhatsApp -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24" fill="currentColor"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 .4c101.1 0 183.3 82.2 183.3 183.3 0 31.8-8.2 62.1-23.5 88.4l-14.9 25.3 54.6 14.4-69.9-68-21.6-13.1c-25.1-15.1-53.8-23.1-84.1-23.1h-.1c-101.1 0-183.3-82.2-183.3-183.3 0-101.1 82.2-183.3 183.3-183.3zm148.5 118.3c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 4.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
        </button>

        <div id="pos-whatsapp-form-popup" style="display: none;">
             <button id="pos-whatsapp-form-close" aria-label="<?php esc_attr_e( 'Cerrar', 'pos-whatsapp' ); ?>">&times;</button>
            <h3><?php echo esc_html( $form_title ); ?></h3>
            <form id="pos-whatsapp-contact-form">
                <p>
                    <label for="pos-whatsapp-name"><?php esc_html_e( 'Nombre:', 'pos-whatsapp' ); ?></label>
                    <input type="text" id="pos-whatsapp-name" name="name" required>
                </p>
                <!-- <p>
                    <label for="pos-whatsapp-client-phone"><?php esc_html_e( 'Tu Teléfono (opcional):', 'pos-whatsapp' ); ?></label>
                    <input type="tel" id="pos-whatsapp-client-phone" name="client_phone">
                </p> -->
                <p>
                    <label for="pos-whatsapp-message"><?php esc_html_e( 'Mensaje:', 'pos-whatsapp' ); ?></label>
                    <textarea id="pos-whatsapp-message" name="message" rows="2" required><?php echo esc_textarea( $default_intro_message ); ?></textarea>
                </p>
                <p>
                    <button type="submit" id="pos-whatsapp-send-button"><?php esc_html_e( 'Enviar por WhatsApp', 'pos-whatsapp' ); ?></button>
                </p>
                <?php // Pasar el número limpio al JS a través de un input oculto ?>
                <input type="hidden" id="pos-whatsapp-target-phone" value="<?php echo esc_attr( $cleaned_phone ); ?>">
            </form>
        </div>
    </div>
    <?php
}
// Enganchar la función al footer del frontend
add_action( 'wp_footer', 'pos_whatsapp_add_floating_button_html_v2', 100 );


// ===========================================
// BOTÓN EN PÁGINA DE PRODUCTO WOOCOMMERCE
// ===========================================

/**
 * Muestra un botón de consulta por WhatsApp en la página de producto individual.
 */
function pos_whatsapp_product_page_button_v2() {
    // 1. Verificar si estamos en una página de producto individual
    if ( ! function_exists('is_product') || ! is_product() ) {
        return;
    }

    // 2. Obtener los ajustes de WhatsApp guardados
    $options = get_option( 'pos_whatsapp_settings' );
    $target_phone_number = isset( $options['target_phone_number'] ) ? trim( $options['target_phone_number'] ) : '';

    // 3. No mostrar el botón si no hay número configurado
    if ( empty( $target_phone_number ) ) {
        return;
    }

    // 4. Obtener información del producto actual
    global $product;
    // Asegurarse de que $product es un objeto WC_Product válido
    if ( ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
        if ( ! is_a( $product, 'WC_Product' ) ) {
            return; // Salir si no podemos obtener el producto
        }
    }
    $product_name = $product->get_name();

    // 5. Preparar URL y mensaje de WhatsApp
    $cleaned_phone = preg_replace( '/\D+/', '', $target_phone_number );

    // --- OBTENER DATOS ADICIONALES ---
    $product_price_html = $product->get_price_html(); // Obtiene el precio formateado (ej: S/ 50.00)
    $product_url = $product->get_permalink();       // Obtiene el enlace permanente del producto

    // Añadimos %2$s para el precio y %3$s para la URL
    // Usamos %1$s, %2$s, %3$s para claridad en la traducción
    $message_text = sprintf(
        /* Translators: %1$s is product name, %2$s is formatted price, %3$s is product URL */
        __( 'Hola, estoy interesado/a en el producto: %1$s\nPrecio: %2$s\nPuedes verlo aquí: %3$s', 'pos-whatsapp' ),
        $product_name,        // %1$s
        wp_strip_all_tags($product_price_html), // %2$s (Quitamos HTML del precio por si acaso)
        $product_url          // %3$s
    );

    $whatsapp_url = 'https://wa.me/' . $cleaned_phone . '?text=' . rawurlencode( $message_text );

    // 6. Generar el HTML del botón
    $button_text = __( 'Consultar por WhatsApp', 'pos-whatsapp' );
    ?>
    <div class="pos-whatsapp-product-button-wrapper" style="margin-top: 15px; clear: both;">
        <a href="<?php echo esc_url( $whatsapp_url ); ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="button pos-whatsapp-product-button"> <?php // Usa la clase 'button' para estilo de tema ?>
            <?php echo esc_html( $button_text ); ?>
        </a>
    </div>
    <?php
}
// Enganchar la función a un hook de la página de producto.
// La prioridad 35 lo suele colocar después del botón "Añadir al carrito" o cerca.
add_action( 'woocommerce_single_product_summary', 'pos_whatsapp_product_page_button_v2', 35 );


/// ===========================================
// BOTÓN EN PÁGINA DE PAGO WOOCOMMERCE
// ===========================================

/**
 * Muestra un botón de consulta por WhatsApp en la página de pago (checkout).
 */
function pos_whatsapp_checkout_page_button_v2() {
    // 1. Verificar si estamos en la página de pago... (sin cambios)
    if ( ! function_exists('is_checkout') || ! is_checkout() ) { return; }

    // 2. Obtener los ajustes... (sin cambios)
    $options = get_option( 'pos_whatsapp_settings' );
    $target_phone_number = isset( $options['target_phone_number'] ) ? trim( $options['target_phone_number'] ) : '';

    // 3. No mostrar el botón si no hay número... (sin cambios)
    if ( empty( $target_phone_number ) ) { return; }

    // 4. Limpiar número (NECESARIO AQUÍ para el data attribute)
    $cleaned_phone = preg_replace( '/\D+/', '', $target_phone_number );

    // 5. Generar el HTML del botón (MODIFICADO con data attribute)
    $button_text = __( 'Completar Pedido por WhatsApp', 'pos-whatsapp' );
    ?>
    <div class="pos-whatsapp-checkout-button-wrapper" style="margin-top: 1em; text-align: center; clear: both;">
        <button type="button"
                id="pos-whatsapp-checkout-complete-button"
                class="button alt pos-whatsapp-checkout-button"
                data-target-phone="<?php echo esc_attr( $cleaned_phone ); ?>"> <?php // <-- AÑADIDO data-target-phone ?>
            <?php echo esc_html( $button_text ); ?>
        </button>
        <p class="description" style="font-size:0.8em; margin-top: 0.5em;">
            <?php esc_html_e('Al hacer clic, se abrirá WhatsApp con los detalles de tu pedido para enviárnoslo directamente.', 'pos-whatsapp'); ?>
        </p>
    </div>
    <?php
}
add_action( 'woocommerce_review_order_after_submit', 'pos_whatsapp_checkout_page_button_v2', 20 );


/**
 * Encola los scripts y estilos para el frontend (AHORA CARGA ARCHIVOS ESTÁTICOS).
 */
function pos_whatsapp_enqueue_floating_scripts_v2() {
    // Solo en el frontend
    if ( ! is_admin() ) {
        error_log('POS WhatsApp Frontend: Ejecutando pos_whatsapp_enqueue_floating_scripts_v2() para cargar archivos estáticos.');

        // --- CSS ---
        $css_file = 'assets/css/floating-button.css';
        $css_path = POS_WHATSAPP_MODULE_DIR . $css_file;
        $css_url = POS_WHATSAPP_MODULE_URL . $css_file;

        // Encolar CSS SOLO SI EXISTE el archivo físico
        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'pos-whatsapp-floating-style',
                $css_url,
                array(),
                filemtime( $css_path ) // Versionado por fecha de modificación
            );
            error_log('POS WhatsApp Frontend: CSS estático encolado.');
        } else {
             error_log('POS WhatsApp Frontend Error: Archivo CSS estático NO encontrado en: ' . $css_path);
        }

        // --- JavaScript ---
        $js_file = 'assets/js/floating-button.js';
        $js_path = POS_WHATSAPP_MODULE_DIR . $js_file;
        $js_url = POS_WHATSAPP_MODULE_URL . $js_file;

        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'pos-whatsapp-floating-script',
                $js_url,
                array('jquery'), // Dependencia
                filemtime( $js_path ), // Versionado por fecha de modificación
                true // Cargar en footer
            );
             error_log('POS WhatsApp Frontend: JS estático encolado.');
        } else {
             error_log('POS WhatsApp Frontend Error: Archivo JS estático NO encontrado en: ' . $js_path);
        }

    } // end if !is_admin()
}
add_action( 'wp_enqueue_scripts', 'pos_whatsapp_enqueue_floating_scripts_v2' );


?>
