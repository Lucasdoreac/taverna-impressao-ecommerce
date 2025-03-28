<?php
// Configurações globais da aplicação
define('BASE_URL', 'http://localhost/taverna-impressao/');
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'taverna_impressao');
define('DB_USER', 'root'); // Alterar em produção
define('DB_PASS', '');     // Alterar em produção

// Configurações de e-mail
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USER', 'user@example.com');
define('SMTP_PASS', 'password');
define('SMTP_PORT', 587);

// Configurações da loja
define('STORE_NAME', 'TAVERNA DA IMPRESSÃO');
define('STORE_EMAIL', 'contato@tavernaimpressao.com.br');
define('STORE_PHONE', '(00) 0000-0000');

// Inicializar sessão
session_start();