<?php
session_start();
require_once 'db.php';

// 【セキュリティ】未ログイン・一般ユーザーを完全に締め出す
if (!isset($_SESSION['user_db_id']) || (int)($_SESSION['role'] ?? 0) !== 1 || ($_SESSION['login_user_id'] ?? '') !== 'adm') {
    header('Location: login.php');
    exit;
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = getPdo();
} catch (Exception $e) {
    die("データベース接続失敗");
}

$message = '';
$messageType = 'info';

// ★【機能強化】売上の取消処理（個別＆一括対応）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_sale') {
    $cancelIds = [];
    
    // ① 個別の「取消」ボタンが押された場合
    if (!empty($_POST['single_sale_id'])) {
        $cancelIds[] = (int)$_POST['single_sale_id'];
    } 
    // ② 「チェックした項目を一括取消」ボタンが押された場合
    elseif (!empty($_POST['bulk_cancel'])) {
        if (!empty($_POST['sale_ids']) && is_array($_POST['sale_ids'])) {
            $cancelIds = array_map('intval', $_POST['sale_ids']);
        } else {
            $message = "取り消す項目がチェックされていません。";
            $messageType = "error";
        }
    }

    // 取消対象のIDが存在する場合のみ処理を実行
    if (!empty($cancelIds)) {
        try {
            $pdo->beginTransaction();

            $stmtItems = $pdo->prepare("SELECT item_id, quantity FROM sale_items WHERE sale_id = ?");
            $stmtUpdateStock = $pdo->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
            $stmtDelItems = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
            $stmtDelSale = $pdo->prepare("DELETE FROM sales WHERE id = ?");

            $processedCount = 0;

            // 対象となる売上IDをループして1つずつ処理
            foreach ($cancelIds as $cid) {
                if ($cid <= 0) continue;

                // 1. 商品の在庫を戻す
                $stmtItems->execute([$cid]);
                $itemsToRestore = $stmtItems->fetchAll();
                foreach ($itemsToRestore as $item) {
                    $stmtUpdateStock->execute([(int)$item['quantity'], (int)$item['item_id']]);
                }

                // 2. 売上データを削除
                $stmtDelItems->execute([$cid]);
                $stmtDelSale->execute([$cid]);
                
                $processedCount++;
            }

            $pdo->commit();
            
            if ($processedCount > 0) {
                $_SESSION['cancel_message'] = "{$processedCount}件の売上データを取り消し、在庫を元に戻しました。";
                header("Location: sales_view.php?period=" . urlencode($_GET['period'] ?? 'all'));
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "取り消し処理中にエラーが発生しました。";
            $messageType = "error";
            error_log("売上取消エラー: " . $e->getMessage());
        }
    }
}

// リダイレクト後に処理完了メッセージを表示
if (isset($_SESSION['cancel_message'])) {
    $message = $_SESSION['cancel_message'];
    $messageType = 'success';
    unset($_SESSION['cancel_message']);
}

// DBのカラム名が amount か total かを自動で判別
try {
    $stmtCols = $pdo->query("SHOW COLUMNS FROM sales");
    $cols = array_column($stmtCols->fetchAll(), 'Field');
    $valField = in_array('total', $cols) ? 'total' : 'amount';
} catch (Exception $e) {
    $valField = 'amount';
}

$period = $_GET['period'] ?? 'all';
$whereClause = '';
$whereClauseSales = '';

if ($period === 'today') {
    $whereClause = 'WHERE created_at >= CURDATE()';
    $whereClauseSales = 'WHERE s.created_at >= CURDATE()';
} elseif ($period === 'week') {
    $whereClause = 'WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    $whereClauseSales = 'WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($period === 'month') {
    $whereClause = 'WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
    $whereClauseSales = 'WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
} elseif ($period === 'year') {
    $whereClause = 'WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
    $whereClauseSales = 'WHERE s.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
}

// 各種データ取得
$stmtTrend = $pdo->query("SELECT DATE(created_at) as date, SUM($valField) as total FROM sales $whereClause GROUP BY DATE(created_at) ORDER BY date");
$trendData = $stmtTrend->fetchAll();
$hasTrendData = count($trendData) > 0;
$trendLabels = json_encode(array_column($trendData, 'date'));
$trendValues = json_encode(array_column($trendData, 'total'));

$stmtRanking = $pdo->query("
    SELECT si.item_name, SUM(si.quantity) as total_qty, SUM(si.subtotal) as total_amount 
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    $whereClauseSales
    GROUP BY si.item_name
    ORDER BY total_qty DESC
    LIMIT 10
");
$rankingData = $stmtRanking->fetchAll();
$hasRankingData = count($rankingData) > 0;
$rankingLabels = json_encode(array_column($rankingData, 'item_name'));
$rankingQty = json_encode(array_column($rankingData, 'total_qty'));

$stmtRecent = $pdo->query("SELECT * FROM sales $whereClause ORDER BY created_at DESC LIMIT 20");
$recentSales = $stmtRecent->fetchAll();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>売上データ分析 | YSE POS</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 10px;
            margin-bottom: 30px;
        }
        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        .chart-wrapper h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: var(--ink);
            border-bottom: 1px solid var(--line);
            padding-bottom: 8px;
        }
        .chart-container {
            position: relative;
            height: 280px;
            width: 100%;
        }
        .no-data-msg {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--muted);
            font-size: 14px;
            background: #f8f9fa;
            border: 1px dashed var(--line);
            border-radius: 4px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid var(--line);
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            color: var(--ink);
        }
        /* テーブルのチェックボックス用スタイル */
        .check-col {
            width: 40px;
            text-align: center;
        }
    </style>
