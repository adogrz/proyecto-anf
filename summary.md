# Resumen de la Sesión

Esta sesión se centró en la implementación de un sistema de roles y permisos para la aplicación RatioView, una herramienta de análisis financiero.

## Implementación de Permisos con Spatie

*   Se instaló y configuró el paquete `spatie/laravel-permission` para gestionar roles y permisos.
*   Se crearon migraciones para las tablas de roles y permisos.

## Definición de Roles y Permisos

Se definieron los siguientes roles y permisos, específicos para una aplicación financiera:

*   **Roles:**
    *   `Analista de Datos`: Puede subir y analizar estados financieros, y generar informes.
    *   `Gerente Financiero`: Tiene todos los permisos de un Analista de Datos, y además puede gestionar estados financieros, proyecciones y análisis proforma.
    *   `Auditor`: Tiene acceso de solo lectura a estados financieros e informes.
    *   `Administrador`: Tiene acceso completo a todas las funcionalidades de la aplicación, incluyendo la gestión de usuarios.

*   **Permisos:** Se crearon permisos granulares para acciones como `ver`, `crear`, `editar` y `eliminar` en los diferentes módulos de la aplicación (catálogos, estados financieros, proyecciones, etc.).

## Interfaz de Usuario

*   Se modificó la barra lateral de la aplicación (`app-sidebar.tsx`) para que sea dinámica y muestre las opciones del menú según los permisos del usuario autenticado.
*   Se actualizó el nombre de la aplicación a "RatioView".
*   Se creó un nuevo logo y favicon para la aplicación.

## Datos de Prueba

*   Se crearon usuarios de prueba para cada uno de los roles definidos, con las siguientes credenciales:
    *   **Administrador:** `admin@localhost.com` / `admin`
    *   **Gerente Financiero:** `gerente@localhost.com` / `password`
    *   **Analista de Datos:** `analista@localhost.com` / `password`
    *   **Auditor:** `auditor@localhost.com` / `password`

## Documentación

*   Se creó un archivo `README.md` con una guía de instalación y una descripción detallada de los roles y permisos implementados.


## Aspectos clave del sistema a considerar (requerimientos del cliente)

* Este sistema emitirá Informes de análisis financieros de empresas de un sector particular predefinido (algo similar a la razón social de las empresas salvadoreñas). Esta tipificación(sector) será fundamentada, para este apartado el grupo puede considerar otras realidades como la 
chilena, mexicana, estadounidense o de otro país que posea información de los ratios financieros (o razones financieras) por sector.  Se definirán parámetros de comparación de los ratios financieros por sector (ejemplo prueba acida=0.55 para sector minas) y en base a un promedio de todos los datos ya introducidos, por ejemplo, si ya se introdujo información financiera de 4 empresas, y sus ratios de prueba acida son 0.5, 0.6, 0.7 y 0.8 , su promedio será  0.65. Así al comparar las 4 empresas A, B, C y D y realizar un análisis de ratios financieros en la prueba Acida aparecerá como empresas que lo cumplen la B, C y D si se considera la ratio por sector y C y D si consideramos los promedios de las empresas procesadas por el sistema. El sistema considerara que se puedan introducir los catálogos contables de la empresa, una sola vez, estableciendo cuáles serán las cuentas que requerirán las ratios financieros para realizar sus cálculos.  Ejemplo para la empresa A la cuenta 11 seria Activos corrientes y para la empresa B podría ser 1.1. En pocas palabras no se utilizará un catálogo estático para que sea utilizado para las n empresas. Solo será estático (la estructura de catálogo) para la empresa particular, y será  introducida una sola vez.  Se considerará comparación horizontal la hecha de dos o más años. El sistema debe de graficar adicionalmente las variaciones de una cuenta seleccionable en un periodo establecido  ejemplo: cuenta de activo circulante, periodo 2020-2024. Proyección de Ventas Este sistema deberá tener un modulo que permita realizar una proyección de 12 meses de ventas basándose en los datos históricos subidos, los métodos a considerar son Mínimos cuadrados, Incremento porcentual e incremento absoluto.  Las entradas de datos (12 meses) podrán ser digitadas o subidas con un archivo Excel, si los datos generados son solo 11, el sistema debe generar el mes 12. 