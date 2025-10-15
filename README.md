# RatioView

RatioView es una herramienta de análisis financiero diseñada para ayudarte a analizar estados financieros, realizar análisis de ratios, comparativas con estándares de la industria, crear proyecciones y realizar análisis proforma de presupuestos de caja.

## Instalación

1.  Clona el repositorio:
    ```bash
    git clone https://github.com/your-username/your-repository.git
    ```
2.  Instala las dependencias de PHP:
    ```bash
    composer install
    ```
3.  Instala las dependencias de Node.js:
    ```bash
    npm install
    ```
4.  Crea una copia del archivo `.env.example` y llámalo `.env`:
    ```bash
    cp .env.example .env
    ```
5.  Genera una clave de aplicación:
    ```bash
    php artisan key:generate
    ```
6.  Configura tu base de datos en el archivo `.env`.
7.  Ejecuta las migraciones y los seeders de la base de datos:
    ```bash
    php artisan migrate:fresh --seed
    ```
8.  Inicia el servidor de desarrollo:
    ```bash
    npm run dev
    ```

## Roles y Permisos

RatioView utiliza un sistema de control de acceso basado en roles para gestionar los permisos de los usuarios. Aquí están los roles definidos:

*   **Analista de Datos**: Este rol es para usuarios que necesitan analizar datos financieros. Pueden:
    *   Subir estados financieros.
    *   Ver y analizar datos financieros.
    *   Generar informes.

*   **Gerente Financiero**: Este rol es para usuarios que gestionan los datos financieros y las proyecciones. Tienen todos los permisos de un Analista de Datos, y además pueden:
    *   Crear, editar y eliminar estados financieros.
    *   Gestionar proyecciones y análisis proforma.

*   **Auditor**: Este rol es para usuarios que necesitan revisar los datos financieros con fines de auditoría. Tienen acceso de solo lectura a:
    *   Estados financieros.
    *   Informes.

*   **Administrador**: Este rol tiene acceso completo a la aplicación, incluyendo:
    *   Gestión de usuarios y sus roles.
    *   Configuraciones y ajustes del sistema.
