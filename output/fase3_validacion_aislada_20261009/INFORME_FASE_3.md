# Fase 3 — Preparación y validación de la regularización histórica

Fecha de elaboración: 2026-10-09  
Alcance: preparación técnica y documental. Este informe no constituye una regularización, no registra una constancia y no modifica información productiva.

## 1. Validación aislada del respaldo de Fase 2

### Origen preservado

- Respaldo original: `C:\laragon\www\BienesInformaticos1\respaldos\fase2_recuperacion_20261009_113036\bienes_informaticos.sql`
- SHA-256 del original: `94D8ED7FC01F30088A379C11064176548B4201E2A5798E5E656FBE1F2D658862`
- Tamaño: 125,084 bytes.
- El respaldo original contiene `DROP DATABASE`, `CREATE DATABASE` y `USE bienes_informaticos`; por seguridad no se ejecutó directamente.
- No se alteró el archivo SQL de Fase 2, la copia privada de archivos ni su manifiesto. El respaldo Fase 2 ya acreditaba 16 archivos copiados, con 16 coincidencias SHA-256.

### Entorno aislado y procedimiento aplicado

Se verificó antes de restaurar que la base `prueba` era una base distinta de `bienes_informaticos` y no contenía tablas. Se generó una copia derivada, no una modificación del original:

- Copia sanitizada: `C:\laragon\www\BienesInformaticos1\output\fase3_validacion_aislada_20261009\restauracion_prueba.sql`
- SHA-256 de la copia: `FEEE129224EBCDE7D1F52E8793BF920D778F7ED7D297ABDD0B0A37C8546BC622`
- Tamaño: 124,831 bytes.
- Se retiraron únicamente las tres directivas que podrían seleccionar o recrear la base productiva: `DROP DATABASE`, `CREATE DATABASE` y `USE bienes_informaticos`.
- Se comprobó que la copia no conserva esas directivas y se ejecutó sólo contra `prueba`.

La restauración aislada concluyó con 30 tablas. La base `prueba` queda como ambiente de validación, no como fuente para operaciones administrativas ni productivas.

### Resultado de las comparaciones

| Comprobación | Resultado |
| --- | --- |
| Cantidad de tablas | 30 en producción y 30 en `prueba` |
| Definición de columnas | Sin diferencias entre ambas bases |
| TR-000007 | `COMPLETADO`; bien 2; resguardo origen 9; destino 32; movimiento 53; fecha de completado `2026-10-07 17:22:59` en ambas bases |
| TR-000008 | `VALIDADO`; bien 3; resguardo origen 10; sin movimiento ni resguardo destino, en ambas bases |
| TR-000007: documentos / encadenados / eventos | 8 / 4 / 13 en ambas bases |
| TR-000008: documentos / encadenados / eventos | 19 / 15 / 25 en ambas bases |
| Huella de documentos TR-000007 | Coincide: `facaf5300bc650b6e158ddadee914849e31f862267842bec04f71a34c3bc732b` |
| Huella de documentos TR-000008 | Coincide: `737ea1e8a06123a14df6e03343d8b5083e7920a6589eeed8784cca554c21f046` |
| Huella de eventos TR-000007 | Coincide: `bc76ef95de889ba9ed794bb43684b715a480e2a58d8266b6d9132b6a86a3b971` |
| Huella de eventos TR-000008 | Coincide: `73a51e303f607f089968a5d0668a072014035f01c8b2374a20dfa7dea54878b5` |
| Movimiento y resguardo | Movimiento 53 y resguardo 32 coinciden: bien 2, resguardo nuevo 32, fecha `2026-10-07 17:22:59`, tipo `Traspaso TR-000007` |

Para repetir la restauración de forma segura se requiere un destino confirmado como no productivo, vacío o desechable, y una copia sanitizada nueva. Nunca debe redirigirse el SQL original a `bienes_informaticos`.

## 2. Inventario del mecanismo de auditoría existente

