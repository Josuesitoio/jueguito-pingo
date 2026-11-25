<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$usuario = requerir_sesion();
$niveles = obtener_niveles();
$flash = get_flash();

$nivelDesbloqueado = (int) $usuario['nivel_desbloqueado'];
$records = $usuario['records_nivel'];
$perfectos = $usuario['niveles_perfectos'];
$esJefe = es_jefe_ventanilla($usuario);

$nivelesCompletados = 0;
foreach ($records as $pts) {
    if ((int) $pts >= 120) {
        $nivelesCompletados++;
    }
}

render_header('Inicio', $usuario, 'escritorio');
?>

<section class="portal-page">
    <!-- Banner institucional de bienvenida y resumen -->
    <div class="portal-hero-banner portal-hero-compact anim-fade-up">
        <div class="portal-hero-row">
            <div>
                <span class="portal-hero-badge"><?= $esJefe ? 'Rango Máximo · Jefe de Ventanilla' : 'Operador en turno' ?></span>
                <h1>Bienvenido, <?= h((string) $usuario['usuario']) ?></h1>
                <p>
                    Completa las jornadas de 5 casos con al menos 3 aciertos (120 pts) para desbloquear cada nuevo nivel.
                </p>
            </div>

            <div class="portal-hero-stats tabular-nums">
                <div class="hero-stat-pill">
                    <strong data-count-to="<?= (int) $usuario['puntos_totales'] ?>"><?= (int) $usuario['puntos_totales'] ?></strong>
                    <span>de 1,000 pts</span>
                </div>
                <div class="hero-stat-pill">
                    <strong data-count-to="<?= $nivelesCompletados ?>" data-suffix=" / 5"><?= $nivelesCompletados ?> / 5</strong>
                    <span>Aprobados</span>
                </div>
                <div class="hero-stat-pill">
                    <strong data-count-to="<?= count($perfectos) ?>" data-suffix=" / 5"><?= count($perfectos) ?> / 5</strong>
                    <span>Perfectos</span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= h($flash['tipo']) ?> anim-fade-up" role="status">
            <?= h($flash['mensaje']) ?>
        </div>
    <?php endif; ?>

    <!-- Panel crema principal inspirado directamente en la sección "Trámites" de la referencia -->
    <section class="cream-panel anim-fade-up" style="animation-delay: 70ms;">
        <div class="section-heading">
            <h2>Trámites y Niveles</h2>
            <p>Jornadas disponibles para atender en ventanilla</p>
        </div>

        <div class="levels-portal-grid">
            <?php foreach ($niveles as $nivel): ?>
                <?php
                    $id = (int) $nivel['id'];
                    $record = (int) ($records[(string) $id] ?? 0);
                    $estaDesbloqueado = ($id <= $nivelDesbloqueado);
                    $estaCompletado = ($record >= 120);
                    $esPerfecto = in_array($id, $perfectos, true);
                    $porcNivel = min(100, (int) round(($record / 200) * 100));

                    if (!$estaDesbloqueado) {
                        $estadoTexto = 'Bloqueado';
                        $estadoClase = 'pill-locked';
                    } elseif ($estaCompletado) {
                        $estadoTexto = $esPerfecto ? 'Perfecto · 5/5' : 'Completado';
                        $estadoClase = 'pill-completed';
                    } else {
                        $estadoTexto = 'Disponible';
                        $estadoClase = 'pill-available';
                    }
                ?>
                <?php if ($estaDesbloqueado): ?>
                    <a href="ventanilla.php?nivel=<?= $id ?>" class="steel-level-card">
                        <div class="steel-card-top">
                            <span class="steel-level-num tabular-nums">Nivel 0<?= $id ?></span>
                            <span class="steel-status-pill <?= $estadoClase ?>">
                                <?php if ($estaCompletado): ?>
                                    <?= icono('check', 'icon icon-xs') ?>
                                <?php endif; ?>
                                <span><?= h($estadoTexto) ?></span>
                            </span>
                        </div>

                        <div class="steel-card-body">
                            <h3><?= h((string) $nivel['nombre']) ?></h3>
                            <p><?= h((string) $nivel['descripcion']) ?></p>
                        </div>

                        <div class="steel-card-footer">
                            <div class="steel-record-wrap">
                                <div class="steel-record-row tabular-nums">
                                    <span><?= h((string) ($nivel['mecanica'] ?? '5 casos')) ?></span>
                                    <strong><?= $record ?> / 200 pts</strong>
                                </div>
                                <div class="progress-track progress-track-steel">
                                    <div class="progress-fill progress-fill-light" style="width: <?= $porcNivel ?>%;"></div>
                                </div>
                            </div>
                            <span class="steel-cta-arrow" aria-hidden="true">
                                <?= icono('arrow-right', 'icon') ?>
                            </span>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="steel-level-card is-locked" aria-disabled="true">
                        <div class="steel-card-top">
                            <span class="steel-level-num tabular-nums">Nivel 0<?= $id ?></span>
                            <span class="steel-status-pill pill-locked">
                                <?= icono('lock', 'icon icon-xs') ?>
                                <span>Bloqueado</span>
                            </span>
                        </div>

                        <div class="steel-card-body">
                            <h3><?= h((string) $nivel['nombre']) ?></h3>
                            <p><?= h((string) $nivel['descripcion']) ?></p>
                        </div>

                        <div class="steel-card-footer">
                            <div class="steel-record-wrap">
                                <div class="steel-record-row tabular-nums">
                                    <span><?= h((string) ($nivel['mecanica'] ?? '5 casos')) ?></span>
                                    <strong>Requiere nivel anterior</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<?php render_footer(); ?>
