<?php

declare(strict_types=1);
require __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
header('Location: subscription-payment.php?plan=' . rawurlencode((string) ($_GET['plan'] ?? $_POST['plan'] ?? '')));
exit;
