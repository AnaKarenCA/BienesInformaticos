<?php

class BienModel extends Model
{
  private const SELECT = "SELECT b.*, ua.codigo_ua, ua.nombre AS unidad_nombre, m.nombre AS marca_nombre,
    mo.nombre AS modelo_nombre, eu.nombre AS estado_nombre, ae.nombre AS activo_especifico_nombre,
    ga.id_grupo_activo AS id_grupo_activo, ga.nombre AS grupo_nombre,
    ag.id_activo_generico AS id_activo_generico, ag.nombre AS activo_generico_nombre,
    r.csp AS resguardante_csp,
    CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS resguardante_nombre,
    ub.municipio, ub.localidad, ub.ubicacion_fisica, COALESCE(rg.fecha_asignacion, b.fecha_asignacion) AS fecha_asignacion_actual,
    cb.codigo AS codigo_barra, rg.id_resguardante, r.id_unidad AS resguardante_id_unidad,
    uar.codigo_ua AS resguardante_codigo_ua, uar.nombre AS resguardante_unidad_nombre
    FROM bien b
    INNER JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
    LEFT JOIN marca m ON m.id_marca = b.id_marca
    LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo AND mo.id_marca = b.id_marca
    LEFT JOIN estado_uso eu ON eu.id_estado_uso = b.id_estado_uso
    LEFT JOIN ubicacion ub ON ub.id_ubicacion = b.id_ubicacion
    LEFT JOIN activo_especifico ae ON ae.id_activo_especifico = b.id_activo_especifico
    LEFT JOIN grupo_activo ga ON ga.id_grupo_activo = ae.id_grupo_activo
    LEFT JOIN activo_generico ag ON ag.id_activo_generico = ga.id_activo_generico
    LEFT JOIN codigo_barra cb ON cb.id_bien = b.id_bien AND cb.activo = 1
    LEFT JOIN resguardo rg ON rg.id_bien = b.id_bien AND rg.activo = 1
    LEFT JOIN resguardante r ON r.csp = rg.csp
    LEFT JOIN unidad_administrativa uar ON uar.id_unidad = r.id_unidad";

  public static function recientes(int $limite = 5): array
  {
    return parent::query(self::SELECT . ' ORDER BY b.fecha_alta DESC, b.fecha_registro DESC LIMIT ' . (int) $limite) ?: [];
  }

  public static function resumen(): array
  {
    $sql = "SELECT COUNT(*) total, COALESCE(SUM(activo = 1), 0) activos,
      COALESCE(SUM(activo = 1 AND NOT EXISTS (SELECT 1 FROM resguardo r WHERE r.id_bien = bien.id_bien AND r.activo = 1)), 0) pendientes,
      COALESCE(SUM(EXISTS (SELECT 1 FROM resguardo r WHERE r.id_bien = bien.id_bien AND r.activo = 1)), 0) con_resguardante FROM bien";
    return (parent::query($sql) ?: [['total' => 0, 'activos' => 0, 'pendientes' => 0, 'con_resguardante' => 0]])[0];
  }

  public static function buscar(array $filtros = []): array
  {
    $sql = self::SELECT . ' WHERE 1=1'; $params = [];
    if (!empty($filtros['q'])) {
      $columnasBusqueda = ['b.numero_inventario', 'b.clave_interna', 'b.nombre_bien', 'b.numero_serie', 'b.nic_cea', 'ua.nombre', 'ua.codigo_ua', 'm.nombre', 'mo.nombre', 'r.csp', 'r.nombre', 'r.apellido_paterno'];
      $gruposBusqueda = [];
      $terminos = preg_split('/\s+/u', trim((string) $filtros['q']), -1, PREG_SPLIT_NO_EMPTY);
      foreach ($terminos as $terminoIndice => $termino) {
        $condiciones = [];
        foreach ($columnasBusqueda as $columnaIndice => $columna) {
          $parametro = 'q_' . $terminoIndice . '_' . $columnaIndice;
          $condiciones[] = $columna . ' LIKE :' . $parametro;
          $params[$parametro] = '%' . $termino . '%';
        }
        $gruposBusqueda[] = '(' . implode(' OR ', $condiciones) . ')';
      }
      if ($gruposBusqueda) $sql .= ' AND (' . implode(' AND ', $gruposBusqueda) . ')';
    }
    foreach ([
      'estado' => 'b.id_estado_uso', 'marca' => 'b.id_marca', 'activo' => 'b.activo',
      'unidad' => 'b.id_unidad', 'resguardante' => 'rg.id_resguardante',
      'generico' => 'ag.id_activo_generico', 'grupo' => 'ga.id_grupo_activo',
      'especifico' => 'ae.id_activo_especifico'
    ] as $key => $column) {
      if (isset($filtros[$key]) && $filtros[$key] !== '') { $sql .= " AND {$column} = :{$key}"; $params[$key] = $filtros[$key]; }
    }
    return parent::query($sql . ' ORDER BY b.fecha_alta DESC, b.fecha_registro DESC', $params) ?: [];
  }

