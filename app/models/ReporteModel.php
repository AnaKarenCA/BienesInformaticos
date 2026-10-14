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
    return '<style>body{font:12px Arial;color:#333}h1{color:#680000}table{width:100%;border-collapse:collapse}th{background:#680000;color:#fff}th,td{padding:7px;border:1px solid #ddd;text-align:left}td small{font-size:10px}</style><h1>' . htmlspecialchars($titulo) . '</h1><table><thead><tr><th>CI</th><th>Número de inventario de Control Interno</th><th>Bien</th><th>Marca / Modelo</th><th>Serie</th><th>Unidad Administrativa</th></tr></thead><tbody>' . $filas . '</tbody></table>';
  }

  /** Ficha imprimible construida con datos vigentes y la jerarquía relacional del bien. */
  public static function detalleBienHtml(array $bien): string
  {
    $e = static fn($value): string => htmlspecialchars(trim((string)($value ?? '')) !== '' ? trim((string)$value) : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $datos = [
      'Clave interna CI' => $bien['clave_interna'] ?? null,
      'Número de inventario' => $bien['numero_inventario'] ?? null,
      'NIC / CEA' => $bien['nic_cea'] ?? null,
      'Nombre del bien' => $bien['nombre_bien'] ?? null,
      'Activo genérico' => $bien['activo_generico_nombre'] ?? null,
      'Grupo del activo' => $bien['grupo_nombre'] ?? null,
      'Activo específico' => $bien['activo_especifico_nombre'] ?? null,
      'Marca / modelo' => trim((string)($bien['marca_nombre'] ?? '') . ' ' . (string)($bien['modelo_nombre'] ?? '')),
      'Material' => $bien['material_nombre'] ?? null,
      'Color' => $bien['color_nombre'] ?? null,
      'Estado de uso' => $bien['estado_nombre'] ?? null,
      'Número de serie' => $bien['numero_serie'] ?? null,
      'Características' => $bien['caracteristicas'] ?? null,
      'Observaciones' => $bien['observaciones'] ?? null,
      'Valor' => isset($bien['valor']) && $bien['valor'] !== '' ? '$' . number_format((float)$bien['valor'], 2) . ' MXN' : null,
      'Fecha de alta' => $bien['fecha_alta'] ?? null,
      'Fecha de adquisición' => $bien['fecha_adquisicion'] ?? null,
      'Fecha de elaboración' => $bien['fecha_elaboracion'] ?? null,
      'Resguardante actual' => $bien['resguardante_nombre'] ?? null,
      'CSP' => $bien['resguardante_csp'] ?? null,
      'Unidad del bien' => trim((string)($bien['codigo_ua'] ?? '') . ' ' . (string)($bien['unidad_nombre'] ?? '')),
      'Ubicación' => $bien['ubicacion_fisica'] ?? null,
      'Municipio' => $bien['municipio'] ?? null,
      'Localidad' => $bien['localidad'] ?? null,
      'Piso' => $bien['piso'] ?? null,
      'Sección / Ala' => $bien['seccion_ala'] ?? null,
      'Cubículo' => $bien['cubiculo'] ?? null,
    ];
    $rows = '';
    foreach ($datos as $label => $value) $rows .= '<tr><th>' . $e($label) . '</th><td>' . $e($value) . '</td></tr>';
    $jerarquiaRows = '';
    foreach (($bien['jerarquia_administrativa']['ruta'] ?? []) as $nodo) {
      $valor = trim((string)($nodo['codigo_ua'] ?? '') . ' ' . (string)($nodo['nombre'] ?? ''));
      $jerarquiaRows .= '<tr><th>' . $e($nodo['tipo_nombre'] ?? 'Pendiente de asignar') . '</th><td>' . $e($valor) . '</td></tr>';
    }
    $componentRows = '';
    foreach (($bien['componentes'] ?? []) as $componente) {
      $componentRows .= '<tr><td>' . $e($componente['tipo_componente'] ?? null) . '</td><td>' . $e($componente['marca'] ?? null) . '</td><td>' . $e($componente['modelo'] ?? null) . '</td><td>' . $e($componente['numero_serie'] ?? null) . '</td><td>' . $e($componente['numero_inventario'] ?? null) . '</td></tr>';
    }
    return '<style>body{font:11px Arial,sans-serif;color:#202124}h1{font-size:19px;color:#680000;border-bottom:3px solid #D4AF37;padding-bottom:8px}h2{font-size:14px;color:#680000;margin:18px 0 7px}table{width:100%;border-collapse:collapse;margin:7px 0}th,td{border:1px solid #bbb;text-align:left;padding:5px;vertical-align:top}th{background:#f1f1f1;width:28%}</style><h1>Ficha del bien: ' . $e($bien['nombre_bien'] ?? null) . '</h1><h2>Identificación, características y ubicación</h2><table>' . $rows . '</table><h2>Jerarquía administrativa vigente</h2><table>' . ($jerarquiaRows ?: '<tr><td>Sin jerarquía administrativa disponible.</td></tr>') . '</table><h2>Componentes</h2><table><thead><tr><th>Componente</th><th>Marca</th><th>Modelo</th><th>Serie</th><th>Inventario / CI</th></tr></thead><tbody>' . ($componentRows ?: '<tr><td colspan="5">Sin componentes registrados.</td></tr>') . '</tbody></table>';
  }
}
