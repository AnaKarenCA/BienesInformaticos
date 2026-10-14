<nav class="navbar-nav sidebar sidebar-dark accordion bi-legacy-sidebar" id="accordionSidebar" aria-label="Navegación principal">
  <a class="sidebar-brand d-flex align-items-center justify-content-center" href="admin" aria-label="Ir al inicio" title="Bienes Informáticos">
    <span class="sidebar-brand-icon"><i class="fas fa-boxes-stacked" aria-hidden="true"></i></span>
    <span class="sidebar-brand-text mx-2">Bienes Informáticos</span>
  </a>

  <hr class="sidebar-divider my-0">
  <div class="sidebar-heading">Principal</div>
  <?php if (can_user((string) get_user('rol'), 'inicio-consultar')): ?><div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'index' ? 'active' : ''; ?>">
    <a class="nav-link" href="admin" aria-label="Inicio" title="Inicio" <?php echo CONTROLLER === 'admin' && METHOD === 'index' ? 'aria-current="page"' : ''; ?>><i class="fas fa-fw fa-chart-pie" aria-hidden="true"></i><span>Inicio</span></a>
  </div><?php endif; ?>
  <hr class="sidebar-divider">

  <?php if (can_user((string) get_user('rol'), 'bienes-consultar')): ?><button class="bi-nav-section" type="button" data-toggle="collapse" data-target="#legacyInventory" aria-controls="legacyInventory" aria-expanded="<?php echo in_array(CONTROLLER, ['identificar', 'bienes'], true) ? 'true' : 'false'; ?>" aria-label="Inventario" title="Inventario"><i class="fas fa-fw fa-boxes-stacked me-2" aria-hidden="true"></i><span class="bi-nav-label">Inventario</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
  <div class="collapse <?php echo in_array(CONTROLLER, ['identificar', 'bienes'], true) ? 'show' : ''; ?>" id="legacyInventory">
    <div class="nav-item <?php echo CONTROLLER === 'identificar' ? 'active' : ''; ?>"><a class="nav-link" href="identificar" aria-label="Identificar bien" title="Identificar bien"><i class="fas fa-fw fa-qrcode" aria-hidden="true"></i><span>Identificar bien</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'bienes' ? 'active' : ''; ?>"><a class="nav-link" href="bienes" aria-label="Inventario" title="Inventario"><i class="fas fa-fw fa-laptop" aria-hidden="true"></i><span>Inventario</span></a></div>
  </div><?php endif; ?>
  <?php if (can_user((string) get_user('rol'), 'resguardos-consultar') || can_user((string) get_user('rol'), 'traspasos-consultar') || can_user((string) get_user('rol'), 'documentos-consultar') || can_user((string) get_user('rol'), 'movimientos-consultar')): ?><button class="bi-nav-section" type="button" data-toggle="collapse" data-target="#legacyCustody" aria-controls="legacyCustody" aria-expanded="<?php echo in_array(CONTROLLER, ['resguardos', 'traspasos', 'documentos', 'historico_movimientos'], true) ? 'true' : 'false'; ?>" aria-label="Documentos y movimientos" title="Documentos y movimientos"><i class="fas fa-fw fa-folder-open me-2" aria-hidden="true"></i><span class="bi-nav-label">Documentos y movimientos</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
  <div class="collapse <?php echo in_array(CONTROLLER, ['resguardos', 'traspasos', 'documentos', 'historico_movimientos'], true) ? 'show' : ''; ?>" id="legacyCustody">
    <?php if (can_user((string) get_user('rol'), 'resguardos-consultar')): ?><div class="nav-item <?php echo CONTROLLER === 'resguardos' ? 'active' : ''; ?>"><a class="nav-link" href="resguardos" aria-label="Resguardos" title="Resguardos"><i class="fas fa-fw fa-clipboard-user" aria-hidden="true"></i><span>Resguardos</span></a></div><?php endif; ?>
    <?php if (can_user((string) get_user('rol'), 'traspasos-consultar')): ?><div class="nav-item <?php echo CONTROLLER === 'traspasos' ? 'active' : ''; ?>"><a class="nav-link" href="traspasos" aria-label="Traspasos" title="Traspasos"><i class="fas fa-fw fa-right-left" aria-hidden="true"></i><span>Traspasos</span></a></div><?php endif; ?>
    <?php if (can_user((string) get_user('rol'), 'documentos-consultar')): ?><div class="nav-item <?php echo CONTROLLER === 'documentos' ? 'active' : ''; ?>"><a class="nav-link" href="documentos" aria-label="Documentos" title="Documentos"><i class="fas fa-fw fa-folder" aria-hidden="true"></i><span>Documentos</span></a></div><?php endif; ?>
    <?php if (can_user((string) get_user('rol'), 'movimientos-consultar')): ?><div class="nav-item <?php echo CONTROLLER === 'historico_movimientos' ? 'active' : ''; ?>"><a class="nav-link" href="historico-movimientos" aria-label="Histórico de movimientos" title="Histórico de movimientos"><i class="fas fa-fw fa-clock-rotate-left" aria-hidden="true"></i><span>Histórico de movimientos</span></a></div><?php endif; ?>
  </div><?php endif; ?>
  <?php if (can_user((string) get_user('rol'), 'catalogos-consultar')): ?>
  <button class="bi-nav-section" type="button" data-toggle="collapse" data-target="#legacyCatalogs" aria-controls="legacyCatalogs" aria-expanded="<?php echo CONTROLLER === 'catalogos' ? 'true' : 'false'; ?>" aria-label="Catálogos" title="Catálogos"><i class="fas fa-fw fa-list me-2" aria-hidden="true"></i><span class="bi-nav-label">Catálogos</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
  <div class="collapse <?php echo CONTROLLER === 'catalogos' ? 'show' : ''; ?>" id="legacyCatalogs">
    <div class="nav-item <?php echo CONTROLLER === 'catalogos' && METHOD === 'clasificacion' ? 'active' : ''; ?>"><a class="nav-link" href="catalogos/clasificacion" aria-label="Clasificación" title="Clasificación"><i class="fas fa-fw fa-sitemap" aria-hidden="true"></i><span>Clasificación</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'catalogos' && METHOD === 'marcas_modelos' ? 'active' : ''; ?>"><a class="nav-link" href="catalogos/marcas_modelos" aria-label="Marcas y modelos" title="Marcas y modelos"><i class="fas fa-fw fa-tags" aria-hidden="true"></i><span>Marcas y modelos</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'catalogos' && METHOD === 'estados_uso' ? 'active' : ''; ?>"><a class="nav-link" href="catalogos/estados_uso" aria-label="Estados de uso" title="Estados de uso"><i class="fas fa-fw fa-heart-pulse" aria-hidden="true"></i><span>Estados de uso</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'catalogos' && METHOD === 'ubicaciones' ? 'active' : ''; ?>"><a class="nav-link" href="catalogos/ubicaciones" aria-label="Ubicaciones" title="Ubicaciones"><i class="fas fa-fw fa-location-dot" aria-hidden="true"></i><span>Ubicaciones</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'catalogos' && METHOD === 'unidades_admin' ? 'active' : ''; ?>"><a class="nav-link" href="catalogos/unidades_admin" aria-label="Unidades administrativas" title="Unidades administrativas"><i class="fas fa-fw fa-building" aria-hidden="true"></i><span>Unidades administrativas</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'catalogos' && in_array(METHOD, ['resguardantes', 'cambiar_resguardante'], true) ? 'active' : ''; ?>"><a class="nav-link" href="catalogos/resguardantes" aria-label="Resguardantes" title="Resguardantes"><i class="fas fa-fw fa-id-card-clip" aria-hidden="true"></i><span>Resguardantes</span></a></div>
  </div>
  <?php endif; ?>
  <hr class="sidebar-divider">

  <?php if (can_user((string) get_user('rol'), 'admin-access')): ?><div class="sidebar-heading">Administración</div>
  <div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'usuarios' ? 'active' : ''; ?>">
    <a class="nav-link" href="admin/usuarios" aria-label="Usuarios" title="Usuarios" <?php echo CONTROLLER === 'admin' && METHOD === 'usuarios' ? 'aria-current="page"' : ''; ?>><i class="fas fa-fw fa-users" aria-hidden="true"></i><span>Usuarios</span></a>
  </div>
  <div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'productos' ? 'active' : ''; ?>">
    <a class="nav-link" href="admin/productos" aria-label="Productos" title="Productos" <?php echo CONTROLLER === 'admin' && METHOD === 'productos' ? 'aria-current="page"' : ''; ?>><i class="fas fa-fw fa-tag" aria-hidden="true"></i><span>Productos</span></a>
  </div>
  <hr class="sidebar-divider">

  <button class="bi-nav-section" type="button" data-toggle="collapse" data-target="#legacyBeeTools" aria-controls="legacyBeeTools" aria-expanded="true" aria-label="Herramientas Bee Framework" title="Herramientas Bee Framework"><i class="fas fa-fw fa-screwdriver-wrench me-2" aria-hidden="true"></i><span class="bi-nav-label">Herramientas Bee Framework</span><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
  <div class="collapse show" id="legacyBeeTools">
    <div class="nav-item <?php echo CONTROLLER === 'creator' ? 'active' : ''; ?>"><a class="nav-link" href="creator" aria-label="Creator" title="Creator"><i class="fas fa-fw fa-pen" aria-hidden="true"></i><span>Creator</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'botones' ? 'active' : ''; ?>"><a class="nav-link" href="admin/botones" aria-label="Botones" title="Botones"><i class="fas fa-fw fa-square-check" aria-hidden="true"></i><span>Botones</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'cartas' ? 'active' : ''; ?>"><a class="nav-link" href="admin/cartas" aria-label="Tarjetas" title="Tarjetas"><i class="fas fa-fw fa-id-card" aria-hidden="true"></i><span>Tarjetas</span></a></div>
    <div class="nav-item <?php echo CONTROLLER === 'bee' && METHOD === 'vuejs' ? 'active' : ''; ?>"><a class="nav-link" href="bee/vuejs" aria-label="Vue.js" title="Vue.js"><i class="fas fa-fw fa-code" aria-hidden="true"></i><span>Vue.js</span></a></div>
  </div>
  <hr class="sidebar-divider">

  <div class="sidebar-heading">Cuenta</div>
  <div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'perfil' ? 'active' : ''; ?>"><a class="nav-link" href="admin/perfil" aria-label="Perfil" title="Perfil"><i class="fas fa-fw fa-user" aria-hidden="true"></i><span>Perfil</span></a></div>
  <?php else: ?><div class="sidebar-heading">Cuenta</div><div class="nav-item <?php echo CONTROLLER === 'admin' && METHOD === 'perfil' ? 'active' : ''; ?>"><a class="nav-link" href="admin/perfil" aria-label="Perfil" title="Perfil"><i class="fas fa-fw fa-user" aria-hidden="true"></i><span>Perfil</span></a></div><?php endif; ?>
</nav>
