# Manual de Usuario - POS Base

¡Bienvenido a POS Base! Este manual te guiará a través de las funcionalidades principales de nuestro sistema de Punto de Venta (POS) integrado en WordPress y WooCommerce.

## 1. ¿Qué es POS Base?

POS Base te permite gestionar ventas directamente desde tu panel de administración de WordPress, de forma similar a una caja registradora física, pero aprovechando tus productos y clientes de WooCommerce. Además, puede ser extendido con módulos para adaptarse a necesidades específicas de tu negocio (como gestión de suscripciones de streaming, reservas de hotel, etc.).

## 2. Instalación y Acceso

*   **Requisito:** Debes tener **WooCommerce instalado y activo** en tu sitio WordPress.
*   **Instalación:** Tu administrador habrá instalado el plugin "POS Base".
*   **Acceso:** Una vez activo, encontrarás un nuevo menú llamado "**POS Base**" en la barra lateral izquierda de tu panel de administración de WordPress. Haz clic en él para acceder a la interfaz principal del punto de venta.

## 3. Interfaz Principal

Al entrar en "POS Base", verás una interfaz dividida en tres pestañas principales:

*   **POS:** Aquí es donde realizarás la mayoría de las operaciones de venta.
*   **Calendario:** Muestra los próximos vencimientos de las suscripciones vendidas a través del POS.
*   **Ventas:** Un historial detallado de todas las ventas realizadas desde el POS.

## 4. Realizar una Venta (Pestaña POS)

La pestaña POS está dividida en dos columnas principales: Productos a la izquierda y Cliente/Carrito/Pago a la derecha. Sigue estos pasos para realizar una venta:

### 4.1. Seleccionar o Añadir un Cliente

*   **Buscar Cliente:** Usa la barra de búsqueda en la sección "Cliente" para encontrar un cliente existente por nombre, email o teléfono. Haz clic en el cliente deseado en los resultados para seleccionarlo.
*   **Añadir Nuevo Cliente:** Si el cliente no existe, haz clic en el botón "**Añadir Nuevo Cliente**". Se abrirá una ventana donde podrás ingresar su Nombre, Apellido, Email, Teléfono y una nota opcional.
    *   **Avatar:** Puedes hacer clic en "Cambiar Imagen" para subir o seleccionar una foto para el cliente.
    *   Haz clic en "**Guardar Cliente**". El nuevo cliente será seleccionado automáticamente.
*   **Editar Cliente:** Una vez seleccionado un cliente, puedes hacer clic en "**Editar**" para modificar sus datos.
*   **Cambiar Cliente:** Haz clic en "**Cambiar**" para volver a la búsqueda y seleccionar otro cliente.
*   **Cliente Invitado:** Si no seleccionas ningún cliente, la venta se registrará a nombre de un cliente "Invitado" genérico.

### 4.2. Añadir Productos al Carrito

*   **Buscar Producto:** Usa la barra de búsqueda en la columna izquierda ("Productos") para encontrar productos por nombre o SKU.
*   **Productos Destacados:** Al inicio, se mostrarán los productos marcados como "destacados" en WooCommerce.
*   **Añadir Producto Simple:** Haz clic en el botón "**Añadir**" junto al producto deseado.
*   **Añadir Producto Variable:** Si un producto tiene opciones (ej: talla, color), aparecerá un desplegable. Selecciona la opción deseada y luego haz clic en "**Añadir**".

### 4.3. Gestionar el Carrito

En la sección "Carrito" (columna derecha), verás los productos añadidos:

*   **Cambiar Cantidad:** Haz clic en los botones `+` o `-` junto a la cantidad, o escribe directamente en el campo numérico.
*   **Modificar Precio:** Haz clic directamente sobre el precio unitario del producto en el carrito. Se convertirá en un campo editable. Introduce el nuevo precio y presiona Enter o haz clic fuera.
*   **Eliminar Producto:** Haz clic en el icono de la papelera (`🗑️`) junto al producto que deseas quitar.
*   **Totales:** El subtotal y el total se actualizan automáticamente.

