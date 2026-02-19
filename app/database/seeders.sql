-- Демо-пользователи (пароль для всех: password)

INSERT INTO users (name, email, password, role) VALUES
    ('Диспетчер Иванова', 'dispatcher@example.com', '$2y$10$WUyqg1kN7Y3apVBO6K4ZQOd615XKjESbrz1NpG9u83laGWVwi5JdO', 'dispatcher'),
    ('Мастер Петров', 'master1@example.com', '$2y$10$WUyqg1kN7Y3apVBO6K4ZQOd615XKjESbrz1NpG9u83laGWVwi5JdO', 'master'),
    ('Мастер Сидоров', 'master2@example.com', '$2y$10$WUyqg1kN7Y3apVBO6K4ZQOd615XKjESbrz1NpG9u83laGWVwi5JdO', 'master');