| Componente | Función actual | Observación para una constancia administrativa |
| --- | --- | --- |
| Tabla `traspaso_evento` | Bitácora de operaciones por traspaso: usuario, tipo, estados anterior/nuevo, motivo, metadatos y fecha. | Es una bitácora operativa; cada alta atribuye un usuario y se inserta desde el modelo. No existe tipo ni flujo específico de constancia administrativa. |
| `TraspasoModel::eventoEn` | Inserta eventos; es privado y se invoca dentro de operaciones transaccionales. | No debe invocarse para crear un evento retroactivo ni para reutilizar un evento de evidencia. |
| `TraspasoModel::validarEvidencia` | Sólo permite validar en estado `VALIDADO`, exige documento origen y evidencia vigente disponibles e íntegros, y registra `VALIDACION_EVIDENCIA`. | Incompatible con TR-000007: los archivos están ausentes. Usarlo alteraría indebidamente la semántica histórica. |
| `TraspasoModel::rechazarEvidencia` | Registra `RECHAZO_EVIDENCIA` sobre una evidencia vigente. | No representa una incidencia documental ni una regularización. |
| `traspasosController::validar_evidencia` | Exige rol administrativo y permiso `traspasos-autorizar`. | No hay permiso independiente para registrar constancias o incidencias. |
| Vista de detalle | Construye el estado de evidencia exclusivamente a partir de eventos `VALIDACION_EVIDENCIA` y `RECHAZO_EVIDENCIA`. | Una constancia futura no debe presentarse como validación de evidencia. |

No se localizó una tabla, modelo, controlador, permiso ni servicio específico de incidencias documentales o constancias administrativas. Aunque `tipo_evento` admite texto, usar la bitácora genérica hoy atribuiría una acción y fecha de sistema a un expediente histórico; por ello no es un mecanismo compatible sin una autorización y diseño expresos.

Una futura autorización debería definir, como mínimo, un registro separado y de sólo adición para una constancia actual: folio relacionado, fecha de registro actual, autoridad responsable actual, causa documentada, referencias a identificadores históricos, soporte de la investigación y prohibición expresa de alterar estados, hashes, versiones, movimientos o resguardos. También requeriría permiso propio, criterios de aprobación y revisión jurídica/administrativa. Esta recomendación no se implementó.

## 3. Borrador de constancia administrativa — no registrada

**Asunto:** Resultado de revisión documental del expediente `TR-000007`.

Durante la revisión técnica realizada en Fase 2 y ratificada en Fase 3 se identificó que los siguientes registros documentales conservan identidad, metadatos y encadenamiento histórico en la base de datos, pero no cuentan con archivo físico localizable en el almacenamiento privado:

| Identificador | Tipo | Versión | Resultado físico actual |
| --- | --- | --- | --- |
| 14 | Oficio de autorización | 1 | Ausente |
| 16 | Tarjeta de resguardo | 1 | Ausente |
| 17 | Resguardo de equipo | 1 | Ausente |
| 18 | Baja de resguardante | 1 | Ausente |
| 20 | Baja de resguardante | 2 | Ausente |
| 21 | Baja de resguardante | 3 | Ausente |
| 22 | Resguardo de equipo | 2 | Ausente |
| 23 | Tarjeta de resguardo | 2 | Ausente |

Las búsquedas realizadas comprendieron el almacenamiento privado configurado, el respaldo y manifiesto de Fase 2, el árbol de trabajo y los respaldos históricos disponibles. No se localizaron fuentes auténticas de los ocho archivos. El único PDF localizado fuera del almacenamiento no coincidió con un hash documental registrado y no se consideró fuente candidata. La copia de Fase 2 acredita la integridad de los 16 archivos que sí existían al respaldarse; no recupera ni acredita contenido de los ocho archivos ausentes.

La comprobación de tamaño y SHA-256 no puede acreditarse para esos ocho registros porque sus bytes no están disponibles. Sí se verificó la existencia en el historial de `TR-000007` del movimiento patrimonial **#53** y del resguardo destino **#32**, ambos vinculados al bien 2 y fechados el `2026-10-07 17:22:59` (el resguardo tiene fecha de asignación `2026-10-07`). El expediente conserva estado `COMPLETADO`.

No existe en la bitácora del sistema un evento `VALIDACION_EVIDENCIA` para `TR-000007`; la consulta tampoco encontró ese evento para `TR-000008`. La ausencia de ese evento en el sistema **no demuestra por sí sola** que no hubiera ocurrido una validación externa, en papel u otro medio. Sólo acredita que no hay una validación verificable en la bitácora disponible.

