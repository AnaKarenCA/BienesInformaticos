<?php

/** Recuperación de contraseña para usuarios anónimos; no revela si una cuenta existe. */
class PasswordRecoveryService
{
  private const TABLE = 'password_reset_requests';
  private const MAX_CODE_ATTEMPTS = 5;
  private const MAX_LOOKUP_REQUESTS_PER_HOUR = 3;
  private const MAX_IP_REQUESTS_PER_HOUR = 10;

  private static function env(string $name, string $default = ''): string
  {
    static $loaded = false;
    if (!$loaded) {
      $envFile = defined('ROOT') ? ROOT . '.env' : getcwd() . DIRECTORY_SEPARATOR . '.env';
      if (class_exists(\Dotenv\Dotenv::class) && is_file($envFile)) {
        \Dotenv\Dotenv::createImmutable(dirname($envFile))->safeLoad();
      }
      $loaded = true;
    }
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
    return is_string($value) && $value !== '' ? $value : $default;
  }

  public static function isMethodConfigured(string $method): bool
  {
    if (strlen(self::env('PASSWORD_RESET_PEPPER')) < 32) return false;
    if ($method === 'email') {
      if (!defined('PHPMAILER_SMTP') || PHPMAILER_SMTP !== true
        || !defined('PHPMAILER_HOST') || trim((string) PHPMAILER_HOST) === ''
        || !defined('PHPMAILER_AUTH') || PHPMAILER_AUTH !== true
        || !defined('PHPMAILER_USERNAME') || trim((string) PHPMAILER_USERNAME) === ''
        || !defined('PHPMAILER_PASSWORD') || trim((string) PHPMAILER_PASSWORD) === ''
        || !defined('PHPMAILER_SECURITY') || !defined('PHPMAILER_PORT')) {
        return false;
      }

      $security = strtolower((string) PHPMAILER_SECURITY);
      $port = (int) PHPMAILER_PORT;
      $gmailTransportMatches = ($port === 465 && $security === 'ssl')
        || ($port === 587 && $security === 'tls');

      return $gmailTransportMatches
        && filter_var((string) PHPMAILER_USERNAME, FILTER_VALIDATE_EMAIL) !== false;
    }
    if ($method === 'whatsapp') {
      return self::env('WHATSAPP_API_URL') !== ''
        && self::env('WHATSAPP_ACCESS_TOKEN') !== ''
        && self::env('WHATSAPP_PHONE_NUMBER_ID') !== ''
        && self::env('WHATSAPP_TEMPLATE_NAME') !== '';
    }
    return false;
  }

  private static function secret(): string
  {
    return self::env('PASSWORD_RESET_PEPPER');
  }

  public static function codeTtlMinutes(): int
  {
    return max(1, min(30, (int) self::env('PASSWORD_RESET_CODE_TTL_MINUTES', '10')));
  }

  private static function normalizeContact(string $method, string $contact): string
  {
    $contact = trim($contact);
    if ($method === 'email') {
      $contact = strtolower($contact);
      if (!filter_var($contact, FILTER_VALIDATE_EMAIL) || strlen($contact) > 100) {
        throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
      }
      return $contact;
    }
    if ($method === 'whatsapp') {
      if (!preg_match('/^\+?[0-9]{7,15}$/', $contact)) {
        throw new InvalidArgumentException('Ingresa un número de WhatsApp válido de 7 a 15 dígitos.');
      }
      return ltrim($contact, '+');
    }
    throw new InvalidArgumentException('Selecciona un medio de recuperación disponible.');
  }

  private static function lookupHash(string $method, string $contact): string
  {
    return hash_hmac('sha256', $method . '|' . $contact, self::secret());
  }

  private static function ipHash(string $ip): string
  {
    return hash_hmac('sha256', $ip, self::secret() . '|ip');
  }

