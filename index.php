<?php
session_start();
require_once 'db.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function yen($value)
{
    return number_format((int)$value) . '円';
}

function cartSubtotal(array $cart)
{
    $subtotal = 0;
    foreach ($cart as $line) {
        $subtotal += max(0, ((int)$line['price'] * (int)$line['quantity']) - (int)$line['discount']);
    }
    return $subtotal;
}

function cartCount(array $cart)
{
    $count = 0;
    foreach ($cart as $line) {
        $count += (int)$line['quantity'];
    }
    return $count;
}

$cart = $_SESSION['cart'] ?? [];
$notice = $_SESSION['notice'] ?? null;
$salesMessage = $_SESSION['sales_message'] ?? null;
$lastReceipt = $_SESSION['last_receipt'] ?? null;
unset($_SESSION['notice'], $_SESSION['sales_message']);

$items = [];
$taxRate = 10.0;
$dbError = '';

try {
    $pdo = getPdo();
    ensureAppSchema($pdo);
    $taxRate = getTaxRate($pdo);
    $items = $pdo->query('SELECT id, name, price, stock FROM items ORDER BY id')->fetchAll();
} catch (PDOException $e) {
    $dbError = 'データベースに接続できません。XAMPPのMySQLを確認してください。';
    error_log('画面表示エラー: ' . $e->getMessage());
}

