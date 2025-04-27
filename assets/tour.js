/* global jQuery, posBaseTourData */
(function($) {
    'use strict';

    // Función para abrir un pointer específico
    function openPointer(index, pointerData) {
        // Obtener las opciones del pointer actual
        var options = $.extend(pointerData.options, {
            // Función que se ejecuta cuando se hace clic en un botón del pointer
            buttons: function(event, t) {
                var button = $(event.target).closest('.button'); // Obtener el botón presionado

                // Si se hizo clic en 'Cerrar' o en el botón 'X' del tooltip
                if (button.hasClass('pos-tour-close-btn') || button.hasClass('close')) {
                    t.element.pointer('close');
                    // Aquí podríamos añadir la llamada AJAX para marcar el tour como visto
                    // dismissTour();
                }
                // Si se hizo clic en 'Siguiente'
                else if (button.hasClass('pos-tour-next-btn')) {
                    t.element.pointer('close'); // Cerrar el actual
                    var nextIndex = index + 1; // Calcular índice del siguiente paso

                    // Si hay un siguiente paso, abrirlo
                    if (nextIndex < pointerKeys.length) {
                        openPointer(nextIndex, pointers[pointerKeys[nextIndex]]);
                    } else {
                        // Si es el último paso, el botón 'Siguiente' también cierra
                        // Aquí también podríamos marcar el tour como visto
                        // dismissTour();
                    }
                }
            },
            // Función que se ejecuta al cerrar el pointer (ej: con la 'X')
            close: function() {
                // Aquí también podríamos marcar el tour como visto si se cierra manualmente
                // dismissTour();
            }
        });

        // Verificar si el elemento target existe en la página
        var $targetElement = $(pointerData.target);
        if ($targetElement.length === 0) {
            console.warn('POS Tour: Target element not found for step:', pointerData.target);
            // Podríamos intentar saltar al siguiente paso si el target no se encuentra
            var nextIndex = index + 1;
            if (nextIndex < pointerKeys.length) {
                // Esperar un poco por si el elemento aparece más tarde (ej: carga AJAX)
                setTimeout(function() {
                    openPointer(nextIndex, pointers[pointerKeys[nextIndex]]);
                }, 500);
            }
            return; // No mostrar este pointer si el target no existe
        }

        // Ajustar el texto del botón 'Siguiente' si es el último paso
        if (index === pointerKeys.length - 1) {
            // Buscar el botón 'next' en las opciones y cambiar su etiqueta
            $.each(options.buttons, function(i, button) {
                if (button.name === 'next') {
                    button.label = posBaseTourData.i18n?.finish || 'Finalizar'; // Usar i18n si lo añadimos
                    return false; // Salir del bucle $.each
                }
            });
        }

        // Abrir el pointer asociado al elemento target
        $targetElement.pointer(options).pointer('open');
        console.log('POS Tour: Abriendo paso ' + (index + 1) + ' apuntando a ' + pointerData.target);

    } // Fin de openPointer

    // Función para marcar el tour como visto (implementación futura con AJAX)
    /*
    function dismissTour() {
        console.log('POS Tour: Dismissing tour...');
        $.post(posBaseTourData.ajax_url, {
            action: 'pos_base_dismiss_tour', // Nombre de la acción AJAX en PHP
            _ajax_nonce: posBaseTourData.nonce // Nonce de seguridad
        })
        .done(function(response) {
            if(response.success) {
                console.log('POS Tour: Dismissed successfully.');
            } else {
                console.error('POS Tour: Failed to dismiss.', response);
            }
        })
        .fail(function() {
            console.error('POS Tour: AJAX request failed.');
        });
    }
    */

    // --- Ejecución Principal ---
    $(document).ready(function() {
        // Verificar si los datos del tour están disponibles
        if (typeof posBaseTourData === 'undefined' || typeof posBaseTourData.pointers === 'undefined') {
            console.log('POS Tour: No tour data found.');
            return; // No hacer nada si no hay datos
        }

        // Obtener los pointers y sus claves (IDs)
        window.pointers = posBaseTourData.pointers; // Hacer global para fácil acceso en callbacks
        window.pointerKeys = Object.keys(pointers); // Obtener ['pos_step_1', 'pos_step_2', ...]

        // Si hay pasos definidos, iniciar el tour abriendo el primer paso
        if (pointerKeys.length > 0) {
            // Esperar un poco para asegurar que la interfaz esté cargada
            setTimeout(function() {
                openPointer(0, pointers[pointerKeys[0]]);
            }, 800); // Ajustar este tiempo si es necesario
        }
    });

}(jQuery));
