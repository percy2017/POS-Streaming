# POS Streaming for WooCommerce 🛒🎬

![WordPress Requires At Least](https://img.shields.io/badge/WordPress->=5.6-blue.svg)
![WooCommerce Tested Up To](https://img.shields.io/badge/WooCommerce-<8.x-purple.svg)
![PHP Requires At Least](https://img.shields.io/badge/PHP->=7.4-blueviolet.svg)
![License](https://img.shields.io/badge/License-GPL--2.0--or--later-red.svg)

Un Punto de Venta (POS) responsive diseñado para WordPress/WooCommerce, específicamente adaptado para gestionar ventas de cuentas y perfiles de servicios de streaming.

Desarrollado por [Ing. Percy Alvarez](https://percyalvarez.com/).

## Descripción ✨

POS Streaming proporciona una interfaz de punto de venta rápida y fácil de usar directamente en tu panel de administración de WordPress. Permite a los vendedores gestionar eficientemente la venta de productos (como cuentas o perfiles de servicios de streaming) configurados en WooCommerce, manejar clientes, aplicar descuentos y registrar detalles de suscripción para seguimiento.

## Características Principales 🚀

*   **Interfaz POS Responsive:** Accede y gestiona ventas desde diferentes dispositivos.
*   **Búsqueda de Productos:** Encuentra productos rápidamente por nombre o SKU.
*   **Soporte para Variaciones:** Añade variaciones específicas de productos al carrito.
*   **Gestión de Clientes:**
    *   Busca clientes existentes (nombre, email, teléfono).
    *   Añade nuevos clientes sobre la marcha.
    *   Edita la información del cliente.
    *   Sube y gestiona avatares personalizados para clientes.
*   **Carrito de Compras Dinámico:**
    *   Añade/quita productos y variaciones.
    *   **Precios Editables:** Modifica el precio unitario de los productos directamente en el carrito del POS.
*   **Aplicación de Cupones:** Valida y aplica cupones de descuento de WooCommerce.
*   **Tipos de Venta:**
    *   Registra ventas como Directas, a Crédito o **Suscripciones**.
*   **Seguimiento de Suscripciones:**
    *   Captura Título, Fecha de Vencimiento y Color para ventas tipo suscripción.
*   **Calendario de Vencimientos:**
    *   Visualiza las fechas de vencimiento de las suscripciones en un calendario integrado (FullCalendar).
    *   Haz clic en eventos para ir directamente al pedido relacionado.
*   **Integración con WooCommerce:**
    *   Utiliza productos y clientes de WooCommerce.
    *   Carga pasarelas de pago activas.
    *   Crea pedidos de WooCommerce al completar la venta.
*   **API REST:** Comunicación eficiente entre el frontend (JavaScript) y el backend (PHP).
*   **Metabox de Pedido:** Muestra los detalles específicos del POS (Tipo de Venta, Detalles de Suscripción) en la pantalla de edición del pedido.

## Requisitos 📋

*   WordPress: 5.6 o superior
*   WooCommerce: 6.0 o superior (Probado hasta 8.x)
*   PHP: 7.4 o superior

## Instalación 🛠️

1.  **Descarga:** Descarga el archivo `.zip` del plugin desde el repositorio de GitHub (o donde lo distribuyas).
2.  **Sube a WordPress:**
    *   Ve a tu panel de administración de WordPress -> Plugins -> Añadir nuevo.
    *   Haz clic en "Subir plugin".
    *   Selecciona el archivo `.zip` descargado y haz clic en "Instalar ahora".
3.  **Activa:** Una vez instalado, haz clic en "Activar plugin".

*Alternativamente, puedes descomprimir el archivo `.zip` y subir la carpeta `pos-streaming` directamente a tu directorio `/wp-content/plugins/` usando FTP, y luego activar el plugin desde el panel de administración.*

## Uso 🖱️

1.  Una vez activado, aparecerá un nuevo menú en tu panel de administración llamado **"POS Streaming"**.
2.  Haz clic en él para acceder a la interfaz del Punto de Venta.
3.  **Interfaz:**
    *   **Izquierda:** Busca productos, añade productos/variaciones al carrito, visualiza el calendario de vencimientos.
    *   **Derecha:** Busca/añade/edita clientes, gestiona el carrito (edita precios, quita items), selecciona tipo de venta, introduce detalles de suscripción (si aplica), selecciona método de pago, aplica cupones y completa la venta.

## Funcionalidad de Suscripción y Calendario 📅

*   Cuando seleccionas el tipo de venta "Suscripción" en el área de pago, aparecerán campos adicionales:
    *   **Título (Calendario):** El texto que se mostrará en el evento del calendario (Ej: "Netflix - Juan Perez").
    *   **Fecha Vencimiento:** La fecha en que vence la suscripción.
    *   **Color Evento:** El color de fondo para este evento en el calendario.
*   Al completar la venta, estos datos se guardan como metadatos del pedido.
*   El calendario en la columna izquierda consulta estos pedidos y muestra los eventos en sus respectivas fechas de vencimiento.
*   Hacer clic en un evento del calendario te llevará a la pantalla de edición del pedido correspondiente.

## Screenshots 📸

<!-- Añade aquí enlaces a imágenes o GIFs mostrando la interfaz -->
<!-- Ejemplo:
!Interfaz Principal
!Modal Cliente
!Calendario
-->

*(Próximamente se añadirán capturas de pantalla)*

## Posibles Mejoras Futuras 💡

*   Integración más detallada con gestión de stock de WooCommerce.
*   Roles y permisos específicos para acceder al POS.
*   Informes básicos de ventas realizadas a través del POS.
*   Soporte para múltiples cajas o vendedores.
*   Opciones de configuración (ej: pasarela por defecto, tipo de venta por defecto).
*   Mejoras en la interfaz de usuario y experiencia.

## Contribuciones 🤝

¡Las contribuciones son bienvenidas! Si encuentras un error o tienes una idea para mejorar el plugin, por favor:

1.  Busca si ya existe un issue similar en la pestaña Issues.
2.  Si no existe, crea un nuevo issue detallando el problema o la sugerencia.
3.  Si quieres contribuir con código, por favor haz un Fork del repositorio, crea una nueva rama para tu funcionalidad/corrección y envía un Pull Request.

## Licencia 📄

Este plugin está licenciado bajo la **GPL v2 or later**.
Consulta el archivo `LICENSE` (o el encabezado del plugin) para más detalles.

---

Desarrollado con ❤️ por Ing. Percy Alvarez.
