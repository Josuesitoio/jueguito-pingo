<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$usuario = requerir_sesion();

// Inicio de jornada por parámetro GET ?nivel=X con validación estricta
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['nivel'])) {
    $nivelSolicitado = filter_var($_GET['nivel'], FILTER_VALIDATE_INT);
    $nivelData = ($nivelSolicitado !== false) ? obtener_nivel((int) $nivelSolicitado) : null;

    if ($nivelData === null || (int) $nivelSolicitado > (int) $usuario['nivel_desbloqueado']) {
        set_flash('error', 'El nivel solicitado no está desbloqueado o no existe.');
        header('Location: escritorio.php');
        exit;
    }

    iniciar_partida((int) $nivelSolicitado);
    header('Location: ventanilla.php');
    exit;
}

// Procesamiento POST con patrón PRG (Post/Redirect/Get)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? '');
    $nombreUsuario = (string) $usuario['usuario'];

    if ($accion === 'responder') {
        $respuesta = (string) ($_POST['respuesta'] ?? '');
        $res = evaluar_respuesta($respuesta);
        if (empty($res['ok'])) {
            set_flash('error', (string) ($res['error'] ?? 'No se pudo evaluar la respuesta.'));
        }
        header('Location: ventanilla.php');
        exit;
    }

    if ($accion === 'siguiente') {
        $destino = avanzar_caso($nombreUsuario);
        header('Location: ' . $destino);
        exit;
    }

    header('Location: ventanilla.php');
    exit;
}

$partida = obtener_partida();
if ($partida === null) {
    header('Location: escritorio.php');
    exit;
}

if (($partida['fase'] ?? '') === 'completada') {
    header('Location: resumen.php');
    exit;
}

$nivelId = (int) $partida['nivel_id'];
if ($nivelId > (int) $usuario['nivel_desbloqueado']) {
    unset($_SESSION['partida']);
    header('Location: escritorio.php');
    exit;
}

$nivel = obtener_nivel($nivelId);
if ($nivel === null) {
    unset($_SESSION['partida']);
    header('Location: escritorio.php');
    exit;
}

$indice = (int) $partida['indice'];
$caso = $nivel['casos'][$indice] ?? null;
if ($caso === null) {
    header('Location: escritorio.php');
    exit;
}

$numeroCaso = $indice + 1;
$fase = (string) $partida['fase'];
$tipoCaso = (string) ($caso['tipo'] ?? 'eleccion');
$documentos = obtener_documentos_catalogo();
$flash = get_flash();

$porcentajeJornada = (int) round((($indice + ($fase === 'feedback' ? 1 : 0)) / 5) * 100);
$ultimo = $partida['ultimo_resultado'] ?? null;

render_header('Ventanilla · Nivel ' . $nivelId, $usuario, 'ventanilla');
?>

