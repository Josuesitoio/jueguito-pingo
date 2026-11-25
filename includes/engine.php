<?php
declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function es_jefe_ventanilla(array $usuario): bool
{
    return (int) ($usuario['puntos_totales'] ?? 0) >= 1000;
}

function iniciar_partida(int $nivelId): array
{
    $partida = [
        'nivel_id' => $nivelId,
        'indice' => 0,
        'aciertos' => 0,
        'racha_actual' => 0,
        'fase' => 'decision',
        'ultimo_resultado' => null,
        'guardada' => false,
        'resumen_final' => null,
        'historial' => [],
    ];

    $_SESSION['partida'] = $partida;
    return $partida;
}

function obtener_partida(): ?array
{
    return isset($_SESSION['partida']) && is_array($_SESSION['partida'])
        ? $_SESSION['partida']
        : null;
}

function evaluar_respuesta(string $respuesta): array
{
    $partida = obtener_partida();
    if ($partida === null || $partida['fase'] !== 'decision') {
        return ['ok' => false, 'error' => 'Este caso ya fue evaluado.'];
    }

    $nivel = obtener_nivel((int) $partida['nivel_id']);
    if ($nivel === null) {
        return ['ok' => false, 'error' => 'Nivel inválido.'];
    }

    $indice = (int) $partida['indice'];
    $caso = $nivel['casos'][$indice] ?? null;
    if ($caso === null) {
        return ['ok' => false, 'error' => 'Caso no encontrado.'];
    }

    $tipo = (string) ($caso['tipo'] ?? 'eleccion');
    $respuestaLimpia = trim($respuesta);
    $esCorrecta = false;

    if ($tipo === 'eleccion') {
        $documentosValidos = array_keys(obtener_documentos_catalogo());
        if (!in_array($respuestaLimpia, $documentosValidos, true)) {
            return ['ok' => false, 'error' => 'Opción de documento no válida.'];
        }
        $esCorrecta = ($respuestaLimpia === (string) ($caso['respuesta_correcta'] ?? ''));
    } elseif ($tipo === 'inspector') {
        if (!in_array($respuestaLimpia, ['aprobar', 'rechazar'], true)) {
            return ['ok' => false, 'error' => 'Decisión de inspección no válida.'];
        }
        $esValido = !empty($caso['es_valido']);
        $esCorrecta = ($respuestaLimpia === 'aprobar' && $esValido) || ($respuestaLimpia === 'rechazar' && !$esValido);
    }

    $puntosCaso = 0;
    if ($esCorrecta) {
        $puntosCaso = 40;
        $_SESSION['partida']['aciertos'] = (int) $partida['aciertos'] + 1;
        $_SESSION['partida']['racha_actual'] = (int) $partida['racha_actual'] + 1;
    } else {
        $_SESSION['partida']['racha_actual'] = 0;
    }

    $_SESSION['partida']['fase'] = 'feedback';
    $_SESSION['partida']['ultimo_resultado'] = [
        'caso_id' => (string) ($caso['id'] ?? ''),
        'tipo' => $tipo,
        'es_correcta' => $esCorrecta,
        'respuesta_usuario' => $respuestaLimpia,
        'respuesta_correcta' => $tipo === 'eleccion'
            ? (string) ($caso['respuesta_correcta'] ?? '')
            : (!empty($caso['es_valido']) ? 'aprobar' : 'rechazar'),
        'explicacion' => (string) ($caso['explicacion'] ?? ''),
        'puntos_caso' => $puntosCaso,
    ];

    $_SESSION['partida']['historial'][] = [
        'caso_id' => (string) ($caso['id'] ?? ''),
        'es_correcta' => $esCorrecta,
    ];

    return ['ok' => true];
}

