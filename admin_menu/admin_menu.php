<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../funcs/auth.php';

requireAdmin('../login.php');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function yen($value)
{
    return number_format((int)$value) . '円';
}

$message = '';
$messageType = 'info';

try {
    $pdo = getPdo();
    ensureAppSchema($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_tax') {
            setTaxRate($pdo, $_POST['tax_rate'] ?? 10);
            $message = '消費税率を更新しました。';
            $messageType = 'success';

        } elseif ($action === 'reset_password') {
            $userId = (int)($_POST['id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';

            $stmt = $pdo->prepare('SELECT user_id, password_hash FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $targetUser = $stmt->fetch();
            
            $targetLoginId = $targetUser['user_id'] ?? '';
            $currentHash = $targetUser['password_hash'] ?? '';

            if ($targetLoginId === 'adm') {
                $currentPassword = $_POST['current_password'] ?? '';
                
                if ($currentPassword === '' || !password_verify($currentPassword, $currentHash)) {
                    $message = '現在のパスワードが正しくありません。';
                    $messageType = 'error';
                } elseif (strlen($newPassword) < 4) {
                    $message = '新しいパスワードは4文字以上で入力してください。';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                    $message = '管理者admのパスワードを変更しました。';
                    $messageType = 'success';
                }
            } else {
                if ($userId <= 0 || strlen($newPassword) < 4) {
                    $message = '新しいパスワードは4文字以上で入力してください。';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                    $message = 'パスワードを変更しました。';
                    $messageType = 'success';
                }
            }

        } elseif ($action === 'delete_user') {
            $userId = (int)($_POST['id'] ?? 0);
            $currentUserId = (int)($_SESSION['user_db_id'] ?? 0);

            if ($userId === $currentUserId) {
                $message = 'ログイン中の自分自身は削除できません。';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('SELECT user_id, role FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $targetUser = $stmt->fetch();
                $targetRole = $targetUser['role'] ?? null;
                $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = 1')->fetchColumn();

                if (($targetUser['user_id'] ?? '') === 'adm') {
                    $message = '固定管理者admは削除できません。';
                    $messageType = 'error';
                } elseif ((int)$targetRole === 1 && $adminCount <= 1) {
                    $message = '最後の管理者は削除できません。';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                    $message = 'ユーザーを削除しました。';
                    $messageType = 'success';
                }
            }

        } elseif ($action === 'update_role') {
            $userId = (int)($_POST['id'] ?? 0);
            $role = (int)($_POST['role'] ?? 0);
            $currentUserId = (int)($_SESSION['user_db_id'] ?? 0);
            $adminCount = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = 1')->fetchColumn();

            if ($userId === $currentUserId && $role !== 1) {
                $message = 'ログイン中の自分自身を一般ユーザーには変更できません。';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('SELECT user_id, role FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $targetUser = $stmt->fetch();
                $targetLoginId = $targetUser['user_id'] ?? '';
                $currentRole = (int)($targetUser['role'] ?? 0);

                if ($targetLoginId !== 'adm' && $role === 1) {
                    $message = '管理者アカウントはadmのみです。';
                    $messageType = 'error';
                } elseif ($targetLoginId === 'adm' && $role !== 1) {
                    $message = '固定管理者admは一般ユーザーに変更できません。';
                    $messageType = 'error';
                } elseif ($currentRole === 1 && $role !== 1 && $adminCount <= 1) {
                    $message = '最後の管理者は一般ユーザーに変更できません。';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                    $stmt->execute([$role === 1 ? 1 : 0, $userId]);
                    $message = 'ユーザー権限を更新しました。';
                    $messageType = 'success';
                }
            }
        } elseif ($action === 'add_item') {
            $name = $_POST['name'] ?? '';
            $price = (int)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            
            if ($name === '') {
                $message = '商品名を入力してください。';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO items (name, price, stock) VALUES (?, ?, ?)');
                $stmt->execute([$name, $price, $stock]);
                $message = '新しい商品を追加しました。';
                $messageType = 'success';
            }

        } elseif ($action === 'update_item') {
            $itemId = (int)($_POST['id'] ?? 0);
            $name = $_POST['name'] ?? '';
            $price = (int)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            
            if ($name === '' || $itemId <= 0) {
                $message = '商品名が正しくありません。';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('UPDATE items SET name = ?, price = ?, stock = ? WHERE id = ?');
                $stmt->execute([$name, $price, $stock, $itemId]);
                $message = '商品情報を更新しました。';
                $messageType = 'success';
            }

        } elseif ($action === 'delete_item') {
            $itemId = (int)($_POST['id'] ?? 0);
            if ($itemId > 0) {
                $stmt = $pdo->prepare('DELETE FROM items WHERE id = ?');
                $stmt->execute([$itemId]);
                $message = '商品を削除しました。';
                $messageType = 'success';
            }
        }
    }

    $taxRate = getTaxRate($pdo);
    $salesToday = (int)$pdo->query('SELECT COALESCE(SUM(amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()')->fetchColumn();
    $salesAll = (int)$pdo->query('SELECT COALESCE(SUM(amount), 0) FROM sales')->fetchColumn();
    $salesCount = (int)$pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn();
    $recentSales = $pdo->query('
        SELECT s.id, s.customer_id, s.amount, s.created_at, u.user_id AS customer_user_id
        FROM sales s
        LEFT JOIN users u ON u.id = s.customer_id
        ORDER BY created_at DESC
        LIMIT 20
    ')->fetchAll();
    $users = $pdo->query('SELECT id, user_id, role, created_at FROM users ORDER BY id')->fetchAll();
    $items = $pdo->query('SELECT id, name, price, stock FROM items ORDER BY id')->fetchAll();
} catch (PDOException $e) {
    $message = 'データベース処理でエラーが発生しました。';
    $messageType = 'error';
    $taxRate = 10;
    $salesToday = 0;
    $salesAll = 0;
    $salesCount = 0;
    $recentSales = [];
    $users = [];
    $items = [];
    error_log('管理者ページエラー: ' . $e->getMessage());
}

include __DIR__ . '/view.php';
