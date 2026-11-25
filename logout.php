<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

cerrar_sesion();
header('Location: index.php');
exit;
