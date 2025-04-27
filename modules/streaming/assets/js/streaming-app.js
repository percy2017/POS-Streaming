/* global jQuery, posBaseParams, wp */ // Asume que posBaseParams está disponible desde el script base
(function($) {
    'use strict';

    // Función para cargar perfiles disponibles usando la API REST
    function loadAvailableProfiles() {
        const $select = $('#pos-streaming-profile-select');
        const $loadingSpinner = $('#pos-streaming-profile-loading');
        const $errorSpan = $('#pos-streaming-profile-error');

        // Mostrar spinner, ocultar error, deshabilitar select
        $loadingSpinner.show();
        $errorSpan.hide().text('');
        $select.prop('disabled', true).empty().append($('<option>', {
            value: '',
            text: posBaseParams.i18n.loading || 'Cargando...' // Usar traducción si existe
        }));

        // --- USAR REST API directamente ---
         $.ajax({
             // Construir la URL completa al endpoint específico
             url: posBaseParams.rest_url + 'streaming/available-profiles',
             method: 'GET',
             beforeSend: function (xhr) {
                 // Añadir el nonce de seguridad para la API REST
                 xhr.setRequestHeader('X-WP-Nonce', posBaseParams.nonce);
             },
             success: function(response) {
                 // Ocultar spinner al terminar
                 $loadingSpinner.hide();
                 // Vaciar y añadir opción por defecto
                 $select.empty().append($('<option>', { value: '', text: '-- ' + (posBaseParams.i18n.select_profile || 'Selecciona Perfil') + ' --' }));

                 // Comprobar si la respuesta es válida y tiene perfiles
                 if (response && Array.isArray(response) && response.length > 0) {
                     // Iterar sobre los perfiles recibidos
                     $.each(response, function(index, profile) {
                         // Construir el texto de la opción (Título Perfil (Título Cuenta))
                         let optionText = profile.title;
                         if (profile.account_title) {
                             optionText += ' (' + profile.account_title + ')';
                         }
                         // Añadir la opción al select
                         $select.append($('<option>', {
                             value: profile.id,
                             text: optionText
                         }));
                     });
                     // Habilitar el select si hay opciones
                     $select.prop('disabled', false);
                 } else {
                     // Si no hay perfiles, mostrar mensaje y mantener deshabilitado
                     $select.append($('<option>', { value: '', text: posBaseParams.i18n.no_profiles_available || 'No hay perfiles disponibles' }));
                 }

                // --- INICIO: Inicializar Select2 (Simplificado) ---
                // Asegurarse de que el elemento existe y tiene la clase 'select2'
                if ($select.length && $select.hasClass('select2')) {
                    try {
                        console.log('[Streaming App DEBUG] Attempting to initialize Select2 on #pos-streaming-profile-select...'); // Log 4
                        // Llamada estándar de inicialización
                        $select.select2({
                            width: '100%' // Asegurar que ocupe el ancho
                        });
                        console.log('[Streaming App DEBUG] Select2 initialization call completed.'); // Log 5
                    } catch (e) {
                        console.error('[Streaming App DEBUG] ERROR initializing Select2:', e); // Log 6
                    }
                } else {
                     console.warn('[Streaming App DEBUG] Select element not found or missing .select2 class for initialization.');
                }
             },
             error: function(jqXHR, textStatus, errorThrown) {
                 // Manejar errores de la llamada AJAX
                 console.error('Error loading available profiles via REST:', textStatus, errorThrown, jqXHR.responseText);
                 $loadingSpinner.hide();
                 // Mostrar mensaje de error en el select y en el span de error
                 $select.empty().append($('<option>', { value: '', text: posBaseParams.i18n.error_loading || 'Error al cargar' }));
                 $errorSpan.text(posBaseParams.i18n.error_loading || 'Error al cargar perfiles.').show();
             }
         });
         // --- Fin REST API ---

         // --- Bloque wp.ajax.send (Comentado porque no lo usamos) ---
         /*
         wp.ajax.send( 'pos_base_get_available_profiles', { // Nombre de acción inventado, mejor usar REST
             success: function(response) {
                 // ... (lógica original) ...
             },
             error: function(error) {
                 // ... (lógica original) ...
             }
         });
         */
         // --- Fin bloque wp.ajax.send ---

    } // Fin loadAvailableProfiles

    // Ejecutar cuando el DOM esté listo
    $(document).ready(function() {
        const $subscriptionFields = $('#pos-subscription-fields');
        const $profileSelectorWrap = $('.pos-streaming-profile-selector-wrap'); // Usar la clase del contenedor

        // Ocultar el selector de perfiles inicialmente
        if ($profileSelectorWrap.length) {
             $profileSelectorWrap.hide();
        }

        // Escuchar cambios en el tipo de venta
        $('#pos-sale-type').on('change', function() {
            const saleType = $(this).val();
            if (saleType === 'subscription') {
                // Mostrar nuestro selector y cargar perfiles
                if ($profileSelectorWrap.length) {
                    $profileSelectorWrap.slideDown();
                    loadAvailableProfiles(); // Llamar a la función que usa REST
                }
            } else {
                // Ocultar nuestro selector
                 if ($profileSelectorWrap.length) {
                    $('#pos-streaming-profile-select').prop('disabled', true).val(''); // Resetear y deshabilitar
                    $profileSelectorWrap.slideUp();
                }
            }
        });

        // --- Modificar la recolección de datos para la venta ---
        // ESTA PARTE SIGUE SIENDO UN EJEMPLO Y NECESITA INTEGRACIÓN
        // Idealmente, engancharse a un filtro/evento de app.js
        // Ejemplo TEMPORAL:
        /*
        // Suponiendo que tienes acceso a la variable 'orderData' antes de enviarla
        // y que la llamada AJAX se hace en una función como 'sendOrderRequest(orderData)'

        // Podrías interceptar o modificar 'sendOrderRequest' o el objeto 'orderData'
        function prepareOrderDataBeforeSend(originalOrderData) {
            let modifiedOrderData = $.extend(true, {}, originalOrderData); // Clonar objeto

            if ($('#pos-sale-type').val() === 'subscription') {
                const selectedProfileId = $('#pos-streaming-profile-select').val();
                if (selectedProfileId) {
                    if (!modifiedOrderData.meta_data) {
                        modifiedOrderData.meta_data = [];
                    } else if (!Array.isArray(modifiedOrderData.meta_data)) {
                         modifiedOrderData.meta_data = [];
                    }
                    // Evitar duplicados si ya existe
                    modifiedOrderData.meta_data = modifiedOrderData.meta_data.filter(meta => meta.key !== '_pos_assigned_profile_id');
                    // Añadir el metadato
                    modifiedOrderData.meta_data.push({
                        key: '_pos_assigned_profile_id',
                        value: selectedProfileId
                    });
                    console.log('Added profile ID to order data:', selectedProfileId);
                } else {
                     console.warn('No profile selected for subscription sale.');
                     // Podrías mostrar un error aquí y detener el proceso
                     // Swal.fire('Error', 'Debes seleccionar un perfil para la suscripción.', 'error');
                     // throw new Error('Profile selection required'); // Lanzar error para detener
                }
            }
            return modifiedOrderData;
        }

        // Luego, donde se llame a la función que envía el pedido:
        // const finalOrderData = prepareOrderDataBeforeSend(orderData);
        // sendOrderRequest(finalOrderData);
        */
        // --- Fin Modificación Temporal ---

    }); // Fin document.ready

}(jQuery));
