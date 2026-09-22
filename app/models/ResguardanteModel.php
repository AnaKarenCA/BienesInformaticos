<?php

class ResguardanteModel extends Model
{
  public static function activos(): array
  {
    return parent::query("SELECT *, CONCAT(nombre, ' ', apellido_paterno, ' ', COALESCE(apellido_materno, '')) AS nombre_completo FROM resguardante WHERE activo = 1 ORDER BY nombre, apellido_paterno") ?: [];
  }
}
