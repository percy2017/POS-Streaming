<?php
/**
 * cron.php
 *
 * Gestiona las tareas programadas (WP-Cron) para el módulo Evolution API.
 * Incluye la programación y ejecución de recordatorios de vencimiento de suscripción.
 */

// Evitar acceso directo
defined( 'ABSPATH' ) or die( '¡Acceso no permitido!' );

// --- Tarea Programada para Recordatorios de Vencimiento ---

// Nombre único para nuestro evento cron
define( 'POS_EVOLUTION_EXPIRY_CRON_HOOK', 'pos_evolution_check_subscription_expiry' );

/**
 * Programa el evento cron si no está ya programado.
 * Se ejecuta en admin_init para asegurar que se verifique regularmente.
 * MODIFICADO: Cambiada la recurrencia a 'hourly' (cada hora).
 */
function pos_evolution_schedule_expiry_check() {
    // Solo programar si no existe ya
    if ( ! wp_next_scheduled( POS_EVOLUTION_EXPIRY_CRON_HOOK ) ) {
        wp_schedule_event( time(), 'hourly', POS_EVOLUTION_EXPIRY_CRON_HOOK );
        error_log("[EVO_API_CRON] Evento '" . POS_EVOLUTION_EXPIRY_CRON_HOOK . "' programado para ejecución HORARIA.");
    }
}
// Enganchar la función de programación a admin_init (esta línea no cambia)
add_action( 'admin_init', 'pos_evolution_schedule_expiry_check' );


/**
 * Función que se ejecuta con el cron: busca suscripciones que vencen hoy y envía recordatorios.
 */
