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

<section class="page-section">
    <div class="page-header page-header-banner">
        <div>
            <p class="meta-label header-pill">★ MANUAL OFICIAL DE VENTANILLA ★</p>
            <h1>Guía Rápida de <span class="text-highlight-pink">Documentos</span></h1>
            <p class="page-subtitle">
                Consulta las reglas de uso y los filtros de validación de los cinco documentos clave sin perder tu turno activo.
            </p>
        </div>

        <div class="page-header-actions">
            <?php if ($puedeVolverVentanilla): ?>
                <a href="ventanilla.php" class="btn btn-primary btn-sheen">
                    <span>Volver a ventanilla</span>
                    <?= icono('arrow-right', 'icon') ?>
                </a>
            <?php else: ?>
                <a href="escritorio.php" class="btn btn-secondary">Volver al escritorio</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="guide-grid">
        <?php $num = 1; ?>
        <?php foreach ($documentos as $doc): ?>
            <article class="card guide-card <?= h($doc['clase']) ?>">
                <div class="guide-card-banner">
                    <div class="guide-card-head">
                        <span class="doc-icon-wrap">
                            <?= icono($doc['icono'], 'icon') ?>
                        </span>
                        <div>
                            <p class="meta-label"><?= h($doc['nombre_completo']) ?></p>
                            <h2><?= h($doc['nombre']) ?></h2>
                        </div>
                    </div>
                    <span class="guide-num-sticker tabular-nums">FICHA 0<?= $num ?></span>
                </div>

                <dl class="guide-fields">
                    <div class="guide-field">
                        <dt class="meta-label">QUÉ ES</dt>
                        <dd><?= h($doc['que_es']) ?></dd>
                    </div>
                    <div class="guide-field">
                        <dt class="meta-label">PARA QUÉ SE SOLICITA</dt>
                        <dd><?= h($doc['para_que']) ?></dd>
                    </div>
                    <div class="guide-field">
                        <dt class="meta-label">QUÉ REVISAR PARA VALIDAR</dt>
                        <dd><?= h($doc['que_revisar']) ?></dd>
                    </div>
                </dl>
            </article>
            <?php $num++; ?>
        <?php endforeach; ?>
    </div>
</section>

<?php render_footer(); ?>
