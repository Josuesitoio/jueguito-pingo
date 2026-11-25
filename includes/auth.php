<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('VENTANILLA_EXPRES_SESS');
        session_start();
    }
}

function usuario_actual(): ?array
{
    iniciar_sesion();
    if (empty($_SESSION['usuario']) || !is_string($_SESSION['usuario'])) {
        return null;
    }

    return buscar_usuario($_SESSION['usuario']);
}

function requerir_sesion(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        unset($_SESSION['usuario'], $_SESSION['partida']);
        header('Location: index.php');
        exit;
    }

    return $usuario;
}

function redirigir_si_autenticado(): void
{
    if (usuario_actual() !== null) {
        header('Location: escritorio.php');
        exit;
    }
}

function set_flash(string $tipo, string $mensaje): void
{
    iniciar_sesion();
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensaje' => $mensaje,
    ];
}

function get_flash(): ?array
{
    iniciar_sesion();
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function autenticar_usuario(string $nombreUsuario, string $password): array
{
    $nombreLimpio = trim($nombreUsuario);
    if ($nombreLimpio === '' || $password === '') {
        return ['ok' => false, 'error' => 'Ingresa tu nombre de usuario y contraseña.'];
    }

    $usuario = buscar_usuario($nombreLimpio);
    if ($usuario === null || (string) ($usuario['password'] ?? '') !== $password) {
        return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
    }

    iniciar_sesion();
    session_regenerate_id(true);
    $_SESSION['usuario'] = $usuario['usuario'];
    unset($_SESSION['partida']);

    return ['ok' => true, 'usuario' => $usuario];
}

function registrar_usuario(string $nombreUsuario, string $password): array
{
    $nombreLimpio = trim($nombreUsuario);
    if ($nombreLimpio === '') {
        return ['ok' => false, 'error' => 'El nombre de usuario no puede estar vacío.'];
    }
    if (mb_strlen($nombreLimpio, 'UTF-8') < 2 || mb_strlen($nombreLimpio, 'UTF-8') > 24) {
        return ['ok' => false, 'error' => 'El usuario debe tener entre 2 y 24 caracteres.'];
    }
    if ($password === '') {
        return ['ok' => false, 'error' => 'Debes ingresar una contraseña.'];
    }

    $resultado = actualizar_usuarios(function (array &$datos) use ($nombreLimpio, $password): array {
        $objetivo = mb_strtolower($nombreLimpio, 'UTF-8');
        foreach ($datos['usuarios'] as $u) {
            if (isset($u['usuario']) && mb_strtolower((string) $u['usuario'], 'UTF-8') === $objetivo) {
                return ['_abortar' => true, 'ok' => false, 'error' => 'Ese nombre de usuario ya está registrado.'];
            }
        }

        $nuevo = [
            'usuario' => $nombreLimpio,
            'password' => $password,
            'puntos_totales' => 0,
            'nivel_desbloqueado' => 1,
            'records_nivel' => [
                '1' => 0,
                '2' => 0,
                '3' => 0,
                '4' => 0,
                '5' => 0,
            ],
            'niveles_perfectos' => [],
        ];

        $datos['usuarios'][] = $nuevo;
        return ['ok' => true, 'usuario' => $nuevo];
    });

    if (!empty($resultado['ok'])) {
        iniciar_sesion();
        session_regenerate_id(true);
        $_SESSION['usuario'] = $resultado['usuario']['usuario'];
        unset($_SESSION['partida']);
    }

    return $resultado;
}

function cerrar_sesion(): void
{
    iniciar_sesion();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