  public static function porId(int $id, bool $incluirJerarquia = true): array
  {
    $rows = parent::query(self::SELECT . ' WHERE b.id_bien = :id', ['id' => $id]);
    if (!$rows) return [];
    $bien = $rows[0];
    if ($incluirJerarquia) $bien['jerarquia_administrativa'] = CatalogoModel::jerarquiaUnidad((int) $bien['id_unidad']);
    $registrados = parent::query('SELECT * FROM componente WHERE id_bien = :id AND activo = 1 ORDER BY id_componente', ['id' => $id]) ?: [];
    $permitidos = ['CPU', 'CARGADOR', 'MONITOR', 'MOUSE', 'TECLADO', 'UPS'];
    $porTipo = [];
    foreach ($registrados as $componente) {
      $tipo = strtoupper(trim((string) ($componente['tipo_componente'] ?? '')));
      if (in_array($tipo, $permitidos, true) && !isset($porTipo[$tipo])) $porTipo[$tipo] = $componente;
    }
    $bien['componentes'] = [];
    foreach ($permitidos as $tipo) {
      $componente = array_merge([
        'tipo_componente' => $tipo, 'marca' => null, 'modelo' => null,
        'numero_serie' => null, 'numero_inventario' => null
      ], $porTipo[$tipo] ?? []);
      $componente['tipo_componente'] = $tipo;
      foreach (['marca', 'modelo', 'numero_serie', 'numero_inventario'] as $campo) {
        $valor = trim((string) ($componente[$campo] ?? ''));
        $componente[$campo] = $valor !== '' ? $valor : null;
      }
      $bien['componentes'][] = $componente;
    }
    $bien['movimientos'] = parent::query('SELECT * FROM movimiento_bien WHERE id_bien = :id ORDER BY fecha_movimiento DESC', ['id' => $id]) ?: [];
    return $bien;
  }

  /** Busca identificadores parciales o exactos sin elegir arbitrariamente entre bienes coincidentes. */
  public static function identificarCoincidencias(string $valor, int $limite = 20): array
  {
    $valor = trim($valor);
    if (mb_strlen($valor, 'UTF-8') < 3) return [];
    $valorLike = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    $busqueda = '%' . $valorLike . '%';
    $sql = "SELECT b.id_bien, b.clave_interna, b.numero_inventario, b.numero_serie, b.nic_cea,
        b.nombre_bien, b.activo, m.nombre AS marca_nombre, mo.nombre AS modelo_nombre
      FROM bien b
      LEFT JOIN marca m ON m.id_marca = b.id_marca
      LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo AND mo.id_marca = b.id_marca
      WHERE b.numero_inventario LIKE :inventario
        OR b.clave_interna LIKE :clave_interna
        OR b.numero_serie LIKE :serie
        OR b.nic_cea LIKE :nic_cea
        OR EXISTS (SELECT 1 FROM codigo_barra cb WHERE cb.id_bien = b.id_bien AND cb.activo = 1 AND cb.codigo LIKE :codigo_barra)
      ORDER BY CASE
        WHEN b.numero_inventario = :exact_inventario OR b.clave_interna = :exact_clave
          OR b.numero_serie = :exact_serie OR b.nic_cea = :exact_nic
          OR EXISTS (SELECT 1 FROM codigo_barra cbe WHERE cbe.id_bien = b.id_bien AND cbe.activo = 1 AND cbe.codigo = :exact_barcode)
        THEN 0 ELSE 1 END, b.id_bien DESC LIMIT " . max(1, min(20, $limite));
    $params = [
      'inventario' => $busqueda, 'clave_interna' => $busqueda, 'serie' => $busqueda,
      'nic_cea' => $busqueda, 'codigo_barra' => $busqueda,
      'exact_inventario' => $valor, 'exact_clave' => $valor, 'exact_serie' => $valor,
      'exact_nic' => $valor, 'exact_barcode' => $valor
    ];
    return parent::query($sql, $params) ?: [];
  }

