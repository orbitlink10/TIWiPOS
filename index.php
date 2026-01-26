<?php
// Entry point for servers whose document root is set to the project root
// or where .htaccess rewrites are disabled. It simply boots the Laravel
// front controller located in /public.
require __DIR__ . '/public/index.php';
