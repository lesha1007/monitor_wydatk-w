CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE wydatki ADD COLUMN user_id INT NOT NULL DEFAULT 1 AFTER id;
ALTER TABLE wydatki ADD CONSTRAINT fk_wydatki_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$YFJvBtE8dBmLLg.V1J7gAeP4ZrHZL.1e8.7Z6C5d.2KK4c5C5d3YW', 'admin'),
('user', '$2y$10$8gBtQ7H4cN3mP1K8vL2nJ.eQ7d8F9g5h6i1j2k3l4m5n6o7p8q9r0s', 'user');