function finalizar_y_guardar_jornada(string $nombreUsuario): array
{
    $partida = obtener_partida();
    if ($partida === null) {
        return ['ok' => false];
    }

    if (!empty($partida['guardada']) && is_array($partida['resumen_final'])) {
        return ['ok' => true, 'resumen' => $partida['resumen_final']];
    }

    $nivelId = (int) $partida['nivel_id'];
    $aciertos = (int) $partida['aciertos'];
    $puntajeJornada = $aciertos * 40;
    $aprobado = ($aciertos >= 3);

    $res = actualizar_usuarios(function (array &$datos) use (
        $nombreUsuario,
        $nivelId,
        $aciertos,
        $puntajeJornada,
        $aprobado
    ): array {
        $objetivo = mb_strtolower($nombreUsuario, 'UTF-8');
        foreach ($datos['usuarios'] as $idx => $u) {
            if (mb_strtolower((string) $u['usuario'], 'UTF-8') === $objetivo) {
                $u = normalizar_usuario($u);
                $claveNivel = (string) $nivelId;
                $recordAnterior = (int) ($u['records_nivel'][$claveNivel] ?? 0);
                $puntosNuevos = max(0, $puntajeJornada - $recordAnterior);

                if ($puntajeJornada > $recordAnterior) {
                    $u['records_nivel'][$claveNivel] = $puntajeJornada;
                }

                $nivelDesbloqueadoNuevo = null;
                if ($aprobado && $nivelId < 5 && (int) $u['nivel_desbloqueado'] <= $nivelId) {
                    $u['nivel_desbloqueado'] = $nivelId + 1;
                    $nivelDesbloqueadoNuevo = $nivelId + 1;
                }

                $esPrimeraVezPerfecta = false;
                if ($aciertos === 5 && !in_array($nivelId, $u['niveles_perfectos'], true)) {
                    $u['niveles_perfectos'][] = $nivelId;
                    $esPrimeraVezPerfecta = true;
                }

                $uNormalizado = normalizar_usuario($u);
                $datos['usuarios'][$idx] = $uNormalizado;

                return [
                    'ok' => true,
                    'resumen' => [
                        'nivel_id' => $nivelId,
                        'aciertos' => $aciertos,
                        'total_casos' => 5,
                        'aprobado' => $aprobado,
                        'puntaje_jornada' => $puntajeJornada,
                        'record_anterior' => $recordAnterior,
                        'record_actual' => (int) $uNormalizado['records_nivel'][$claveNivel],
                        'puntos_anadidos' => $puntosNuevos,
                        'puntos_totales_globales' => (int) $uNormalizado['puntos_totales'],
                        'nivel_desbloqueado_nuevo' => $nivelDesbloqueadoNuevo,
                        'nivel_desbloqueado_actual' => (int) $uNormalizado['nivel_desbloqueado'],
                        'primera_vez_perfecta' => $esPrimeraVezPerfecta,
                    ],
                ];
            }
        }

        return ['_abortar' => true, 'ok' => false];
    });

    if (!empty($res['ok'])) {
        $_SESSION['partida']['fase'] = 'completada';
        $_SESSION['partida']['guardada'] = true;
        $_SESSION['partida']['resumen_final'] = $res['resumen'];
        return ['ok' => true, 'resumen' => $res['resumen']];
    }

    return ['ok' => false];
}

function avanzar_caso(string $nombreUsuario): string
{
    $partida = obtener_partida();
    if ($partida === null) {
        return 'escritorio.php';
    }
    if ($partida['fase'] === 'completada') {
        return 'resumen.php';
    }
    if ($partida['fase'] !== 'feedback') {
        return 'ventanilla.php';
    }

    $siguienteIndice = (int) $partida['indice'] + 1;
    if ($siguienteIndice >= 5) {
        finalizar_y_guardar_jornada($nombreUsuario);
        return 'resumen.php';
    }

    $_SESSION['partida']['indice'] = $siguienteIndice;
    $_SESSION['partida']['fase'] = 'decision';
    $_SESSION['partida']['ultimo_resultado'] = null;

    return 'ventanilla.php';
}
