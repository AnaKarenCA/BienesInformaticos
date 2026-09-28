<?php

/** Base común de los módulos internos del inventario. */
class InventoryController extends Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->requirePermission('inventario-consultar');
    $user = get_user();
    $this->addToData('can_manage_goods', $this->hasPermission('bienes-guardar', $user));
    $this->addToData('can_inactivate_goods', $this->hasPermission('bienes-inactivar', $user));
    $this->addToData('can_view_catalogs', true);
    $this->addToData('can_manage_catalogs', $this->hasPermission('catalogos-guardar', $user));
    $this->addToData('can_inactivate_catalogs', $this->hasPermission('catalogos-inactivar', $user));
    $this->addToData('can_delete_catalogs', $this->hasPermission('catalogos-eliminar', $user));
    $this->addToData('can_change_custody', $this->hasPermission('bienes-inactivar', $user));
    $this->addToData('can_access_documents', true);
    $this->addToData('can_generate_documents', $this->hasPermission('documentos-generar', $user));
    $this->addToData('can_admin_users', $this->hasPermission('admin-access', $user));
    $this->addToData('can_export_reports', $this->hasPermission('reportes-exportar', $user));
    if ((get_user('rol') ?? '') === 'admin') {
      $this->addToData('pending_user_count', userModel::count_pending());
    }
    $this->setEngine('twig');
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('current_user', get_user());
    $this->addToData('flash_html', Flasher::flash());
  }

  protected function renderInventory(string $view, array $data = []): void
  {
    foreach ($data as $key => $value) $this->addToData($key, $value);
    $this->setView($view);
    $this->render();
  }

  protected function can(string $permission): void
  {
    $this->requirePermission($permission);
  }
}
