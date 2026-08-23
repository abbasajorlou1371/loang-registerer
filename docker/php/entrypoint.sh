#!/bin/sh
set -eu

echo "Waiting for MySQL..."
i=0
until php -r '
$h = getenv("DB_HOST") ?: "db";
$p = getenv("DB_PORT") ?: "3306";
$n = getenv("DB_NAME") ?: "loan_registrarer";
$u = getenv("DB_USER") ?: "loanapp";
$w = getenv("DB_PASS") !== false ? (string) getenv("DB_PASS") : "loanapp";
try {
    $pdo = new PDO(
        sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $h, $p, $n),
        $u,
        $w,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
    $pdo->query("SELECT 1 FROM users LIMIT 1");
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
'; do
  i=$((i + 1))
  if [ "$i" -ge 60 ]; then
    echo "Database did not become ready in time." >&2
    exit 1
  fi
  sleep 2
done

php /var/www/html/sql/seed_admin.php

exec docker-php-entrypoint "$@"
