<?php

declare(strict_types=1);

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$code = rotate_ipg_captcha();
echo ipg_captcha_svg($code);
