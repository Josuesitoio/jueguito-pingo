<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

redirigir_si_autenticado();

$errorLogin = null;
$errorRegistro = null;
$tabActiva = (isset($_GET['tab']) && $_GET['tab'] === 'registro') ? 'registro' : 'login';
$usuarioPrevioLogin = '';
$usuarioPrevioRegistro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? '');
    $usuarioInput = (string) ($_POST['usuario'] ?? '');
    $passwordInput = (string) ($_POST['password'] ?? '');

    if ($accion === 'login') {
        $tabActiva = 'login';
        $usuarioPrevioLogin = trim($usuarioInput);
        $res = autenticar_usuario($usuarioInput, $passwordInput);
        if (!empty($res['ok'])) {
            header('Location: escritorio.php');
            exit;
        }
        $errorLogin = (string) ($res['error'] ?? 'No se pudo iniciar sesión.');
    } elseif ($accion === 'registro') {
        $tabActiva = 'registro';
        $usuarioPrevioRegistro = trim($usuarioInput);
        $res = registrar_usuario($usuarioInput, $passwordInput);
        if (!empty($res['ok'])) {
            header('Location: escritorio.php');
            exit;
        }
        $errorRegistro = (string) ($res['error'] ?? 'No se pudo crear la cuenta.');
    }
}

$flash = get_flash();
render_header('Acceso', null, 'index');
?>

