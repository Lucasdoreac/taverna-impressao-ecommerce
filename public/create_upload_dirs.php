<?php
/**
 * Script para criar diretórios de upload ausentes
 * 
 * Este script deve ser executado para garantir que todos os diretórios
 * necessários para uploads existam no servidor.
 */

// Definir caminho raiz
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');

// Lista de diretórios que devem existir
$directories = [
    UPLOADS_PATH,
    UPLOADS_PATH . '/products',
    UPLOADS_PATH . '/categories',
    UPLOADS_PATH . '/customization',
    UPLOADS_PATH . '/models',
    UPLOADS_PATH . '/users',
    UPLOADS_PATH . '/temp'
];

echo "Verificando e criando diretórios de upload...\n";

// Criar diretórios se não existirem
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Diretório criado: {$dir}\n";
        } else {
            echo "ERRO: Não foi possível criar o diretório: {$dir}\n";
        }
    } else {
        echo "Diretório já existe: {$dir}\n";
    }
}

// Verificar permissões
foreach ($directories as $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "Permissões de {$dir}: {$perms}\n";
        
        if ($perms != '0755') {
            if (chmod($dir, 0755)) {
                echo "Permissões corrigidas para o diretório: {$dir}\n";
            } else {
                echo "ERRO: Não foi possível corrigir as permissões: {$dir}\n";
            }
        }
    }
}

echo "\nVerificação e criação de diretórios concluída!\n";
echo "Para uso em produção, acesse este script via navegador em: " . str_replace([ROOT_PATH, '\\'], ['', '/'], __FILE__) . "\n";
