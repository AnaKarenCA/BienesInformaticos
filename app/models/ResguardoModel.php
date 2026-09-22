<?php

class ResguardoModel extends Model
{
  public static function vigentes(): array
  {
    $sql = "SELECT r.*, b.numero_inventario, b.nombre_bien, b.numero_serie, ua.codigo_ua, ua.nombre unidad_nombre,
      CONCAT(p.nombre, ' ', p.apellido_paterno, ' ', COALESCE(p.apellido_materno, '')) resguardante_nombre
      FROM resguardo r INNER JOIN bien b ON b.id_bien=r.id_bien INNER JOIN resguardante p ON p.id_resguardante=r.id_resguardante
      INNER JOIN unidad_administrativa ua ON ua.id_unidad=b.id_unidad WHERE r.activo=1 ORDER BY resguardante_nombre, b.nombre_bien";
    return parent::query($sql) ?: [];
  }
  public static function historico(): array { return parent::query('SELECT * FROM movimiento_bien ORDER BY fecha_movimiento DESC') ?: []; }

  /** Registra la devolución/baja sólo cuando el usuario la confirma explícitamente. */
  public static function darDeBaja(int $bienId, string $fecha, ?string $motivo, ?string $observaciones): void
  {
    $actual = parent::query('SELECT * FROM resguardo WHERE id_bien = :bien AND activo = 1 LIMIT 1', ['bien' => $bienId]);
    if (!$actual) throw new Exception('El bien no tiene una custodia vigente que pueda darse de baja.');
    $resguardo = $actual[0];
    parent::add('baja_resguardo', [
      'id_resguardo' => $resguardo['id_resguardo'], 'id_bien' => $bienId, 'fecha_baja' => $fecha,
      'motivo' => $motivo ?: null, 'observaciones' => $observaciones ?: null
    ]);
    parent::update('resguardo', ['id_resguardo' => $resguardo['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => $fecha]);
    parent::add('movimiento_bien', [
      'id_bien' => $bienId, 'tipo_movimiento' => 'Baja de resguardante', 'resguardante_anterior' => $resguardo['id_resguardante'],
      'fecha_movimiento' => now(), 'motivo' => $motivo ?: null, 'observaciones' => $observaciones ?: null
    ]);
  }
}
