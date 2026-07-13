<?php

declare(strict_types=1);

/**
 * Generate a strong APP_KEY and print an .env line.
 * Usage: php scripts/generate_app_key.php
 */

$key = bin2hex(random_bytes(32));
echo "APP_KEY={$key}" . PHP_EOL;
echo "Paste into .env (do not commit .env)." . PHP_EOL;
