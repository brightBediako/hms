<?php

declare(strict_types=1);

/**
 * XAMPP convenience only: project root is NOT the document root.
 * Production hosts must set the vhost / site document root to /public.
 */
header('Location: public/', true, 302);
exit;
