<?php 

class loginController extends Controller implements ControllerInterface 
{
  function __construct()
  {
    if (Auth::validate()) {
      Redirect::to('admin');
    }

    // Ejecutar la funcionalidad del Controller padre
    parent::__construct();
  }

  function index()
  {
    $this->setTitle('Ingresa a tu cuenta');
    $this->setEngine('twig');
    $this->addToData('csrf', (new Csrf())->get_token());
    $this->addToData('flash_html', Flasher::flash());
    $flow = $_SESSION['password_recovery_flow'] ?? null;
    $verified = $_SESSION['password_recovery_verified'] ?? null;
    if (is_array($verified) && (int) ($verified['expires_at'] ?? 0) <= time()) {
      unset($_SESSION['password_recovery_verified'], $_SESSION['password_recovery_flow']);
      $flow = null;
      $verified = null;
    }
    $this->addToData('recovery_step', is_array($verified) ? 'password' : (is_array($flow) ? 'verify' : 'request'));
    $this->addToData('recovery_open', is_array($flow) || is_array($verified));
    $this->addToData('recovery_email_available', PasswordRecoveryService::isMethodConfigured('email'));
    $this->addToData('recovery_whatsapp_available', PasswordRecoveryService::isMethodConfigured('whatsapp'));
    $this->addToData('recovery_ttl_minutes', PasswordRecoveryService::codeTtlMinutes());
    $this->setView('login');
    $this->render();
  }

  function recovery_request()
  {
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud no es válida. Actualiza la página e inténtalo nuevamente.');
      $method = (string) ($_POST['method'] ?? '');
      $contact = (string) ($method === 'email' ? ($_POST['email'] ?? '') : ($_POST['telefono'] ?? ''));
      $lookupHash = PasswordRecoveryService::requestCode($method, $contact, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
      $_SESSION['password_recovery_flow'] = ['lookup_hash' => $lookupHash, 'method' => $method];
      unset($_SESSION['password_recovery_verified']);
      Flasher::info('Si los datos proporcionados corresponden a una cuenta activa, recibirás un código de recuperación por el medio seleccionado.');
    } catch (InvalidArgumentException $e) {
      Flasher::error($e->getMessage());
    } catch (RuntimeException $e) {
      Flasher::warn('Este medio de recuperación no está disponible en este momento. Contacta a administración.');
    } catch (Throwable $e) {
      Flasher::error('No fue posible procesar la solicitud. Inténtalo nuevamente más tarde.');
    }
    Redirect::to('login#recuperar-contrasena');
  }

