# Monitor Wydatków Osobistych

Aplikacja webowa do ewidencji i wizualizacji wydatków osobistych.

## 📋 Wymagania

- XAMPP (lub Apache + PHP 7.4+ + MySQL)
- Przeglądarka internetowa
- Bootstrap 5.3
- Chart.js
- Biblioteka Font Awesome

## 🚀 Instalacja

### Krok 1: Przygotowanie XAMPP

1. Pobierz i zainstaluj XAMPP: https://www.apachefriends.org/
2. Uruchom Control Panel XAMPP
3. Uruchom Apache i MySQL

### Krok 2: Skopiowanie plików projektu

1. Skopiuj folder projektu do katalogu:
   ```
   C:\xampp\htdocs\monitor_wydatkow
   ```
   (lub innej ścieżki, gdzie zamontowany jest htdocs)

### Krok 3: Inicjalizacja bazy danych

1. Otwórz w przeglądarce: `http://localhost/monitor_wydatkow/setup_baza.php`
2. Strona wyświetli komunikat o pomyślnym utworzeniu bazy danych
3. Kliknij link "Przejdź do aplikacji" lub wejdź na: `http://localhost/monitor_wydatkow/`

### Krok 4: Konfiguracja (opcjonalnie)

Jeśli używasz innego użytkownika MySQL niż `root`, edytuj plik `db.php`:

```php
$host = 'localhost';      // Adres serwera MySQL
$db = 'portfel';          // Nazwa bazy danych
$user = 'root';           // Użytkownik MySQL
$password = '';           // Hasło MySQL
```

## 📂 Struktura plików

```
monitor_wydatkow/
├── index.php              # Strona główna z formularzem i wykresem
├── db.php                 # Połączenie z bazą danych (PDO)
├── dodaj.php              # Odbieranie danych z formularza i zapis do BD
├── dane_wykresu.php       # API zwracające dane pogrupowane w JSON
├── setup_baza.php         # Skrypt inicjalizujący bazę danych
└── README.md              # Ten plik
```

## 🗄️ Struktura bazy danych

### Baza danych: `portfel`

#### Tabela: `wydatki`

| Pole | Typ | Opis |
|------|-----|------|
| id | INT | Klucz główny, autoinkrementacja |
| nazwa | VARCHAR(100) | Nazwa wydatku (do 100 znaków) |
| kwota | DECIMAL(10,2) | Kwota wydatku w złotych |
| kategoria | VARCHAR(50) | Kategoria: Jedzenie, Transport, Rozrywka, Rachunki, Inne |
| data_wydatku | DATE | Data wydatku |
| created_at | TIMESTAMP | Czas dodania rekordu (automatycznie) |

## 🎨 Funkcjonalności

- ✅ **Formularz dodawania wydatków** z walidacją danych
- ✅ **Wysyłanie danych** metodą POST z bezpiecznym bindowaniem parametrów (PDO)
- ✅ **Wykres kołowy (pie chart)** za pomocą Chart.js
- ✅ **Pobieranie danych asynchroniczne** z fetch API
- ✅ **Responsywny design** dzięki Bootstrap 5
- ✅ **Ochrona przed SQL Injection** poprzez prepared statements
- ✅ **Kategoryzacja wydatków** (5 kategorii do wyboru)

## 🔒 Bezpieczeństwo

Aplikacja wykorzystuje następujące rozwiązania bezpieczeństwa:

1. **Prepared Statements (PDO)** - ochrona przed SQL Injection
2. **Walidacja danych** po stronie serwera
3. **Walidacja kategorii** - tylko dozwolone wartości
4. **Obsługa błędów** - PDOException i try/catch

## 🐛 Troubleshooting

### Problem: "Błąd połączenia z bazą danych"
- Sprawdź czy MySQL jest uruchomiony w XAMPP
- Sprawdź dane dostępu w pliku `db.php`
- Sprawdź czy baza `portfel` została utworzona

### Problem: "Tabela wydatki nie istnieje"
- Uruchom ponownie `setup_baza.php`
- Sprawdź czy MySQL ma uprawnienia

### Problem: "Formularz nie wysyła danych"
- Sprawdź konsolę developerską (F12) czy nie ma błędów
- Sprawdź czy Apache i MySQL są uruchomione
- Sprawdź uprawnienia pliku `dodaj.php`

## 📊 Przykład użycia

1. Otwórz aplikację: http://localhost/monitor_wydatkow/
2. Uzupełnij formularz:
   - Nazwa: "Zakupy spożywcze"
   - Kwota: "150.50"
   - Kategoria: "Jedzenie"
   - Data: dzisiejsza data
3. Kliknij "Zapisz wydatek"
4. Wykres powinien się zaktualizować automatycznie

## 📝 Notatki

- Wszystkie daty są w formacie YYYY-MM-DD
- Kwoty są w złotych polskich (PLN)
- Kategorie są predefiniowane i nie można dodawać nowych
- Baza danych obsługuje wielobajtowe znaki (utf8mb4)

## 📧 Autor
 Oleksii Pavlenko 4A Technikum Nauk Nowoczesnych TerraNova.
Projekt stworzony na potrzeby zadania szkolnego.

---

**Ostatnia aktualizacja:** 2026-01-29

