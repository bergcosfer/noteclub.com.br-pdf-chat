<?php
require 'config.php';
try {
    $pdo = db();
    echo "Banco OK\n";
    $pdo->exec("SELECT 1 FROM users_online LIMIT 1");
    echo "Tabela users_online OK\n";
    $pdo->exec("SELECT 1 FROM messages LIMIT 1");
    echo "Tabela messages OK\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
