
<?php
session_start();
// セッションの初期化
$amount = $_SESSION['amount'] ?? '';
$quantity = $_SESSION['quantity'] ?? 1;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YSEレジ System</title>
    <style>
        :root { --bg-color: #f0f2f5; --display-bg: #1a1a1a; --key-bg: #ffffff; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg-color); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .pos-container { background: #333; padding: 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 320px; }
        
        /* ディスプレイ部分 */
        .display-area { background: var(--display-bg); color: #00ff41; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: right; border: 4px solid #444; }
        .label { font-size: 12px; color: #888; display: block; }
        .main-value { font-size: 32px; font-family: 'Courier New', Courier, monospace; min-height: 38px; letter-spacing: 2px; }

        /* キーパッド部分 */
        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        button { 
            border: none; padding: 20px; font-size: 20px; font-weight: bold; border-radius: 8px; cursor: pointer;
            background: var(--key-bg); transition: transform 0.1s, background 0.2s; box-shadow: 0 4px #bbb;
        }
        button:active { transform: translateY(3px); box-shadow: 0 1px #888; }
        .btn-num { color: #333; }
        .btn-op { background: #ff9800; color: white; box-shadow: 0 4px #e68a00; }
        .btn-ac { background: #f44336; color: white; box-shadow: 0 4px #d32f2f; grid-column: span 1; }
        .btn-calc { background: #4caf50; color: white; box-shadow: 0 4px #388e3c; grid-column: span 2; }
        
        .quantity-control { margin-top: 15px; display: flex; align-items: center; background: #444; padding: 10px; border-radius: 8px; color: white; }
        input[type="number"] { width: 60px; margin-left: 10px; border-radius: 4px; border: none; padding: 5px; font-size: 16px; }
    </style>
</head>
<body>

<div class="pos-container">
    <div class="display-area">
        <span class="label">TOTAL AMOUNT (MAX 9 DIGITS)</span>
        <div class="main-value" id="disp">0</div>
    </div>

    <form id="pos-form" method="POST" action="process.php">
        <div class="keypad">
            <?php for ($i = 1; $i <= 9; $i++): ?>
                <button type="button" class="btn-num" onclick="addNum(<?= $i ?>)"><?= $i ?></button>
            <?php endfor; ?>
            
            <button type="button" class="btn-ac" onclick="clearAll()">AC</button>
            <button type="button" class="btn-num" onclick="addNum(0)">0</button>
            <button type="submit" name="action" value="calc" class="btn-calc">ENTER / 計算</button>
        </div>

        <div class="quantity-control">
            <span>個数:</span>
            <input type="number" name="quantity" id="qty" value="<?= $quantity ?>" min="1">
        </div>
        
        <input type="hidden" name="amount" id="hidden_amount" value="<?= $amount ?>">
    </form>
</div>

<script>
    const disp = document.getElementById('disp');
    const hiddenAmount = document.getElementById('hidden_amount');
    let currentVal = "<?= $amount ?>";

    function updateDisplay() {
        // 9桁パディングのロジック
        let text = currentVal === '' ? '0' : currentVal;
        disp.innerText = text.padStart(9, ' ');
        hiddenAmount.value = currentVal;
    }

    function addNum(num) {
        if (currentVal.length < 9) {
            currentVal += num;
            updateDisplay();
        }
    }

    function clearAll() {
        currentVal = '';
        updateDisplay();
    }

    // 初期表示
    updateDisplay();
</script>

</body>
</html>