function pos_evolution_run_expiry_check() {
    error_log("[EVO_API_CRON] Ejecutando '" . POS_EVOLUTION_EXPIRY_CRON_HOOK . "' para vencimientos de HOY.");

    // 1. Verificar Configuración de API y Existencia de Instancia Gestionada
    $settings = pos_evolution_api_get_settings(); // Asume que esta función está disponible (de settings.php)
    $api_client = new Evolution_API_Client();     // Asume que esta clase está disponible (de api-client.php)

    if ( ! $api_client->is_configured() || empty( $settings['managed_instance_name'] ) ) {
        error_log("[EVO_API_CRON] API no configurada o instancia no gestionada. Saliendo.");
        return; // Salir si la API no está lista
    }
    $instance_name = $settings['managed_instance_name'];

    // 2. Obtener Fecha de Hoy
    $today_date_obj = new DateTime( 'now', wp_timezone() );
    $today_date_str = $today_date_obj->format('Y-m-d'); // Formato YYYY-MM-DD

    error_log("[EVO_API_CRON] Buscando suscripciones que vencen exactamente HOY: " . $today_date_str);

    // 3. Consultar Pedidos
    $order_args = array(
        'limit'       => -1, // Procesar todos los encontrados
        'status'      => array('wc-processing', 'wc-completed', 'wc-on-hold'), // Estados relevantes
        'meta_query'  => array(
            'relation' => 'AND',
            array( 'key' => '_pos_sale_type', 'value' => 'subscription', 'compare' => '=' ),
            array( 'key' => '_pos_subscription_expiry_date', 'value' => $today_date_str, 'compare' => '=' ), // Fecha exacta de hoy
        ),
        'return'      => 'ids', // Solo necesitamos los IDs
    );

    $order_ids = wc_get_orders( $order_args );

    if ( empty( $order_ids ) ) {
        error_log("[EVO_API_CRON] No se encontraron suscripciones que venzan hoy (" . $today_date_str . ")");
        return; // Nada que hacer
    }

    error_log("[EVO_API_CRON] Encontradas " . count($order_ids) . " suscripciones que vencen hoy (" . $today_date_str . "). Procesando...");

    // 4. Procesar cada Pedido Encontrado
    foreach ( $order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            error_log("[EVO_API_CRON] No se pudo obtener el objeto WC_Order para ID: {$order_id}. Saltando.");
            continue;
        }

        $expiry_date = $order->get_meta( '_pos_subscription_expiry_date', true ); // Ya sabemos que es hoy

        // 5. Verificar si ya se envió hoy
        $reminder_sent_meta_key = '_pos_evo_reminder_sent_' . $expiry_date; // Clave usa la fecha de vencimiento (hoy)
        if ( $order->get_meta( $reminder_sent_meta_key, true ) ) {
            error_log("[EVO_API_CRON] Recordatorio para pedido #{$order_id} (vence hoy {$expiry_date}) ya fue enviado. Saltando.");
            continue;
        }

        // 6. Obtener Teléfono del Cliente
        $customer_id = $order->get_customer_id();
        $phone = '';
        if ( $customer_id ) {
            $phone = get_user_meta( $customer_id, 'billing_phone', true );
        } else {
            // Intentar obtener del billing phone del pedido si no hay customer ID
            $phone = $order->get_billing_phone();
        }

        if ( empty( $phone ) ) {
            error_log("[EVO_API_CRON] Pedido #{$order_id} no tiene teléfono asociado (cliente o pedido). Saltando.");
            continue;
        }
        // Formatear teléfono (simple, ajustar según necesidad)
        $formatted_phone = preg_replace('/[^0-9]/', '', $phone);
        // Considera añadir código de país si es necesario (ej: '51' para Perú)
        // if (strlen($formatted_phone) == 9) { $formatted_phone = '51' . $formatted_phone; }

        // --- INICIO CORRECCIÓN: Obtener Nota usando wc_get_order_notes() ---
        // 7. Obtener la ÚLTIMA Nota del Pedido marcada como "Nota para el cliente" usando wc_get_order_notes()
        $customer_note = ''; // Inicializar vacía
        $args = array(
            'order_id' => $order_id,         // <-- Pasar el ID del pedido
            'type'     => 'customer',        // Solo notas marcadas como "Nota para el cliente"
            'number'   => 1,                 // <-- Usar 'number' en lugar de 'limit'
            'orderby'  => 'comment_date_gmt',// <-- Usar el campo de fecha estándar de comentarios/notas
            'order'    => 'DESC',            // Ordenar por fecha descendente para obtener la más reciente
        );
        $notes = wc_get_order_notes( $args ); // <-- USAR LA FUNCIÓN INDEPENDIENTE

        if ( ! empty( $notes ) ) {
            $latest_note = reset( $notes ); // Obtener el primer elemento (el más reciente)
            // El contenido está en la propiedad 'content' del objeto de nota
            $customer_note = $latest_note->content;
            error_log("[EVO_API_CRON] Pedido #{$order_id}: Encontrada Nota para Cliente: '" . substr($customer_note, 0, 100) . "...'"); // Log para confirmar
        } else {
             error_log("[EVO_API_CRON] Pedido #{$order_id}: No se encontraron 'Notas para el Cliente' (públicas).");
        }
        // --- FIN CORRECCIÓN ---

        // Verificar si se obtuvo una nota
        if ( empty( $customer_note ) ) {
            error_log("[EVO_API_CRON] Pedido #{$order_id} no tiene 'Nota para el Cliente' válida para enviar. Saltando.");
            continue; // No hay mensaje para enviar
        }

        // 8. Enviar Mensaje vía API
        error_log("[EVO_API_CRON] Intentando enviar recordatorio (usando Nota Cliente) a {$formatted_phone} para pedido #{$order_id}.");

        $result = $api_client->send_text_message( $formatted_phone, $customer_note, $instance_name );

        // 9. Procesar Resultado y Marcar como Enviado
        if ( is_wp_error( $result ) ) {
            $error_message = $result->get_error_message();
            error_log("[EVO_API_CRON] ERROR al enviar mensaje para pedido #{$order_id}: " . $error_message);
            // Opcional: Añadir nota de error al pedido
            // $order->add_order_note( sprintf(__( 'Error al enviar recordatorio WhatsApp (vence hoy): %s', 'pos-base' ), $error_message), false, false );
            // $order->save();
        } else {
            error_log("[EVO_API_CRON] ÉXITO al enviar mensaje para pedido #{$order_id}. Respuesta API: " . print_r($result, true));
            // Marcar como enviado para esta fecha
            $order->update_meta_data( $reminder_sent_meta_key, true );
            // Añadir nota de éxito al pedido
            $order->add_order_note( sprintf(__( 'Recordatorio de vencimiento (%s - Hoy) enviado por WhatsApp usando Nota Cliente.', 'pos-base' ), $expiry_date), false, false ); // Nota privada
            $order->save(); // Guardar meta y nota
        }

        // Pequeña pausa para no sobrecargar la API o WP
        sleep(2);

    } // Fin foreach $order_ids

    error_log("[EVO_API_CRON] Finalizada ejecución de '" . POS_EVOLUTION_EXPIRY_CRON_HOOK . "' para vencimientos de HOY.");
}
// Enganchar la función de ejecución al hook del cron
add_action( POS_EVOLUTION_EXPIRY_CRON_HOOK, 'pos_evolution_run_expiry_check' );

/**
 * Limpia el evento cron programado al desactivar.
 */
function pos_evolution_clear_scheduled_events() {
    $timestamp = wp_next_scheduled( POS_EVOLUTION_EXPIRY_CRON_HOOK );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, POS_EVOLUTION_EXPIRY_CRON_HOOK );
        error_log("[EVO_API_CRON] Evento '" . POS_EVOLUTION_EXPIRY_CRON_HOOK . "' desprogramado.");
    } else {
        error_log("[EVO_API_CRON] Evento '" . POS_EVOLUTION_EXPIRY_CRON_HOOK . "' no encontrado para desprogramar.");
    }
}
// NOTA: La llamada a pos_evolution_clear_scheduled_events() debe hacerse
// desde el hook de desactivación del módulo/plugin principal.
// Ejemplo: register_deactivation_hook( __FILE_DEL_MODULO__, 'pos_evolution_clear_scheduled_events' );
// o llamarla desde la función de desactivación de POS Base.

?>