  /** Registra toda solicitud para limitar abusos, pero emite código solo para cuentas activas. */
  public static function requestCode(string $method, string $rawContact, string $ip): string
  {
    if (!self::isMethodConfigured($method)) {
      throw new RuntimeException('El medio de recuperación aún no está configurado.');
    }
    $contact = self::normalizeContact($method, $rawContact);
    $lookupHash = self::lookupHash($method, $contact);
    $requestIpHash = self::ipHash($ip);
    $pdo = Db::connect(true);
    $pdo->beginTransaction();
    try {
      $limit = $pdo->prepare('SELECT SUM(lookup_hash = :lookup) AS lookup_count, SUM(request_ip_hash = :ip) AS ip_count FROM ' . self::TABLE . ' WHERE created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR) AND (lookup_hash = :lookup_filter OR request_ip_hash = :ip_filter)');
      $limit->execute(['lookup' => $lookupHash, 'ip' => $requestIpHash, 'lookup_filter' => $lookupHash, 'ip_filter' => $requestIpHash]);
      $counts = $limit->fetch(PDO::FETCH_ASSOC) ?: [];
      if ((int) ($counts['lookup_count'] ?? 0) >= self::MAX_LOOKUP_REQUESTS_PER_HOUR || (int) ($counts['ip_count'] ?? 0) >= self::MAX_IP_REQUESTS_PER_HOUR) {
        $pdo->commit();
        return $lookupHash;
      }

      $user = false;
      if ($method === 'email') {
        $stmt = $pdo->prepare('SELECT id, nombre, email, telefono FROM bee_users WHERE activo = 1 AND LOWER(email) = :contact LIMIT 2');
        $stmt->execute(['contact' => $contact]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user = count($matches) === 1 ? $matches[0] : false;
      } else {
        $stmt = $pdo->prepare('SELECT id, nombre, email, telefono FROM bee_users WHERE activo = 1 AND telefono = :contact LIMIT 2');
        $stmt->execute(['contact' => $contact]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user = count($matches) === 1 ? $matches[0] : false;
      }

      $code = $user ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) : null;
      if ($user) {
        $invalidate = $pdo->prepare('UPDATE ' . self::TABLE . ' SET used_at = UTC_TIMESTAMP() WHERE lookup_hash = :lookup AND used_at IS NULL AND code_hash IS NOT NULL');
        $invalidate->execute(['lookup' => $lookupHash]);
      }
      $insert = $pdo->prepare('INSERT INTO ' . self::TABLE . ' (user_id, method, lookup_hash, request_ip_hash, code_hash, expires_at, created_at) VALUES (:user_id, :method, :lookup_hash, :ip_hash, :code_hash, IF(:has_code = 1, DATE_ADD(UTC_TIMESTAMP(), INTERVAL ' . self::codeTtlMinutes() . ' MINUTE), NULL), UTC_TIMESTAMP())');
      $insert->execute([
        'user_id' => $code !== null ? (int) $user['id'] : null,
        'method' => $method,
        'lookup_hash' => $lookupHash,
        'ip_hash' => $requestIpHash,
        'code_hash' => $code !== null ? password_hash($code, PASSWORD_DEFAULT) : null,
        'has_code' => $code !== null ? 1 : 0,
      ]);
      $requestId = (int) $pdo->lastInsertId();
      $pdo->commit();

      if ($code !== null) {
        $sent = $method === 'email'
          ? self::sendEmail((string) $user['email'], (string) $user['nombre'], $code)
          : self::sendWhatsApp($contact, $code);
        if (!$sent) {
          $expire = $pdo->prepare('UPDATE ' . self::TABLE . ' SET used_at = UTC_TIMESTAMP() WHERE id = :id AND used_at IS NULL');
          $expire->execute(['id' => $requestId]);
        }
      }
      return $lookupHash;
    } catch (Throwable $error) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $error;
    }
  }

