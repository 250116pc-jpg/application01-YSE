<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/funcs/auth.php';

requireAdmin('login.php');

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

// ★ sales_action.php から戻ってきたときのメッセージを受け取る
if (isset($_SESSION['cancel_message'])) {
    $message = $_SESSION['cancel_message'];
    $messageType = $_SESSION['cancel_message_type'] ?? 'success';
    unset($_SESSION['cancel_message'], $_SESSION['cancel_message_type']);
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
        .dashboard-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 10px; margin-bottom: 30px; }
        @media (max-width: 900px) { .dashboard-grid { grid-template-columns: 1fr; } }
        .chart-wrapper h3 { font-size: 16px; margin-bottom: 12px; color: var(--ink); border-bottom: 1px solid var(--line); padding-bottom: 8px; }
        .chart-container { position: relative; height: 280px; width: 100%; }
        .no-data-msg { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--muted); font-size: 14px; background: #f8f9fa; border: 1px dashed var(--line); border-radius: 4px; }
        .filter-form { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .filter-form select { padding: 8px 12px; border: 1px solid var(--line); border-radius: 4px; font-size: 14px; font-weight: bold; color: var(--ink); }
        .check-col { width: 40px; text-align: center; }
    </style>
    <?php renderTabSessionGuard('login.php'); ?>
</head>
<body class="admin-page">
    <header class="app-header">
        <div><p class="eyebrow">DASHBOARD</p><h1>売上データ分析</h1></div>
        <nav class="top-actions">
            <a href="admin_menu/admin_menu.php">管理メニューへ</a>
            <a href="index.php">レジ画面へ</a>
        </nav>
    </header>

    <?php if ($message): ?><div class="notice <?= h($messageType) ?>"><?= h($message) ?></div><?php endif; ?>

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
                style="margin-left: 20px; padding: 10px 24px; background: #2980b9; color: #fff; text-decoration: none; border-radius: 4px; font-size: 14px; font-weight: bold; border: 1px solid #2980b9; display: inline-block; white-space: nowrap;">CSV出力</a>
            </form>
            <div class="dashboard-grid">
                <div class="chart-wrapper"><h3>売上推移（日別）</h3><div class="chart-container"><?php if ($hasTrendData): ?><canvas id="trendChart"></canvas><?php else: ?><div class="no-data-msg">データなし</div><?php endif; ?></div></div>
                <div class="chart-wrapper"><h3>売れ筋商品トップ10</h3><div class="chart-container"><?php if ($hasRankingData): ?><canvas id="rankingChart"></canvas><?php else: ?><div class="no-data-msg">データなし</div><?php endif; ?></div></div>
            </div>
        </section>

        <section class="admin-card">
            <h2 style="margin-bottom: 15px;">対象期間の売上履歴</h2>
            <form method="post" action="sales_action.php">
                <input type="hidden" name="action" value="cancel_sale">
                <input type="hidden" name="period" value="<?= h($period) ?>">
                <div style="margin-bottom: 12px; text-align: right;">
                    <button type="submit" name="bulk_cancel" value="1" style="padding: 6px 16px; background: #9f2a23; color: #fff; font-size: 13px; border: 1px solid #9f2a23; border-radius: 4px; font-weight: bold; cursor: pointer;" onclick="return confirm('チェックした売上を一括取消しますか？')">チェックした項目を一括取消</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="check-col"><input type="checkbox" id="selectAllCheckbox" title="すべて選択"></th>
                                <th><label for="selectAllCheckbox" style="cursor: pointer; display: block;">売上ID</label></th>
                                <th>金額</th>
                                <th>日時</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentSales) > 0): foreach ($recentSales as $sale): ?>
                            <tr>
                                <td class="check-col"><input type="checkbox" name="sale_ids[]" value="<?= h($sale['id']) ?>" class="sale-checkbox" id="sale_<?= h($sale['id']) ?>"></td>
                                <td><label for="sale_<?= h($sale['id']) ?>" style="cursor: pointer; display: block;"><?= h($sale['id']) ?></label></td>
                                <td><?= number_format($sale[$valField]) ?>円</td> 
                                <td><?= h($sale['created_at']) ?></td>
                                <td>
                                    <button type="submit" name="single_sale_id" value="<?= h($sale['id']) ?>" style="padding: 4px 10px; background: #9f2a23; color: #fff; font-size: 12px; border: 1px solid #9f2a23; border-radius: 4px; font-weight: bold; cursor: pointer;" onclick="return confirm('売上ID #<?= h($sale['id']) ?> を取消しますか？')">取消</button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--muted);">データがありません</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </section>
    </main>
    <script>
        const selectAll = document.getElementById('selectAllCheckbox');
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.sale-checkbox').forEach(cb => cb.checked = selectAll.checked);
        });
        <?php if ($hasTrendData): ?>
        new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: <?= $trendLabels ?>, datasets: [{ label: '売上金額 (円)', data: <?= $trendValues ?>, borderColor: '#244a73', backgroundColor: 'rgba(36, 74, 115, 0.1)', fill: true }] } });
        <?php endif; ?>
        <?php if ($hasRankingData): ?>
        new Chart(document.getElementById('rankingChart'), { type: 'bar', data: { labels: <?= $rankingLabels ?>, datasets: [{ label: '販売数 (個)', data: <?= $rankingQty ?>, backgroundColor: '#2f6f4e', borderRadius: 3 }] }, options: { indexAxis: 'y' } });
        <?php endif; ?>
    </script>
</body>
</html>

