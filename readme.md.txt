# CostoPyme
## Sistema de gestión de costos, producción y rentabilidad para pequeñas empresas

### 1. Descripción general

CostoPyme es una aplicación web diseñada para ayudar a pequeñas empresas y emprendimientos a administrar sus productos y servicios, registrar costos, controlar la producción, gestionar promociones y analizar la rentabilidad del negocio.

El objetivo principal es facilitar la toma de decisiones comerciales mediante información organizada, cálculos de costos y herramientas de simulación de precios.

La aplicación contempla diferentes rubros comerciales, entre ellos:

- Gastronomía.
- Textil.
- Servicios.

El sistema permite trabajar con diferentes usuarios dentro de una misma empresa, con permisos y responsabilidades según el rol asignado.

---

### 2. Tecnologías utilizadas

CostoPyme está desarrollado utilizando tecnologías web tradicionales, con una arquitectura basada en PHP y MySQL.

| Tecnología | Utilización |
|---|---|
| PHP | Programación del servidor, procesamiento de formularios, validaciones, permisos y cálculos. |
| MySQL | Almacenamiento de usuarios, empresas, productos, costos, promociones y registros operativos. |
| HTML5 | Estructura y contenido de las páginas. |
| CSS3 | Diseño visual, distribución de los elementos y adaptación a diferentes tamaños de pantalla. |
| JavaScript | Interactividad, validaciones en el navegador, cálculos dinámicos y gráficos. |
| SQL | Creación, consulta y modificación de las tablas de la base de datos. |

**Arquitectura general:**

- Frontend: HTML, CSS y JavaScript.
- Backend: PHP.
- Base de datos: MySQL.
- Comunicación entre las pantallas y el servidor mediante formularios y endpoints PHP.

---

### 3. Roles de usuario y permisos

El sistema cuenta con tres roles principales. Cada uno tiene diferentes responsabilidades y niveles de acceso.

#### 3.1. Administrador

Es el responsable principal de la empresa dentro de CostoPyme.

Sus funciones incluyen:

- Registrar una empresa.
- Informar el nombre comercial, CUIT y rubro.
- Administrar los usuarios de la empresa.
- Generar y revocar invitaciones.
- Activar o desactivar usuarios.
- Gestionar productos y servicios.
- Administrar promociones y descuentos.
- Gestionar costos y comisiones.
- Supervisar y aprobar registros pendientes.
- Consultar reportes.
- Utilizar el simulador de rentabilidad.

#### 3.2. Encargado

Es el usuario responsable de la gestión operativa y comercial del negocio.

Sus funciones incluyen:

- Administrar productos y servicios, según los permisos implementados.
- Registrar y actualizar costos.
- Gestionar promociones.
- Configurar comisiones.
- Registrar información de producción.
- Revisar los registros realizados por los operadores.
- Aprobar o rechazar costos y datos de producción pendientes.
- Consultar reportes y utilizar el simulador.

#### 3.3. Operador

Es el usuario destinado a cargar información operativa.

Sus funciones incluyen:

- Registrar costos.
- Cargar información de producción.
- Consultar la información disponible según sus permisos.
- Modificar sus propios registros mientras se encuentren pendientes de aprobación.

Una vez que un encargado o administrador aprueba o rechaza un registro, el operador deja de poder modificarlo.

#### 3.4. Flujo de aprobación

Los registros realizados por operadores tienen un estado de aprobación.

Los estados principales son:

- **Pendiente:** el registro está esperando revisión.
- **Aprobado:** el encargado o administrador confirmó la información.
- **Rechazado:** la información fue rechazada por el responsable.

Los registros pendientes no se consideran información aprobada para los cálculos y reportes que utilizan datos validados.

---

### 4. Registro de empresas y usuarios

El registro inicial permite seleccionar el rol del usuario.

**Registro de administrador:**

- Nombre y datos personales.
- Datos de acceso.
- Nombre de la empresa.
- CUIT.
- Rubro comercial.

El CUIT se valida mediante su cantidad de dígitos y su dígito verificador.

**Registro de encargado u operador:**

