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
    <!-- Panel crema principal de ventanilla -->
    <div class="cream-panel ventanilla-panel">
        <!-- 1. Cabecera de progreso -->
        <header class="ventanilla-header">
            <div class="ventanilla-progress-row">
                <div>
                    <span class="meta-label tabular-nums">Nivel 0<?= $nivelId ?> · <?= h((string) $nivel['nombre']) ?></span>
                    <div class="ventanilla-case-counter tabular-nums">
                        <h2>Caso 0<?= $numeroCaso ?> de 05</h2>
                        <?php if ((int) $partida['racha_actual'] > 0): ?>
                            <span class="streak-pill anim-pop">Racha <?= (int) $partida['racha_actual'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ventanilla-score-live tabular-nums">
                    <span>Aciertos</span>
                    <strong><?= (int) $partida['aciertos'] ?> / 5</strong>
                </div>
            </div>

            <div class="progress-track" role="progressbar" aria-valuenow="<?= $numeroCaso ?>" aria-valuemin="1" aria-valuemax="5">
                <div class="progress-fill" style="width: <?= $porcentajeJornada ?>%;"></div>
            </div>
        </header>

        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= h($flash['tipo']) ?> anim-shake" role="alert">
                <?= h($flash['mensaje']) ?>
            </div>
        <?php endif; ?>

        <!-- 2. Área del caso o expediente -->
        <?php if ($tipoCaso === 'eleccion'): ?>
            <article class="white-sheet<?= $fase === 'decision' ? ' anim-fade-up' : '' ?>">
                <div class="sheet-top-bar">
                    <div>
                        <span class="meta-label">Solicitud en ventanilla · Folio <?= h((string) $caso['id']) ?></span>
                        <h3><?= h((string) ($caso['ciudadano'] ?? 'Solicitante en ventanilla')) ?></h3>
                    </div>
                    <span class="mode-tag">Elección de documento</span>
                </div>

                <hr class="divider">

                <div class="case-request-body">
                    <p class="case-quote">“<?= h((string) $caso['solicitud']) ?>”</p>
                    <p class="case-instruction">
                        Selecciona el documento oficial correspondiente para atender este trámite.
                    </p>
                </div>
            </article>
        <?php else: ?>
            <?php
                $docKey = (string) ($caso['documento'] ?? 'curp');
                $docInfo = $documentos[$docKey] ?? $documentos['curp'];
                $campos = (array) ($caso['campos'] ?? []);
            ?>
            <article class="white-sheet inspector-sheet<?= $fase === 'decision' ? ' anim-fade-up' : '' ?>">
                <div class="sheet-top-bar">
                    <div class="inspector-doc-title">
                        <span class="doc-icon-badge">
                            <?= icono($docInfo['icono'], 'icon') ?>
                        </span>
                        <div>
                            <span class="meta-label">Revisión documental · Folio <?= h((string) $caso['id']) ?></span>
                            <h3><?= h($docInfo['nombre_completo']) ?></h3>
                        </div>
                    </div>
                    <span class="mode-tag">Modo Inspector</span>
                </div>

                <hr class="divider">

                <div class="inspector-context">
                    <span class="meta-label">Contexto del trámite</span>
                    <p><?= h((string) ($caso['contexto'] ?? '')) ?></p>
                </div>

                <dl class="inspector-fields">
                    <?php foreach ($campos as $claveCampo => $valorCampo): ?>
                        <div class="inspector-field">
                            <dt class="meta-label"><?= h(etiqueta_campo_documento((string) $claveCampo)) ?></dt>
                            <dd class="mono-data"><?= h((string) $valorCampo) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </article>
        <?php endif; ?>

        <!-- 3. Zona de decisión (Tarjetas azul acero como en la imagen) o Retroalimentación -->
        <?php if ($fase === 'decision'): ?>
            <div class="decision-zone">
                <?php if ($tipoCaso === 'eleccion'): ?>
                    <form method="post" action="ventanilla.php" class="choice-steel-grid">
                        <input type="hidden" name="accion" value="responder">
                        <?php
                            $orden = ['acta', 'curp', 'nss', 'cfe', 'correo'];
                            foreach ($orden as $pos => $docId):
                                $doc = $documentos[$docId];
                        ?>
                            <button
                                type="submit"
                                name="respuesta"
                                value="<?= h($docId) ?>"
                                class="choice-card choice-pos-<?= $pos + 1 ?><?= $pos === 4 ? ' choice-card-wide' : '' ?>"
                            >
                                <span class="choice-card-top">
                                    <span class="choice-card-icon">
                                        <?= icono($doc['icono'], 'icon') ?>
                                    </span>
                                    <kbd class="kbd-hint"><?= $pos + 1 ?></kbd>
                                </span>
                                <span class="choice-card-title"><?= h($doc['nombre']) ?></span>
                                <span class="choice-card-desc"><?= h($doc['resumen']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </form>
                <?php else: ?>
                    <form method="post" action="ventanilla.php" class="inspector-actions">
                        <input type="hidden" name="accion" value="responder">
                        <button type="submit" name="respuesta" value="rechazar" class="btn btn-reject btn-lg">
                            <?= icono('x', 'icon') ?>
                            <span>Rechazar</span>
                            <kbd class="kbd-hint kbd-hint-outline">R</kbd>
                        </button>
                        <button type="submit" name="respuesta" value="aprobar" class="btn btn-approve btn-lg">
                            <?= icono('check', 'icon') ?>
                            <span>Aprobar</span>
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
            <section class="feedback-panel <?= $esCorrecta ? 'feedback-success anim-fade-up' : 'feedback-error anim-shake' ?>" aria-live="polite">
                <div class="feedback-head">
                    <div class="feedback-title">
                        <?= icono($esCorrecta ? 'check' : 'x', 'icon') ?>
                        <div>
                            <span class="meta-label"><?= $esCorrecta ? 'Resolución correcta' : 'Resolución incorrecta' ?></span>
                            <h3>
                                <?php if ($esCorrecta): ?>
                                    Trámite atendido correctamente
                                <?php else: ?>
                                    Respuesta esperada: <?= h($respuestaCorrectaTexto) ?>
                                <?php endif; ?>
                            </h3>
                        </div>
                    </div>

                    <span class="reward-chip tabular-nums">+<?= (int) ($ultimo['puntos_caso'] ?? 0) ?> pts</span>
                </div>

                <p class="feedback-explanation"><?= h((string) ($ultimo['explicacion'] ?? '')) ?></p>

                <div class="feedback-actions">
                    <form method="post" action="ventanilla.php" class="feedback-next-form">
                        <input type="hidden" name="accion" value="siguiente">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span><?= $numeroCaso < 5 ? 'Siguiente caso' : 'Ver resumen de jornada' ?></span>
                            <?= icono('arrow-right', 'icon') ?>
                            <kbd class="kbd-hint kbd-hint-invert">Enter</kbd>
                        </button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php render_footer(); ?>
