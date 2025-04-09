<?php

class AdminLoginLogController extends AdminController {
    private $loginLogModel;

    public function __construct() {
        parent::__construct();
        $this->loginLogModel = new LoginLogModel();
    }

    public function index() {
        $page = $_GET['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $logs = $this->loginLogModel->getLogs($offset, $limit);
        $totalLogs = $this->loginLogModel->getTotalLogs();
        $totalPages = ceil($totalLogs / $limit);

        $this->view('admin/login_logs', [
            'logs' => $logs,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    public function userHistory($userId) {
        $logs = $this->loginLogModel->getLoginHistory($userId);
        $this->view('admin/user_login_history', [
            'logs' => $logs
        ]);
    }

    public function export() {
        $logs = $this->loginLogModel->getAllLogs();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="login_logs_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date/Time', 'User', 'Action', 'Status', 'IP Address']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['email'],
                $log['action'],
                $log['status'],
                $log['ip_address']
            ]);
        }
        
        fclose($output);
        exit;
    }
}