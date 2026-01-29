CREATE DATABASE IF NOT EXISTS portfel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE portfel;

CREATE TABLE IF NOT EXISTS wydatki (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nazwa VARCHAR(100) NOT NULL COMMENT 'Nazwa wydatku',
    kwota DECIMAL(10, 2) NOT NULL COMMENT 'Kwota wydatku w zł',
    kategoria VARCHAR(50) NOT NULL COMMENT 'Kategoria: Jedzenie, Transport, Rozrywka, Rachunki, Inne',
    data_wydatku DATE NOT NULL COMMENT 'Data wydatku',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Czas dodania rekordu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO wydatki (nazwa, kwota, kategoria, data_wydatku) VALUES
('Zakupy spożywcze', 125.50, 'Jedzenie', '2026-01-29'),
('Bilet autobusowy', 50.00, 'Transport', '2026-01-29'),
('Kino', 40.00, 'Rozrywka', '2026-01-29'),
('Opłata za internet', 75.99, 'Rachunki', '2026-01-25'),
('Dostawa jedzenia', 35.50, 'Jedzenie', '2026-01-28'),
('Ubezpieczenie samochodu', 200.00, 'Rachunki', '2026-01-20');

SELECT kategoria, SUM(kwota) AS suma FROM wydatki GROUP BY kategoria ORDER BY suma DESC;

SELECT * FROM wydatki ORDER BY data_wydatku DESC;

SELECT SUM(kwota) AS razem FROM wydatki;

