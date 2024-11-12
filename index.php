<?php

declare(strict_types= 1);

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use Core\Database;

$db = new Database($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);

echo '<pre>';
var_dump($db->table('posts')->where('id', '<', '4')->orderBy('id', 'ASC')->limit(2,1)->get());
echo '</pre>';

echo $db->getLastId();