<?php

class BienModel extends Model
{
  private const SELECT = "SELECT b.*, ua.codigo_ua, ua.nombre AS unidad_nombre, m.nombre AS marca_nombre,
    mo.nombre AS modelo_nombre, eu.nombre AS estado_nombre, ae.nombre AS activo_especifico_nombre,
    COALESCE(mt.nombre, b.material) AS material_nombre, COALESCE(cl.nombre, b.color) AS color_nombre,
    ga.id_grupo_activo AS id_grupo_activo, ga.nombre AS grupo_nombre,
    ag.id_activo_generico AS id_activo_generico, ag.nombre AS activo_generico_nombre,
    r.csp AS resguardante_csp,
    CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS resguardante_nombre,
    ub.municipio, ub.localidad, ub.ubicacion_fisica, COALESCE(rg.fecha_asignacion, b.fecha_asignacion) AS fecha_asignacion_actual,
    cb.codigo AS codigo_qr, rg.id_resguardante, r.id_unidad AS resguardante_id_unidad,
    uar.codigo_ua AS resguardante_codigo_ua, uar.nombre AS resguardante_unidad_nombre
    FROM bien b
    INNER JOIN unidad_administrativa ua ON ua.id_unidad = b.id_unidad
    LEFT JOIN marca m ON m.id_marca = b.id_marca
    LEFT JOIN modelo mo ON mo.id_modelo = b.id_modelo AND mo.id_marca = b.id_marca
    LEFT JOIN estado_uso eu ON eu.id_estado_uso = b.id_estado_uso
    LEFT JOIN material mt ON mt.id_material = b.id_material
    LEFT JOIN color cl ON cl.id_color = b.id_color
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
    $bien['historial_resguardos'] = parent::query("SELECT rg.fecha_asignacion, rg.fecha_devolucion, rg.activo AS resguardo_activo, rg.csp,
      CONCAT_WS(' ', r.nombre, r.apellido_paterno, NULLIF(r.apellido_materno, '')) AS resguardante_nombre,
      br.fecha_baja, br.motivo AS motivo_baja, br.observaciones AS observaciones_baja
      FROM resguardo rg LEFT JOIN resguardante r ON r.id_resguardante = rg.id_resguardante
      LEFT JOIN baja_resguardo br ON br.id_resguardo = rg.id_resguardo
      WHERE rg.id_bien = :id ORDER BY COALESCE(br.fecha_baja, rg.fecha_devolucion, rg.fecha_asignacion) DESC", ['id' => $id]) ?: [];
    $bien['traspasos'] = parent::query("SELECT t.folio, t.tipo, t.estado, t.motivo, t.fecha_creacion, t.fecha_completado,
      uo.nombre AS unidad_origen_nombre, ud.nombre AS unidad_destino_nombre
      FROM traspaso_bien tb JOIN traspaso t ON t.id_traspaso = tb.id_traspaso
      LEFT JOIN unidad_administrativa uo ON uo.id_unidad = t.id_unidad_origen
      LEFT JOIN unidad_administrativa ud ON ud.id_unidad = t.id_unidad_destino
      WHERE tb.id_bien = :id ORDER BY t.fecha_creacion DESC, t.id_traspaso DESC", ['id' => $id]) ?: [];
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
        OR EXISTS (SELECT 1 FROM codigo_barra cb WHERE cb.id_bien = b.id_bien AND cb.activo = 1 AND cb.codigo LIKE :codigo)
      ORDER BY CASE
        WHEN b.numero_inventario = :exact_inventario OR b.clave_interna = :exact_clave
          OR b.numero_serie = :exact_serie OR b.nic_cea = :exact_nic
          OR EXISTS (SELECT 1 FROM codigo_barra cbe WHERE cbe.id_bien = b.id_bien AND cbe.activo = 1 AND cbe.codigo = :exact_codigo)
        THEN 0 ELSE 1 END, b.id_bien DESC LIMIT " . max(1, min(20, $limite));
    $params = [
      'inventario' => $busqueda, 'clave_interna' => $busqueda, 'serie' => $busqueda,
      'nic_cea' => $busqueda, 'codigo' => $busqueda,
      'exact_inventario' => $valor, 'exact_clave' => $valor, 'exact_serie' => $valor,
      'exact_nic' => $valor, 'exact_codigo' => $valor
    ];
    return parent::query($sql, $params) ?: [];
  }

  /** Devuelve el bien solo cuando CI/inventario exactos no chocan con otro identificador. */
  public static function idPorIdentificadorExactoSeguro(string $valor): ?int
  {
    $valor = trim($valor);
    if ($valor === '') return null;
    $rows = parent::query('SELECT id_bien, (clave_interna = :ci_flag OR numero_inventario = :inventario_flag) AS coincide_unico
      FROM bien WHERE clave_interna = :ci OR numero_inventario = :inventario OR nic_cea = :nic OR numero_serie = :serie LIMIT 2', [
      'ci_flag' => $valor, 'inventario_flag' => $valor, 'ci' => $valor,
      'inventario' => $valor, 'nic' => $valor, 'serie' => $valor
    ]) ?: [];
    return count($rows) === 1 && (int) $rows[0]['coincide_unico'] === 1 ? (int) $rows[0]['id_bien'] : null;
  }

