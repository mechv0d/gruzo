<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin.php');
}

$errors = [];
$success = '';

$stmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
$courses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? '';
    $start_date = trim($_POST['start_date'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($course_id)) {
        $errors['course_id'] = 'Выберите курс';
    }
    
    if (empty($start_date)) {
        $errors['start_date'] = 'Укажите дату начала обучения';
    } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $start_date)) {
        $errors['start_date'] = 'Дата должна быть в формате ДД.ММ.ГГГГ';
    } else {
        $dateParts = explode('.', $start_date);
        $mysqlDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        
        if (!checkdate($dateParts[1], $dateParts[0], $dateParts[2])) {
            $errors['start_date'] = 'Некорректная дата';
        }
    }
    
    if (empty($payment_method)) {
        $errors['payment_method'] = 'Выберите способ оплаты';
    } elseif (!in_array($payment_method, ['cash', 'phone_transfer'])) {
        $errors['payment_method'] = 'Некорректный способ оплаты';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO applications (user_id, course_id, start_date, payment_method) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$_SESSION['user_id'], $course_id, $mysqlDate, $payment_method])) {
            $success = 'Заявка успешно отправлена на рассмотрение!';
        } else {
            $errors['general'] = 'Ошибка при создании заявки';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание заявки - Корочки.есть</title>
    <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="mb-4">Создание заявки на обучение</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                            <a href="applications.php" class="btn btn-primary">Перейти к моим заявкам</a>
                        <?php else: ?>
                            <?php if (isset($errors['general'])): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="course_id" class="form-label">Курс *</label>
                                    <select class="form-select <?= isset($errors['course_id']) ? 'is-invalid' : '' ?>" 
                                            id="course_id" name="course_id">
                                        <option value="">Выберите курс</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['id'] ?>" 
                                                <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['course_id'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['course_id']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Желаемая дата начала обучения *</label>
                                    <input type="text" class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>" 
                                           id="start_date" name="start_date" 
                                           value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" 
                                           placeholder="ДД.ММ.ГГГГ">
                                    <?php if (isset($errors['start_date'])): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($errors['start_date']) ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Способ оплаты *</label>
                                    <div>
                                        <div class="form-check">
                                            <input class="form-check-input <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" 
                                                   type="radio" name="payment_method" id="cash" value="cash"
                                                   <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="cash">
                                                Наличными
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input <?= isset($errors['payment_method']) ? 'is-invalid' : '' ?>" 
                                                   type="radio" name="payment_method" id="phone_transfer" value="phone_transfer"
                                                   <?= (isset($_POST['payment_method']) && $_POST['payment_method'] === 'phone_transfer') ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="phone_transfer">
                                                Перевод по номеру телефона
                                            </label>
                                        </div>
                                        <?php if (isset($errors['payment_method'])): ?>
                                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['payment_method']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Отправить заявку</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
