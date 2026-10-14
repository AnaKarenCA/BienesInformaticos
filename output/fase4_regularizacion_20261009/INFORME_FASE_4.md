# Fase 4 — Interfaz documental y preparación de constancias administrativas

Fecha: 2026-10-09  
Alcance: implementación de interfaz y pruebas aisladas. No se registró una constancia en producción ni se modificó información histórica.

## Cambios realizados

- El listado de traspasos ahora separa **Estado patrimonial** de un resumen de disponibilidad documental calculado con verificaciones reales.
- El detalle muestra, por documento, etiquetas accesibles de disponibilidad: Disponible e íntegro, Ausente, Ruta inválida, Integridad inconsistente, Integridad no verificada o Lectura no disponible.
- El detalle presenta un panel compacto de incidencia cuando existen anomalías. Incluye conteos reales, estado patrimonial sin alterarlo, fecha de detección sólo cuando hay constancia registrada y un borrador de lectura con sus limitaciones.
- Documentos históricos conservan identificador y versión. Las acciones Ver/Descargar siguen condicionadas a `archivo_disponible`; no se muestran rutas privadas.
- Se añadió el diseño institucional para las etiquetas y el panel: guinda, oro y fondos cálidos definidos en el requerimiento.
- Se creó un mecanismo separado de `traspaso_evento`: `traspaso_constancia_administrativa` y su relación con documentos. Es inmutable en esta fase, registra la fecha actual del sistema y la identidad de sesión, y no modifica traspasos, documentos, hashes, movimientos, resguardos ni eventos de evidencia.
- Se añadió el permiso específico `traspasos-regularizar-documentos`. El servidor lo verifica mediante `accionPost`, que además exige CSRF y sesión activa.
- Las escrituras de constancias están deshabilitadas por defecto. Sólo se habilitan con `BIENES_ENABLE_DOCUMENT_REGULARIZATION=1`; esta bandera se usó exclusivamente dentro de la prueba aislada.

## Migración y entorno aislado

La migración propuesta es:

`C:\laragon\www\BienesInformaticos1\database\migrations\20261009_constancias_regularizacion_documental.sql`

Se aplicó únicamente a la base `prueba`, después de confirmar el destino. Producción no contiene las tablas `traspaso_constancia_administrativa` ni `traspaso_constancia_documento`.

## Pruebas ejecutadas

| Prueba | Resultado |
| --- | --- |
| Sintaxis PHP de servicios, modelo, controladores y pruebas | Correcta |
| `tests\\TraspasoDocumentServiceAvailabilityTest.php` | Correcta |
| `tests\\TraspasoAdministrativeConstancyTest.php` en `prueba` | Correcta |
| Sintaxis de `indexView.twig` y `detalleView.twig` | Correcta |
| Constancia aislada con ocho documentos ausentes de TR-000007 | Se registra y relaciona los 8 identificadores |
| Selección incompleta/manipulada | Rechazada |
| Duplicado exacto | Rechazado por huella única |
| Bandera de entorno deshabilitada | Rechazada antes de escribir |
| Eventos, estado, movimiento y resguardo durante prueba | Sin cambios |
| Limpieza final de fixture `prueba` | 0 constancias residuales |
| Regresión productiva de TR-000007 | `COMPLETADO`, movimiento 53, resguardo 32 |
| Regresión productiva de TR-000008 | `VALIDADO`, sin movimiento ni resguardo destino |
| SHA-256 del respaldo Fase 2 | Sin cambio: `94D8ED7FC01F30088A379C11064176548B4201E2A5798E5E656FBE1F2D658862` |

## Archivos modificados o creados

- `C:\laragon\www\BienesInformaticos1\app\classes\TraspasoDocumentService.php`
- `C:\laragon\www\BienesInformaticos1\app\models\TraspasoModel.php`
- `C:\laragon\www\BienesInformaticos1\app\controllers\traspasosController.php`
- `C:\laragon\www\BienesInformaticos1\app\controllers\adminController.php`
- `C:\laragon\www\BienesInformaticos1\templates\views\traspasos\indexView.twig`
- `C:\laragon\www\BienesInformaticos1\templates\views\traspasos\detalleView.twig`
- `C:\laragon\www\BienesInformaticos1\assets\css\traspasos-detalle.css`
- `C:\laragon\www\BienesInformaticos1\database\migrations\20261009_constancias_regularizacion_documental.sql`
- `C:\laragon\www\BienesInformaticos1\tests\TraspasoAdministrativeConstancyTest.php`
- `C:\laragon\www\BienesInformaticos1\output\fase4_regularizacion_20261009\INFORME_FASE_4.md`

## Pendientes y autorización requerida

1. La migración no debe aplicarse a `bienes_informaticos` sin autorización expresa.
2. La bandera `BIENES_ENABLE_DOCUMENT_REGULARIZATION` debe permanecer sin habilitar en producción hasta autorizar el flujo, la persona responsable y la referencia administrativa correspondiente.
3. No se debe registrar automáticamente una constancia para TR-000007. Si se autoriza, el usuario con el permiso específico deberá revisar el borrador y efectuar una actuación presente con fecha e identidad reales.
4. La constancia no acredita una validación histórica externa ni sustituye archivos ausentes. La falta de un evento `VALIDACION_EVIDENCIA` sólo acredita que no existe ese evento en el sistema.
