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

render_header('Escritorio', $usuario, 'escritorio');
?>

<section class="page-section">
    <div class="page-header page-header-banner">
        <div>
            <p class="meta-label header-pill">★ ESCRITORIO DE OPERACIONES ★</p>
            <h1>Niveles de <span class="text-highlight-gold">Ventanilla</span></h1>
            <p class="page-subtitle">
                Atiende 5 casos por jornada. Consigue al menos 3 aciertos (120 / 200 pts) para desbloquear el siguiente nivel.
            </p>
        </div>

        <?php if ($esJefe): ?>
            <div class="rank-banner anim-pop" role="status">
                <?= icono('sello', 'icon') ?>
                <div>
                    <p class="meta-label">★ RANGO MÁXIMO ALCANZADO</p>
                    <strong>Jefe de Ventanilla (1000 / 1000 pts)</strong>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= h($flash['tipo']) ?> anim-pop" role="status">
            <?= h($flash['mensaje']) ?>
        </div>
    <?php endif; ?>

    <!-- Tablero maximalista de métricas por bloques de color -->
    <div class="stats-overview-grid">
        <div class="card stat-tile stat-tile-cobalt anim-slide-up">
            <span class="meta-label">PROGRESO GLOBAL</span>
            <strong class="stat-tile-value tabular-nums" data-count-to="<?= (int) $usuario['puntos_totales'] ?>" data-suffix=" / 1000"><?= (int) $usuario['puntos_totales'] ?> / 1000</strong>
            <div class="progress-track progress-track-invert">
                <div class="progress-fill progress-fill-gold" style="width: <?= min(100, (int) round(((int) $usuario['puntos_totales'] / 1000) * 100)) ?>%;"></div>
            </div>
        </div>

        <div class="card stat-tile stat-tile-gold anim-slide-up" style="animation-delay: 60ms;">
            <span class="meta-label">NIVELES APROBADOS</span>
            <strong class="stat-tile-value tabular-nums" data-count-to="<?= $nivelesCompletados ?>" data-suffix=" / 5"><?= $nivelesCompletados ?> / 5</strong>
            <span class="stat-tile-sub">★ <?= count($perfectos) ?> jornadas perfectas (5/5)</span>
        </div>

        <div class="card stat-tile stat-tile-pink anim-slide-up" style="animation-delay: 120ms;">
            <span class="meta-label">NIVEL DESBLOQUEADO</span>
            <strong class="stat-tile-value tabular-nums" data-count-to="<?= $nivelDesbloqueado ?>" data-prefix="NIVEL 0" data-suffix=" / 05">NIVEL 0<?= $nivelDesbloqueado ?> / 05</strong>
            <span class="stat-tile-sub"><?= $esJefe ? '★ Rango: Jefe de Ventanilla' : 'Meta final: 1,000 puntos' ?></span>
        </div>
    </div>

    <div class="levels-grid">
        <?php foreach ($niveles as $nivel): ?>
            <?php
                $id = (int) $nivel['id'];
                $record = (int) ($records[(string) $id] ?? 0);
                $estaDesbloqueado = ($id <= $nivelDesbloqueado);
                $estaCompletado = ($record >= 120);
                $esPerfecto = in_array($id, $perfectos, true);

                if (!$estaDesbloqueado) {
                    $estadoTexto = 'Bloqueado';
                    $estadoClase = 'status-locked';
                } elseif ($estaCompletado) {
                    $estadoTexto = $esPerfecto ? '★ Perfecto · 5/5' : 'Completado';
                    $estadoClase = 'status-completed';
                } else {
                    $estadoTexto = 'Disponible';
                    $estadoClase = 'status-available';
                }
            ?>
            <article class="card level-card level-accent-<?= $id ?><?= !$estaDesbloqueado ? ' level-card-locked' : '' ?>">
                <div class="level-card-ribbon">
                    <span class="level-num-tag tabular-nums">NIVEL 0<?= $id ?></span>
                    <span class="status-badge <?= $estadoClase ?>">
                        <?php if (!$estaDesbloqueado): ?>
                            <?= icono('lock', 'icon icon-sm') ?>
                        <?php elseif ($estaCompletado): ?>
                            <?= icono('check', 'icon icon-sm') ?>
                        <?php endif; ?>
                        <span><?= h($estadoTexto) ?></span>
                    </span>
                </div>

                <div class="level-card-body">
                    <h2><?= h((string) $nivel['nombre']) ?></h2>
                    <p class="level-description"><?= h((string) $nivel['descripcion']) ?></p>

                    <div class="level-meta-row">
                        <span class="meta-chip"><?= h((string) ($nivel['mecanica'] ?? '5 casos')) ?></span>
                        <span class="meta-chip meta-chip-pop tabular-nums">40 PTS / ACIERTO · 200 MÁX</span>
                    </div>
                </div>

                <div class="level-card-footer">
                    <div class="level-record">
                        <span class="meta-label">RÉCORD DEL NIVEL</span>
                        <span class="record-value tabular-nums"><?= $record ?> / 200 pts</span>
                        <div class="progress-track progress-track-sm">
                            <div class="progress-fill progress-fill-animated" style="width: <?= min(100, (int) round(($record / 200) * 100)) ?>%;"></div>
                        </div>
                    </div>

                    <div class="level-action">
                        <?php if ($estaDesbloqueado): ?>
                            <a
                                href="ventanilla.php?nivel=<?= $id ?>"
                                class="btn <?= $estaCompletado ? 'btn-secondary' : 'btn-primary btn-sheen' ?>"
                            >
                                <span><?= $estaCompletado ? 'Rejugar nivel' : 'Atender ventanilla' ?></span>
                                <?= icono('arrow-right', 'icon') ?>
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-disabled" disabled>
                                <?= icono('lock', 'icon') ?>
                                <span>Bloqueado</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php render_footer(); ?>
