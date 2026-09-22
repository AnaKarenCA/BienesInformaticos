<?php

/** Base común de los módulos internos del inventario. */
class InventoryController extends Controller
{
  public function __construct()
  {
    if (!Auth::validate()) {
      Flasher::error('Debes iniciar sesión para consultar el inventario.');
      Redirect::to('login');
    }
    parent::__construct();
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

  protected function adminOnly(): void
  {
    $user = get_user();
    if (($user['rol'] ?? 'inventario') !== 'admin') {
      Flasher::error('No tienes autorización para administrar usuarios.');
      Redirect::to('admin');
    }
  }

  protected function can(string $permission): void
  {
    $user = get_user();
    $role = $user['rol'] ?? 'inventario';
    if ($role === 'admin') return;
    try {
      if ((new BeeRoleManager($role))->can($permission)) return;
    } catch (Exception $e) {
      // La migración crea el rol inventario; mientras tanto se limita a permisos básicos.
    }
    Flasher::error('No tienes permiso para realizar esta acción.');
    Redirect::to('admin');
  }
}
