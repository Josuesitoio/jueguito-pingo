<?php
declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'data');
define('USUARIOS_FILE', DATA_DIR . DIRECTORY_SEPARATOR . 'usuarios.json');
define('CASOS_FILE', DATA_DIR . DIRECTORY_SEPARATOR . 'casos.json');
define('ICONS_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'icons');

function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function icono(string $nombre, string $clase = 'icon'): string
{
    static $cache = [];
    $seguro = preg_replace('/[^a-z0-9_-]/i', '', $nombre);
    if (!isset($cache[$seguro])) {
        $ruta = ICONS_DIR . DIRECTORY_SEPARATOR . $seguro . '.svg';
        if (is_file($ruta)) {
            $cache[$seguro] = trim((string) file_get_contents($ruta));
        } else {
            $cache[$seguro] = '';
        }
    }

    $svg = $cache[$seguro];
    if ($svg === '') {
        return '';
    }

    return preg_replace('/<svg\b/', '<svg class="' . h($clase) . '"', $svg, 1) ?? $svg;
}

function leer_json(string $ruta): array
{
    if (!is_file($ruta)) {
        return [];
    }

    $fp = fopen($ruta, 'rb');
    if ($fp === false) {
        return [];
    }

    flock($fp, LOCK_SH);
    $contenido = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    if ($contenido === false || trim($contenido) === '') {
        return [];
    }

    $datos = json_decode($contenido, true);
    return is_array($datos) ? $datos : [];
}

function normalizar_usuario(array $u): array
{
    $records = [];
    $suma = 0;
    for ($i = 1; $i <= 5; $i++) {
        $clave = (string) $i;
        $valor = isset($u['records_nivel'][$clave]) ? (int) $u['records_nivel'][$clave] : 0;
        $valor = max(0, min(200, $valor));
        $records[$clave] = $valor;
        $suma += $valor;
    }

    $perfectos = isset($u['niveles_perfectos']) && is_array($u['niveles_perfectos'])
        ? array_values(array_unique(array_map('intval', $u['niveles_perfectos'])))
        : [];
    sort($perfectos);

    return [
        'usuario' => (string) ($u['usuario'] ?? ''),
        'password' => (string) ($u['password'] ?? ''),
        'puntos_totales' => min(1000, $suma),
        'nivel_desbloqueado' => max(1, min(5, (int) ($u['nivel_desbloqueado'] ?? 1))),
        'records_nivel' => $records,
        'niveles_perfectos' => $perfectos,
    ];
}

/**
 * Ejecuta una actualización atómica con bloqueo exclusivo sobre usuarios.json.
 * Si el callback retorna ['_abortar' => true, ...], no escribe en disco.
 */
function actualizar_usuarios(callable $callback)
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }

    $fp = fopen(USUARIOS_FILE, 'c+b');
    if ($fp === false) {
        throw new RuntimeException('No se pudo abrir el archivo de usuarios.');
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new RuntimeException('No se pudo bloquear el archivo de usuarios.');
    }

    rewind($fp);
    $contenido = stream_get_contents($fp);
    $datos = ($contenido !== false && trim($contenido) !== '') ? json_decode($contenido, true) : null;
    if (!is_array($datos) || !isset($datos['usuarios']) || !is_array($datos['usuarios'])) {
        $datos = ['usuarios' => []];
    }

    $resultado = $callback($datos);
    $abortar = is_array($resultado) && !empty($resultado['_abortar']);

    if (!$abortar) {
        foreach ($datos['usuarios'] as $idx => $usuario) {
            $datos['usuarios'][$idx] = normalizar_usuario($usuario);
        }

        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, $json);
        fflush($fp);
    }

    flock($fp, LOCK_UN);
    fclose($fp);

    return $resultado;
}

function buscar_usuario(string $nombreUsuario): ?array
{
    $datos = leer_json(USUARIOS_FILE);
    $usuarios = $datos['usuarios'] ?? [];
    $objetivo = mb_strtolower(trim($nombreUsuario), 'UTF-8');

    foreach ($usuarios as $u) {
        if (isset($u['usuario']) && mb_strtolower((string) $u['usuario'], 'UTF-8') === $objetivo) {
            return normalizar_usuario($u);
        }
    }

    return null;
}

function cargar_casos(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = leer_json(CASOS_FILE);
    }
    return $cache;
}

function obtener_niveles(): array
{
    $datos = cargar_casos();
    return $datos['niveles'] ?? [];
}

function obtener_nivel(int $nivelId): ?array
{
    foreach (obtener_niveles() as $nivel) {
        if ((int) ($nivel['id'] ?? 0) === $nivelId) {
            return $nivel;
        }
    }
    return null;
}

