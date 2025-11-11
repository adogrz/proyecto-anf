# 📘 Descripción Narrada de los Elementos que Debe Considerar el Sistema

## 🧮 Análisis Financiero

Este sistema emitirá informes de análisis financieros de empresas pertenecientes a un sector particular predefinido (similar a la razón social de las empresas salvadoreñas).

- La tipificación del sector será fundamentada. El grupo puede considerar realidades de otros países como Chile, México, Estados Unidos, u otros que posean información confiable sobre ratios financieros por sector.
- Se definirán parámetros de comparación de ratios financieros por sector.  
  **Ejemplo:**  
  - Prueba ácida para el sector minas = 0.55  
  - Si se introducen ratios de prueba ácida de 4 empresas: 0.5, 0.6, 0.7 y 0.8 → Promedio = 0.65  
  - Comparación:  
    - Según ratio sectorial: cumplen B, C y D  
    - Según promedio interno: cumplen C y D

- El sistema permitirá introducir el catálogo contable de cada empresa una sola vez, definiendo las cuentas necesarias para calcular los ratios financieros.  
  **Ejemplo:**  
  - Empresa A: cuenta 11 = Activos corrientes  
  - Empresa B: cuenta 1.1 = Activos corrientes  
  No se usará un catálogo contable estático para todas las empresas.

- Se considerará como comparación horizontal aquella que involucre dos o más años.
- El sistema debe graficar las variaciones de una cuenta seleccionable en un periodo determinado.  
  **Ejemplo:** Activo circulante entre 2020 y 2024.

---

## 📈 Proyección de Ventas

El sistema incluirá un módulo para proyectar ventas durante 12 meses, basado en datos históricos.

- Métodos a considerar:
  - Mínimos cuadrados
  - Incremento porcentual
  - Incremento absoluto

- Las entradas de datos podrán ser:
  - Digitadas manualmente
  - Subidas mediante archivo Excel

- Si se ingresan solo 11 meses, el sistema debe generar el mes 12 automáticamente.

---

## 📝 Elementos Importantes para la Evaluación del Proyecto

- **Funcionalidad:**  
  La aplicación debe realizar correctamente todas las operaciones que se indican.

- **Entrada de datos financieros:**  
  Debe permitir ingreso manual o mediante carga masiva (Excel u otro formato).

- **Código compartido:**  
  No debe existir código compartido entre grupos. Si se detecta y se demuestra dominio del sistema, la nota máxima será de 1 punto sobre 10.

---

## 💡 Valores Agregados (Opcionales, +10%)

### 🔐 Opción 1: Seguridad y Creatividad

- Seguridad:  
  Implementación de ventana de contraseña al inicio, validación de acceso según permisos del usuario.

- Creatividad:  
  Inclusión de componentes móviles, librerías de voz, u otros elementos innovadores.

### 🧾 Opción 2: Seguimiento de Sistemas Contables

- Ampliar el sistema para soportar múltiples empresas.
- Definir un catálogo de tipos de empresa para asociarlas.
- Basarse en el sistema desarrollado en la asignatura "Sistemas Contables" para obtener hasta 10 puntos adicionales.
