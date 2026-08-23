<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    redirect('/admin/login');
}

auth_logout();
redirect('/admin/login');
