<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?= isAdmin() ? 'admin.php' : 'applications.php' ?>">
            Корочки.есть
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn() && !isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">Мои заявки</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_application.php">Создать заявку</a>
                    </li>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Выход</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
