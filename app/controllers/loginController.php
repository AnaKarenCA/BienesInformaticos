<?php 

class loginController extends Controller implements ControllerInterface 
{
  function __construct()
  {
    if (Auth::validate()) {
      Flasher::new('Ya hay una sesión abierta.');
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
    $this->setView('login');
    $this->render();
  }

  function registro()
  {
    $this->index();
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

        if (isset($user['activo']) && (int) $user['activo'] !== 1) {
          throw new Exception('Tu cuenta se encuentra inactiva.');
        }

        // Verifica el password del usuario con base al ingresado y el de la db
        if (!password_verify($password.AUTH_SALT, $user['password'])) {
          throw new Exception('Las credenciales no son correctas.');
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
      Redirect::back();
    }
  }

  function post_registro()
  {
    try {
      if (!Csrf::validate($_POST['csrf'] ?? '') || !check_posted_data(['nombre', 'usuario', 'email', 'password'], $_POST)) {
        throw new Exception('Completa los datos requeridos.');
      }
      $username = sanitize_input($_POST['usuario']);
      $email = sanitize_input($_POST['email']);
      $nombre = sanitize_input($_POST['nombre']);
      $password = $_POST['password'];
      if (!preg_match('/^[a-zA-Z0-9._-]{5,50}$/', $username)) throw new Exception('El usuario debe tener entre 5 y 50 caracteres válidos.');
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Ingresa un correo electrónico válido.');
      if (strlen($password) < 8) throw new Exception('La contraseña debe tener al menos 8 caracteres.');
      if (Model::query('SELECT id FROM bee_users WHERE username = :usuario OR email = :email', ['usuario' => $username, 'email' => $email])) throw new Exception('El usuario o correo ya está registrado.');
      Model::add('bee_users', [
        'username' => $username, 'email' => $email, 'password' => password_hash($password . AUTH_SALT, PASSWORD_BCRYPT),
        'nombre' => $nombre, 'telefono' => sanitize_input($_POST['telefono'] ?? ''), 'rol' => 'inventario', 'activo' => 1, 'created_at' => now()
      ]);
      Flasher::success('Registro realizado. Ya puedes iniciar sesión.');
      Redirect::to('login');
    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }
}
