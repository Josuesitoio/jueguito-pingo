<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$usuario = requerir_sesion();
$documentos = obtener_documentos_catalogo();
$partidaActiva = obtener_partida();
$puedeVolverVentanilla = ($partidaActiva !== null && ($partidaActiva['fase'] ?? '') !== 'completada');

render_header('Guía Rápida', $usuario, 'guia');
?>

<section class="portal-page">
    <div class="portal-hero-banner portal-hero-compact anim-fade-up">
        <div class="portal-hero-row">
            <div>
                <span class="portal-hero-badge">Manual de Referencia</span>
                <h1>Guía Rápida de Documentos</h1>
                <p>
                    Consulta la función y criterios de validación de cada documento oficial sin perder tu jornada activa.
                </p>
            </div>

            <div>
                <?php if ($puedeVolverVentanilla): ?>
                    <a href="ventanilla.php" class="btn btn-hero-white">
                        <span>Volver a ventanilla</span>
                        <?= icono('arrow-right', 'icon') ?>
                    </a>
                <?php else: ?>
                    <a href="escritorio.php" class="btn btn-hero-white">
                        <span>Volver al inicio</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <section class="cream-panel anim-fade-up" style="animation-delay: 70ms;">
        <div class="section-heading">
            <h2>Documentos oficiales</h2>
            <p>Requisitos clave para aprobar o rechazar expedientes</p>
        </div>

        <div class="guide-grid">
            <?php $num = 1; ?>
            <?php foreach ($documentos as $doc): ?>
                <article class="guide-card">
                    <div class="guide-card-banner">
                        <div class="guide-card-head">
                            <span class="guide-icon-box">
                                <?= icono($doc['icono'], 'icon') ?>
                            </span>
                            <div>
                                <span class="guide-banner-sub"><?= h($doc['nombre_completo']) ?></span>
                                <h3><?= h($doc['nombre']) ?></h3>
                            </div>
                        </div>
                        <span class="guide-num-pill tabular-nums">Ficha 0<?= $num ?></span>
                    </div>

                    <dl class="guide-fields">
                        <div class="guide-field">
                            <dt class="meta-label">Qué es</dt>
                            <dd><?= h($doc['que_es']) ?></dd>
                        </div>
                        <div class="guide-field">
                            <dt class="meta-label">Para qué se solicita</dt>
                            <dd><?= h($doc['para_que']) ?></dd>
                        </div>
                        <div class="guide-field">
                            <dt class="meta-label">Qué revisar para validar</dt>
                            <dd><?= h($doc['que_revisar']) ?></dd>
                        </div>
                    </dl>
                </article>
                <?php $num++; ?>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<?php render_footer(); ?>
