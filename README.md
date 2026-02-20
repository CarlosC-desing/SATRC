# 🏛️ Sistema de Registro Civil - Municipio Peña

![Estado del Proyecto](https://img.shields.io/badge/Estado-En_Desarrollo-success)
![PHP Version](https://img.shields.io/badge/PHP-8.0+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-orange.svg)

Un sistema de automatización web desarrollado para optimizar y digitalizar la gestión de actas y solicitudes en el Registro Civil del Municipio Peña. Diseñado con una arquitectura MVC simplificada y enfoque en la seguridad, trazabilidad de usuarios y experiencia de usuario (UI/UX).

## ✨ Características Principales

* **🔐 Control de Acceso y Seguridad:** Autenticación de usuarios con protección CSRF y gestión de roles (Administrador / Operador).
* **📄 Gestión de Actas:** Creación, edición y búsqueda de actas de:
  * Nacimiento
  * Unión Estable de Hecho
  * Matrimonio
  * Defunción
* **👥 Registro de Personas:** Base de datos centralizada para ciudadanos registrados en el sistema.
* **📊 Panel de Solicitudes:** Seguimiento en tiempo real de nuevas solicitudes con notificaciones visuales (badges).
* **📈 Reportes y Estadísticas:** Generación de reportes dinámicos y un Dashboard resumen para la toma de decisiones.
* **📝 Trazabilidad (Bitácora):** Registro automático de acciones de los usuarios (login, creación de documentos, errores) exclusivo para administradores.

## 🛠️ Tecnologías Utilizadas

* **Frontend:** HTML5, CSS3 (Variables, Flexbox/Grid), JavaScript Vanilla.
* **Backend:** PHP nativo (Gestión de sesiones, PDO/MySQLi).
* **Base de Datos:** MySQL / MariaDB.
* **Servidor:** Apache (Configuración estricta de rutas vía `.htaccess`).

## 📂 Estructura del Proyecto

El proyecto utiliza un enrutamiento centralizado hacia el directorio `public/` para proteger el código fuente.

```text
├── assets/          # Hojas de estilo (CSS), imágenes (SVG, PNG) y scripts JS
├── functions/       # Lógica de negocio (ej. registrar_log.php)
├── includes/        # Archivos críticos (Conexión a BD, variables de entorno)
├── modules/         # Módulos del sistema (Login, Actas, Reportes, Solicitudes)
├── public/          # Punto de entrada público (index.php, dashboard)
├── uploads/         # Almacenamiento de expedientes y archivos subidos
├── vendor/          # Dependencias de terceros instaladas por Composer
└── .htaccess        # Reglas de seguridad y redirección Apache
```
##🚀 Instalación y Despliegue
 Requisitos Previos
  * Servidor web Apache (XAMPP/Laragon para desarrollo local).
  * PHP 8.0 o superior.
  * MySQL o MariaDB.
  * Composer (opcional, dependiendo de las dependencias en vendor/).

Pasos para desarrollo local
 *Clona este repositorio en tu carpeta htdocs o www.
 * Importa la base de datos: Ejecuta el archivo .sql en tu gestor (ej.phpMyAdmin).
 * Configura el entorno: Renombra o edita includes/db/config.php con tus credenciales:

PHP
```
define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_clave');
define('DB_NAME', 'registro_civil');
define('BASE_URL', 'http://localhost/tu_carpeta/');
```
Despliegue en Producción (Ej. InfinityFree / Hostinger)
Sube todos los archivos respetando la estructura (omitiendo carpetas locales como node_modules).

Configura las variables en config.php con los datos proporcionados por tu proveedor (Host, Usuario DB, Password DB).

Asegúrate de que el archivo .htaccess esté en la raíz del servidor (htdocs/ o public_html/) para enrutar el tráfico correctamente a /public/index.php.

* 👨‍💻 Autor
Carlos Canelón - Desarrollo Full Stack y Diseño UI/UX

