<?php
// Configurações globais da aplicação
define('BASE_URL', 'https://seudominio.com.br/'); // Atualize com seu domínio
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');

// Configurações do banco de dados
define('DB_HOST', 'localhost'); // Em ambiente hostinger, geralmente é 'localhost'
define('DB_NAME', 'u135851624_taverna'); // Nome do banco conforme criado
define('DB_USER', 'u135851624_taverna'); // Usuário conforme criado
define('DB_PASS', '#Taverna'); // Senha conforme definida

// Configurações de e-mail
define('SMTP_HOST', 'smtp.hostinger.com'); // Atualize com o SMTP correto da Hostinger
define('SMTP_USER', 'contato@seudominio.com.br'); // Atualize com seu email
define('SMTP_PASS', 'senha-do-email'); // Atualize com a senha
define('SMTP_PORT', 587);

// Configurações da loja
define('STORE_NAME', 'TAVERNA DA IMPRESSÃO');
define('STORE_EMAIL', 'contato@seudominio.com.br');
define('STORE_PHONE', '(00) 0000-0000');

// Inicializar sessão
session_start();