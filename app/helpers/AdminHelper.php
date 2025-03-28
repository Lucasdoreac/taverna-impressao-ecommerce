<?php
/**
 * AdminHelper - Helper para funções administrativas
 */
class AdminHelper {
    /**
     * Verifica se o usuário está logado e é administrador
     * Se não estiver, redireciona para a página de login
     */
    public static function checkAdminAccess() {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            // Salvar página de redirecionamento
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Redirecionar para login
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        // Verificar se é administrador
        if ($_SESSION['user']['role'] !== 'admin') {
            // Redirecionar para a página inicial
            $_SESSION['error'] = 'Acesso negado. Você não tem permissão para acessar esta área.';
            header('Location: ' . BASE_URL);
            exit;
        }
    }

    /**
     * Retorna o nome do menu ativo com base na URL atual
     */
    public static function getActiveMenu() {
        $uri = $_SERVER['REQUEST_URI'];
        
        if (strpos($uri, '/admin/produtos') !== false) {
            return 'produtos';
        } else if (strpos($uri, '/admin/categorias') !== false) {
            return 'categorias';
        } else if (strpos($uri, '/admin/pedidos') !== false) {
            return 'pedidos';
        } else if (strpos($uri, '/admin/usuarios') !== false) {
            return 'usuarios';
        } else if (strpos($uri, '/admin/configuracoes') !== false) {
            return 'configuracoes';
        } else {
            return 'dashboard';
        }
    }

    /**
     * Formata um valor em moeda brasileira
     */
    public static function formatMoney($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    /**
     * Formata uma data no padrão brasileiro
     */
    public static function formatDate($date) {
        return date('d/m/Y', strtotime($date));
    }

    /**
     * Formata uma data com hora no padrão brasileiro
     */
    public static function formatDateTime($date) {
        return date('d/m/Y H:i', strtotime($date));
    }

    /**
     * Limita um texto a um determinado número de caracteres
     */
    public static function limitText($text, $limit = 100) {
        if (strlen($text) <= $limit) {
            return $text;
        }
        
        return substr($text, 0, $limit) . '...';
    }

    /**
     * Gera um slug a partir de um texto
     */
    public static function generateSlug($text) {
        // Converter para minúsculas
        $text = mb_strtolower($text, 'UTF-8');
        
        // Remover acentos
        $text = preg_replace('/[áàãâä]/u', 'a', $text);
        $text = preg_replace('/[éèêë]/u', 'e', $text);
        $text = preg_replace('/[íìîï]/u', 'i', $text);
        $text = preg_replace('/[óòõôö]/u', 'o', $text);
        $text = preg_replace('/[úùûü]/u', 'u', $text);
        $text = preg_replace('/[ç]/u', 'c', $text);
        
        // Remover caracteres especiais
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        
        // Converter espaços para hífens
        $text = preg_replace('/[\s-]+/', '-', $text);
        
        // Remover hífens do início e fim
        $text = trim($text, '-');
        
        return $text;
    }

    /**
     * Gera um código SKU único para produtos
     */
    public static function generateSKU($categoryCode, $productId) {
        $randomPart = strtoupper(substr(md5(uniqid()), 0, 4));
        return $categoryCode . '-' . str_pad($productId, 4, '0', STR_PAD_LEFT) . '-' . $randomPart;
    }

    /**
     * Upload de imagem
     */
    public static function uploadImage($file, $destination, $maxWidth = 1200, $maxHeight = 1200) {
        // Verificar se é uma imagem válida
        $check = getimagesize($file['tmp_name']);
        if (!$check) {
            return [
                'success' => false,
                'message' => 'O arquivo enviado não é uma imagem válida.'
            ];
        }
        
        // Obter informações do arquivo
        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Verificar extensão
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExt, $allowedExts)) {
            return [
                'success' => false,
                'message' => 'Tipo de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.'
            ];
        }
        
        // Verificar tamanho (max 5MB)
        if ($fileSize > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Arquivo muito grande. O tamanho máximo é 5MB.'
            ];
        }
        
        // Criar diretório de upload se não existir
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Gerar nome único para o arquivo
        $newFileName = uniqid() . '.' . $fileExt;
        $uploadPath = $destination . '/' . $newFileName;
        
        // Redimensionar imagem se necessário
        list($width, $height) = getimagesize($fileTmp);
        
        if ($width > $maxWidth || $height > $maxHeight) {
            // Calcular novas dimensões mantendo proporção
            if ($width > $height) {
                $newWidth = $maxWidth;
                $newHeight = ($height / $width) * $maxWidth;
            } else {
                $newHeight = $maxHeight;
                $newWidth = ($width / $height) * $maxHeight;
            }
            
            // Criar nova imagem
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            // Carregar imagem original
            switch ($fileExt) {
                case 'jpg':
                case 'jpeg':
                    $source = imagecreatefromjpeg($fileTmp);
                    break;
                case 'png':
                    $source = imagecreatefrompng($fileTmp);
                    // Preservar transparência
                    imagealphablending($thumb, false);
                    imagesavealpha($thumb, true);
                    break;
                case 'gif':
                    $source = imagecreatefromgif($fileTmp);
                    break;
            }
            
            // Redimensionar
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Salvar nova imagem
            switch ($fileExt) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($thumb, $uploadPath, 90);
                    break;
                case 'png':
                    imagepng($thumb, $uploadPath);
                    break;
                case 'gif':
                    imagegif($thumb, $uploadPath);
                    break;
            }
            
            // Liberar memória
            imagedestroy($source);
            imagedestroy($thumb);
        } else {
            // Mover arquivo sem redimensionar
            move_uploaded_file($fileTmp, $uploadPath);
        }
        
        return [
            'success' => true,
            'message' => 'Imagem enviada com sucesso.',
            'filename' => $newFileName
        ];
    }
}