  /** Recupera el código activo o lo crea usando exclusivamente la Clave Interna. */
  public static function codigoBarra(int $bienId): array
  {
    $bien = self::porId($bienId);
    if (!$bien) return [];
    if (empty($bien['clave_interna'])) throw new Exception('El bien no cuenta con una Clave Interna para generar su código de barras.');

    $existente = parent::query('SELECT id_codigo_barra, codigo, activo FROM codigo_barra WHERE id_bien = :bien ORDER BY activo DESC, fecha_generacion DESC LIMIT 1', ['bien' => $bienId]);
    if ($existente) {
      $registro = $existente[0];
      if ($registro['codigo'] !== $bien['clave_interna'] || !(int) $registro['activo']) {
        parent::update('codigo_barra', ['id_codigo_barra' => $registro['id_codigo_barra']], [
          'codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128', 'activo' => 1
        ]);
      }
      return ['codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128'];
    }

    parent::add('codigo_barra', [
      'id_bien' => $bienId, 'codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128', 'activo' => 1
    ]);
    return ['codigo' => $bien['clave_interna'], 'tipo_codigo' => 'CODE128'];
  }

  public static function siguienteCI(): string
  {
    $marcaTiempo = date('YmdHis');
    for ($sufijo = 1; $sufijo <= 9; $sufijo++) {
      $clave = 'CI-' . $marcaTiempo . $sufijo;
      if (!parent::query('SELECT id_bien FROM bien WHERE clave_interna = :clave LIMIT 1', ['clave' => $clave])) return $clave;
    }
    throw new Exception('No fue posible generar una Clave Interna única; intenta nuevamente.');
  }

  public static function inventarioEnUso(string $inventario, ?int $exceptoId = null): bool
  {
    $sql = 'SELECT id_bien FROM bien WHERE numero_inventario = :inventario';
    $params = ['inventario' => $inventario];
    if ($exceptoId) { $sql .= ' AND id_bien != :id'; $params['id'] = $exceptoId; }
    return (bool) parent::query($sql . ' LIMIT 1', $params);
  }

  public static function guardar(array $datos, array $componentes = [], ?int $id = null, ?array $usuario = null): int
  {
    $contextoAnterior = $id ? ResguardoModel::contextoBien($id) : [];
    $datos['id_activo_especifico'] = CatalogoModel::obtenerOCrearActivoEspecifico($datos['activo_especifico_nombre'], (int) $datos['id_grupo_activo']);
    $datos['id_modelo'] = CatalogoModel::obtenerOCrearModelo($datos['modelo_nombre'] ?? '', (int) ($datos['id_marca'] ?? 0));
    unset($datos['activo_especifico_nombre'], $datos['id_grupo_activo'], $datos['modelo_nombre']);
    if ($id) {
      parent::update('bien', ['id_bien' => $id], $datos);
    } else {
      $datos['clave_interna'] = self::siguienteCI();
      $id = (int) parent::add('bien', $datos);
      parent::add('codigo_barra', ['id_bien' => $id, 'codigo' => $datos['clave_interna'], 'tipo_codigo' => 'CODE128', 'activo' => 1]);
    }
    self::sincronizarComponentes($id, $componentes);
    $contextoNuevo = ResguardoModel::contextoBien($id);
    if (!$contextoAnterior) {
      ResguardoModel::registrarMovimiento($id, 'Alta de bien', [], $contextoNuevo, null, null, $usuario);
    } else {
      if ((int) ($contextoAnterior['id_unidad'] ?? 0) !== (int) ($contextoNuevo['id_unidad'] ?? 0)) {
        ResguardoModel::registrarMovimiento($id, 'Cambio de unidad administrativa', $contextoAnterior, $contextoNuevo, null, null, $usuario);
      }
      $ubicacionAnterior = implode('|', array_map(static fn($campo) => (string) ($contextoAnterior[$campo] ?? ''), ['id_ubicacion', 'piso', 'seccion_ala', 'cubiculo']));
      $ubicacionNueva = implode('|', array_map(static fn($campo) => (string) ($contextoNuevo[$campo] ?? ''), ['id_ubicacion', 'piso', 'seccion_ala', 'cubiculo']));
      if ($ubicacionAnterior !== $ubicacionNueva) {
        ResguardoModel::registrarMovimiento($id, 'Cambio de ubicación', $contextoAnterior, $contextoNuevo, null, null, $usuario);
      }
    }
    return $id;
  }

