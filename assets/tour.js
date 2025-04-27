/* global jQuery, posBaseTourData */
(function($) {
    'use strict';

    // Variable para evitar múltiples llamadas AJAX al descartar
    var dismissingTour = false;
    // Variable global para rastrear el índice del paso actual
    var currentTourIndex = -1;

    // Función para abrir un pointer específico
    function openPointer(index, pointerData) {
        // Actualizar índice global
        currentTourIndex = index;

        // Verificar si los datos del pointer son válidos
        if (!pointerData || !pointerData.target || !pointerData.options) {
            console.error('POS Tour: Datos inválidos para el paso ' + index, pointerData);
            var nextIndex = index + 1;
            if (nextIndex < pointerKeys.length) {
                setTimeout(function() { openPointer(nextIndex, pointers[pointerKeys[nextIndex]]); }, 100);
            }
            return;
        }

        // Clonar las opciones originales de PHP para referencia
        let originalPointerOptions = $.extend(true, {}, pointerData.options);

        // --- Opciones Finales para wp-pointer ---
        // Crear la definición base de los botones que viene de PHP
        let buttonDefinitionArray = $.extend(true, [], originalPointerOptions.buttons || []);

        // Ajustar etiqueta a 'Finalizar' si es el último paso
        if (currentTourIndex === pointerKeys.length - 1) {
            $.each(buttonDefinitionArray, function(i, buttonDef) {
                if (buttonDef.name === 'next') {
                    buttonDef.label = posBaseTourData.i18n?.finish || 'Finalizar';
                    return false;
                }
            });
        }

        // Opciones que SÍ pasamos a wp-pointer:
        var options = {
            content: originalPointerOptions.content,
            position: originalPointerOptions.position,
            // PASAMOS LA FUNCIÓN buttons que devuelve la ESTRUCTURA para renderizar
            buttons: function(event, t) {
                console.log('POS Tour: buttons callback (renderizado). Event:', event, 'Instance:', t);
                var $buttons = $();
                $.each(buttonDefinitionArray, function(i, buttonDef) {
                    var $button = $('<button type="button" class="button"></button>')
                        .addClass(buttonDef.class || '')
                        .html(buttonDef.label || '');
                    if (buttonDef.name) {
                        $button.attr('data-button-name', buttonDef.name);
                    }
                    $buttons = $buttons.add($button);
                });
                console.log('POS Tour: Devolviendo elementos de botón para renderizar.');
                return $buttons;
            }
            // --- ELIMINAMOS EL CALLBACK 'close' ---
            // close: function() { ... } // <-- ELIMINADO
        };

        // Verificar si el elemento target existe en la página
        var $targetElement = $(pointerData.target);
        if ($targetElement.length === 0) {
            console.warn('POS Tour: Target element not found for step:', pointerData.target);
            var nextIndexSkip = index + 1;
            if (nextIndexSkip < pointerKeys.length) {
                setTimeout(function() { openPointer(nextIndexSkip, pointers[pointerKeys[nextIndexSkip]]); }, 500);
            } else {
                 dismissTour();
            }
            return;
        }

        // Abrir el pointer asociado al elemento target
        if ($targetElement.data('wp-pointer')) {
             $targetElement.pointer('destroy');
        }
        $targetElement.pointer(options).pointer('open');
        console.log('POS Tour: Abriendo paso ' + (index + 1) + ' apuntando a ' + pointerData.target);

    } // Fin de openPointer

    // Función para marcar el tour como visto (implementación con AJAX)
    function dismissTour() {
        if (dismissingTour) { return; }
        if (typeof posBaseTourData === 'undefined' || !posBaseTourData.ajax_url || !posBaseTourData.nonce) {
            console.error('POS Tour: Faltan datos AJAX para descartar.');
            return;
        }
        dismissingTour = true;
        console.log('POS Tour: Dismissing tour via AJAX...');
        $.post(posBaseTourData.ajax_url, {
            action: 'pos_base_dismiss_tour',
            _ajax_nonce: posBaseTourData.nonce
        })
        .done(function(response) {
            if(response && response.success) { console.log('POS Tour: Dismissed successfully via AJAX.'); }
            else { console.error('POS Tour: Failed to dismiss via AJAX.', response); }
        })
        .fail(function(jqXHR, textStatus, errorThrown) { console.error('POS Tour: AJAX request failed.', textStatus, errorThrown); })
        .always(function() {
            console.log('POS Tour: AJAX dismiss call finished.');
        });
    }


    // --- Ejecución Principal ---
    $(document).ready(function() {
        // Verificar si los datos del tour están disponibles
        if (typeof posBaseTourData === 'undefined' || typeof posBaseTourData.pointers === 'undefined' || $.isEmptyObject(posBaseTourData.pointers)) {
            console.log('POS Tour: No tour data found or pointers object is empty.');
            return;
        }

        // Obtener los pointers y sus claves (IDs)
        window.pointers = posBaseTourData.pointers;
        window.pointerKeys = Object.keys(pointers);

        // Si hay pasos definidos, iniciar el tour abriendo el primer paso
        if (pointerKeys.length > 0) {
            setTimeout(function() {
                openPointer(0, pointers[pointerKeys[0]]);
            }, 800);
        } else {
            console.log('POS Tour: No pointer steps defined.');
        }

        // --- MANEJO DE CLICS EN BOTONES (DELEGADO - ¡LA SOLUCIÓN!) ---
        // Escuchar clics en el documento, pero solo actuar si el origen es un botón DENTRO de un pointer
        // O el botón de cierre nativo (X)
        $(document).on('click', '.wp-pointer-content .button, .wp-pointer-close', function(event) {
            event.preventDefault();
            event.stopPropagation(); // Detener propagación para evitar conflictos

            var $clickedElement = $(this);
            // Encontrar el tooltip del pointer
            var $pointer = $clickedElement.closest('.wp-pointer');
            if (!$pointer.length) return; // Salir si no estamos dentro de un pointer

            // Encontrar el elemento al que apunta este pointer
            var $targetElement = null;
            if (typeof $.find_pointer_target === 'function') {
                 $targetElement = $.find_pointer_target($pointer);
            } else {
                console.warn("POS Tour: $.find_pointer_target no disponible.");
            }

            // Cerrar el pointer actual (siempre que se haga clic en un botón o la X)
            if ($targetElement && $targetElement.length) {
                 console.log('POS Tour: Cerrando pointer asociado a:', $targetElement);
                 try { $targetElement.pointer('close'); } catch(e) { console.error("Error closing pointer via target:", e); }
            } else {
                console.warn('POS Tour: No se pudo encontrar el target del pointer actual para cerrarlo. Intentando remover tooltip.');
                 try { $pointer.remove(); } catch(e){} // Como último recurso, quitar el tooltip
            }

            console.log('POS Tour: Click delegado detectado en:', $clickedElement.attr('class'));

            // Determinar qué botón/elemento se presionó
            if ($clickedElement.hasClass('pos-tour-next-btn')) {
                // Botón Siguiente/Finalizar
                var nextIndex = currentTourIndex + 1;
                if (nextIndex < pointerKeys.length) {
                    // Abrir siguiente paso
                    console.log('POS Tour: Abriendo siguiente paso:', nextIndex);
                    setTimeout(function() {
                        openPointer(nextIndex, pointers[pointerKeys[nextIndex]]);
                    }, 50); // Pequeña demora
                } else {
                    // Era el último paso (Finalizar)
                    console.log('POS Tour: Finalizando tour.');
                    dismissTour();
                }
            } else if ($clickedElement.hasClass('pos-tour-close-btn') || $clickedElement.hasClass('wp-pointer-close')) {
                // Botón Cerrar (el nuestro) O el botón 'X' nativo
                console.log('POS Tour: Cerrando tour (botón nuestro o X).');
                dismissTour();
            }
        });
        // --- FIN MANEJO DE CLICS ---

    }); // Fin document ready

}(jQuery));