<section class="ventanilla-shell">
    <!-- 1. Cabecera maximalista de progreso -->
    <header class="ventanilla-header card">
        <div class="ventanilla-progress-row">
            <div class="ventanilla-meta">
                <span class="level-num-tag tabular-nums">NIVEL 0<?= $nivelId ?> · <?= h(mb_strtoupper((string) $nivel['nombre'], 'UTF-8')) ?></span>
                <div class="ventanilla-case-counter tabular-nums">
                    <span>CASO 0<?= $numeroCaso ?> / 05</span>
                    <?php if ((int) $partida['racha_actual'] > 0): ?>
                        <span class="streak-pill anim-pop">★ RACHA x<?= (int) $partida['racha_actual'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ventanilla-score-live tabular-nums">
                <span class="meta-label">ACIERTOS</span>
                <strong><?= (int) $partida['aciertos'] ?> / 5</strong>
            </div>
        </div>

        <div class="progress-track progress-track-ventanilla" role="progressbar" aria-valuenow="<?= $numeroCaso ?>" aria-valuemin="1" aria-valuemax="5">
            <div class="progress-fill progress-fill-animated" style="width: <?= $porcentajeJornada ?>%;"></div>
        </div>
    </header>

    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= h($flash['tipo']) ?> anim-shake" role="alert">
            <?= h($flash['mensaje']) ?>
        </div>
    <?php endif; ?>

    <!-- 2. Área del caso o expediente -->
    <?php if ($tipoCaso === 'eleccion'): ?>
        <article class="card document-card case-request-card<?= $fase === 'decision' ? ' anim-paper-in' : '' ?>">
            <div class="dossier-tab-strip">
                <span>EXPEDIENTE // FOLIO <?= h((string) $caso['id']) ?></span>
                <span class="mode-tag">ENTREGA DE DOCUMENTO</span>
            </div>

            <div class="document-card-content">
                <div class="document-card-head">
                    <div>
                        <p class="meta-label">SOLICITANTE EN VENTANILLA</p>
                        <h2><?= h((string) ($caso['ciudadano'] ?? 'Solicitante en ventanilla')) ?></h2>
                    </div>
                </div>

                <hr class="divider">

                <div class="case-request-body">
                    <p class="case-quote">“<?= h((string) $caso['solicitud']) ?>”</p>
                    <p class="case-instruction">
                        Elige el documento oficial exacto para despachar este trámite.
                    </p>
                </div>
            </div>
        </article>
    <?php else: ?>
        <?php
            $docKey = (string) ($caso['documento'] ?? 'curp');
            $docInfo = $documentos[$docKey] ?? $documentos['curp'];
            $campos = (array) ($caso['campos'] ?? []);
        ?>
        <article class="card document-card inspector-sheet <?= h($docInfo['clase']) ?><?= $fase === 'decision' ? ' anim-paper-in' : '' ?>">
            <div class="dossier-tab-strip dossier-tab-doc">
                <span>INSPECCIÓN // FOLIO <?= h((string) $caso['id']) ?></span>
                <span class="mode-tag">MODO INSPECTOR</span>
            </div>

            <div class="document-card-content">
                <div class="document-card-head">
                    <div class="inspector-doc-title">
                        <span class="doc-icon-wrap">
                            <?= icono($docInfo['icono'], 'icon') ?>
                        </span>
                        <div>
                            <p class="meta-label">DOCUMENTO PRESENTADO</p>
                            <h2><?= h($docInfo['nombre_completo']) ?></h2>
                        </div>
                    </div>
                    <div class="barcode-deco" aria-hidden="true"></div>
                </div>

                <hr class="divider">

                <div class="inspector-context">
                    <span class="meta-label">CONTEXTO DEL TRÁMITE</span>
                    <p><?= h((string) ($caso['contexto'] ?? '')) ?></p>
                </div>

                <dl class="inspector-fields">
                    <?php foreach ($campos as $claveCampo => $valorCampo): ?>
                        <div class="inspector-field">
                            <dt class="meta-label">
                                <?= h(mb_strtoupper(etiqueta_campo_documento((string) $claveCampo), 'UTF-8')) ?>
                            </dt>
                            <dd class="mono-data"><?= h((string) $valorCampo) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </article>
    <?php endif; ?>

    <!-- 3. Zona de decisión o Retroalimentación -->
    <?php if ($fase === 'decision'): ?>
        <div class="decision-zone">
            <?php if ($tipoCaso === 'eleccion'): ?>
                <form method="post" action="ventanilla.php" class="choice-grid">
                    <input type="hidden" name="accion" value="responder">
                    <?php
                        $orden = ['curp', 'nss', 'acta', 'cfe', 'correo'];
                        foreach ($orden as $pos => $docId):
                            $doc = $documentos[$docId];
                    ?>
                        <button
                            type="submit"
                            name="respuesta"
                            value="<?= h($docId) ?>"
                            class="choice-card <?= h($doc['clase']) ?> choice-pos-<?= $pos + 1 ?>"
                        >
                            <span class="choice-card-head">
                                <span class="doc-icon-wrap">
                                    <?= icono($doc['icono'], 'icon') ?>
                                </span>
                                <span class="choice-card-title"><?= h($doc['nombre']) ?></span>
                                <kbd class="kbd-hint">0<?= $pos + 1 ?></kbd>
                            </span>
                            <span class="choice-card-desc"><?= h($doc['resumen']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </form>
            <?php else: ?>
                <form method="post" action="ventanilla.php" class="inspector-actions">
                    <input type="hidden" name="accion" value="responder">
                    <button type="submit" name="respuesta" value="rechazar" class="btn btn-reject btn-lg">
                        <?= icono('x', 'icon') ?>
                        <span>RECHAZAR</span>
                        <kbd class="kbd-hint kbd-hint-outline">R</kbd>
                    </button>
                    <button type="submit" name="respuesta" value="aprobar" class="btn btn-approve btn-lg btn-sheen">
                        <?= icono('check', 'icon') ?>
                        <span>APROBAR</span>
                        <kbd class="kbd-hint kbd-hint-invert">A</kbd>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php elseif ($fase === 'feedback' && is_array($ultimo)): ?>
        <?php
            $esCorrecta = !empty($ultimo['es_correcta']);
            $respuestaCorrectaTexto = '';
            if (($ultimo['tipo'] ?? '') === 'eleccion') {
                $docCorr = $documentos[(string) ($ultimo['respuesta_correcta'] ?? '')] ?? null;
                $respuestaCorrectaTexto = $docCorr ? $docCorr['nombre'] : '';
            } else {
                $respuestaCorrectaTexto = (($ultimo['respuesta_correcta'] ?? '') === 'aprobar') ? 'Aprobar' : 'Rechazar';
            }
        ?>
        <section class="feedback-panel <?= $esCorrecta ? 'feedback-success anim-feedback-ok' : 'feedback-error anim-shake' ?>" aria-live="polite">
            <div class="stamp-seal <?= $esCorrecta ? 'stamp-seal-ok' : 'stamp-seal-err' ?>" aria-hidden="true">
                <?= $esCorrecta ? '★ ¡ACIERTO! ★' : '✕ ¡ERROR! ✕' ?>
            </div>

            <div class="feedback-head">
                <div class="feedback-title">
                    <?= icono($esCorrecta ? 'check' : 'x', 'icon') ?>
                    <div>
                        <p class="meta-label"><?= $esCorrecta ? 'RESOLUCIÓN CORRECTA' : 'RESOLUCIÓN INCORRECTA' ?></p>
                        <h2>
                            <?php if ($esCorrecta): ?>
                                ¡Trámite sellado con éxito!
                            <?php else: ?>
                                Respuesta esperada: <?= h($respuestaCorrectaTexto) ?>
                            <?php endif; ?>
                        </h2>
                    </div>
                </div>

                <div class="feedback-rewards tabular-nums">
                    <span class="reward-chip anim-pop">+<?= (int) ($ultimo['puntos_caso'] ?? 0) ?> PTS</span>
                </div>
            </div>

            <p class="feedback-explanation"><?= h((string) ($ultimo['explicacion'] ?? '')) ?></p>

            <div class="feedback-actions">
                <form method="post" action="ventanilla.php" class="feedback-next-form">
                    <input type="hidden" name="accion" value="siguiente">
                    <button type="submit" class="btn btn-primary btn-lg btn-sheen">
                        <span><?= $numeroCaso < 5 ? 'Siguiente caso' : 'Ver resumen de jornada' ?></span>
                        <?= icono('arrow-right', 'icon') ?>
                        <kbd class="kbd-hint kbd-hint-invert">Enter</kbd>
                    </button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</section>

<?php render_footer(); ?>
