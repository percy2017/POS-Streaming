// JS for POS WhatsApp Floating Button & Checkout Button
document.addEventListener('DOMContentLoaded', function() {

    // --- Lógica Botón Flotante ---
    const fabButton = document.getElementById('pos-whatsapp-fab-button');
    const formPopup = document.getElementById('pos-whatsapp-form-popup');
    const closeButton = document.getElementById('pos-whatsapp-form-close');
    const contactForm = document.getElementById('pos-whatsapp-contact-form');
    const targetPhoneInput = document.getElementById('pos-whatsapp-target-phone'); // Input oculto con el número destino

    // Verificar elementos del botón flotante (solo si existen)
    if (fabButton && formPopup && closeButton && contactForm && targetPhoneInput) {
        // Mostrar/ocultar formulario al hacer clic en el botón FAB
        fabButton.addEventListener('click', function() {
            const isHidden = formPopup.style.display === 'none' || formPopup.style.display === '';
            formPopup.style.display = isHidden ? 'block' : 'none';
        });

        // Ocultar formulario al hacer clic en el botón de cerrar
        closeButton.addEventListener('click', function() {
            formPopup.style.display = 'none';
        });

        // Manejar el envío del formulario flotante
        contactForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const name = document.getElementById('pos-whatsapp-name').value.trim();
            const message = document.getElementById('pos-whatsapp-message').value.trim();
            const targetPhone = targetPhoneInput.value;

            if (!message) {
                alert('Por favor, escribe tu mensaje.');
                return;
            }

            let fullMessage = `Consulta desde Formulario Flotante:\nNombre: ${name}.\nMensaje: ${message}`;
            const whatsappUrl = `https://wa.me/${targetPhone}?text=${encodeURIComponent(fullMessage)}`;
            window.open(whatsappUrl, '_blank');
            formPopup.style.display = 'none';
        });

        // Opcional: Ocultar el popup si se hace clic fuera de él
        document.addEventListener('click', function(event) {
            if (formPopup.style.display === 'block' && !formPopup.contains(event.target) && !fabButton.contains(event.target)) {
                 formPopup.style.display = 'none';
            }
        });
    } else if (document.getElementById('pos-whatsapp-fab-container')) {
         console.error('POS WhatsApp Frontend Error: Elementos internos del widget flotante no encontrados. ¿Número de teléfono configurado?');
    }

    // --- Lógica Botón Checkout (CON EVENT DELEGATION) ---
    // Añadir listener al DOCUMENTO, no directamente al botón
    document.addEventListener('click', function(event) {

        // Verificar si el elemento clickeado es nuestro botón (o está dentro de él)
        const whatsappCheckoutBtn = event.target.closest('#pos-whatsapp-checkout-complete-button');

        if (whatsappCheckoutBtn) {
            // console.log('Botón Completar Pedido por WhatsApp clickeado (detectado por delegación).');
            event.preventDefault(); // Prevenir cualquier acción por defecto

            // Obtener número del ATRIBUTO DATA del botón
            const targetPhone = whatsappCheckoutBtn.dataset.targetPhone;

            if (!targetPhone) {
                alert('Error: No se pudo obtener el número de WhatsApp de destino desde el botón.');
                return;
            }

            // --- Recopilar Datos del Checkout ---
            let orderSummary = "Nuevo Pedido (Checkout WhatsApp):\n";
            orderSummary += "-----------------------------------\n";

            // Datos del Cliente
            const firstName = document.getElementById('billing_first_name')?.value || '';
            const lastName = document.getElementById('billing_last_name')?.value || '';
            const email = document.getElementById('billing_email')?.value || '';
            const phone = document.getElementById('billing_phone')?.value || '';
            const address1 = document.getElementById('billing_address_1')?.value || '';
            const address2 = document.getElementById('billing_address_2')?.value || '';
            const city = document.getElementById('billing_city')?.value || '';
            const stateEl = document.getElementById('billing_state');
            const state = stateEl?.value || '';
            const postcode = document.getElementById('billing_postcode')?.value || '';
            const countryEl = document.getElementById('billing_country');
            const country = countryEl?.value || '';

            // Construir resumen del cliente
            orderSummary += `Cliente: ${firstName} ${lastName}\n`;
            if (phone) orderSummary += `Teléfono: ${phone}\n`;
            if (email) orderSummary += `Email: ${email}\n`;
            if (address1) orderSummary += `Dirección: ${address1}${address2 ? ', ' + address2 : ''}\n`;
            if (city) orderSummary += `Ciudad: ${city}\n`;
            let stateText = state;
            if (state && stateEl?.options && stateEl.selectedIndex >= 0) {
                 stateText = stateEl.options[stateEl.selectedIndex]?.text || state;
            }
            if(stateText) orderSummary += `Región/Estado: ${stateText}\n`;
            if (postcode) orderSummary += `Cód. Postal: ${postcode}\n`;
            let countryText = country;
             if (country && countryEl?.options && countryEl.selectedIndex >= 0) {
                 countryText = countryEl.options[countryEl.selectedIndex]?.text || country;
            }
            if(countryText) orderSummary += `País: ${countryText}\n`;

            orderSummary += "-----------------------------------\nProductos:\n";

            // Datos del Pedido (Tabla de Revisión)
            const orderTableRows = document.querySelectorAll('table.woocommerce-checkout-review-order-table tbody tr.cart_item');
            orderTableRows.forEach((row, index) => {
                const productNameElement = row.querySelector('td.product-name');
                const quantityElement = productNameElement?.querySelector('.product-quantity');
                let productName = Array.from(productNameElement?.childNodes || []).filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent.trim()).join(' ').replace(/×$/, '').trim() || 'Producto Desconocido';
                let quantityText = quantityElement?.textContent.trim() || '';
                orderSummary += `- ${productName} ${quantityText}\n`;
            });

            // Obtener Total
            const totalElement = document.querySelector('table.woocommerce-checkout-review-order-table tfoot tr.order-total .woocommerce-Price-amount');
            const total = totalElement?.textContent.trim() || 'N/A';

            orderSummary += "-----------------------------------\nTotal: " + total + "\n"; // <-- CORREGIDO

             // Obtener Método de Envío
             const shippingMethodElement = document.querySelector('.woocommerce-shipping-methods input[type="radio"]:checked, .woocommerce-shipping-methods input[type="hidden"]');
             if (shippingMethodElement) {
                 const shippingLabel = document.querySelector(`label[for="${shippingMethodElement.id}"]`);
                 if (shippingLabel) {
                     let shippingText = shippingLabel.textContent.trim();
                     const priceSpan = shippingLabel.querySelector('.woocommerce-Price-amount');
                     if (priceSpan) {
                         shippingText = shippingText.replace(priceSpan.textContent, '').trim();
                     }
                     orderSummary += `Envío: ${shippingText}\n`;
                 } else if (shippingMethodElement.id && shippingMethodElement.id.startsWith('shipping_method_') && shippingMethodElement.value.includes('local_pickup')) {
                      orderSummary += `Envío: Recogida Local\n`;
                 }
             }

            // Obtener Método de Pago
            const paymentMethodElement = document.querySelector('.wc_payment_methods input[type="radio"]:checked');
             if (paymentMethodElement) {
                 const paymentLabel = document.querySelector(`label[for="${paymentMethodElement.id}"]`);
                 if (paymentLabel) {
                      let paymentText = Array.from(paymentLabel.childNodes).filter(node => node.nodeType === Node.TEXT_NODE).map(node => node.textContent.trim()).join(' ').trim();
                      if (!paymentText) paymentText = paymentLabel.textContent.trim();
                      orderSummary += `Pago: ${paymentText}\n`;
                 }
             }

            orderSummary += "-----------------------------------\n";

            // Notas del cliente
            const orderNotesElement = document.getElementById('order_comments');
            const orderNotes = orderNotesElement?.value || '';
            if (orderNotes) {
                orderSummary += `Notas del Cliente: ${orderNotes}\n`;
                orderSummary += "-----------------------------------\n";
            }

            // console.log('--- Resumen Final del Pedido para WhatsApp ---');
            // console.log(orderSummary);
            const whatsappUrl = `https://wa.me/${targetPhone}?text=${encodeURIComponent(orderSummary)}`;

            if (orderSummary.length > 1800) {
                alert('Advertencia: El resumen del pedido es muy largo y podría cortarse en WhatsApp.');
            }
            window.open(whatsappUrl, '_blank'); // Abrir WhatsApp

        } // Fin del if (whatsappCheckoutBtn)

    }); // Fin del listener en document

    // console.log('Listener de delegación para botón checkout configurado en document.');

});