### 4.4. Proceso de Pago y Checkout

En la sección "Pago":

1.  **Tipo de Venta:** Selecciona el tipo de venta:
    *   **Directo:** Una venta normal de productos.
    *   **Suscripción:** Para ventas que representan una suscripción (requiere llenar campos adicionales).
    *   **Crédito:** Si la venta no se paga inmediatamente (el pedido quedará "En Espera").
2.  **Detalles de Suscripción (Si aplica):** Si seleccionaste "Suscripción", aparecerán campos adicionales:
    *   **Título (Calendario):** Un nombre descriptivo para el evento en el calendario (ej: "Netflix Juan Perez").
    *   **Fecha Vencimiento:** La fecha en que esta suscripción expira.
    *   **Color Evento:** El color con el que aparecerá este vencimiento en el calendario.
    *   *(Pueden aparecer más campos aquí si hay módulos específicos activados, como un selector de perfiles)*.
3.  **Método de Pago:** Selecciona cómo pagó el cliente (ej: Efectivo / Manual, Transferencia, etc.). Las opciones disponibles dependen de la configuración de WooCommerce y del POS.
4.  **Cupón:** Si el cliente tiene un código de cupón, ingrésalo en el campo "Código de Cupón" y haz clic en "**Aplicar**". Si es válido, el descuento se reflejará en el total. Puedes quitarlo haciendo clic en "Quitar cupón".
5.  **Nota del Pedido:** Puedes añadir una nota interna sobre la venta (no visible para el cliente por defecto).
6.  **Completar Venta:** Una vez que todo esté correcto, haz clic en el botón "**Completar Venta**". El sistema creará el pedido en WooCommerce y limpiará la interfaz para la siguiente venta.

## 5. Consultar el Calendario (Pestaña Calendario)

*   Esta pestaña muestra un calendario visual.
*   Los eventos que aparecen aquí corresponden a las **fechas de vencimiento** que ingresaste al realizar ventas de tipo "Suscripción".
*   Cada evento muestra el título que le diste y tiene el color que seleccionaste.
*   Puedes hacer clic en un evento para ver más detalles (como un enlace al pedido original).

## 6. Revisar Ventas Anteriores (Pestaña Ventas)

*   Esta pestaña muestra una tabla con el historial de todos los pedidos creados desde el POS.
*   **Columnas:**
    *   **Pedido #:** Número del pedido en WooCommerce (con enlace para ver detalles).
    *   **Fecha:** Fecha y hora de la venta.
    *   **Cliente:** Nombre del cliente (con enlace a su perfil si no es invitado).
    *   **Total:** Monto total de la venta.
    *   **Tipo (POS):** El tipo de venta seleccionado (Directo, Suscripción, Crédito).
    *   **Notas:** Un extracto de la última nota añadida al pedido.
    *   **Meta:** Información adicional relevante. Para suscripciones, mostrará el Título, Fecha de Vencimiento y Color. *(Puede mostrar más información si hay módulos activados)*.
*   **Funcionalidades:**
    *   **Buscar:** Usa el campo "Buscar" arriba a la derecha para filtrar la tabla por cualquier dato (número de pedido, nombre de cliente, etc.).
    *   **Mostrar Registros:** Cambia cuántas ventas quieres ver por página.
    *   **Ordenar:** Haz clic en las cabeceras de las columnas (Pedido #, Fecha, Cliente, Total, Tipo) para ordenar la tabla.
    *   **Paginación:** Usa los botones "Anterior" y "Siguiente" para navegar entre las páginas de resultados.

## 7. Configuración (Menú POS Base -> Configuración)

*   Esta sección es generalmente manejada por el administrador del sitio.
*   Permite activar o desactivar **módulos** que añaden funcionalidades extra al POS Base (ej: módulo para streaming, módulo para hoteles).
*   Las funcionalidades específicas que veas en la interfaz del POS (especialmente en el checkout o en la columna "Meta" de ventas) pueden depender de qué módulos estén activos.

---

Si tienes alguna duda o problema, por favor, contacta al administrador de tu sitio web.