  public static function existe(int $bienId): bool
  {
    return $bienId > 0 && (bool) parent::query('SELECT id_bien FROM bien WHERE id_bien = :id LIMIT 1', ['id' => $bienId]);
  }

  /** Lee y valida la base canónica de QR configurada para este entorno. */
  public static function qrBaseUrl(): string
  {
    static $envCargado = false;
    if (!$envCargado) {
      $archivoEnv = rtrim(ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';
      if (class_exists(\Dotenv\Dotenv::class) && is_file($archivoEnv)) {
        \Dotenv\Dotenv::createImmutable(dirname($archivoEnv))->safeLoad();
      }
      $envCargado = true;
    }

    $base = trim((string) ($_ENV['BIENES_QR_BASE_URL'] ?? $_SERVER['BIENES_QR_BASE_URL'] ?? getenv('BIENES_QR_BASE_URL') ?: ''));
    $partes = $base !== '' ? parse_url($base) : false;
    if (!$partes || empty($partes['scheme']) || empty($partes['host'])
      || !in_array(strtolower($partes['scheme']), ['http', 'https'], true)
      || isset($partes['user']) || isset($partes['pass']) || isset($partes['query']) || isset($partes['fragment'])) {
      throw new Exception('La URL base canónica de los códigos QR no está configurada correctamente.');
    }
    return rtrim($base, '/') . '/';
  }

  /** Construye el enlace QR sin tomar el host de la petición. */
  public static function urlQr(int $bienId): string
  {
    if ($bienId < 1) throw new Exception('El identificador del bien no es válido.');
    return self::qrBaseUrl() . 'bienes/detalle/' . $bienId;
  }

  /** Acepta únicamente enlaces canónicos a la ruta de detalle de esta aplicación. */
  public static function idDesdeUrlQr(string $valor): ?int
  {
    $base = parse_url(self::qrBaseUrl());
    $url = parse_url(trim($valor));
    if (!$base || !$url || empty($url['scheme']) || empty($url['host']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) return null;
    $puertoBase = $base['port'] ?? (strtolower($base['scheme']) === 'https' ? 443 : 80);
    $puertoUrl = $url['port'] ?? (strtolower($url['scheme']) === 'https' ? 443 : 80);
    if (strtolower($base['scheme']) !== strtolower($url['scheme']) || strtolower($base['host']) !== strtolower($url['host']) || $puertoBase !== $puertoUrl) return null;
    $basePath = rtrim($base['path'] ?? '/', '/');
    $patron = '~^' . preg_quote($basePath, '~') . '/bienes/detalle/([1-9][0-9]*)/?$~D';
    if (!preg_match($patron, $url['path'] ?? '', $coincidencia)) return null;
    $id = filter_var($coincidencia[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : (int) $id;
  }

  /** Genera en memoria la imagen de un QR; esta operación no escribe en codigo_barra. */
  public static function codigoQr(int $bienId): array
  {
    $rows = parent::query('SELECT id_bien, clave_interna, numero_inventario, nombre_bien FROM bien WHERE id_bien = :id LIMIT 1', ['id' => $bienId]);
    if (!$rows) return [];
    $bien = $rows[0];
    if (empty($bien['clave_interna'])) throw new Exception('El bien no cuenta con una Clave Interna para generar su código QR.');
    $url = self::urlQr($bienId);
    $qrCode = \Endroid\QrCode\QrCode::create($url)->setSize(320)->setMargin(16);
    $result = (new \Endroid\QrCode\Writer\SvgWriter())->write($qrCode);
    return [
      'codigo' => $url, 'url' => $url, 'clave_interna' => $bien['clave_interna'],
      'numero_inventario' => $bien['numero_inventario'], 'nombre_bien' => $bien['nombre_bien'], 'tipo_codigo' => 'QR',
      'svg' => $result->getString(), 'data_uri' => $result->getDataUri()
    ];
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
    self::validarCatalogosBien($datos, $id);
    $contextoAnterior = $id ? ResguardoModel::contextoBien($id) : [];
    unset($datos['id_activo_generico'], $datos['id_grupo_activo']);
    if ($id) {
      parent::update('bien', ['id_bien' => $id], $datos);
    } else {
      $datos['clave_interna'] = self::siguienteCI();
      $id = (int) parent::add('bien', $datos);
      parent::add('codigo_barra', ['id_bien' => $id, 'codigo' => $datos['clave_interna'], 'tipo_codigo' => 'QR', 'activo' => 1]);
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

  /** Valida que cada selección pertenezca a la jerarquía enviada y esté activa. */
  private static function validarCatalogosBien(array $datos, ?int $bienId): void
  {
    $actual = $bienId ? parent::query('SELECT * FROM bien WHERE id_bien = :id LIMIT 1', ['id' => $bienId]) : [];
    if ($bienId && !$actual) throw new Exception('El bien solicitado no existe.');
    $actual = $actual[0] ?? [];
    $unidadId = (int) ($datos['id_unidad'] ?? 0);
    if ($unidadId && !CatalogoModel::unidadActivaOActual($unidadId, (int) ($actual['id_unidad'] ?? 0))) throw new Exception('Selecciona una unidad administrativa activa.');
    $esActivoOActual = static function (string $tabla, string $campo, int $valor, string $campoActual) use ($actual): bool {
      if ($valor < 1) return false;
      return (bool) parent::query("SELECT {$campo} FROM {$tabla} WHERE {$campo} = :id AND (activo = 1 OR {$campo} = :actual) LIMIT 1", ['id' => $valor, 'actual' => (int) ($actual[$campoActual] ?? 0)]);
    };
    $generico = (int) ($datos['id_activo_generico'] ?? 0);
    $grupo = (int) ($datos['id_grupo_activo'] ?? 0);
    $especifico = (int) ($datos['id_activo_especifico'] ?? 0);
    if (!$esActivoOActual('activo_generico', 'id_activo_generico', $generico, 'id_activo_generico')) throw new Exception('Selecciona un activo genérico activo.');
    $grupoValido = parent::query('SELECT g.id_grupo_activo FROM grupo_activo g WHERE g.id_grupo_activo = :grupo AND g.id_activo_generico = :generico AND (g.activo = 1 OR g.id_grupo_activo = :actual)', ['grupo' => $grupo, 'generico' => $generico, 'actual' => (int) ($actual['id_grupo_activo'] ?? 0)]);
    if (!$grupoValido) throw new Exception('El grupo seleccionado no pertenece al activo genérico.');
    $especificoValido = parent::query('SELECT e.id_activo_especifico FROM activo_especifico e WHERE e.id_activo_especifico = :especifico AND e.id_grupo_activo = :grupo AND (e.activo = 1 OR e.id_activo_especifico = :actual)', ['especifico' => $especifico, 'grupo' => $grupo, 'actual' => (int) ($actual['id_activo_especifico'] ?? 0)]);
    if (!$especificoValido) throw new Exception('El activo específico no pertenece al grupo seleccionado.');
    $marca = (int) ($datos['id_marca'] ?? 0); $modelo = (int) ($datos['id_modelo'] ?? 0);
    if ($marca && !$esActivoOActual('marca', 'id_marca', $marca, 'id_marca')) throw new Exception('Selecciona una marca activa.');
    if ($modelo && !$marca) throw new Exception('Selecciona la marca correspondiente al modelo.');
    if ($modelo && !parent::query('SELECT id_modelo FROM modelo WHERE id_modelo = :modelo AND id_marca = :marca AND (activo = 1 OR id_modelo = :actual) LIMIT 1', ['modelo' => $modelo, 'marca' => $marca, 'actual' => (int) ($actual['id_modelo'] ?? 0)])) throw new Exception('El modelo seleccionado no pertenece a la marca elegida.');
    foreach ([['material', 'id_material'], ['color', 'id_color'], ['estado_uso', 'id_estado_uso'], ['ubicacion', 'id_ubicacion']] as [$tabla, $campo]) {
      $valor = (int) ($datos[$campo] ?? 0);
      if ($valor && !$esActivoOActual($tabla, $campo, $valor, $campo)) throw new Exception('Uno de los valores de catálogo seleccionados ya no está activo. Actualiza la página e inténtalo de nuevo.');
    }
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
    $resguardanteId = null;
    if ($csp !== null && trim($csp) !== '') {
      $resguardantes = parent::query('SELECT id_resguardante FROM resguardante WHERE csp = :csp LIMIT 1', ['csp' => trim($csp)]);
      if (!$resguardantes) throw new Exception('El CSP seleccionado no corresponde a un resguardante registrado.');
      $resguardanteId = (int) $resguardantes[0]['id_resguardante'];
    }
    ResguardoModel::cambiarCustodia($bienId, $resguardanteId, ['fecha_asignacion' => $fecha], null, null, null, $usuario);
  }
}
