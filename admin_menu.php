<?php
session_start();
require_once 'db.php';

if ((int)($_SESSION['role'] ?? -1) !== 1 || ($_SESSION['login_user_id'] ?? '') !== 'adm') {
    header('Location: login.php');
    exit;
}

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
    ensureDefaultAdmin($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_tax') {
            setTaxRate($pdo, $_POST['tax_rate'] ?? 10);
            $message = '消費税率を更新しました。';
            $messageType = 'success';

        } elseif ($action === 'reset_password') {
            $userId = (int)($_POST['id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';

            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $targetLoginId = (string)$stmt->fetchColumn();

            if ($targetLoginId === 'adm') {
                $message = '管理者admのパスワードは固定です。';
                $messageType = 'error';
            } elseif ($userId <= 0 || strlen($newPassword) < 4) {
                $message = '新しいパスワードは4文字以上で入力してください。';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                $message = 'パスワードを変更しました。';
                $messageType = 'success';
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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ページ | YSEレジ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-page">
    <header class="app-header">
        <div>
            <p class="eyebrow">ADMIN</p>
            <h1>管理者ページ</h1>
        </div>
        <nav class="top-actions">
            <a href="index.php">レジへ</a>
            <div class="user-status">
                <span>ログイン中</span>
                <strong><?= h($_SESSION['login_user_id'] ?? '未ログイン') ?></strong>
            </div>
            <a href="login.php">ログイン</a>
            <a href="login.php?logout=1">ログアウト</a>
        </nav>
    </header>

    <?php if ($message): ?>
        <div class="notice <?= h($messageType) ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <main class="admin-layout">
        <section class="admin-card hero-admin">
            <div>
                <p class="eyebrow">SYSTEM</p>
                <h2>管理機能一覧</h2>
                <p>消費税変更、売上確認、ユーザー削除、パスワード変更をここで操作できます。</p>
            </div>
        </section>

        <section class="admin-card">
            <h2>消費税の変更</h2>
            <form method="post" class="inline-form">
                <input type="hidden" name="action" value="update_tax">
                <label>
                    税率 (%)
                    <input type="number" name="tax_rate" value="<?= h($taxRate) ?>" min="0" max="100" step="0.1">
                </label>
                <button type="submit" class="primary-btn">保存</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>売上の表示</h2>
            <div class="metric-grid">
                <div><span>本日の売上</span><strong><?= yen($salesToday) ?></strong></div>
                <div><span>総売上</span><strong><?= yen($salesAll) ?></strong></div>
                <div><span>計上回数</span><strong><?= h($salesCount) ?>回</strong></div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>売上ID</th>
                            <th>ユーザー</th>
                            <th>金額</th>
                            <th>日時</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><?= h($sale['id']) ?></td>
                                <td><?= h($sale['customer_user_id'] ?? $sale['customer_id']) ?></td>
                                <td><?= yen($sale['amount']) ?></td>
                                <td><?= h($sale['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card">
            <h2>ユーザー管理</h2>
            <div class="user-list">
                <?php foreach ($users as $user): ?>
                    <div class="user-row">
                        <div>
                            <strong><?= h($user['user_id']) ?></strong>
                            <span><?= ((int)$user['role'] === 1) ? '管理者' : 'ユーザー' ?> / <?= h($user['created_at']) ?></span>
                        </div>
                        <form method="post" class="mini-form">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                            <select name="role">
                                <option value="0" <?= ((int)$user['role'] === 0) ? 'selected' : '' ?>>ユーザー</option>
                                <option value="1" <?= ((int)$user['role'] === 1) ? 'selected' : '' ?>>管理者</option>
                            </select>
                            <button type="submit">権限保存</button>
                        </form>
                        <form method="post" class="mini-form">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                            <input type="password" name="new_password" placeholder="新パスワード">
                            <button type="submit">変更</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                            <button type="submit" class="danger-btn" onclick="return confirm('このユーザーを削除しますか？')">削除</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-card">
            <h2>商品と在庫</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>商品名</th>
                            <th>価格</th>
                            <th>在庫</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= h($item['id']) ?></td>
                                <td><?= h($item['name']) ?></td>
                                <td><?= yen($item['price']) ?></td>
                                <td><?= h($item['stock']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
