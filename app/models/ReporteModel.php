<?php

class ReporteModel extends Model
{
  public static function inventarioHtml(array $bienes, string $titulo = 'Inventario de bienes informáticos'): string
  {
    $filas = '';
    foreach ($bienes as $bien) {
      $marcaModelo = !empty($bien['marca_nombre'])
        ? '<strong>' . htmlspecialchars(mb_strtoupper($bien['marca_nombre'], 'UTF-8')) . '</strong>' . (!empty($bien['modelo_nombre']) ? '<br><small>' . htmlspecialchars($bien['modelo_nombre']) . '</small>' : '')
        : '';
      $filas .= '<tr><td>' . htmlspecialchars($bien['clave_interna'] ?? '') . '</td><td>' . htmlspecialchars($bien['numero_inventario'] ?? '') . '</td><td>' . htmlspecialchars(mb_convert_case($bien['nombre_bien'], MB_CASE_TITLE, 'UTF-8')) . '</td><td>' . $marcaModelo . '</td><td>' . htmlspecialchars($bien['numero_serie'] ?? '') . '</td><td>' . htmlspecialchars(mb_convert_case($bien['unidad_nombre'], MB_CASE_TITLE, 'UTF-8')) . '</td></tr>';
    }
    return '<style>body{font:12px Arial;color:#333}h1{color:#680000}table{width:100%;border-collapse:collapse}th{background:#680000;color:#fff}th,td{padding:7px;border:1px solid #ddd;text-align:left}td small{font-size:10px}</style><h1>' . htmlspecialchars($titulo) . '</h1><table><thead><tr><th>CI</th><th>Inventario SICOPA</th><th>Bien</th><th>Marca / Modelo</th><th>Serie</th><th>Unidad Administrativa</th></tr></thead><tbody>' . $filas . '</tbody></table>';
  }
}