  function recovery_verify()
  {
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud no es válida. Actualiza la página e inténtalo nuevamente.');
      $flow = $_SESSION['password_recovery_flow'] ?? null;
      if (!is_array($flow) || empty($flow['lookup_hash'])) throw new Exception('Inicia nuevamente la solicitud de recuperación.');
      $result = PasswordRecoveryService::verifyCode((string) $flow['lookup_hash'], trim((string) ($_POST['code'] ?? '')));
      if ($result['status'] === 'expired') throw new Exception('El código ha expirado. Solicita uno nuevo.');
      if ($result['status'] !== 'verified') throw new Exception('El código ingresado no es válido.');
      $_SESSION['password_recovery_verified'] = [
        'reset_id' => $result['reset_id'],
        'user_id' => $result['user_id'],
        'expires_at' => $result['expires_at'],
      ];
    } catch (Throwable $e) {
      $expected = [
        'La solicitud no es válida. Actualiza la página e inténtalo nuevamente.',
        'Inicia nuevamente la solicitud de recuperación.',
        'El código ha expirado. Solicita uno nuevo.',
        'El código ingresado no es válido.',
      ];
      Flasher::error(in_array($e->getMessage(), $expected, true) ? $e->getMessage() : 'No fue posible verificar el código. Inténtalo nuevamente.');
    }
    Redirect::to('login#recuperar-contrasena');
  }

  function recovery_reset()
  {
    $success = false;
    try {
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::validate($_POST['csrf'] ?? '')) throw new Exception('La solicitud no es válida. Actualiza la página e inténtalo nuevamente.');
      $verified = $_SESSION['password_recovery_verified'] ?? null;
      if (!is_array($verified) || (int) ($verified['expires_at'] ?? 0) <= time()) throw new Exception('La verificación expiró. Solicita un nuevo código.');
      $password = (string) ($_POST['password'] ?? '');
      $confirmation = (string) ($_POST['password_confirm'] ?? '');
      if ($password !== $confirmation) throw new Exception('Las contraseñas no coinciden.');
      if (!PasswordRecoveryService::validateNewPassword($password)) throw new Exception('La contraseña debe tener entre 5 y 20 caracteres e incluir minúscula, mayúscula, número y un carácter especial permitido (!@#$%^&*_-).');
      if (!PasswordRecoveryService::resetPassword((int) $verified['reset_id'], (int) $verified['user_id'], $password)) throw new Exception('La verificación expiró o ya fue utilizada. Solicita un nuevo código.');
      unset($_SESSION['password_recovery_verified'], $_SESSION['password_recovery_flow']);
      Flasher::success('La contraseña se ha restablecido correctamente. Ya puedes iniciar sesión con tu nueva contraseña.');
      $success = true;
    } catch (Throwable $e) {
      $expected = [
        'La solicitud no es válida. Actualiza la página e inténtalo nuevamente.',
        'La verificación expiró. Solicita un nuevo código.',
        'Las contraseñas no coinciden.',
        'La contraseña debe tener entre 5 y 20 caracteres e incluir minúscula, mayúscula, número y un carácter especial permitido (!@#$%^&*_-).',
        'La verificación expiró o ya fue utilizada. Solicita un nuevo código.',
      ];
      Flasher::error(in_array($e->getMessage(), $expected, true) ? $e->getMessage() : 'No fue posible restablecer la contraseña. Inténtalo nuevamente.');
    }
    Redirect::to($success ? 'login' : 'login#recuperar-contrasena');
  }

  function recovery_cancel()
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::validate($_POST['csrf'] ?? '')) {
      unset($_SESSION['password_recovery_verified'], $_SESSION['password_recovery_flow']);
    } else {
      Flasher::error('La solicitud no es válida. Actualiza la página e inténtalo nuevamente.');
    }
    Redirect::to('login');
  }

  function registro()
  {
    Redirect::to('login');
  }

  function post_login()
  {
    try {
      if (!Csrf::validate($_POST['csrf']) || !check_posted_data(['usuario','csrf','password'], $_POST)) {
        throw new Exception(get_bee_message(0));
      }
  
      // Data pasada del formulario
      $usuario  = sanitize_input($_POST['usuario']);
      $password = sanitize_input($_POST['password']);
  
      // Información del usuario loggeado, simplemente se puede reemplazar aquí con un query a la base de datos
      // para cargar la información del usuario si es existente
  
      // Sesiones no persistentes con variables de sesión normales
      if (persistent_session() === false) {
        // Credenciales dummy de usuario, solo son usadas si BEE_COOKIES es false | settings.php
        $user = 
        [
          'id'       => 123,
          'name'     => 'Bee Default', 
          'email'    => 'hellow@joystick.com.mx', 
          'avatar'   => 'myavatar.jpg', 
          'tel'      => '11223344', 
          'color'    => '#112233',
          'username' => 'bee', // puedes cambiar este dato a lo que gustes si usarás este sistema de login (es relativamente seguro dependiendo el tipo de sistema)
          'password' => '$2y$10$xHEI5cJ3q7rBJaL.M9qBRe909ahHvIZVTfRRxlLqfnWwAYwWQE/Wu' // 123456 por defecto, puedes generar una nueva en bee/password
        ];
    
        // Verificar información del usuario
        if ($usuario !== $user['username'] || !password_verify($password.AUTH_SALT, $user['password'])) {
          throw new Exception('Las credenciales no son correctas.');
        }
  
        // Registrar la información en sesión
        Auth::login($user['id'], $user);
  
      } else {
        // Verificar información del usuario
        if (!$user = Model::list(BEE_USERS_TABLE, ['username' => $usuario], 1)) {
          throw new Exception('Las credenciales no son correctas.');
        }

        if (!password_verify($password . AUTH_SALT, $user['password'])) {
          throw new Exception('Las credenciales no son correctas.');
        }

        if ((int) ($user['activo'] ?? 0) !== 1) {
          throw new Exception('Tu cuenta se encuentra inactiva.');
        }
  
        // Sesiones totalmente persistentes con base a Cookies
        BeeSession::new_session($user['id']);

        // Recargar información de usuario
        $user = Model::list(BEE_USERS_TABLE, ['id' => $user['id']], 1);

        // Registrar la información en sesión
        Auth::login($user['id'], $user);
      }
      
      // Redirección a la página inicial después de log in
      Redirect::to('admin');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::to('login');
    }
  }

  function post_registro()
  {
    Flasher::error('La creación de cuentas está disponible únicamente desde Administración > Todos los usuarios.');
    Redirect::to('login');
  }
}
