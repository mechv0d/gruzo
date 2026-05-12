<?php
require_once 'config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $fio = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($login)) {
        $errors['login'] = 'Логин обязателен для заполнения';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $login)) {
        $errors['login'] = 'Логин должен содержать только латиницу и цифры';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Пароль обязателен для заполнения';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Пароль должен содержать минимум 8 символов';
    }
    
    if (empty($fio)) {
        $errors['fio'] = 'ФИО обязательно для заполнения';
    } elseif (!preg_match('/^[а-яёА-ЯЁ\s]+$/u', $fio)) {
        $errors['fio'] = 'ФИО должно содержать только кириллицу и пробелы';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^8\(\d{3}\)\d{5}-\d{2}$/', $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате 8(XXX)XXXXX-XX';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный формат email';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($stmt->fetch()) {
            $errors['login'] = 'Логин уже занят';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (login, password, fio, phone, email) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$login, $password, $fio, $phone, $email])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $errors['general'] = 'Ошибка при создании пользователя';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Корочки.есть</title>
    <link href="css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Регистрация</h2>
                        <p class="text-center text-muted mb-4">Портал "Корочки.есть"</p>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="registerForm">
                            <div class="mb-3">
                                <label for="login" class="form-label">Логин *</label>
                                <input type="text" class="form-control <?= isset($errors['login']) ? 'is-invalid' : '' ?>" 
                                       id="login" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" 
                                       placeholder="Только латиница и цифры">
                                <?php if (isset($errors['login'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['login']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Пароль *</label>
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                       id="password" name="password" placeholder="Минимум 8 символов">
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fio" class="form-label">ФИО *</label>
                                <input type="text" class="form-control <?= isset($errors['fio']) ? 'is-invalid' : '' ?>" 
                                       id="fio" name="fio" value="<?= htmlspecialchars($_POST['fio'] ?? '') ?>" 
                                       placeholder="Иванов Иван Иванович">
                                <?php if (isset($errors['fio'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['fio']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Телефон *</label>
                                <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" 
                                       placeholder="8(999)12345-67">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                       placeholder="example@mail.ru">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">Зарегистрироваться</button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">Уже зарегистрированы? <a href="login.php">Войти</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="js/validation.js"></script>
</body>
</html>
