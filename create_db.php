<?php
System.Management.Automation.Internal.Host.InternalHost = '127.0.0.1';
 = 3306;
 = 'root';
 = '';
try {
     = new PDO("mysql:host={System.Management.Automation.Internal.Host.InternalHost};port={}", , , [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    ->exec("CREATE DATABASE IF NOT EXISTS laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "Database created or already exists\n";
} catch (Throwable ) {
    echo "Error: ".->getMessage()."\n";
    exit(1);
}
