<?php
declare(strict_types=1);

require_once __DIR__ . '/engine.php';

function render_header(string $tituloPagina, ?array $usuario = null, string $vistaActiva = ''): void
{
    $esVentanilla = ($vistaActiva === 'ventanilla');
    $partidaActiva = isset($_SESSION['partida']) && is_array($_SESSION['partida']) && (($_SESSION['partida']['fase'] ?? '') !== 'completada');
    $nivelDisponible = $usuario !== null ? (int) ($usuario['nivel_desbloqueado'] ?? 1) : 1;
    $urlVentanilla = $partidaActiva ? 'ventanilla.php' : ('ventanilla.php?nivel=' . $nivelDisponible);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($tituloPagina) ?> — Ventanilla Exprés</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/base.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="<?= $usuario === null ? 'layout-auth' : 'has-dock' ?> vista-<?= h($vistaActiva) ?>">

<header class="portal-header" role="banner">
    <div class="portal-header-inner">
        <a href="<?= $usuario !== null ? 'escritorio.php' : 'index.php' ?>" class="portal-brand">
            <span class="portal-logo-box" aria-hidden="true">
                <?= icono('sello', 'icon icon-logo') ?>
            </span>
            <span class="portal-brand-text">
                <strong class="portal-title">Ventanilla Exprés</strong>
                <span class="portal-subtitle"><span class="portal-paec-prefix">PAEC:</span>IMPULSO 64 PERFIL NAVOJOA CONECTA 2.0</span>
            </span>
        </a>

        <?php if ($usuario !== null): ?>
            <?php
                $puntos = (int) ($usuario['puntos_totales'] ?? 0);
                $porcentaje = min(100, (int) round(($puntos / 1000) * 100));
                $esJefe = es_jefe_ventanilla($usuario);
            ?>
            <div class="portal-header-right">
                <div class="header-progress-box" title="Progreso global hacia 1,000 puntos">
                    <div class="header-progress-row tabular-nums">
                        <span class="header-progress-label">Progreso global</span>
                        <strong><?= $puntos ?> / 1000 pts</strong>
                    </div>
                    <div class="progress-track progress-track-navy" role="progressbar" aria-valuenow="<?= $puntos ?>" aria-valuemin="0" aria-valuemax="1000">
                        <div class="progress-fill" style="width: <?= $porcentaje ?>%;"></div>
                    </div>
                </div>

                <div class="header-user-chip">
                    <span class="header-user-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr((string) $usuario['usuario'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span>
                    <div class="header-user-info">
                        <span class="header-user-name"><?= h((string) $usuario['usuario']) ?></span>
                        <span class="header-user-role"><?= $esJefe ? 'Jefe de Ventanilla' : ('Nivel ' . $nivelDisponible . ' / 5') ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>

<main class="main-container<?= $esVentanilla ? ' container-ventanilla' : '' ?>">
    <?php
}

function render_footer(): void
{
    $hayUsuario = isset($_SESSION['usuario']) && is_string($_SESSION['usuario']) && $_SESSION['usuario'] !== '';
    $vistaActual = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
    $partidaActiva = isset($_SESSION['partida']) && is_array($_SESSION['partida']) && (($_SESSION['partida']['fase'] ?? '') !== 'completada');
    $urlVentanilla = $partidaActiva ? 'ventanilla.php' : 'escritorio.php';
    ?>
</main>

<?php if ($hayUsuario): ?>
<nav class="floating-dock" aria-label="Navegación principal">
    <a href="escritorio.php" class="dock-item<?= $vistaActual === 'escritorio.php' ? ' is-active' : '' ?>">
        <?= icono('home', 'icon icon-sm') ?>
        <span>Inicio</span>
    </a>
    <a href="<?= h($urlVentanilla) ?>" class="dock-item<?= ($vistaActual === 'ventanilla.php' || $vistaActual === 'resumen.php') ? ' is-active' : '' ?>">
        <?= icono('acta', 'icon icon-sm') ?>
        <span>Trámites</span>
    </a>
    <a href="guia.php" class="dock-item<?= $vistaActual === 'guia.php' ? ' is-active' : '' ?>">
        <?= icono('book', 'icon icon-sm') ?>
        <span>Guía</span>
    </a>
    <a href="logout.php" class="dock-item dock-item-logout">
        <?= icono('logout', 'icon icon-sm') ?>
        <span>Salir</span>
    </a>
</nav>
<?php endif; ?>
</body>
</html>
    <?php
}