- Datos personales.
- Datos de acceso.
- Código de invitación.
- Nombre de la empresa.
- CUIT de la empresa.

El sistema verifica que los datos de la empresa coincidan con los asociados a la invitación.

Esto permite incorporar usuarios a una empresa existente sin crear empresas duplicadas.

---

### 5. Módulo Productos / Servicios

Permite administrar el catálogo comercial de la empresa.

Entre sus funcionalidades se encuentran:

- Crear productos y servicios.
- Editar sus datos.
- Definir su descripción.
- Establecer unidades de comercialización.
- Registrar volúmenes.
- Definir márgenes de rentabilidad.
- Cargar información de mano de obra propia.
- Consultar los costos asociados a cada producto o servicio.

El sistema contempla tanto productos vendidos por unidad como servicios comercializados por hora u otras unidades de trabajo.

---

### 6. Módulo Costos

Permite registrar y organizar los costos necesarios para determinar la rentabilidad.

Contempla:

- Costos variables por unidad.
- Costos correspondientes a un período.
- Costos asociados a la producción.
- Mano de obra propia.
- Costos operativos.
- Revisión de registros pendientes.
- Aprobación y rechazo de registros.

Es importante diferenciar los costos unitarios de los importes totales correspondientes a un período para evitar errores en los cálculos.

#### Comisiones comerciales

Dentro del módulo también se pueden configurar comisiones relacionadas con:

- Marketplaces.
- Medios de pago.
- Canales de venta.

Cada comisión puede contemplar:

- Porcentaje.
- Importe fijo.
- Moneda.
- IVA.
- Base de cálculo.
- Configuración específica para determinados productos.

Estas comisiones se utilizan para analizar cuánto dinero queda realmente después de vender un producto o servicio.

---

### 7. Módulo Promociones y descuentos

Permite crear promociones comerciales para mejorar las ventas sin perder el control de la rentabilidad.

Las promociones pueden aplicarse a:

- Un producto o servicio individual.
- Todos los productos o servicios seleccionados.

También se contempla la posibilidad de incluir automáticamente productos futuros en promociones generales.

Los tipos de promociones contemplados incluyen:

- Descuentos porcentuales.
- Descuentos por importe fijo.
- Precios mayoristas.
- Combos.

Cada promoción puede tener:

- Fecha de inicio.
- Fecha de finalización.
- Estado activo o pausado.
- Productos asociados.
- Condiciones comerciales.

#### Reglas comerciales

El sistema contempla las siguientes reglas:

- No acumular promociones entre sí.
- Dar prioridad a una promoción individual frente a una promoción general.
- Finalizar automáticamente la vigencia de las promociones vencidas.
- Controlar el margen mínimo permitido.
- Evitar precios negativos o márgenes inferiores al mínimo configurado.

---

### 8. Módulo Producción

Permite registrar información operativa de producción.

Contempla:

- Carga de datos de producción.
- Organización por período mensual.
- Registro de información por operadores.
- Edición de registros propios mientras estén pendientes.
- Aprobación o rechazo por encargados y administradores.

Esta información permite organizar el seguimiento de la actividad productiva del negocio.

---

### 9. Módulo Simulador

Es una de las herramientas principales de CostoPyme.

Permite analizar diferentes alternativas comerciales antes de definir un precio de venta.

El simulador contempla hasta tres escenarios editables para comparar distintas alternativas.

Entre los datos y resultados disponibles se incluyen:

- Producto o servicio seleccionado.
- Costos asociados.
- Precio sugerido.
- Precio alternativo ingresado manualmente.
- Margen real.
- Contribución.
- Punto de equilibrio.
- Facturación necesaria.
- Aplicación de promociones.
- Aplicación de comisiones comerciales.

También incorpora una tabla comparativa y una representación gráfica de los escenarios.

El objetivo es ayudar al usuario a identificar qué precio resulta conveniente y qué rentabilidad puede obtener.

---

### 10. Módulo Reportes

Permite consultar información económica y operativa para evaluar el negocio.

Incluye información relacionada con:

- Costos.
- Rentabilidad.
- Resultados comerciales.
- Punto de equilibrio.
- Facturación necesaria para alcanzar el equilibrio.