  /** @return array{status:string,reset_id?:int,user_id?:int,expires_at?:int} */
  public static function verifyCode(string $lookupHash, string $code): array
  {
    if (!preg_match('/^[0-9]{6}$/', $code)) return ['status' => 'invalid'];
    $pdo = Db::connect(true);
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('SELECT id, user_id, code_hash, expires_at, used_at, attempts FROM ' . self::TABLE . ' WHERE lookup_hash = :lookup AND code_hash IS NOT NULL ORDER BY id DESC LIMIT 1 FOR UPDATE');
      $stmt->execute(['lookup' => $lookupHash]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row || $row['used_at'] !== null || (int) $row['attempts'] >= self::MAX_CODE_ATTEMPTS) {
        $pdo->commit();
        return ['status' => 'invalid'];
      }
      if (strtotime((string) $row['expires_at'] . ' UTC') <= time()) {
        $pdo->commit();
        return ['status' => 'expired'];
      }
      if (!password_verify($code, (string) $row['code_hash'])) {
        $update = $pdo->prepare('UPDATE ' . self::TABLE . ' SET attempts = attempts + 1 WHERE id = :id');
        $update->execute(['id' => $row['id']]);
        $pdo->commit();
        return ['status' => 'invalid'];
      }
      if (empty($row['user_id'])) {
        $pdo->commit();
        return ['status' => 'invalid'];
      }
      $update = $pdo->prepare('UPDATE ' . self::TABLE . ' SET verified_at = UTC_TIMESTAMP() WHERE id = :id AND verified_at IS NULL');
      $update->execute(['id' => $row['id']]);
      $pdo->commit();
      return [
        'status' => 'verified',
        'reset_id' => (int) $row['id'],
        'user_id' => (int) $row['user_id'],
        'expires_at' => strtotime((string) $row['expires_at'] . ' UTC'),
      ];
    } catch (Throwable $error) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $error;
    }
  }

  public static function validateNewPassword(string $password): bool
  {
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-])[A-Za-z\d!@#$%^&*_-]{5,20}$/', $password);
  }

  public static function resetPassword(int $resetId, int $userId, string $password): bool
  {
    if ($resetId < 1 || $userId < 1 || !self::validateNewPassword($password)) return false;
    $pdo = Db::connect(true);
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('SELECT user_id, lookup_hash, expires_at, verified_at, used_at, attempts FROM ' . self::TABLE . ' WHERE id = :id FOR UPDATE');
      $stmt->execute(['id' => $resetId]);
      $reset = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$reset || (int) $reset['user_id'] !== $userId || $reset['verified_at'] === null || $reset['used_at'] !== null || (int) $reset['attempts'] >= self::MAX_CODE_ATTEMPTS || strtotime((string) $reset['expires_at'] . ' UTC') <= time()) {
        $pdo->rollBack();
        return false;
      }
      $hash = password_hash($password . AUTH_SALT, PASSWORD_BCRYPT);
      $updateUser = $pdo->prepare('UPDATE bee_users SET password = :password WHERE id = :user_id AND activo = 1');
      $updateUser->execute(['password' => $hash, 'user_id' => $userId]);
      if ($updateUser->rowCount() !== 1) {
        $pdo->rollBack();
        return false;
      }
      $markUsed = $pdo->prepare('UPDATE ' . self::TABLE . ' SET used_at = UTC_TIMESTAMP() WHERE id = :id AND used_at IS NULL');
      $markUsed->execute(['id' => $resetId]);
      $invalidateOther = $pdo->prepare('UPDATE ' . self::TABLE . ' SET used_at = UTC_TIMESTAMP() WHERE user_id = :user_id AND id <> :id AND used_at IS NULL AND code_hash IS NOT NULL');
      $invalidateOther->execute(['user_id' => $userId, 'id' => $resetId]);
      $pdo->commit();
      return true;
    } catch (Throwable $error) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $error;
    }
  }

  private static function sendEmail(string $email, string $name, string $code): bool
  {
    try {
      $safeName = htmlspecialchars($name !== '' ? $name : 'usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $body = '<main style="max-width:560px;margin:24px auto;padding:28px;border:1px solid #E5E5E5;border-radius:12px">'
        . '<h1 style="font-size:22px;color:#CB2027">Recuperación de contraseña</h1>'
        . '<p>Hola, ' . $safeName . ':</p>'
        . '<p>Recibimos una solicitud para recuperar la contraseña de tu cuenta en Bienes Informáticos.</p>'
        . '<p style="margin:24px 0 8px">Tu código de recuperación es:</p>'
        . '<p style="font-size:32px;font-weight:bold;letter-spacing:8px;margin:0 0 18px">' . $code . '</p>'
        . '<p>Este código vence en ' . self::codeTtlMinutes() . ' minutos y solo puede utilizarse una vez.</p>'
        . '<p>Si no solicitaste esta recuperación, ignora este mensaje.</p></main>';
      $alt = "Recuperación de contraseña\n\nHola, " . ($name !== '' ? $name : 'usuario')
        . ":\n\nTu código es {$code}. Vence en " . self::codeTtlMinutes()
        . " minutos y solo puede utilizarse una vez. Si no solicitaste esta recuperación, ignora este mensaje.";

      return send_email(
        get_siteemail(),
        $email,
        'Recuperación de contraseña · Bienes Informáticos',
        $body,
        $alt
      );
    } catch (Throwable $error) {
      return false;
    }
  }

  private static function sendWhatsApp(string $phone, string $code): bool
  {
    if (!function_exists('curl_init')) return false;
    $baseUrl = rtrim(self::env('WHATSAPP_API_URL'), '/');
    $phoneNumberId = self::env('WHATSAPP_PHONE_NUMBER_ID');
    $url = str_ends_with($baseUrl, '/messages') ? $baseUrl : $baseUrl . '/' . rawurlencode($phoneNumberId) . '/messages';
    $payload = [
      'messaging_product' => 'whatsapp',
      'to' => $phone,
      'type' => 'template',
      'template' => [
        'name' => self::env('WHATSAPP_TEMPLATE_NAME'),
        'language' => ['code' => self::env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_MX')],
        'components' => [['type' => 'body', 'parameters' => [
          ['type' => 'text', 'text' => $code],
          ['type' => 'text', 'text' => (string) self::codeTtlMinutes()],
        ]]],
      ],
    ];
    $handle = curl_init($url);
    curl_setopt_array($handle, [
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT => 12,
      CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . self::env('WHATSAPP_ACCESS_TOKEN'), 'Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_errno($handle);
    curl_close($handle);
    return $error === 0 && $status >= 200 && $status < 300;
  }
}
