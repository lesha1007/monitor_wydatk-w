<?php
session_start();
require_once 'config.php';
require_once 'db.php';

class Auth
{
    private static $pdo;

    public static function initialize($pdo_instance)
    {
        self::$pdo = $pdo_instance;
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }

    public static function requireAdmin()
    {
        self::requireLogin();
        if ($_SESSION['role'] !== 'admin') {
            header('Location: index.php');
            exit();
        }
    }

    public static function login($username, $password)
    {
        global $pdo;
        
        try {
            $sql = "SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function logout()
    {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    public static function register($username, $password, $confirm_password)
    {
        global $pdo;
        
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Nazwa użytkownika i hasło są wymagane'];
        }

        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Nazwa użytkownika musi mieć co najmniej 3 znaki'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Hasło musi mieć co najmniej 6 znaków'];
        }

        if ($password !== $confirm_password) {
            return ['success' => false, 'message' => 'Hasła nie są identyczne'];
        }

        try {
            $sql = "SELECT id FROM users WHERE username = :username LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Nazwa użytkownika jest już zajęta'];
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashed_password,
                ':role' => 'user'
            ]);

            return ['success' => true, 'message' => 'Konto utworzono pomyślnie'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Błąd podczas rejestracji: ' . $e->getMessage()];
        }
    }

    public static function getCurrentUser()
    {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
}

Auth::initialize($pdo);
?>
