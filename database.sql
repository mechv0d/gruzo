-- База данных для портала "Корочки.есть"
CREATE DATABASE IF NOT EXISTS korocki_est CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE korocki_est;

-- Таблица ролей
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(50) UNIQUE NOT NULL,
    role_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fio VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role_id INT NOT NULL DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица курсов
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица статусов заявок (отдельная таблица)
CREATE TABLE IF NOT EXISTS application_statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status_code VARCHAR(50) UNIQUE NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица заявок (без статуса в ENUM)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    status_id INT NOT NULL DEFAULT 1,
    start_date DATE NOT NULL,
    payment_method ENUM('cash', 'phone_transfer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES application_statuses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов на курсы (отдельная таблица)
CREATE TABLE IF NOT EXISTS course_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
    application_id INT NOT NULL,
    review_text TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка ролей
INSERT INTO roles (role_code, role_name, description) VALUES 
('admin', 'Администратор', 'Полный доступ к системе'),
('user', 'Пользователь', 'Обычный пользователь');

INSERT INTO application_statuses (status_code, status_name, description) VALUES 
('new', 'Новая', 'Заявка только что создана'),
('in_progress', 'Идет обучение', 'Обучение в процессе'),
('completed', 'Обучение завершено', 'Курс успешно пройден');

-- Вставка администратора (ПАРОЛЬ В ОТКРЫТОМ ВИДЕ - ДЛЯ ДЕМО ЭКЗАМЕНА)
INSERT INTO users (login, password, fio, phone, email, role_id)
VALUES ('Admin', 'KorokNET', 'Администратор', '8(999)99999-99', 'admin@korocki.est', 1);

-- Вставка курсов
INSERT INTO courses (name, description) VALUES 
('Основы алгоритмизации и программирования', 'Изучение основ программирования и алгоритмов'),
('Основы веб-дизайна', 'Создание современных веб-интерфейсов'),
('Основы проектирования баз данных', 'Проектирование и разработка баз данных');
