<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $application_id = $_POST['application_id'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $review_text = trim($_POST['review_text'] ?? '');
    $rating = $_POST['rating'] ?? '';
    
    if (empty($review_text)) {
        $error = 'Введите текст отзыва';
    } elseif (empty($rating) || $rating < 1 || $rating > 5) {
        $error = 'Выберите оценку от 1 до 5';
    } else {
        $stmt = $pdo->prepare("
            SELECT a.status_id, s.status_code 
            FROM applications a
            JOIN application_statuses s ON a.status_id = s.id
            WHERE a.id = ? AND a.user_id = ?
        ");
        $stmt->execute([$application_id, $_SESSION['user_id']]);
        $app = $stmt->fetch();
        
        if (!$app) {
            $error = 'Заявка не найдена';
        } elseif ($app['status_code'] !== 'completed') {
            $error = 'Отзыв можно оставить только после завершения обучения';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM course_reviews WHERE application_id = ?");
            $stmt->execute([$application_id]);
            
            if ($stmt->fetch()) {
                $error = 'Вы уже оставили отзыв к этой заявке';
            } else {
                $stmt = $pdo->prepare("INSERT INTO course_reviews (course_id, user_id, application_id, review_text, rating) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$course_id, $_SESSION['user_id'], $application_id, $review_text, $rating])) {
                    $success = 'Отзыв успешно добавлен!';
                } else {
                    $error = 'Ошибка при добавлении отзыва';
                }
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, c.name as course_name, c.id as course_id,
           s.status_name as status_text, s.status_code,
           r.id as review_id, r.review_text, r.rating
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    JOIN application_statuses s ON a.status_id = s.id
    LEFT JOIN course_reviews r ON a.id = r.application_id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заявки - Корочки.есть</title>
    <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="slider-container mb-5">
            <div id="courseSlider" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#courseSlider" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#courseSlider" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#courseSlider" data-bs-slide-to="2"></button>
                    <button type="button" data-bs-target="#courseSlider" data-bs-slide-to="3"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="media/slide1.jpg" class="d-block w-100" alt="Обучение программированию">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Профессиональное образование</h5>
                            <p>Получите новые навыки с нашими курсами</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="media/slide2.jpg" class="d-block w-100" alt="Веб-дизайн">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Основы веб-дизайна</h5>
                            <p>Создавайте красивые интерфейсы</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="media/slide3.jpg" class="d-block w-100" alt="Базы данных">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Проектирование баз данных</h5>
                            <p>Освойте работу с данными</p>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="media/slide4.jpg" class="d-block w-100" alt="Сертификаты">
                        <div class="carousel-caption d-none d-md-block">
                            <h5>Получите сертификат</h5>
                            <p>Подтвердите свою квалификацию</p>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#courseSlider" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                    <span class="visually-hidden">Назад</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#courseSlider" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                    <span class="visually-hidden">Вперед</span>
                </button>
            </div>
        </div>
        
        <h2 class="mb-4">Мои заявки на обучение</h2>
        
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
        
        <?php if (empty($applications)): ?>
            <div class="alert alert-info">
                У вас пока нет заявок. <a href="create_application.php">Создать заявку</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($applications as $app): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($app['course_name']) ?></h5>
                                <p class="card-text">
                                    <strong>Дата начала:</strong> <?= date('d.m.Y', strtotime($app['start_date'])) ?><br>
                                    <strong>Способ оплаты:</strong> <?= $app['payment_method'] === 'cash' ? 'Наличными' : 'Перевод по телефону' ?><br>
                                    <strong>Статус:</strong> 
                                    <span class="badge bg-<?= $app['status_code'] === 'new' ? 'secondary' : ($app['status_code'] === 'in_progress' ? 'primary' : 'success') ?>">
                                        <?= htmlspecialchars($app['status_text']) ?>
                                    </span><br>
                                    <small class="text-muted">Создана: <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></small>
                                </p>
                                
                                <?php if ($app['status_code'] === 'completed'): ?>
                                    <?php if ($app['review_id']): ?>
                                        <div class="alert alert-success mt-3">
                                            <strong>Ваш отзыв:</strong><br>
                                            <?= htmlspecialchars($app['review_text']) ?><br>
                                            <strong>Оценка:</strong> <?= str_repeat('⭐', $app['rating']) ?>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" 
                                                data-bs-toggle="modal" data-bs-target="#reviewModal<?= $app['id'] ?>">
                                            Оставить отзыв
                                        </button>
                                        
                                        <div class="modal fade" id="reviewModal<?= $app['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Оставить отзыв</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                                            <input type="hidden" name="course_id" value="<?= $app['course_id'] ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Оценка</label>
                                                                <select class="form-select" name="rating" required>
                                                                    <option value="">Выберите оценку</option>
                                                                    <option value="5">⭐⭐⭐⭐⭐ Отлично</option>
                                                                    <option value="4">⭐⭐⭐⭐ Хорошо</option>
                                                                    <option value="3">⭐⭐⭐ Удовлетворительно</option>
                                                                    <option value="2">⭐⭐ Плохо</option>
                                                                    <option value="1">⭐ Очень плохо</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Отзыв</label>
                                                                <textarea class="form-control" name="review_text" rows="4" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                            <button type="submit" name="add_review" class="btn btn-primary">Отправить отзыв</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