</head>
<body class="admin-page">
    <header class="app-header">
        <div>
            <p class="eyebrow">DASHBOARD</p>
            <h1>売上データ分析</h1>
        </div>
        <nav class="top-actions">
            <a href="admin_menu.php">管理メニューへ</a>
            <a href="index.php">レジ画面へ</a>
        </nav>
    </header>

    <?php if ($message): ?>
        <div class="notice <?= h($messageType) ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <main class="admin-layout">
        <section class="admin-card">
            <form method="get" class="filter-form">
                <label for="period" style="font-size: 14px; font-weight: bold; color: var(--muted);">集計期間：</label>
                <select name="period" id="period" onchange="this.form.submit()">
                    <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>全期間</option>
                    <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>今日</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>過去7日間</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>過去1ヶ月</option>
                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>過去1年</option>
                </select>
                
                <a href="export_csv.php?period=<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>" 
                style="margin-left: 20px; padding: 10px 24px; background: #2980b9; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; border: 1px solid #2980b9; display: inline-block; white-space: nowrap;">
                CSV出力
                </a>
            </form>

            <div class="dashboard-grid">
                <div class="chart-wrapper">
                    <h3>売上推移（日別）</h3>
                    <div class="chart-container">
                        <?php if ($hasTrendData): ?>
                            <canvas id="trendChart"></canvas>
                        <?php else: ?>
                            <div class="no-data-msg">指定された期間内に売上データがありません</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chart-wrapper">
                    <h3>売れ筋商品トップ10（販売数）</h3>
                    <div class="chart-container">
                        <?php if ($hasRankingData): ?>
                            <canvas id="rankingChart"></canvas>
                        <?php else: ?>
                            <div class="no-data-msg">指定された期間内に販売データがありません</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="admin-card">
            <h2 style="margin-bottom: 15px;">対象期間の売上履歴（最新20件）</h2>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="cancel_sale">
                
                <div style="margin-bottom: 12px; text-align: right;">
                    <button type="submit" name="bulk_cancel" value="1" style="padding: 6px 16px; background: #9f2a23; color: #fff; font-size: 13px; border: 1px solid #9f2a23; border-radius: 4px; font-weight: bold; cursor: pointer;" onclick="return confirm('チェックした売上を一括で取り消し、在庫を元に戻しますか？（この操作は取り消せません）')">
                        チェックした項目を一括取消
                    </button>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="check-col">
                                    <input type="checkbox" id="selectAllCheckbox" title="すべて選択">
                                </th>
                                <th>売上ID</th>
                                <th>金額</th>
                                <th>日時</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentSales) > 0): ?>
                                <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td class="check-col">
                                        <input type="checkbox" name="sale_ids[]" value="<?= h($sale['id']) ?>" class="sale-checkbox">
                                    </td>
                                    <td><?= h($sale['id']) ?></td>
                                    <td><?= number_format($sale[$valField]) ?>円</td> 
                                    <td><?= h($sale['created_at']) ?></td>
                                    <td>
                                        <button type="submit" name="single_sale_id" value="<?= h($sale['id']) ?>" style="padding: 4px 10px; background: #9f2a23; color: #fff; font-size: 12px; border: 1px solid #9f2a23; border-radius: 4px; font-weight: bold; cursor: pointer;" onclick="return confirm('売上ID #<?= h($sale['id']) ?> のデータを取り消し、在庫を元に戻しますか？')">
                                            取消
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px; color: var(--muted);">データがありません</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    </main>

    <script>
        // ★【新機能】一番上のチェックボックスを押した時、すべてのチェックボックスを連動させる
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const saleCheckboxes = document.querySelectorAll('.sale-checkbox');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                saleCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        }

        // グラフの描画
        <?php if ($hasTrendData): ?>
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= $trendLabels ?>,
                datasets: [{
                    label: '売上金額 (円)',
                    data: <?= $trendValues ?>,
                    borderColor: '#244a73',
                    backgroundColor: 'rgba(36, 74, 115, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: { callback: function(value) { return value.toLocaleString() + '円'; } }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if ($hasRankingData): ?>
        const rankingCtx = document.getElementById('rankingChart').getContext('2d');
        new Chart(rankingCtx, {
            type: 'bar',
            data: {
                labels: <?= $rankingLabels ?>,
                datasets: [{
                    label: '販売数 (個)',
                    data: <?= $rankingQty ?>,
                    backgroundColor: '#2f6f4e',
                    borderWidth: 0,
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
