<?php
$loginLogModel = new LoginLogModel();
$recentLogs = $loginLogModel->getLogs(0, 5);
?>

<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">Recent Login Activity</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><?php echo date('H:i', strtotime($log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($log['email']); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $log['status'] === 'success' ? 'success' : 'danger'; ?>">
                                    <?php echo htmlspecialchars($log['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <a href="/admin/login-logs" class="btn btn-primary btn-sm">View All Activity</a>
        </div>
    </div>
</div>