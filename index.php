<?php

declare(strict_types=1);

/**
 * XAMPP convenience: project root is not the document root.
 * Send browsers to the public front controller.
 */
header('Location: public/', true, 302);
exit;