function obtener_documentos_catalogo(): array
{
    return [
        'curp' => [
            'id' => 'curp',
            'nombre' => 'CURP',
            'nombre_completo' => 'Clave Única de Registro de Población',
            'resumen' => 'Identidad poblacional (18 caracteres)',
            'color' => '#2D6A4F',
            'clase' => 'doc-curp',
            'icono' => 'curp',
            'que_es' => 'Clave alfanumérica única de 18 caracteres asignada a ciudadanos y residentes en México.',
            'para_que' => 'Identificación poblacional oficial para becas, trámites federales, expedientes escolares, clínicos y pasaporte.',
            'que_revisar' => 'Debe tener exactamente 18 caracteres alfanuméricos y su fecha incrustada (posiciones 5 a 10: AAMMDD) debe coincidir con la fecha de nacimiento del titular.',
        ],
        'nss' => [
            'id' => 'nss',
            'nombre' => 'NSS',
            'nombre_completo' => 'Número de Seguridad Social',
            'resumen' => 'Seguridad social e Infonavit (11 dígitos)',
            'color' => '#2B5B84',
            'clase' => 'doc-nss',
            'icono' => 'nss',
            'que_es' => 'Número único, permanente e intransferible de 11 dígitos emitido por el IMSS.',
            'para_que' => 'Alta en empleos formales, acceso a servicios médicos de seguridad social, semanas cotizadas y crédito Infonavit.',
            'que_revisar' => 'Debe constar exclusivamente de 11 dígitos numéricos, sin letras, guiones ni espacios en blanco.',
        ],
        'acta' => [
            'id' => 'acta',
            'nombre' => 'Acta de nacimiento',
            'nombre_completo' => 'Acta de Nacimiento Oficial',
            'resumen' => 'Nombre completo, edad y filiación',
            'color' => '#8C6239',
            'clase' => 'doc-acta',
            'icono' => 'acta',
            'que_es' => 'Documento expedido por el Registro Civil que certifica oficialmente el nacimiento de una persona.',
            'para_que' => 'Acreditar nombre completo, fecha y lugar de nacimiento, edad y filiación (parentesco con padres) en escuelas y trámites civiles.',
            'que_revisar' => 'El nombre completo del documento debe coincidir exactamente con el titular del trámite y presentar fechas lógicas.',
        ],
        'cfe' => [
            'id' => 'cfe',
            'nombre' => 'Recibo de CFE',
            'nombre_completo' => 'Comprobante de Domicilio (CFE)',
            'resumen' => 'Comprobante oficial de domicilio',
            'color' => '#1F7A54',
            'clase' => 'doc-cfe',
            'icono' => 'cfe',
            'que_es' => 'Estado de cuenta o recibo del servicio eléctrico emitido por la Comisión Federal de Electricidad.',
            'para_que' => 'Acreditar la dirección física actual del solicitante al abrir cuentas bancarias o contratar servicios.',
            'que_revisar' => 'Antigüedad máxima de 3 meses respecto a la fecha del trámite. Nunca es válido como identificación oficial con fotografía.',
        ],
        'correo' => [
            'id' => 'correo',
            'nombre' => 'Correo electrónico',
            'nombre_completo' => 'Dirección de Correo Electrónico',
            'resumen' => 'Contacto, avisos y recuperación',
            'color' => '#4A5568',
            'clase' => 'doc-correo',
            'icono' => 'correo',
            'que_es' => 'Dirección digital personal para intercambio de mensajes electrónicos y avisos institucionales.',
            'para_que' => 'Creación de cuentas en portales digitales, recepción de constancias, notificaciones y recuperación de contraseñas.',
            'que_revisar' => 'Debe incluir exactamente un símbolo @, un dominio con extensión válida (ej. .mx o .com) y no contener espacios en blanco.',
        ],
    ];
}

function etiqueta_campo_documento(string $clave): string
{
    $mapa = [
        'titular' => 'Titular',
        'titular_tramite' => 'Titular del trámite',
        'nombre_en_acta' => 'Nombre en acta',
        'folio' => 'Folio / NSS',
        'folio_registro' => 'Folio de registro',
        'clave' => 'Clave CURP',
        'emision' => 'Estatus',
        'fecha_emision' => 'Fecha de emisión',
        'fecha_nacimiento' => 'Fecha de nacimiento',
        'entidad_registro' => 'Entidad de registro',
        'domicilio' => 'Domicilio registrado',
        'direccion' => 'Dirección de correo',
        'uso' => 'Uso declarado',
        'uso_solicitado' => 'Uso solicitado',
    ];

    return $mapa[$clave] ?? ucfirst(str_replace('_', ' ', $clave));
}
