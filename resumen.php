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
    class="page-section summary-shell"
    <?= $aprobado ? 'data-confetti="true" data-confetti-intensity="' . ($confettiAlto ? 'high' : 'normal') . '"' : '' ?>
>
    <div class="page-header page-header-banner">
        <div>
            <p class="meta-label header-pill tabular-nums">★ RESUMEN DE JORNADA · NIVEL 0<?= $nivelId ?> ★</p>
            <h1><?= h((string) ($nivel['nombre'] ?? 'Jornada completada')) ?></h1>
            <p class="page-subtitle">
                <?php if ($aciertos === 5): ?>
                    ¡Jornada legendaria! Despachaste los cinco casos sin un solo error.
                <?php elseif ($aprobado): ?>
                    ¡Jornada aprobada! Superaste el mínimo requerido de 3 aciertos.
                <?php else: ?>
                    Necesitas al menos 3 de 5 aciertos (120 pts) para aprobar y desbloquear el siguiente nivel.
                <?php endif; ?>
            </p>
        </div>

        <span class="status-badge status-badge-lg <?= $aprobado ? 'status-completed anim-pop' : 'status-failed anim-shake' ?>">
            <?= icono($aprobado ? 'check' : 'x', 'icon icon-sm') ?>
            <span><?= $aprobado ? '★ Jornada aprobada' : '✕ Jornada no aprobada' ?></span>
        </span>
    </div>

    <?php if (!empty($resumen['nivel_desbloqueado_nuevo'])): ?>
        <div class="alert alert-success anim-pop" role="status">
            <?= icono('check', 'icon') ?>
            <span>¡Nuevo nivel desbloqueado! Ya puedes atender el <strong>Nivel 0<?= (int) $resumen['nivel_desbloqueado_nuevo'] ?></strong>.</span>
        </div>
    <?php endif; ?>

    <?php if ($esJefe && (int) $resumen['puntos_anadidos'] > 0): ?>
        <div class="alert alert-gold anim-pop" role="status">
            <?= icono('sello', 'icon') ?>
            <span>¡Alcanzaste el tope de <strong>1000 / 1000 puntos</strong>! Ahora tienes el rango máximo: <strong>Jefe de Ventanilla</strong>.</span>
        </div>
    <?php endif; ?>

    <div class="summary-grid">
        <article class="card summary-card summary-card-left anim-slide-up">
            <p class="meta-label">DESEMPEÑO DE JORNADA</p>
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
                    <dt>Puntos añadidos al récord global</dt>
                    <dd data-count-to="<?= (int) $resumen['puntos_anadidos'] ?>" data-prefix="+" data-suffix=" pts">+<?= (int) $resumen['puntos_anadidos'] ?> pts</dd>
                </div>
                <div class="summary-row">
                    <dt>Récord actual del nivel</dt>
                    <dd><strong><?= (int) $resumen['record_actual'] ?> / 200 pts</strong></dd>
                </div>
            </dl>
        </article>

        <article class="card summary-card summary-card-right anim-slide-up" style="animation-delay: 80ms;">
            <p class="meta-label">PROGRESO GLOBAL Y RANGO</p>
            <div class="summary-big-metric tabular-nums">
                <strong data-count-to="<?= $puntosGlobales ?>" data-suffix=" / 1000"><?= $puntosGlobales ?> / 1000</strong>
                <span>pts</span>
            </div>
            <div class="progress-track summary-progress-track">
                <div class="progress-fill progress-fill-animated" style="width: <?= $porcentajeGlobal ?>%;"></div>
            </div>
            <hr class="divider">
            <dl class="summary-rows tabular-nums">
                <div class="summary-row">
                    <dt>Nivel máximo desbloqueado</dt>
                    <dd>Nivel 0<?= (int) $resumen['nivel_desbloqueado_actual'] ?> / 05</dd>
                </div>
                <div class="summary-row">
                    <dt>Jornadas perfectas (5/5)</dt>
                    <dd><?= count($usuario['niveles_perfectos']) ?> / 5 niveles</dd>
                </div>
                <div class="summary-row summary-row-highlight">
                    <dt>Rango actual</dt>
                    <dd><strong><?= $esJefe ? '★ Jefe de Ventanilla' : 'Operador en turno' ?></strong></dd>
                </div>
            </dl>
        </article>
    </div>

    <div class="summary-actions anim-slide-up" style="animation-delay: 140ms;">
        <?php if ($siguienteNivelId !== null): ?>
            <a href="ventanilla.php?nivel=<?= $siguienteNivelId ?>" class="btn btn-primary btn-lg btn-sheen">
                <span>Siguiente nivel (Nivel 0<?= $siguienteNivelId ?>)</span>
                <?= icono('arrow-right', 'icon') ?>
            </a>
        <?php endif; ?>

        <a href="ventanilla.php?nivel=<?= $nivelId ?>" class="btn <?= $siguienteNivelId === null ? 'btn-accent-pink btn-lg btn-sheen' : 'btn-secondary' ?>">
            <span>Reintentar nivel</span>
        </a>

        <a href="escritorio.php" class="btn btn-secondary">
            <span>Volver al escritorio</span>
        </a>
    </div>
</section>

<?php render_footer(); ?>
