<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$usuario = requerir_sesion();
$partida = obtener_partida();

if ($partida === null || empty($partida['resumen_final']) || !is_array($partida['resumen_final'])) {
    header('Location: escritorio.php');
    exit;
}

$resumen = $partida['resumen_final'];
$nivelId = (int) $resumen['nivel_id'];
$nivel = obtener_nivel($nivelId);
$aciertos = (int) $resumen['aciertos'];
$aprobado = !empty($resumen['aprobado']);
$siguienteNivelId = ($nivelId < 5 && $aprobado) ? $nivelId + 1 : null;
$esJefe = es_jefe_ventanilla($usuario);
$confettiAlto = ($aciertos === 5 || !empty($resumen['nivel_desbloqueado_nuevo']) || $esJefe);
$puntosGlobales = (int) $resumen['puntos_totales_globales'];
$porcentajeGlobal = min(100, (int) round(($puntosGlobales / 1000) * 100));

render_header('Resumen de Jornada', $usuario, 'resumen');
?>

<section
    class="portal-page"
    <?= $aprobado ? 'data-confetti="true" data-confetti-intensity="' . ($confettiAlto ? 'high' : 'normal') . '"' : '' ?>
>
    <div class="portal-hero-banner portal-hero-compact anim-fade-up">
        <div class="portal-hero-row">
            <div>
                <span class="portal-hero-badge tabular-nums">Resumen de jornada · Nivel 0<?= $nivelId ?></span>
                <h1><?= h((string) ($nivel['nombre'] ?? 'Jornada completada')) ?></h1>
                <p>
                    <?php if ($aciertos === 5): ?>
                        Jornada perfecta. Atendiste los cinco casos sin ningún error.
                    <?php elseif ($aprobado): ?>
                        Jornada aprobada. Alcanzaste el mínimo requerido de 3 aciertos.
                    <?php else: ?>
                        Se requieren al menos 3 de 5 aciertos (120 pts) para aprobar y desbloquear el siguiente nivel.
                    <?php endif; ?>
                </p>
            </div>

            <span class="result-hero-badge <?= $aprobado ? 'is-pass' : 'is-fail' ?>">
                <?= icono($aprobado ? 'check' : 'x', 'icon icon-sm') ?>
                <span><?= $aprobado ? 'Jornada aprobada' : 'No aprobada' ?></span>
            </span>
        </div>
    </div>

    <?php if (!empty($resumen['nivel_desbloqueado_nuevo'])): ?>
        <div class="alert alert-success anim-fade-up" role="status">
            <?= icono('check', 'icon icon-sm') ?>
            <span>Nuevo nivel desbloqueado: ya puedes atender el <strong>Nivel 0<?= (int) $resumen['nivel_desbloqueado_nuevo'] ?></strong>.</span>
        </div>
    <?php endif; ?>

    <?php if ($esJefe && (int) $resumen['puntos_anadidos'] > 0): ?>
        <div class="alert alert-success anim-fade-up" role="status">
            <?= icono('sello', 'icon icon-sm') ?>
            <span>Alcanzaste <strong>1,000 / 1,000 puntos</strong> y obtuviste el rango máximo: <strong>Jefe de Ventanilla</strong>.</span>
        </div>
    <?php endif; ?>

    <div class="cream-panel anim-fade-up" style="animation-delay: 80ms;">
        <div class="summary-grid">
            <article class="white-sheet summary-card">
                <span class="meta-label">Desempeño de jornada</span>
                <div class="summary-big-metric tabular-nums">
                    <strong data-count-to="<?= $aciertos ?>" data-suffix=" / 5"><?= $aciertos ?> / 5</strong>
                    <span>aciertos</span>
                </div>
                <hr class="divider">
                <dl class="summary-rows tabular-nums">
                    <div class="summary-row">
                        <dt>Puntaje de esta jornada</dt>
                        <dd data-count-to="<?= (int) $resumen['puntaje_jornada'] ?>" data-suffix=" / 200 pts"><?= (int) $resumen['puntaje_jornada'] ?> / 200 pts</dd>
                    </div>
                    <div class="summary-row">
                        <dt>Récord previo del nivel</dt>
                        <dd><?= (int) $resumen['record_anterior'] ?> / 200 pts</dd>
                    </div>
                    <div class="summary-row summary-row-highlight">
                        <dt>Puntos añadidos al global</dt>
                        <dd data-count-to="<?= (int) $resumen['puntos_anadidos'] ?>" data-prefix="+" data-suffix=" pts">+<?= (int) $resumen['puntos_anadidos'] ?> pts</dd>
                    </div>
                    <div class="summary-row">
                        <dt>Récord actual del nivel</dt>
                        <dd><strong><?= (int) $resumen['record_actual'] ?> / 200 pts</strong></dd>
                    </div>
                </dl>
            </article>

            <article class="steel-summary-card summary-card">
                <span class="meta-label">Progreso global y rango</span>
                <div class="summary-big-metric tabular-nums">
                    <strong data-count-to="<?= $puntosGlobales ?>" data-suffix=" / 1000"><?= $puntosGlobales ?> / 1000</strong>
                    <span>pts</span>
                </div>
                <div class="progress-track progress-track-steel summary-progress-track">
                    <div class="progress-fill progress-fill-light" style="width: <?= $porcentajeGlobal ?>%;"></div>
                </div>
                <hr class="divider divider-light">
                <dl class="summary-rows summary-rows-light tabular-nums">
                    <div class="summary-row">
                        <dt>Nivel desbloqueado</dt>
                        <dd>Nivel 0<?= (int) $resumen['nivel_desbloqueado_actual'] ?> / 05</dd>
                    </div>
                    <div class="summary-row">
                        <dt>Jornadas perfectas (5/5)</dt>
                        <dd><?= count($usuario['niveles_perfectos']) ?> / 5 niveles</dd>
                    </div>
                    <div class="summary-row summary-row-highlight">
                        <dt>Rango actual</dt>
                        <dd><strong><?= $esJefe ? 'Jefe de Ventanilla' : 'Operador en turno' ?></strong></dd>
                    </div>
                </dl>
            </article>
        </div>

        <div class="summary-actions">
            <?php if ($siguienteNivelId !== null): ?>
                <a href="ventanilla.php?nivel=<?= $siguienteNivelId ?>" class="btn btn-primary btn-lg">
                    <span>Siguiente nivel (Nivel 0<?= $siguienteNivelId ?>)</span>
                    <?= icono('arrow-right', 'icon') ?>
                </a>
            <?php endif; ?>

            <a href="ventanilla.php?nivel=<?= $nivelId ?>" class="btn <?= $siguienteNivelId === null ? 'btn-primary btn-lg' : 'btn-secondary' ?>">
                <span>Reintentar nivel</span>
            </a>

            <a href="escritorio.php" class="btn btn-secondary">
                <span>Volver al inicio</span>
            </a>
        </div>
    </div>
</section>

<?php render_footer(); ?>
