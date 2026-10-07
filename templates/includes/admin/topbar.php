<nav class="navbar navbar-expand topbar bi-legacy-topbar" aria-label="Barra superior">
  <button id="biLegacyNavToggle" class="btn bi-topbar-toggle me-3" type="button" aria-label="Mostrar u ocultar menú" aria-controls="accordionSidebar" aria-expanded="false" title="Mostrar u ocultar menú"><i class="fas fa-bars" aria-hidden="true"></i></button>
  <span class="bi-legacy-brand">Bienes Informáticos</span>
  <div class="ms-auto d-flex align-items-center gap-3">
    <a class="bi-legacy-user" href="admin/perfil" title="Ver perfil"><i class="fas fa-user-circle me-2" aria-hidden="true"></i><span><?php echo htmlspecialchars((string) (get_user('username') ?: 'Usuario'), ENT_QUOTES, 'UTF-8'); ?></span></a>
    <a class="btn btn-sm btn-outline-secondary bi-legacy-logout" href="logout"><i class="fas fa-right-from-bracket me-1" aria-hidden="true"></i><span>Cerrar sesión</span></a>
  </div>
</nav>