<section class="portal-page">
    <!-- Banner superior institucional estilo portada -->
    <div class="portal-hero-banner anim-fade-up">
        <div class="portal-hero-content">
            <span class="portal-hero-badge">Módulo Digital de Capacitación</span>
            <h1>Atiende trámites ciudadanos y valida documentos oficiales</h1>
            <p>
                Practica con 25 casos reales distribuidos en 5 niveles progresivos y domina los requisitos de CURP, NSS, Acta de Nacimiento, Recibo CFE y Correo Electrónico.
            </p>
            <div class="portal-hero-stats tabular-nums">
                <div class="hero-stat-pill">
                    <strong>5</strong>
                    <span>Niveles</span>
                </div>
                <div class="hero-stat-pill">
                    <strong>25</strong>
                    <span>Casos prácticos</span>
                </div>
                <div class="hero-stat-pill">
                    <strong>1,000</strong>
                    <span>Puntos máximos</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección crema principal (estilo "Trámites" de la referencia) -->
    <div class="cream-panel auth-portal-grid anim-fade-up" style="animation-delay: 80ms;">
        <div class="auth-portal-info">
            <div class="section-heading">
                <h2>Trámites incluidos</h2>
                <p>Documentos que aprenderás a identificar y revisar en ventanilla</p>
            </div>

            <div class="steel-tiles-preview">
                <div class="steel-tile-mini">
                    <?= icono('acta', 'icon') ?>
                    <span>Acta de Nacimiento</span>
                </div>
                <div class="steel-tile-mini">
                    <?= icono('curp', 'icon') ?>
                    <span>CURP</span>
                </div>
                <div class="steel-tile-mini">
                    <?= icono('nss', 'icon') ?>
                    <span>NSS (IMSS)</span>
                </div>
                <div class="steel-tile-mini">
                    <?= icono('cfe', 'icon') ?>
                    <span>Recibo de CFE</span>
                </div>
                <div class="steel-tile-mini steel-tile-mini-wide">
                    <?= icono('correo', 'icon') ?>
                    <span>Correo Electrónico</span>
                </div>
            </div>
        </div>

        <div class="auth-card-white">
            <?php if ($flash !== null): ?>
                <div class="alert alert-<?= h($flash['tipo']) ?> anim-fade-up" role="status">
                    <?= h($flash['mensaje']) ?>
                </div>
            <?php endif; ?>

            <div
                class="auth-tabs"
                role="tablist"
                aria-label="Acceso de usuario"
                data-auth-tabs
                data-initial-tab="<?= h($tabActiva) ?>"
            >
                <span class="auth-tab-indicator" aria-hidden="true"></span>
                <button
                    type="button"
                    role="tab"
                    id="tab-login"
                    aria-controls="panel-login"
                    aria-selected="<?= $tabActiva === 'login' ? 'true' : 'false' ?>"
                    class="auth-tab-btn<?= $tabActiva === 'login' ? ' is-active' : '' ?>"
                    data-tab-target="login"
                >
                    Iniciar sesión
                </button>
                <button
                    type="button"
                    role="tab"
                    id="tab-registro"
                    aria-controls="panel-registro"
                    aria-selected="<?= $tabActiva === 'registro' ? 'true' : 'false' ?>"
                    class="auth-tab-btn<?= $tabActiva === 'registro' ? ' is-active' : '' ?>"
                    data-tab-target="registro"
                >
                    Crear cuenta
                </button>
            </div>

            <!-- Panel: Iniciar sesión -->
            <div
                id="panel-login"
                role="tabpanel"
                aria-labelledby="tab-login"
                class="auth-tab-panel<?= $tabActiva === 'login' ? ' is-active' : '' ?>"
                data-tab-panel="login"
                <?= $tabActiva === 'login' ? '' : 'hidden' ?>
            >
                <div class="auth-form-head">
                    <h3>Acceso al portal</h3>
                    <p>Ingresa con tu usuario para continuar tus niveles.</p>
                </div>

                <?php if ($errorLogin !== null): ?>
                    <div class="alert alert-error anim-shake" role="alert">
                        <?= icono('x', 'icon icon-sm') ?>
                        <span><?= h($errorLogin) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php" class="stack-form" novalidate>
                    <input type="hidden" name="accion" value="login">
                    <div class="field-group">
                        <label for="login-usuario" class="field-label">Usuario</label>
                        <input
                            id="login-usuario"
                            name="usuario"
                            type="text"
                            class="text-input"
                            placeholder="Ingresa tu usuario"
                            value="<?= h($usuarioPrevioLogin) ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                    <div class="field-group">
                        <label for="login-password" class="field-label">Contraseña</label>
                        <input
                            id="login-password"
                            name="password"
                            type="password"
                            class="text-input"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <span>Entrar al portal</span>
                        <?= icono('arrow-right', 'icon') ?>
                    </button>
                </form>
            </div>

            <!-- Panel: Crear cuenta -->
            <div
                id="panel-registro"
                role="tabpanel"
                aria-labelledby="tab-registro"
                class="auth-tab-panel<?= $tabActiva === 'registro' ? ' is-active' : '' ?>"
                data-tab-panel="registro"
                <?= $tabActiva === 'registro' ? '' : 'hidden' ?>
            >
                <div class="auth-form-head">
                    <h3>Registro de alumno / operador</h3>
                    <p>Crea tu expediente para guardar tus récords por nivel.</p>
                </div>

                <?php if ($errorRegistro !== null): ?>
                    <div class="alert alert-error anim-shake" role="alert">
                        <?= icono('x', 'icon icon-sm') ?>
                        <span><?= h($errorRegistro) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php" class="stack-form" novalidate>
                    <input type="hidden" name="accion" value="registro">
                    <div class="field-group">
                        <label for="reg-usuario" class="field-label">Usuario</label>
                        <input
                            id="reg-usuario"
                            name="usuario"
                            type="text"
                            class="text-input"
                            placeholder="Ej. operador_64"
                            value="<?= h($usuarioPrevioRegistro) ?>"
                            minlength="2"
                            maxlength="24"
                            autocomplete="username"
                            required
                        >
                    </div>
                    <div class="field-group">
                        <label for="reg-password" class="field-label">Contraseña</label>
                        <input
                            id="reg-password"
                            name="password"
                            type="password"
                            class="text-input"
                            placeholder="Crea una contraseña"
                            autocomplete="new-password"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <span>Registrar cuenta</span>
                        <?= icono('arrow-right', 'icon') ?>
                    </button>
                </form>

                <p class="form-footnote">
                    Inicias en Nivel 1 con acceso inmediato a la Guía Rápida de documentos.
                </p>
            </div>
        </div>
    </div>
</section>

<?php render_footer(); ?>