$subtotal = cartSubtotal($cart);
$taxAmount = (int)floor($subtotal * ($taxRate / 100));
$total = $subtotal + $taxAmount;
$itemCount = cartCount($cart);
$showLastReceipt = !$cart && $lastReceipt;
$receiptRows = $showLastReceipt ? ($lastReceipt['cart'] ?? []) : $cart;
$displaySubtotal = $showLastReceipt ? (int)$lastReceipt['subtotal'] : $subtotal;
$displayTaxRate = $showLastReceipt ? (float)$lastReceipt['tax_rate'] : $taxRate;
$displayTotal = $showLastReceipt ? (int)$lastReceipt['total'] : $total;
$displayTaxAmount = max(0, $displayTotal - $displaySubtotal);
$displayItemCount = cartCount($receiptRows);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YSEレジ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="pos-page">
    <header class="app-header">
        <div>
            <p class="eyebrow">YSE POS</p>
            <h1>YSEレジ</h1>
        </div>
        <nav class="top-actions">
            <a href="login.php">ログイン</a>
            <a href="admin_menu.php">管理者</a>
        </nav>
    </header>

    <?php if ($dbError): ?>
        <div class="notice error"><?= h($dbError) ?></div>
    <?php endif; ?>
    <?php if ($notice): ?>
        <div class="notice <?= h($notice['type']) ?>"><?= h($notice['message']) ?></div>
    <?php endif; ?>
    <?php if ($salesMessage): ?>
        <div class="notice success"><?= h($salesMessage) ?></div>
    <?php endif; ?>

    <main class="pos-layout">
        <section class="register-panel">
            <div class="screen modern-screen">
                <div class="op-indicator">商品ID</div>
                <div id="disp">0</div>
            </div>

            <form method="post" action="process.php" class="scan-form" id="scanForm">
                <input type="hidden" name="action" value="add_item">
                <label for="item_id">商品検索</label>
                <div class="scan-row">
                    <input
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        name="item_id"
                        id="item_id"
                        placeholder="商品ID / QR / バーコード"
                        autocomplete="off"
                        autofocus
                    >
                    <button type="submit" class="primary-btn">追加</button>
                </div>
                <p class="hint">値段は入力できません。商品IDからデータベースの価格を読み込みます。</p>
            </form>

            <div class="keypad product-keypad" aria-label="商品ID入力キー">
                <button type="button" onclick="addDigit('7')">7</button>
                <button type="button" onclick="addDigit('8')">8</button>
                <button type="button" onclick="addDigit('9')">9</button>
                <button type="button" class="danger-soft" onclick="deleteDigit()">C</button>
                <button type="button" onclick="addDigit('4')">4</button>
                <button type="button" onclick="addDigit('5')">5</button>
                <button type="button" onclick="addDigit('6')">6</button>
                <button type="button" class="danger-soft" onclick="clearInput()">AC</button>
                <button type="button" onclick="addDigit('1')">1</button>
                <button type="button" onclick="addDigit('2')">2</button>
                <button type="button" onclick="addDigit('3')">3</button>
                <button type="button" class="accent-btn" onclick="submitScan()">読込</button>
                <button type="button" class="span-2" onclick="addDigit('0')">0</button>
                <button type="button" class="span-2" onclick="startScanner()">QR/バーコード</button>
            </div>

            <div class="camera-box" id="cameraBox" hidden>
                <video id="scannerVideo" muted playsinline></video>
                <button type="button" onclick="stopScanner()">停止</button>
            </div>

            <div class="catalog">
                <h2>商品一覧</h2>
                <div class="item-grid">
                    <?php foreach ($items as $item): ?>
                        <form method="post" action="process.php" class="item-tile">
                            <input type="hidden" name="action" value="add_item">
                            <input type="hidden" name="item_id" value="<?= h($item['id']) ?>">
                            <button type="submit">
                                <span class="item-id">ID <?= h($item['id']) ?></span>
                                <strong><?= h($item['name']) ?></strong>
                                <span><?= yen($item['price']) ?> / 在庫 <?= h($item['stock']) ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="receipt-panel">
            <div class="receipt-head">
                <div>
                    <p class="eyebrow">RECEIPT</p>
                    <h2><?= $showLastReceipt ? '直近のレシート' : 'レシート' ?></h2>
                </div>
                <span class="count-badge"><?= h($displayItemCount) ?>点</span>
            </div>

            <div class="receipt-lines" id="printArea">
                <?php if (!$receiptRows): ?>
                    <p class="empty">商品IDを入力、またはバーコードリーダーで読み取ってください。</p>
                <?php endif; ?>

                <?php foreach ($receiptRows as $index => $line): ?>
                    <?php $lineSubtotal = max(0, ((int)$line['price'] * (int)$line['quantity']) - (int)$line['discount']); ?>
                    <?php if ($showLastReceipt): ?>
                        <div class="receipt-line print-line">
                            <div class="line-main">
                                <span class="item-id">ID <?= h($line['item_id']) ?></span>
                                <strong><?= h($line['name']) ?></strong>
                                <span><?= yen($line['price']) ?> x <?= h($line['quantity']) ?></span>
                            </div>
                            <span>数量 <?= h($line['quantity']) ?></span>
                            <span>割引 <?= yen($line['discount']) ?></span>
                            <strong class="line-total"><?= yen($lineSubtotal) ?></strong>
                        </div>
                    <?php else: ?>
                        <form method="post" action="process.php" class="receipt-line">
                            <input type="hidden" name="action" value="update_line">
                            <input type="hidden" name="line_index" value="<?= h($index) ?>">
                            <div class="line-main">
                                <span class="item-id">ID <?= h($line['item_id']) ?></span>
                                <strong><?= h($line['name']) ?></strong>
                                <span><?= yen($line['price']) ?> x <?= h($line['quantity']) ?></span>
                            </div>
                            <label>
                                数量
                                <input type="number" name="quantity" value="<?= h($line['quantity']) ?>" min="1" max="99">
                            </label>
                            <label>
                                割引
                                <input type="number" name="discount" value="<?= h($line['discount']) ?>" min="0" step="1">
                            </label>
                            <strong class="line-total"><?= yen($lineSubtotal) ?></strong>
                            <div class="line-actions">
                                <button type="submit">更新</button>
                                <button type="submit" name="action" value="remove_line" class="danger-btn">取消</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="totals">
                <div><span>小計</span><strong><?= yen($displaySubtotal) ?></strong></div>
                <div><span>消費税 <?= h(rtrim(rtrim(number_format($displayTaxRate, 2), '0'), '.')) ?>%</span><strong><?= yen($displayTaxAmount) ?></strong></div>
                <div class="grand-total"><span>合計</span><strong><?= yen($displayTotal) ?></strong></div>
            </div>

            <div class="receipt-actions">
                <form method="post" action="process.php">
                    <button type="submit" name="action" value="undo">← 取り消し</button>
                </form>
                <form method="post" action="process.php">
                    <button type="submit" name="action" value="sales_total">売上</button>
                </form>
                <form method="post" action="process.php">
                    <button type="submit" name="action" value="clear_cart" class="danger-soft">全消去</button>
                </form>
                <form method="post" action="process.php">
                    <button type="submit" name="action" value="checkout" class="primary-btn">計上</button>
                </form>
                <button type="button" onclick="window.print()">印刷</button>
            </div>

            <?php if ($lastReceipt): ?>
                <div class="last-receipt">
                    <h3>直近の印刷データ #<?= h($lastReceipt['sale_id']) ?></h3>
                    <p><?= h($lastReceipt['created_at']) ?> / 合計 <?= yen($lastReceipt['total']) ?></p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        const input = document.getElementById('item_id');
        const display = document.getElementById('disp');
        const scanForm = document.getElementById('scanForm');
        const cameraBox = document.getElementById('cameraBox');
        const scannerVideo = document.getElementById('scannerVideo');
        let scannerStream = null;
        let scannerTimer = null;

        function syncDisplay() {
            display.textContent = input.value || '0';
        }

        function addDigit(digit) {
            if (input.value.length >= 12) return;
            input.value += digit;
            syncDisplay();
            input.focus();
        }

        function deleteDigit() {
            input.value = input.value.slice(0, -1);
            syncDisplay();
            input.focus();
        }

        function clearInput() {
            input.value = '';
            syncDisplay();
            input.focus();
        }

        function submitScan() {
            if (!input.value) return;
            scanForm.submit();
        }

        async function startScanner() {
            if (!('BarcodeDetector' in window)) {
                alert('このブラウザはカメラでのQR/バーコード読取に対応していません。USBリーダーなら入力欄にそのまま読み込めます。');
                input.focus();
                return;
            }

            const detector = new BarcodeDetector({ formats: ['qr_code', 'ean_13', 'code_128', 'code_39'] });
            scannerStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            scannerVideo.srcObject = scannerStream;
            cameraBox.hidden = false;
            await scannerVideo.play();

            scannerTimer = setInterval(async () => {
                const codes = await detector.detect(scannerVideo);
                if (codes.length > 0) {
                    input.value = codes[0].rawValue.replace(/[^0-9]/g, '');
                    syncDisplay();
                    stopScanner();
                    submitScan();
                }
            }, 450);
        }

        function stopScanner() {
            if (scannerTimer) clearInterval(scannerTimer);
            scannerTimer = null;
            if (scannerStream) {
                scannerStream.getTracks().forEach(track => track.stop());
            }
            scannerStream = null;
            cameraBox.hidden = true;
        }

        document.querySelectorAll('button').forEach(button => {
            button.addEventListener('pointerdown', () => {
                button.classList.add('pressed');
                setTimeout(() => button.classList.remove('pressed'), 180);
            });
        });

        input.addEventListener('input', () => {
            input.value = input.value.replace(/[^0-9]/g, '');
            syncDisplay();
        });
        syncDisplay();
    </script>
</body>
</html>
