<?php
/** Plantilla institucional de dos páginas; los datos variables llegan del expediente/BD. */
$v = static function ($key) use ($fields, $e) {
  $value = trim((string)($fields[$key] ?? ''));
  return $value === '' ? '&nbsp;' : nl2br($e($value), false);
};
$months = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
$datePrinted = date('j') . ' de ' . $months[(int)date('n')] . ' de ' . date('Y');
$privacyUrl = 'https://cultura.edomex.gob.mx/sites/cultura.edomex.gob.mx/files/files/transparencia/avisos/3-SCTUR-SDP-2025%20Adquisici%C3%B3n%20de%20Equipos%20de%20C%C3%B3mputo%20para%20uso%20del%20personal%20asignado%20de%20la%20Secretar%C3%ADa%20de%20Cultura%20y%20Turismo..pdf';
$denseFirstPage = false;
foreach ($componentRows as $componentRow) {
  foreach (['marca', 'modelo', 'serie', 'inventario'] as $componentField) {
    if (strlen(trim((string)($componentRow[$componentField] ?? ''))) > 18) {
      $denseFirstPage = true;
      break 2;
    }
  }
}
?>
<style>
*{box-sizing:border-box} @page{size:letter portrait;margin:20mm 25mm}
body{font:8.4pt Arial,Helvetica,sans-serif;color:#111;margin:0;line-height:1.15}
.page-letterhead{position:absolute;top:-20mm;left:-25mm;width:215.9mm;height:auto;z-index:-1}
.page{position:relative;height:220mm;padding-bottom:16mm;page-break-after:always}
.page:last-child{page-break-after:auto}
.page-title{text-align:center;font-size:15pt;font-weight:bold;margin:0 0 3mm;text-transform:none}
.data-grid,.components{width:100%;max-width:100%;border-collapse:collapse;border:1px solid #000;font-size:10.5pt}
.data-grid{table-layout:fixed}
.components{table-layout:auto;word-wrap:break-word;word-break:break-word}
.data-grid th,.data-grid td,.components th,.components td{border:1px solid #000;padding:1mm 1.5mm;vertical-align:top;overflow-wrap:anywhere}
.components th,.components td{white-space:normal;word-wrap:break-word;word-break:break-word}
.data-grid th{width:33%;text-align:left}
.data-grid thead th,.components thead th{background:#800000;color:#fff;font-weight:bold}
.data-grid tbody th{font-weight:normal;color:#111}
.data-grid tbody tr:nth-child(odd)>*,.components tbody tr:nth-child(odd)>*{background:#ededed;color:#111}
.data-grid tbody tr:nth-child(even)>*,.components tbody tr:nth-child(even)>*{background:#fff;color:#111}
.data-grid td{width:67%;min-height:5mm}
.components{margin-top:4mm;margin-bottom:0}
.components thead th{text-align:center}
.agreement{margin:4.5mm 0 2.5mm;text-align:center;text-decoration:underline;font-weight:bold;font-size:8.5pt;line-height:1.15}
.intro{margin:0 0 1.5mm;text-align:justify;font-size:8.5pt;line-height:1.5}
.roman{margin:0;padding:0;list-style:none}
.roman li{margin:0 0 2mm;text-align:justify;line-height:1.5}
.roman .roman-label{font-weight:normal;display:inline}
.section-heading{text-align:center;font-weight:bold;margin:3mm 0 2.5mm;font-size:9.8pt;line-height:1.15}
.section-heading span{display:block}
.section-heading span + span{margin-top:1mm}
.alpha{margin:0 0 2mm;padding:0;list-style:none}
.alpha li{margin:0 0 1.5mm;text-align:justify;font-size:8.7pt;line-height:1.45}
.alpha .alpha-label{font-weight:normal;display:inline}
.privacy{margin:3mm 0 0;text-align:justify;overflow-wrap:anywhere;line-height:1.15}
a{color:#111;text-decoration:none}
.signatures{width:100%;border-collapse:collapse;table-layout:fixed;margin-top:6mm}
.signatures td{width:50%;padding:0 7mm;text-align:center;vertical-align:top}
.signatures .heading{height:12mm;font-weight:normal;font-size:8pt;line-height:1.15}
.signatures .heading--delivery{font-size:6.7pt}
.signatures .signature-space{height:6mm}
.signatures .line{border-top:1px solid #000;height:0}
.signatures .label{margin-top:1.5mm;font-size:8pt;line-height:1.15}
.page-footer{position:absolute;left:0;right:0;bottom:-3mm;width:100%;padding-top:2mm;font-size:7.5pt;color:#333}
.page-footer span:last-child{float:right}
.page--one .data-grid th,.page--one .data-grid td{padding-top:1.15mm;padding-bottom:1.15mm}
.page--one .components th,.page--one .components td{padding-top:1.15mm;padding-bottom:1.15mm}
.page--one .agreement-block{margin-top:4mm}
.page--one .agreement{margin-top:0;margin-bottom:3mm}
.page--one .intro{margin-bottom:1.5mm;line-height:1.4}
.page--one .roman li{margin-bottom:0.7mm;line-height:1.7}
.page--one .roman li:last-child{page-break-inside:avoid;page-break-after:avoid}
.page--one.is-dense .page-title{margin-bottom:2mm}
.page--one.is-dense .data-grid th,.page--one.is-dense .data-grid td,
.page--one.is-dense .components th,.page--one.is-dense .components td{padding-top:.8mm;padding-bottom:.8mm}
.page--one.is-dense .components{margin-top:3mm}
.page--one.is-dense .agreement-block{margin-top:2mm}
.page--one.is-dense .agreement{margin-bottom:1.5mm}
.page--one.is-dense .intro{margin-bottom:.5mm;line-height:1.25}
.page--one.is-dense .roman li{margin-bottom:.2mm;line-height:1.25}
</style>
<section class="page page--one compact<?= $denseFirstPage ? ' is-dense' : '' ?>" aria-label="Resguardo de equipo, página 1 de 2">
  <img class="page-letterhead" src="<?= $e($membrete) ?>" alt="">
  <h1 class="page-title">Resguardo del equipo</h1>
  <table class="data-grid" aria-label="Información del resguardo"><thead><tr><th>Información</th><th>Detalle</th></tr></thead><tbody>
    <?php foreach ($fields as $label => $_value): ?><tr><th scope="row"><?= $e($label) ?></th><td><?= $v($label) ?></td></tr><?php endforeach; ?>
  </tbody></table>
  
  <table class="components" aria-label="Componentes del equipo"><thead><tr><th>Componente</th><th>Marca</th><th>Modelo</th><th>Serie</th><th>Inventario</th></tr></thead><tbody>
    <?php foreach ($componentRows as $row): ?><tr><td><?= $e($row['componente']) ?></td><td><?= trim((string)($row['marca'] ?? '')) !== '' ? $e($row['marca']) : '&nbsp;' ?></td><td><?= trim((string)($row['modelo'] ?? '')) !== '' ? $e($row['modelo']) : '&nbsp;' ?></td><td><?= trim((string)($row['serie'] ?? '')) !== '' ? $e($row['serie']) : '&nbsp;' ?></td><td><?= trim((string)($row['inventario'] ?? '')) !== '' ? $e($row['inventario']) : '&nbsp;' ?></td></tr><?php endforeach; ?>
  </tbody></table>
  <div class="agreement-block">
  <h2 class="agreement">ACUERDO DE RESPONSABILIDAD PARA EL ACTIVO TECNOLÓGICO INSTITUCIONAL DE <br>LA SECRETARÍA DE CULTURA Y TURISMO DEL GOBIERNO DEL ESTADO DE MÉXICO.</h2>
  <p class="intro">Por medio del presente, manifiesto que he sido debidamente informado(a) y acepto que el equipo de cómputo identificado con número de control interno <?= $v('Clave interna') ?>, así como sus accesorios, me han sido entregados en perfectas condiciones para llevar a cabo el ejercicio de mis funciones dentro de la Secretaría de Cultura y Turismo del Gobierno del Estado de México, quedando bajo mi entera custodia y resguardo. En ese sentido, me doy por enterado que está prohibido realizar cualquiera de las siguientes conductas:</p>
  <ol class="roman">
    <li><span class="roman-label">I.</span> Dañar o maltratar el equipo de cómputo.</li>
    <li><span class="roman-label">II.</span> Reubicar el equipo de cómputo.</li>
    <li><span class="roman-label">III.</span> Manipular el hardware y software del equipo de cómputo descrito en el encabezado de la presente para intentar corregir fallas internas y/o modificar la configuración administrada por el Departamento de Tecnologías de la Información.</li>
    <li><span class="roman-label">IV.</span> Añadir o retirar algún componente interno.</li>
    <li><span class="roman-label">V.</span> Cubrir y/o tapar de cualquier manera los orificios de ventilación del monitor y/o del equipo de cómputo.</li>
    <li><span class="roman-label">VI.</span> Consumir alimentos y/o líquidos cerca del equipo de cómputo.</li>
    <li><span class="roman-label">VII.</span> Almacenar y compartir archivos personales de cualquier tipo en el equipo de cómputo (documentos, videos, imágenes, audios, juegos, entre otros).</li>
    <li><span class="roman-label">VIII.</span> Instalar cualquier tipo de software que no haya sido previamente autorizado de manera oficial y por escrito por el personal del Departamento de Tecnologías de la Información.</li>
  </ol></div>
  <footer class="page-footer"><span>Fecha de impresión: <?= $e($datePrinted) ?></span><span>Página 1 de 2</span></footer>
</section>
<section class="page page--two compact" aria-label="Resguardo de equipo, página 2 de 2">
  <ol class="roman" start="9">
    <li><span class="roman-label">IX.</span> Instalar software que trate de evadir, alterar, modificar y/o manipular los filtros de seguridad en la red local vía Ethernet o Wi-Fi configurados por el Departamento de Tecnologías de la Información</li>
    <li><span class="roman-label">X.</span> Ejecutar código y/o software malicioso que pueda llegar afectar el funcionamiento del equipo y que ponga en riesgo la información de la Secretaría.</li>
    <li><span class="roman-label">XI.</span> Cambiar la configuración del acceso a la red administrada por el Departamento de Tecnologías de la Información.</li>
  </ol>
   <br>
  <h2 class="section-heading"><span>Disposiciones para la Administración y Uso del Equipo de Cómputo de</span><span>la Secretaría de Cultura y Turismo del Gobierno del Estado de México:</span></h2>
   <br>
  <ol class="alpha" type="a">
    <li><span class="alpha-label">a)</span> La única instancia autorizada para llevar a cabo la administración y/o gestión del equipo de cómputo y sus bienes informáticos es el personal técnico del Departamento de Tecnologías de la Información adscrito a la Unidad de Información, Planeación, Programación y Evaluación de esta Secretaría de Cultura y Turismo.</li>
    <li><span class="alpha-label">b)</span> Cuando mi relación laboral con la Secretaría termine, me comprometo a notificar al Departamento de Tecnologías de la Información para que realice la revisión física del equipo de cómputo y sus accesorios, con la finalidad de que el Departamento de Tecnologías de la Información pueda estar en posibilidad de formalizar mi Certificado de No Adeudo, así como todas las gestiones administrativas correspondientes.</li>
    <li><span class="alpha-label">c)</span> En caso de ser necesario me comprometo a solicitar la reubicación y/o el cambio de Resguardatario del equipo de cómputo, informando al Departamento de Tecnologías de la Información sobre la reasignación del equipo de cómputo, a más tardar el día hábil siguiente al que se presente la incidencia.</li>
    <li><span class="alpha-label">d)</span> Una vez reasignado el bien, el nuevo resguardo deberá ser firmado por el nuevo servidor público usuario, quien será el nuevo responsable del equipo de cómputo y los bienes tecnológicos asignados.</li>
    <li><span class="alpha-label">e)</span> Me comprometo a realizar constantemente el respaldo de la información generada, recopilada, administrada, manejada, procesada, archivada y/o conservada en el equipo de cómputo bajo mi resguardo.</li>
    <li><span class="alpha-label">f)</span> Me comprometo a entregar a mi superior inmediato una copia del respaldo de la información generada, recopilada, administrada, manejada, procesada, archivada y/o conservada contenida en el equipo de cómputo bajo mi resguardo cuando me sea requerida y/o al término de relación laboral con la Secretaría de Cultura y Turismo. </li>
    <li><span class="alpha-label">g)</span> Me comprometo a reportar al personal técnico del Departamento de Tecnologías de la Información cualquier tipo de falla del equipo de cómputo y/o los bienes informáticos bajo mi resguardo, a través del mecanismo que establezca el Departamento de Tecnologías de la Información. </li>
    <li><span class="alpha-label">h)</span> En caso de que el equipo de cómputo y/o sus accesorios sean robados y/o extraviados, me comprometo a hacer el pago total del equipo de cómputo y sus accesorios descritos en el encabezado del presente.</li>
    <li><span class="alpha-label">i)</span> El incumplimiento de estas disposiciones podrá dar lugar a las sanciones administrativas establecidas en el Artículo 50 fracción XVI, y Articulo 51 de la Ley de Responsabilidades Administrativas del Estado de México Y Municipios. </li>
  </ol>
  <p class="privacy">Aviso de privacidad en: <br><a href="<?= $e($privacyUrl) ?>">https://cultura.edomex.gob.mx/sites/cultura.edomex.gob.mx/files/files/transparencia/avisos/3-SCTUR-SDP-2025 Adquisición de Equipos de Cómputo para uso del personal asignado de la Secretaría de Cultura y Turismo._0.pdf</a>.</p>
  <table class="signatures" aria-label="Firmas de entrega y resguardo"><tbody><tr>
    <td><div class="heading">NOMBRE Y FIRMA DEL<br>SERVIDOR PÚBLICO RESGUARDATARIO</div><div class="signature-space"></div><div class="line"></div><div class="label">Nombre y firma del responsable</div></td>
    <td><div class="heading heading--delivery">SERVIDOR PÚBLICO ADSCRITO AL<br>DEPARTAMENTO DE TECNOLOGÍAS DE LA INFORMACIÓN<br>QUE ENTREGA EL ACTIVO TECNOLÓGICO INSTITUCIONAL</div><div class="signature-space"></div><div class="line"></div><div class="label">Nombre y firma de quien entrega</div></td>
  </tr></tbody></table>
  <footer class="page-footer"><span>Fecha de impresión: <?= $e($datePrinted) ?></span><span>Página 2 de 2</span></footer>
</section>
