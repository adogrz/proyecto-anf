# RatioView

RatioView es una herramienta de análisis financiero diseñada para analizar estados financieros, realizar análisis de ratios, comparativas con estándares de la industria, crear proyecciones y realizar análisis proforma de presupuestos de caja.

## Características Principales

*   **Análisis de Ratios Detallado:** Calcula automáticamente los ratios financieros clave de una empresa.
*   **Benchmarking Avanzado:** Compara los ratios de la empresa contra dos métricas clave:
    1.  **Estándares de la Industria:** Valores de referencia por sector industrial, basados en el 'Almanac Of Business And Industrial Financial Ratios'.
    2.  **Promedio del Sistema:** Comparativa contra el promedio de todas las empresas registradas en la plataforma.
*   **Análisis Cualitativo:** Ofrece interpretaciones de texto sobre la salud financiera de la empresa, explicando si los ratios son superiores, inferiores o similares al estándar del sector.
*   **Catálogo de Cuentas Flexible:** Cada empresa puede definir su propio catálogo de cuentas. El sistema incluye una herramienta de **mapeo** para vincular el catálogo de la empresa con un catálogo base estandarizado que el sistema usa para los cálculos.
*   **Importación desde Excel:** Facilita la carga de estados financieros a través de archivos Excel, reconociendo las cuentas por su código o nombre gracias al sistema de mapeo.
*   **Análisis Horizontal:** Permite comparar la evolución de los estados financieros de una empresa a lo largo de varios años.
*   **Proyecciones de Ventas:** Un módulo dedicado para proyectar ventas a 12 meses utilizando métodos de Mínimos Cuadrados, Incremento Porcentual e Incremento Absoluto.
*   **Gestión de Roles y Permisos:** Sistema de control de acceso robusto con roles predefinidos (Administrador, Gerente Financiero, Analista de Datos, Auditor).

## Futuras Mejoras

*   **Análisis con Inteligencia Artificial:** Se planea integrar un modelo de IA (vía Ollama o similar) como una opción para generar un análisis financiero cualitativo de forma automática, ofreciendo una alternativa a los textos predefinidos.

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