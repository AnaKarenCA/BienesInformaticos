<?php

/**
 * @author Joystick
 * 
 * Contribuido por
 * @author Pere | Discord
 * 
 * @version 1.1.0
 *
 */
class Redirect
{
  private $location;

  /**
   * Método para redirigir al usuario a una sección determinada
   *
   * @param string $location
   * @return void
   */
  public static function to($location)
  {
    $self           = new self();
    $self->location = $location;

    // Verificar que si la URL o locación de redirección es externa o no
    if (strpos($self->location, 'http') === false) {
      $self->location = URL . $self->location;
    }

    // Si las cabeceras ya fueron envíadas
    if (headers_sent()) {
      echo '<script type="text/javascript">';
      echo 'window.location.href="' . $self->location . '";';
      echo '</script>';
      echo '<noscript>';
      echo '<meta http-equiv="refresh" content="0;url=' . $self->location . '" />';
      echo '</noscript>';
      die();
    }

    // Redirigir al usuario a otra sección
    header('Location: ' . $self->location);
    die();
  }

  /**
   * Redirige de vuelta a la URL previa
   *
   * @param string $location
   * @return void
   */
  public static function back($location = '')
  {
    if (!isset($_POST['redirect_to']) && !isset($_GET['redirect_to']) && $location == '') {
      $base = parse_url(URL);
      $referer = parse_url($_SERVER['HTTP_REFERER'] ?? '');
      $sameOrigin = $base && $referer
        && strtolower((string) ($base['scheme'] ?? '')) === strtolower((string) ($referer['scheme'] ?? ''))
        && strtolower((string) ($base['host'] ?? '')) === strtolower((string) ($referer['host'] ?? ''))
        && (int) ($base['port'] ?? 0) === (int) ($referer['port'] ?? 0);
      $basePath = rtrim((string) ($base['path'] ?? ''), '/') . '/';
      $refererPath = (string) ($referer['path'] ?? '');
      $insideApplication = $basePath === '/' || strpos($refererPath, $basePath) === 0;

      if ($sameOrigin && $insideApplication) {
        $target = $refererPath . (isset($referer['query']) ? '?' . $referer['query'] : '');
        header('Location: ' . $target);
        die();
      }

      header('Location: ' . URL . DEFAULT_CONTROLLER);
      die();
    }

    if (isset($_POST['redirect_to'])) {
      header(sprintf('Location: %s', $_POST['redirect_to']));
      die();
    }

    if (isset($_GET['redirect_to'])) {
      header(sprintf('Location: %s', urldecode($_GET['redirect_to'])));
      die();
    }

    if (!empty($location)) {
      self::to($location);
    }
  }
}
