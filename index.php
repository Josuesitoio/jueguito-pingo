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

<section class="auth-landing">
    <div class="auth-showcase">
        <div class="showcase-eyebrow">
            <span class="live-dot" aria-hidden="true"></span>
            <span class="meta-label">SIMULADOR CÍVICO INTERACTIVO · MÉXICO</span>
        </div>

        <div class="auth-brand">
            <span class="brand-mark brand-mark-xl">VE</span>
            <div>
                <h1 class="hero-title">
                    VENTANILLA <span class="hero-title-highlight">EXPRÉS</span>
                </h1>
                <p class="hero-tagline">
                    Sella trámites a toda velocidad, caza documentos falsos y domina los 5 papeles oficiales de México.
                </p>
            </div>
        </div>

        <!-- Escenario maximalista de escritorio con documentos coloridos y sello rotatorio -->
        <div class="hero-desk-stage" aria-hidden="true">
            <div class="hero-burst-sticker">
                <span>1,000</span>
                <small>PTS MÁX</small>
            </div>

            <div class="hero-doc-card doc-curp hero-doc-1">
                <div class="hero-doc-top">
                    <span class="doc-icon-wrap"><?= icono('curp', 'icon icon-sm') ?></span>
                    <span class="meta-label">CURP · 18 CARACTERES</span>
                    <span class="hero-doc-status hero-doc-ok">★ VÁLIDO</span>
                </div>
                <div class="mono-data hero-doc-code">RALS980514MDFMPF09</div>
            </div>

            <div class="hero-doc-card doc-nss hero-doc-2">
                <div class="hero-doc-top">
                    <span class="doc-icon-wrap"><?= icono('nss', 'icon icon-sm') ?></span>
                    <span class="meta-label">NSS · IMSS / INFONAVIT</span>
                    <span class="hero-doc-status hero-doc-warn">✕ ERROR</span>
                </div>
                <div class="mono-data hero-doc-code">4598A201934</div>
            </div>

            <div class="hero-doc-card doc-acta hero-doc-3">
                <div class="hero-doc-top">
                    <span class="doc-icon-wrap"><?= icono('acta', 'icon icon-sm') ?></span>
                    <span class="meta-label">ACTA · FILIACIÓN Y EDAD</span>
                    <span class="hero-doc-status hero-doc-info">OFICIAL</span>
                </div>
                <div class="mono-data hero-doc-code">FOLIO REGISTRO: 004821</div>
            </div>

            <div class="hero-stamp-badge">
                <?= icono('sello', 'icon icon-sm') ?>
                <span>¡APROBADO!</span>
            </div>
        </div>

        <div class="showcase-pills">
            <div class="showcase-pill pill-pink">
                <?= icono('check', 'icon icon-sm') ?>
                <span>5 Niveles · 25 Casos</span>
            </div>
            <div class="showcase-pill pill-cobalt">
                <?= icono('lupa', 'icon icon-sm') ?>
                <span>Modo Inspector</span>
            </div>
            <div class="showcase-pill pill-gold">
                <?= icono('sello', 'icon icon-sm') ?>
                <span>Rango Jefe de Ventanilla</span>
            </div>
        </div>
    </div>

    <div class="auth-panel-wrap">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?= h($flash['tipo']) ?> anim-pop" role="status">
                <?= h($flash['mensaje']) ?>
            </div>
        <?php endif; ?>

        <article class="card auth-card auth-card-elevated">
            <div class="card-stripe-header">
                <span>MÓDULO DE ACCESO // TURNO ACTIVO</span>
                <span class="card-dots" aria-hidden="true"><i></i><i></i><i></i></span>
            </div>

            <div class="auth-card-inner">
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
                    <div class="card-head">
                        <p class="meta-label">OPERADOR REGISTRADO</p>
                        <h2>¡Abre tu ventanilla!</h2>
                    </div>

                    <?php if ($errorLogin !== null): ?>
                        <div class="alert alert-error anim-shake" role="alert">
                            <?= icono('x', 'icon') ?>
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
                                placeholder="Tu nombre de operador"
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
                        <button type="submit" class="btn btn-primary btn-lg btn-block btn-sheen">
                            <span>Entrar al escritorio</span>
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
                    <div class="card-head">
                        <p class="meta-label">NUEVO EXPEDIENTE</p>
                        <h2>¡Únete al turno!</h2>
                    </div>

                    <?php if ($errorRegistro !== null): ?>
                        <div class="alert alert-error anim-shake" role="alert">
                            <?= icono('x', 'icon') ?>
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
                                placeholder="Ej. operador_mx"
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
                        <button type="submit" class="btn btn-accent-pink btn-lg btn-block btn-sheen">
                            <span>Registrar y comenzar</span>
                            <?= icono('arrow-right', 'icon') ?>
                        </button>
                    </form>

                    <p class="form-footnote">
                        Arrancas en <strong>Nivel 01</strong> con acceso total a la Guía Rápida y reto hacia <strong>1,000 puntos</strong>.
                    </p>
                </div>
            </div>
        </article>
    </div>
</section>

<?php render_footer(); ?>
