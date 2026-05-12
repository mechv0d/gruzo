<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'] ?? '';
    $new_status_id = $_POST['status_id'] ?? '';
    
    if (!empty($new_status_id)) {
        $stmt = $pdo->prepare("UPDATE applications SET status_id = ? WHERE id = ?");
        if ($stmt->execute([$new_status_id, $application_id])) {
            $success = 'Статус заявки обновлен!';
        } else {
            $error = 'Ошибка при обновлении статуса';
        }
    }
}

$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClause = '';
if ($filter !== 'all') {
    $whereClause = "WHERE s.status_code = :filter";
}

$countQuery = "SELECT COUNT(*) FROM applications a";
if ($filter !== 'all') {
    $countQuery .= " JOIN application_statuses s ON a.status_id = s.id WHERE s.status_code = :filter";
}
$countStmt = $pdo->prepare($countQuery);
if ($filter !== 'all') {
    $countStmt->bindParam(':filter', $filter);
}
$countStmt->execute();
$totalApplications = $countStmt->fetchColumn();
$totalPages = ceil($totalApplications / $perPage);

$stmt = $pdo->prepare("
    SELECT a.*, c.name as course_name, u.fio, u.phone, u.email,
           s.status_name as status_text, s.status_code, s.id as status_id
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    JOIN users u ON a.user_id = u.id
    JOIN application_statuses s ON a.status_id = s.id
    $whereClause
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
");

if ($filter !== 'all') {
    $stmt->bindParam(':filter', $filter);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Корочки.есть</title>
    <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Панель администратора</h2>
            <a href="logout.php" class="btn btn-outline-danger">Выход</a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Фильтр заявок</h5>
                <div class="btn-group" role="group">
                    <a href="?filter=all" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">
                        Все (<?= $totalApplications ?>)
                    </a>
                    <a href="?filter=new" class="btn btn-<?= $filter === 'new' ? 'secondary' : 'outline-secondary' ?>">
                        Новые
                    </a>
                    <a href="?filter=in_progress" class="btn btn-<?= $filter === 'in_progress' ? 'info' : 'outline-info' ?>">
                        В процессе
                    </a>
                    <a href="?filter=completed" class="btn btn-<?= $filter === 'completed' ? 'success' : 'outline-success' ?>">
                        Завершенные
                    </a>
                </div>
            </div>
        </div>
        
        <?php if (empty($applications)): ?>
            <div class="alert alert-info">Заявки не найдены</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Контакты</th>
                            <th>Курс</th>
                            <th>Дата начала</th>
                            <th>Оплата</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?= $app['id'] ?></td>
                                <td><?= htmlspecialchars($app['fio']) ?></td>
                                <td>
                                    <small>
                                        <?= htmlspecialchars($app['phone']) ?><br>
                                        <?= htmlspecialchars($app['email']) ?>
                                    </small>
                                </td>
                                <td><?= htmlspecialchars($app['course_name']) ?></td>
                                <td><?= date('d.m.Y', strtotime($app['start_date'])) ?></td>
                                <td><?= $app['payment_method'] === 'cash' ? 'Наличные' : 'Телефон' ?></td>
                                <td>
                                    <span class="badge bg-<?= $app['status_code'] === 'new' ? 'secondary' : ($app['status_code'] === 'in_progress' ? 'primary' : 'success') ?>">
                                        <?= htmlspecialchars($app['status_text']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" data-bs-target="#statusModal<?= $app['id'] ?>">
                                        Изменить
                                    </button>
                                    
                                    <div class="modal fade" id="statusModal<?= $app['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Изменить статус заявки #<?= $app['id'] ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Новый статус</label>
                                                            <select class="form-select" name="status_id" required>
                                                                <?php
                                                                $statuses = $pdo->query("SELECT id, status_name FROM application_statuses ORDER BY id")->fetchAll();
                                                                foreach ($statuses as $status): ?>
                                                                    <option value="<?= $status['id'] ?>" <?= $app['status_id'] == $status['id'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($status['status_name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="alert alert-info">
                                                            <strong>Информация о заявке:</strong><br>
                                                            <strong>Пользователь:</strong> <?= htmlspecialchars($app['fio']) ?><br>
                                                            <strong>Курс:</strong> <?= htmlspecialchars($app['course_name']) ?><br>
                                                            <strong>Дата создания:</strong> <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Сохранить</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