  private static function sincronizarComponentes(int $bienId, array $componentes): void
  {
    $tiposPermitidos = ['CPU', 'CARGADOR', 'MONITOR', 'MOUSE', 'TECLADO', 'UPS'];
    $existentes = parent::query('SELECT id_componente, tipo_componente FROM componente WHERE id_bien = :bien AND activo = 1', ['bien' => $bienId]) ?: [];
    $pendientes = [];
    foreach ($existentes as $fila) {
      if (in_array(strtoupper(trim((string) $fila['tipo_componente'])), $tiposPermitidos, true)) $pendientes[(int) $fila['id_componente']] = true;
    }
    foreach ($componentes as $componente) {
      $tipo = strtoupper(trim((string) ($componente['tipo_componente'] ?? '')));
      if (!in_array($tipo, $tiposPermitidos, true)) continue;
      $datos = ['tipo_componente' => $tipo, 'marca' => trim((string) ($componente['marca'] ?? '')) ?: null,
        'modelo' => trim((string) ($componente['modelo'] ?? '')) ?: null,
        'numero_serie' => trim((string) ($componente['numero_serie'] ?? '')) ?: null,
        'numero_inventario' => trim((string) ($componente['numero_inventario'] ?? '')) ?: null, 'activo' => 1];
      $idComponente = (int) ($componente['id_componente'] ?? 0);
      if ($idComponente && isset($pendientes[$idComponente])) {
        parent::update('componente', ['id_componente' => $idComponente], $datos);
        unset($pendientes[$idComponente]);
      } else {
        $datos['id_bien'] = $bienId;
        parent::add('componente', $datos);
      }
    }
    foreach (array_keys($pendientes) as $idComponente) parent::update('componente', ['id_componente' => $idComponente], ['activo' => 0]);
  }

  public static function cambiarEstado(int $id, ?array $usuario = null): bool
  {
    $bien = self::porId($id);
    if (!$bien) return false;
    $anterior = ResguardoModel::contextoBien($id);
    $nuevoActivo = $bien['activo'] ? 0 : 1;
    parent::update('bien', ['id_bien' => $id], ['activo' => $nuevoActivo]);
    $nuevo = ResguardoModel::contextoBien($id);
    ResguardoModel::registrarMovimiento($id, $nuevoActivo ? 'Reactivación de bien' : 'Baja de bien', $anterior, $nuevo, null, null, $usuario);
    return true;
  }

  public static function asignarResguardante(int $bienId, ?string $csp, ?string $fecha, ?array $usuario = null): void
  {
    $resguardanteId = null; $resguardanteActivo = false;
    if ($csp !== null && $csp !== '') {
      $resguardantes = parent::query('SELECT id_resguardante, activo FROM resguardante WHERE csp = :csp LIMIT 1', ['csp' => $csp]);
      if (!$resguardantes) throw new Exception('El CSP seleccionado no corresponde a un resguardante registrado.');
      $resguardanteId = (int) $resguardantes[0]['id_resguardante'];
      $resguardanteActivo = (bool) $resguardantes[0]['activo'];
    }
    $actual = parent::query('SELECT * FROM resguardo WHERE id_bien = :bien AND activo = 1 LIMIT 1', ['bien' => $bienId]);
    if (!$resguardanteId) {
      if (!$actual) return;
      $resguardo = $actual[0];
      $anterior = ResguardoModel::contextoBien($bienId);
      parent::update('resguardo', ['id_resguardo' => $resguardo['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => date('Y-m-d')]);
      $nuevo = ResguardoModel::contextoBien($bienId);
      ResguardoModel::registrarMovimiento($bienId, 'Liberación de resguardo', $anterior, $nuevo, null, null, $usuario, (int) $resguardo['id_resguardo']);
      return;
    }
    if ($actual && (string) $actual[0]['csp'] === (string) $csp) {
      if ($fecha) parent::update('resguardo', ['id_resguardo' => $actual[0]['id_resguardo']], ['fecha_asignacion' => $fecha]);
      return;
    }
    if ($resguardanteId && !$resguardanteActivo) throw new Exception('El resguardante seleccionado está inactivo.');
    $anterior = ResguardoModel::contextoBien($bienId);
    if ($actual) parent::update('resguardo', ['id_resguardo' => $actual[0]['id_resguardo']], ['activo' => 0, 'fecha_devolucion' => date('Y-m-d')]);
    $idResguardoNuevo = (int) parent::add('resguardo', ['id_bien' => $bienId, 'id_resguardante' => $resguardanteId, 'csp' => $csp, 'fecha_asignacion' => $fecha ?: date('Y-m-d'), 'activo' => 1]);
    $nuevo = ResguardoModel::contextoBien($bienId);
    $tipo = $actual ? 'Cambio de resguardante' : 'Asignación de resguardo';
    ResguardoModel::registrarMovimiento($bienId, $tipo, $anterior, $nuevo, null, null, $usuario, $actual ? (int) $actual[0]['id_resguardo'] : null, $idResguardoNuevo);
  }
}
