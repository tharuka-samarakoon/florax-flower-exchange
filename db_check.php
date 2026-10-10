<?php
session_start();
require_once __DIR__.'/config.php';
header('Content-Type: text/plain; charset=utf-8');
try {
  $pdo=db();
  echo "FloraX database connection: OK\n";
  echo "Database: ".DB_NAME."\n";
  echo "Flowers: ".$pdo->query("SELECT COUNT(*) FROM flowers")->fetchColumn()."\n";
  echo "Users: ".$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()."\n";
  echo "Demo buyer: ".($pdo->query("SELECT COUNT(*) FROM users WHERE email='buyer@florax.test'")->fetchColumn() ? "READY":"MISSING")."\n";
  echo "Demo seller: ".($pdo->query("SELECT COUNT(*) FROM users WHERE email='seller@florax.test'")->fetchColumn() ? "READY":"MISSING")."\n";
} catch(Throwable $e) {
  http_response_code(500);
  echo "FloraX database connection: FAILED\n".$e->getMessage()."\n";
}
?>