**Limitaciones:** no se localizaron documentos auténticos para restaurar; no se reconstruyeron hechos, no se regeneraron archivos, no se registró una validación retroactiva y no se modificaron el estado, movimientos, resguardos, versiones, hashes ni metadatos.

**Fechas que deben mantenerse diferenciadas:**

- Hecho histórico registrado: finalización de `TR-000007`, `2026-10-07 17:22:59`.
- Detección técnica documentada: Fase 2, `2026-10-09` (el respaldo se completó el `2026-10-09 11:30:36`).
- Eventual regularización administrativa: fecha futura de registro y resolución, si una autoridad competente la autoriza; no debe presentarse como fecha del hecho histórico.

Este texto es un borrador de trabajo y no fue registrado en la base de datos ni atribuido a una persona usuaria.

## 4. Matriz de pruebas y resultados

| Caso | Método aislado o de sólo lectura | Resultado |
| --- | --- | --- |
| Archivo ausente | Prueba autónoma `tests\\TraspasoDocumentServiceAvailabilityTest.php` | `AUSENTE`; aprobado |
| Ruta fuera del almacenamiento | Misma prueba | `RUTA_INVALIDA`; aprobado |
| Hash o tamaño divergente | Misma prueba | `HASH_INVALIDO` y `TAMANO_INVALIDO`; aprobado |
| Archivo sin metadatos verificables | Misma prueba | `NO_VERIFICABLE`; aprobado |
| Persistencia fallida/corrupta | Misma prueba, directorio temporal | Se rechaza y limpia el archivo de prueba; aprobado |
| Presentación de un documento ausente | Inspección de `traspasosController::detalle` y `detalleView.twig` | Se conserva el registro y se muestra “Archivo no disponible”; enlaces de descarga/visualización sólo se renderizan si `archivo_disponible`; aprobado |
| Descarga de ausente | Inspección de `servirDocumento` | Respuesta controlada: 404 para `AUSENTE`, sin lectura ni descarga; aprobado por revisión de código |
| Identidad histórica | Consulta restaurada y vista | Los registros siguen presentes: TR-000007 tiene 8 documentos y 4 enlaces a predecesor; aprobado |
| Disponibilidad TR-000007 | Comparación tamaño/SHA-256 de sólo lectura | 8 de 8 registros ausentes; sin afirmación de integridad de bytes inexistentes |
| Disponibilidad TR-000008 | Comparación tamaño/SHA-256 de sólo lectura | #39, #40, #41, #47 y #48 disponibles e íntegros; 14 históricos ausentes |
| Estados y movimientos | Comparación producción/restauración y revisión de código | La detección no actualiza estados ni movimientos; TR-000007 sigue `COMPLETADO`, TR-000008 sigue `VALIDADO`; aprobado |

Las pruebas de interfaz se validaron por condiciones de controlador/plantilla y por pruebas de servicio aisladas. No se realizó una descarga HTTP autenticada contra el entorno productivo para no provocar cambios de sesión ni depender de un navegador autenticado; el controlador tiene validación previa de permisos y disponibilidad.

## 5. Riesgos pendientes y requisitos para una autorización futura

1. Los ocho archivos de `TR-000007` no tienen fuente auténtica localizada; la recuperación o sustitución no está justificada con la evidencia actual.
2. El estado `COMPLETADO` coexistiendo con ausencia documental es una anomalía histórica que no debe corregirse automáticamente.
3. No existe mecanismo específico para una constancia administrativa; implementar uno requerirá autorización expresa, revisión de datos/retención, diseño de permisos y pruebas en un entorno aislado.
4. Cualquier actuación futura debe registrar su fecha presente y autoridad actual, no suplantar la fecha ni la autoría del evento histórico.
5. Antes de cualquier cambio productivo deben aprobarse el texto final de la constancia, su fundamento administrativo, la persona responsable y un plan reversible de despliegue y auditoría.

## 6. Archivos creados en esta fase

- `C:\laragon\www\BienesInformaticos1\output\fase3_validacion_aislada_20261009\restauracion_prueba.sql` — copia sanitizada exclusiva para restaurar en `prueba`; no es el respaldo original.
- `C:\laragon\www\BienesInformaticos1\output\fase3_validacion_aislada_20261009\INFORME_FASE_3.md` — este informe.

No se modificó ningún archivo funcional, plantilla, migración, documento histórico, archivo del almacenamiento privado ni dato de `bienes_informaticos`.
