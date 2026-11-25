<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

function render_header(string $tituloPagina, ?array $usuario = null, string $vistaActiva = ''): void
{
    $esVentanilla = ($vistaActiva === 'ventanilla');
    $partidaActiva = isset($_SESSION['partida']) && is_array($_SESSION['partida']) && (($_SESSION['partida']['fase'] ?? '') !== 'completada');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($tituloPagina) ?> — Ventanilla Exprés</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="<?= $usuario === null ? 'layout-auth' : 'has-topbar' ?> vista-<?= h($vistaActiva) ?>">
<div class="bg-stage" aria-hidden="true">
    <div class="bg-halftone"></div>
    <div class="bg-stripe-corner bg-stripe-tl"></div>
    <div class="bg-stripe-corner bg-stripe-br"></div>
    <div class="bg-blob bg-blob-pink"></div>
    <div class="bg-blob bg-blob-cobalt"></div>
    <div class="bg-blob bg-blob-gold"></div>
    <div class="bg-blob bg-blob-emerald"></div>
    <svg class="bg-star bg-star-1" viewBox="0 0 100 100" fill="currentColor">
        <polygon points="50,0 61,35 98,35 68,57 79,92 50,70 21,92 32,57 2,35 39,35"/>
    </svg>
    <svg class="bg-star bg-star-2" viewBox="0 0 100 100" fill="currentColor">
        <polygon points="50,0 59,28 85,15 72,41 100,50 72,59 85,85 59,72 50,100 41,72 15,85 28,59 0,50 28,41 15,15 41,28"/>
    </svg>
    <svg class="bg-star bg-star-3" viewBox="0 0 100 100" fill="currentColor">
        <polygon points="50,0 63,37 100,50 63,63 50,100 37,63 0,50 37,37"/>
    </svg>
</div>

<?php if ($usuario === null): ?>
    <div class="marquee-bar" aria-hidden="true">
        <div class="marquee-track">
            <span>★ VENTANILLA EXPRÉS</span>
            <span>● CURP 18 CARACTERES</span>
            <span>★ NSS 11 DÍGITOS</span>
            <span>● ACTA DE NACIMIENTO</span>
            <span>★ RECIBO CFE ≤ 3 MESES</span>
            <span>● CORREO ELECTRÓNICO</span>
            <span>★ 5 NIVELES · 25 CASOS</span>
            <span>● META 1,000 PUNTOS</span>
            <span>★ VENTANILLA EXPRÉS</span>
            <span>● CURP 18 CARACTERES</span>
            <span>★ NSS 11 DÍGITOS</span>
            <span>● ACTA DE NACIMIENTO</span>
            <span>★ RECIBO CFE ≤ 3 MESES</span>
            <span>● CORREO ELECTRÓNICO</span>
            <span>★ 5 NIVELES · 25 CASOS</span>
            <span>● META 1,000 PUNTOS</span>
        </div>
    </div>
<?php else: ?>
    <?php
        $puntos = (int) ($usuario['puntos_totales'] ?? 0);
        $porcentaje = min(100, (int) round(($puntos / 1000) * 100));
        $esJefe = es_jefe_ventanilla($usuario);
    ?>
    <header class="topbar" role="banner">
        <div class="topbar-inner">
            <div class="topbar-identity">
                <a href="escritorio.php" class="brand-link" aria-label="Ir al Escritorio de Ventanilla Exprés">
                    <span class="brand-mark">VE</span>
                    <span class="brand-name">Ventanilla Exprés</span>
                </a>
                <span class="topbar-divider" aria-hidden="true"></span>
                <div class="user-identity">
                    <span class="user-pill"><?= h((string) $usuario['usuario']) ?></span>
                    <?php if ($esJefe): ?>
                        <span class="badge-tag badge-tag-prestige">★ Jefe de Ventanilla</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="topbar-metrics">
                <div class="global-progress" title="Progreso global hacia el tope de 1,000 puntos">
                    <div class="global-progress-text tabular-nums">
                        <span class="meta-label">GLOBAL</span>
                        <strong><?= $puntos ?> / 1000 PTS</strong>
                    </div>
                    <div class="progress-track" role="progressbar" aria-valuenow="<?= $puntos ?>" aria-valuemin="0" aria-valuemax="1000">
                        <div class="progress-fill progress-fill-animated" style="width: <?= $porcentaje ?>%;"></div>
                    </div>
                </div>
            </div>

            <nav class="topbar-nav" aria-label="Navegación principal">
                <?php if ($partidaActiva && $vistaActiva !== 'ventanilla'): ?>
                    <a href="ventanilla.php" class="nav-link nav-link-active-case">
                        <?= icono('arrow-right', 'icon') ?>
                        <span>Ventanilla</span>
                    </a>
                <?php endif; ?>
                <a href="guia.php" class="nav-link<?= $vistaActiva === 'guia' ? ' is-active' : '' ?>">
                    <?= icono('book', 'icon') ?>
                    <span>Guía</span>
                </a>
                <a href="logout.php" class="nav-link nav-link-logout">
                    <?= icono('logout', 'icon') ?>
                    <span>Salir</span>
                </a>
            </nav>
        </div>
    </header>
<?php endif; ?>
<main class="main-container<?= $esVentanilla ? ' container-ventanilla' : '' ?>">
    <?php
}

function render_footer(): void
{
    ?>
</main>
</body>
</html>
    <?php
}
