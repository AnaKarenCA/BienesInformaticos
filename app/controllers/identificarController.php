<?php

class identificarController extends InventoryController implements ControllerInterface
{
  public function index()
  {
    $consulta = trim((string) ($_GET['q'] ?? ''));
    $coincidencias = $consulta !== '' ? BienModel::identificarCoincidencias($consulta) : [];
    $bien = [];
    $idSeleccionado = filter_var($_GET['bien'] ?? null, FILTER_VALIDATE_INT);
    if ($idSeleccionado && $coincidencias) {
      foreach ($coincidencias as $coincidencia) {
        if ((int) $coincidencia['id_bien'] === $idSeleccionado) {
          $bien = BienModel::porId((int) $idSeleccionado);
          break;
        }
      }
    } elseif (count($coincidencias) === 1) {
      $bien = BienModel::porId((int) $coincidencias[0]['id_bien']);
    }
    $this->setTitle('Identificar bien');
    $this->renderInventory('index', [
      'consulta' => $consulta, 'coincidencias' => $coincidencias, 'bien' => $bien,
      'busqueda_corta' => $consulta !== '' && mb_strlen($consulta, 'UTF-8') < 3,
      'bien_seleccionado' => $idSeleccionado ?: null
    ]);
  }
}
