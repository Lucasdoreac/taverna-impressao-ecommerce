<?php
/**
 * test-results-save.php
 * 
 * Script para salvar os resultados dos testes do visualizador 3D
 * em dispositivos móveis em um arquivo JSON estruturado.
 */

// Incluir configurações
require_once '../app/config/config.php';

// Verificar se é um POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: test-mobile-viewer.php');
    exit;
}

// Validar dados recebidos
$required_fields = ['test_case', 'device', 'browser', 'test_result'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

// Se campos obrigatórios estão faltando, redirecionar com erro
if (!empty($missing_fields)) {
    $error_params = http_build_query([
        'error' => 'missing_fields',
        'fields' => implode(',', $missing_fields)
    ]);
    header('Location: test-mobile-viewer.php?' . $error_params);
    exit;
}

// Extrair e sanitizar dados
$test_case = htmlspecialchars($_POST['test_case']);
$device = htmlspecialchars($_POST['device']);
$browser = htmlspecialchars($_POST['browser']);
$test_result = htmlspecialchars($_POST['test_result']);
$load_time = isset($_POST['load_time']) ? (float)$_POST['load_time'] : null;
$fps = isset($_POST['fps']) ? (float)$_POST['fps'] : null;
$observations = isset($_POST['observations']) ? htmlspecialchars($_POST['observations']) : '';
$model = isset($_POST['model']) ? htmlspecialchars($_POST['model']) : 'small';
$device_info = isset($_POST['device_info_json']) ? $_POST['device_info_json'] : '{}';

// Validar JSON da info do dispositivo
$device_info_array = [];
try {
    $device_info_array = json_decode($device_info, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $device_info_array = [];
    }
} catch (Exception $e) {
    $device_info_array = [];
}

// Preparar dados para salvar
$test_result_data = [
    'id' => uniqid('test_'),
    'test_case' => $test_case,
    'device' => $device,
    'browser' => $browser,
    'model' => $model,
    'result' => $test_result,
    'metrics' => [
        'load_time' => $load_time,
        'fps' => $fps
    ],
    'observations' => $observations,
    'device_info' => $device_info_array,
    'timestamp' => date('Y-m-d H:i:s')
];

// Caminho para o arquivo de resultados
$results_file = ROOT_PATH . '/data/mobile_test_results.json';
$results_dir = dirname($results_file);

// Criar diretório se não existir
if (!is_dir($results_dir)) {
    mkdir($results_dir, 0755, true);
}

// Carregar resultados existentes ou criar novo array
$all_results = [];
if (file_exists($results_file)) {
    $file_content = file_get_contents($results_file);
    try {
        $all_results = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($all_results)) {
            $all_results = [];
        }
    } catch (Exception $e) {
        $all_results = [];
    }
}

// Adicionar novo resultado
$all_results[] = $test_result_data;

// Salvar resultados
$save_success = file_put_contents(
    $results_file,
    json_encode($all_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// Verificar se salvou com sucesso
if ($save_success === false) {
    $error_params = http_build_query([
        'error' => 'save_failed',
        'test' => $test_case
    ]);
    header('Location: test-mobile-viewer.php?' . $error_params);
    exit;
}

// Redirecionar para a página principal com sucesso
$success_params = http_build_query([
    'success' => '1',
    'test' => $test_case
]);
header('Location: test-mobile-viewer.php?' . $success_params);
exit;
