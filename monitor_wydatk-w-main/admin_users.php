<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'db.php';

Auth::requireLogin();
Auth::requireAdmin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_user' && isset($_POST['user_id'])) {
        try {
            $user_id = (int)$_POST['user_id'];
            $current_user = Auth::getCurrentUser();
            
            if ($user_id === $current_user['id']) {
                throw new Exception('Nie możesz usunąć swojego konta!');
            }
            
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $user_id]);
            
            $message = 'Użytkownik został usunięty pomyślnie!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Błąd: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie użytkownikami - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-users"></i> Zarządzanie użytkownikami</h1>
            <p class="mb-0">
                <a href="index.php" class="text-white"><i class="fas fa-home"></i> Powrót do domu</a>
                |
                <a href="admin.php" class="text-white"><i class="fas fa-cogs"></i> Panel administracyjny</a>
            </p>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Lista użytkowników</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $sql = "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC";
                            $stmt = $pdo->query($sql);
                            $users = $stmt->fetchAll();
                            
                            $current_user = Auth::getCurrentUser();
                            
                            if (!empty($users)) {
                                echo '<div class="table-responsive">
                                      <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                          <tr>
                                            <th>ID</th>
                                            <th>Nazwa użytkownika</th>
                                            <th>Rola</th>
                                            <th>Konto utworzone</th>
                                            <th class="text-center">Akcje</th>
                                          </tr>
                                        </thead>
                                        <tbody>';
                                
                                foreach ($users as $user) {
                                    $badge_color = $user['role'] === 'admin' ? 'danger' : 'primary';
                                    $is_current = $user['id'] === $current_user['id'];
                                    
                                    echo '<tr' . ($is_current ? ' style="background-color: #fff3cd;"' : '') . '>
                                            <td>' . $user['id'] . '</td>
                                            <td>
                                                <strong>' . htmlspecialchars($user['username']) . '</strong>';
                                    
                                    if ($is_current) {
                                        echo '<br><small class="text-success"><i class="fas fa-check"></i> (Ty)</small>';
                                    }
                                    
                                    echo '</td>
                                            <td><span class="badge bg-' . $badge_color . '">' . htmlspecialchars($user['role']) . '</span></td>
                                            <td>' . date('d.m.Y H:i', strtotime($user['created_at'])) . '</td>
                                            <td class="text-center">';
                                    
                                    if (!$is_current) {
                                        echo '<form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="' . $user['id'] . '">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm(\'Czy na pewno chcesz usunąć użytkownika ' . htmlspecialchars($user['username']) . '?\')">
                                                    <i class="fas fa-trash"></i> Usuń
                                                </button>
                                            </form>';
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    
                                    echo '</td>
                                          </tr>';
                                }
                                
                                echo '  </tbody>
                                      </table>
                                    </div>';
                            } else {
                                echo '<div class="alert alert-info">Brak użytkowników</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger"><strong>Błąd:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informacje</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-lock"></i> <strong>Bezpieczeństwo:</strong> Hasła są przechowywane w postaci haszowanej</li>
                            <li><i class="fas fa-user"></i> <strong>Rola 'user':</strong> Zwykły użytkownik z dostępem do swoich wydatków</li>
                            <li><i class="fas fa-crown"></i> <strong>Rola 'admin':</strong> Administrator z dostępem do panelu administracyjnego</li>
                            <li><i class="fas fa-trash"></i> <strong>Usunięcie użytkownika:</strong> Wszystkie jego wydatki będą usunięte (CASCADE)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
