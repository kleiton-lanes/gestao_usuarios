<?php
declare(strict_types=1);

final class Database
{
    public static function connect(): PDO
    {
        $dir  = __DIR__ . '/../database';
        $file = $dir . '/users.sqlite';

        // Cria a pasta database se ela não existir
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec(file_get_contents($dir . '/schema.sql'));

        return $pdo;
    }
}