Los reportes utilizan los registros aprobados cuando corresponde, evitando incorporar datos pendientes como si fueran definitivos.

También contempla opciones de exportación de información.

---

### 11. Usuarios e invitaciones

El módulo permite administrar el acceso de las personas que trabajan dentro de la empresa.

Sus funcionalidades incluyen:

- Consultar usuarios registrados.
- Generar códigos de invitación.
- Revocar invitaciones.
- Activar usuarios.
- Desactivar usuarios.
- Asociar nuevos integrantes a una empresa existente.

El sistema utiliza el rol del usuario para determinar qué funcionalidades puede utilizar.

---

### 12. Mi perfil y cierre de sesión

La aplicación dispone de un espacio de perfil para identificar al usuario conectado.

También incorpora un mecanismo de cierre de sesión que:

- Elimina la sesión activa.
- Invalida el token de acceso recordado, cuando corresponde.
- Redirige al usuario a la pantalla de inicio de sesión.

---

### 13. Seguridad y validaciones

La aplicación contempla diferentes controles para proteger la información y mantener la consistencia de los datos.

Entre ellos:

- Validación del CUIT.
- Validación de invitaciones.
- Verificación de permisos por rol.
- Restricción de operaciones según el usuario.
- Control de aprobación de registros.
- Validaciones del lado del servidor.
- Gestión de sesiones.
- Protección de operaciones de modificación.

Las validaciones realizadas en el servidor son especialmente importantes, ya que no dependen solamente de lo que el usuario pueda modificar desde el navegador.

---

### 14. Configuración e instalación

Para ejecutar CostoPyme se necesita un entorno compatible con PHP y MySQL.

Requisitos generales:

- Servidor web compatible con PHP.
- PHP con las extensiones requeridas por la aplicación.
- Servidor MySQL o MariaDB compatible.
- Navegador web actualizado.

#### Configuración de base de datos

Los datos de conexión se administran mediante variables de entorno:

- `COSTOPYME_DB_HOST`
- `COSTOPYME_DB_NAME`
- `COSTOPYME_DB_USER`
- `COSTOPYME_DB_PASSWORD`

Se debe configurar el entorno con las credenciales correspondientes.

#### Base de datos existente

Si se instala sobre una base de datos anterior, se debe realizar previamente una copia de seguridad y ejecutar las migraciones SQL incluidas en el proyecto cuando corresponda.

La migración de roles, promociones y comisiones se encuentra documentada en:

`migraciones/2026_11_roles_promociones_comisiones.sql`

Se recomienda revisar la documentación de instalación incluida en el proyecto antes de realizar cambios en producción.

---

### 15. Consideraciones importantes

CostoPyme está pensado como una herramienta de gestión y análisis para pequeñas empresas.

Los resultados del simulador y los reportes dependen de la calidad y actualización de los datos cargados.

Para obtener resultados confiables es importante:

- Mantener actualizados los costos.
- Registrar correctamente los volúmenes.
- Configurar las comisiones reales.
- Revisar las promociones activas.
- Aprobar los registros pendientes.
- Controlar periódicamente los márgenes de rentabilidad.

La aplicación cuenta con verificaciones estáticas de código, pero antes de utilizarla en producción es necesario realizar pruebas completas con una base de datos real, usuarios de cada rol y operaciones comerciales reales.

---

### 16. Objetivo final del sistema

CostoPyme busca centralizar la información económica y operativa de una pequeña empresa en una única herramienta.

Su propósito es que el negocio pueda:

1. Conocer cuánto cuesta producir o prestar un servicio.
2. Definir precios de venta con criterios de rentabilidad.
3. Evaluar promociones sin comprometer los márgenes.
4. Incorporar las comisiones de venta y medios de pago.
5. Controlar la producción.
6. Organizar el trabajo entre administradores, encargados y operadores.
7. Analizar escenarios comerciales antes de tomar decisiones.
8. Consultar información para mejorar la gestión del negocio.

**CostoPyme — Gestión y rentabilidad para pequeñas empresas.**