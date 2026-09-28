<?php require_once INCLUDES . 'admin/header.php'; ?>

<body id="page-top" class="bi-legacy-body">
  <a class="bi-skip-link" href="#mainContent">Saltar al contenido principal</a>

  <!-- Page Wrapper -->
  <div id="wrapper">

    <?php require_once INCLUDES . 'admin/sidebar.php'; ?>
    <div class="bi-legacy-backdrop" data-bi-legacy-backdrop aria-hidden="true"></div>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <?php require_once INCLUDES . 'admin/topbar.php'; ?>

        <!-- Begin Page Content -->
        <main class="container-fluid px-4 pb-4" id="mainContent" tabindex="-1">

          <?php if (!(CONTROLLER === 'admin' && METHOD === 'index')): ?>
            <nav class="bi-breadcrumbs" aria-label="Ruta de navegación"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="admin">Inicio</a></li><li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars((string) ($d->title ?? 'Sección'), ENT_QUOTES, 'UTF-8'); ?></li></ol></nav>
          <?php endif; ?>

          <!-- Page Heading -->
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 bi-title"><?php echo htmlspecialchars((string) ($d->title ?? 'Administración'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <?php require_once INCLUDES . 'admin/dashboardButtons.php'; ?>
          </div>

          <?php echo Flasher::flash(); ?>
