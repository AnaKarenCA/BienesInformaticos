<?php

class ReporteModel extends Model
{
  public static function inventarioHtml(array $bienes, string $titulo = 'Inventario de bienes informáticos'): string
  {
    $filas = '';
    foreach ($bienes as $bien) {
      $filas .= '<tr><td>' . htmlspecialchars($bien['numero_inventario']) . '</td><td>' . htmlspecialchars($bien['nombre_bien']) . '</td><td>' . htmlspecialchars($bien['marca_nombre'] ?? '') . '</td><td>' . htmlspecialchars($bien['numero_serie'] ?? '') . '</td><td>' . htmlspecialchars($bien['unidad_nombre']) . '</td></tr>';
    }
    return '<style>body{font:12px Arial;color:#333}h1{color:#680000}table{width:100%;border-collapse:collapse}th{background:#680000;color:#fff}th,td{padding:7px;border:1px solid #ddd;text-align:left}</style><h1>' . htmlspecialchars($titulo) . '</h1><table><thead><tr><th>CI</th><th>Bien</th><th>Marca</th><th>Serie</th><th>Unidad</th></tr></thead><tbody>' . $filas . '</tbody></table>';
  }
}
