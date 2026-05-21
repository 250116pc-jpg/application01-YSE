<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ページ | YSEレジ</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-page">
    <header class="app-header">
        <div>
            <p class="eyebrow">ADMIN</p>
            <h1>管理者ページ</h1>
        </div>
        <nav class="top-actions">
            <a href="../index.php">レジへ</a>
            <a href="../sales_view.php">売上分析</a> <div class="user-status">
                <span>ログイン中</span>
                <strong><?= h($_SESSION['login_user_id'] ?? '未ログイン') ?></strong>
            </div>
            <?php if (!isset($_SESSION['user_db_id'])): ?>
                <a href="../login.php">ログイン</a>
            <?php else: ?>
                <a href="../login.php?logout=1">ログアウト</a>
            <?php endif; ?>
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
                <p>消費税変更、売上確認、ユーザー削除、パスワード変更、商品管理をここで操作できます。</p>
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
                        
                        <form method="post" style="display: flex; gap: 6px; align-items: center;">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                            <?php if ($user['user_id'] === 'adm'): ?>
                                <input type="password" name="current_password" placeholder="現パスワード" required style="width: 95px; padding: 6px 8px; font-size: 13px;">
                            <?php endif; ?>
                            <input type="password" name="new_password" placeholder="新パスワード" required style="width: 95px; padding: 6px 8px; font-size: 13px;">
                            <button type="submit" style="padding: 6px 10px; font-size: 13px; white-space: nowrap;">変更</button>
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
            <h2>商品と在庫の管理</h2>
            
            <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin-top: 0; font-size: 14px; color: #555;">新規商品追加</h3>
                <form method="post" class="inline-form" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="add_item">
                    <input type="text" name="name" placeholder="商品名" required style="flex: 1; min-width: 150px;">
                    <input type="number" name="price" placeholder="価格" min="0" required style="width: 100px;">
                    <input type="number" name="stock" placeholder="在庫数" min="0" required style="width: 100px;">
                    <button type="submit" class="primary-btn">追加</button>
                </form>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>商品名</th>
                            <th>価格 (円)</th>
                            <th>在庫</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= h($item['id']) ?></td>
                                <td>
                                    <form method="post" id="form_update_item_<?= h($item['id']) ?>" style="margin: 0;">
                                        <input type="hidden" name="action" value="update_item">
                                        <input type="hidden" name="id" value="<?= h($item['id']) ?>">
                                        <input type="text" name="name" value="<?= h($item['name']) ?>" required style="width: 100%; box-sizing: border-box; padding: 4px;">
                                    </form>
                                </td>
                                <td>
                                    <input type="number" name="price" value="<?= h($item['price']) ?>" min="0" required form="form_update_item_<?= h($item['id']) ?>" style="width: 80px; padding: 4px;">
                                </td>
                                <td>
                                    <input type="number" name="stock" value="<?= h($item['stock']) ?>" min="0" required form="form_update_item_<?= h($item['id']) ?>" style="width: 80px; padding: 4px;">
                                </td>
                                <td style="display: flex; gap: 6px;">
                                    <button type="submit" form="form_update_item_<?= h($item['id']) ?>" style="padding: 4px 8px;">更新</button>
                                    
                                    <form method="post" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="id" value="<?= h($item['id']) ?>">
                                        <button type="submit" class="danger-btn" style="padding: 4px 8px;" onclick="return confirm('「<?= h($item['name']) ?>」を本当に削除しますか？')">削除</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>