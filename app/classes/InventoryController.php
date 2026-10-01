<?php

/** Base común de los módulos internos del inventario. */
class InventoryController extends Controller
{
  public function __construct()
  {
    parent::__construct();
    $user = get_user();
    $this->addToData('can_create_goods', $this->hasPermission('bienes-crear', $user));
    $this->addToData('can_update_goods', $this->hasPermission('bienes-actualizar', $user));
    $this->addToData('can_manage_goods', $this->hasPermission('bienes-crear', $user) || $this->hasPermission('bienes-actualizar', $user));
    $this->addToData('can_activate_goods', $this->hasPermission('bienes-activar', $user));
    $this->addToData('can_deactivate_goods', $this->hasPermission('bienes-desactivar', $user));
    $this->addToData('can_inactivate_goods', $this->hasPermission('bienes-activar', $user) && $this->hasPermission('bienes-desactivar', $user));
    $this->addToData('can_print_goods', $this->hasPermission('bienes-imprimir', $user));
    $this->addToData('can_view_catalogs', $this->hasPermission('catalogos-consultar', $user));
    $this->addToData('can_create_catalogs', $this->hasPermission('catalogos-crear', $user));
    $this->addToData('can_update_catalogs', $this->hasPermission('catalogos-actualizar', $user));
    $this->addToData('can_manage_catalogs', $this->hasPermission('catalogos-crear', $user) || $this->hasPermission('catalogos-actualizar', $user));
    $this->addToData('can_activate_catalogs', $this->hasPermission('catalogos-activar', $user));
    $this->addToData('can_deactivate_catalogs', $this->hasPermission('catalogos-desactivar', $user));
    $this->addToData('can_inactivate_catalogs', $this->hasPermission('catalogos-activar', $user) && $this->hasPermission('catalogos-desactivar', $user));
    $this->addToData('can_change_custody', $this->hasPermission('catalogos-actualizar', $user) && $this->hasPermission('catalogos-desactivar', $user));
    $this->addToData('can_access_documents', $this->hasPermission('documentos-consultar', $user));
    $this->addToData('can_generate_documents', $this->hasPermission('documentos-generar', $user));
    $this->addToData('can_admin_users', $this->hasPermission('admin-access', $user));
    $this->addToData('can_export_reports', $this->hasPermission('reportes-exportar', $user));
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
