<?php
session_start();
$amount = $_SESSION['amount'] ?? '';
$quantity = $_SESSION['quantity'] ?? 1;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>YSE POS System v4.2 - Fixed</title>
    <style>
        body { background: #34495e; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Helvetica', sans-serif; }
        
        .pos-machine { 
            background: #dcdde1; padding: 30px; border-radius: 15px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            display: flex; gap: 30px; align-items: flex-start;
            border-bottom: 10px solid #7f8c8d;
        }

        .display-section { width: 350px; }
        
        .screen { 
            background: #2f3640; color: #00ecff; padding: 15px 20px; 
            border-radius: 8px; border: 5px solid #1e272e;
            box-shadow: inset 0 0 15px #000;
            margin-bottom: 15px;
            text-align: right;
        }
        
        .formula-display { 
            font-size: 14px; color: #7f8c8d; font-family: 'Courier New', monospace;
            min-height: 1.2em; margin-bottom: 5px; word-break: break-all;
        }
        .screen-label { font-size: 12px; color: #718093; margin-bottom: 5px; font-weight: bold; text-align: left; }
        .main-display { 
            font-size: 48px; font-family: 'Courier New', monospace; 
            min-height: 55px; letter-spacing: 2px;
        }

        .control-section { width: 240px; }

        .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        button { 
            height: 50px; border: none; border-radius: 6px; font-size: 18px; font-weight: bold;
            cursor: pointer; background: #f5f6fa; box-shadow: 0 4px #bdc3c7; color: #2f3640;
        }
        button:active { transform: translateY(2px); box-shadow: 0 2px #95a5a6; }
        
        .btn-ac { background: #e84118; color: white; box-shadow: 0 4px #c23616; }
        .btn-c { background: #f39c12; color: white; box-shadow: 0 4px #d68910; }
        .btn-op { background: #3498db; color: white; box-shadow: 0 4px #2980b9; } 
        .btn-tax { background: #9b59b6; color: white; box-shadow: 0 4px #8e44ad; }
        .btn-equal { background: #7f8c8d; color: white; box-shadow: 0 4px #636e72; }
        .btn-enter { background: #27ae60; color: white; box-shadow: 0 4px #219150; grid-column: span 3; margin-top: 5px; }
        
        .quantity-box { 
            margin-top: 20px; padding: 15px; background: #f5f6fa; 
            border-radius: 8px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        input[type="number"] { width: 60px; font-size: 20px; text-align: center; border: 2px solid #dcdde1; border-radius: 4px; }
    </style>
</head>
<body>

<div class="pos-machine">
    <div class="display-section">
        <h2 style="margin-top: -20px; margin-bottom: 40px; color: #2f3640; font-size: 50px;">YSEレジ</h2>
        
        <div class="screen">
            <div class="screen-label">TOTAL</div>
            <div class="formula-display" id="formula-disp"></div>
            <div class="main-display" id="disp">0</div>
        </div>
        <div class="quantity-box">
            <span style="font-weight: bold; color: #2f3640;">数量:</span>
            <input type="number" id="qty-input" form="pos-form" name="quantity" value="<?= $quantity ?>" min="1">
        </div>
    </div>

    <div class="control-section">
        <form id="pos-form" method="POST" action="process.php" onsubmit="return finalize()">
            <div class="keypad">
                <button type="button" onclick="addNum('7')">7</button>
                <button type="button" onclick="addNum('8')">8</button>
                <button type="button" onclick="addNum('9')">9</button>
                
                <button type="button" onclick="addNum('4')">4</button>
                <button type="button" onclick="addNum('5')">5</button>
                <button type="button" onclick="addNum('6')">6</button>
                
                <button type="button" onclick="addNum('1')">1</button>
                <button type="button" onclick="addNum('2')">2</button>
                <button type="button" onclick="addNum('3')">3</button>
                
                <button type="button" onclick="addNum('0')">0</button>
                <button type="button" class="btn-c" onclick="clearLast()">C</button>
                <button type="button" class="btn-ac" onclick="clearAll()">AC</button>
                
                <button type="button" class="btn-op" onclick="handleOp('*')">×</button>
                <button type="button" class="btn-op" onclick="handleOp('+')">+</button>
                <button type="button" class="btn-tax" onclick="applyTax()">税込</button>
                
                <button type="button" class="btn-equal" style="grid-column: span 3;" onclick="pressEqual()">＝</button>
                
                <button type="submit" class="btn-enter">確定 (SEND)</button>
            </div>
            <input type="hidden" name="amount" id="hidden_amount" value="<?= $amount ?>">
        </form>
    </div>
</div>

<script>
    const disp = document.getElementById('disp');
    const formulaDisp = document.getElementById('formula-disp');
    const hiddenInput = document.getElementById('hidden_amount');
    const quantityInput = document.getElementById('qty-input');
    
    let currentVal = "";      
    let runningTotal = 0;    
    let formulaParts = [];   
    let isTaxApplied = false;

    function updateDisplay(val) {
        let num = (typeof val === 'number') ? val : parseInt(val);
        if (isNaN(num)) num = 0;
        disp.innerText = num.toLocaleString();
        hiddenInput.value = num;
        
        formulaDisp.innerText = formulaParts.join(" ").replace(/\*/g, "×");
    }

    function addNum(n) {
        if (isTaxApplied) return; 
        if (currentVal.length < 9) {
            if (currentVal === "" && n === "0") return;
            currentVal += n;
            updateDisplay(currentVal);
        }
    }

    function clearLast() {
        if (currentVal.length > 0) {
            currentVal = currentVal.slice(0, -1);
            updateDisplay(currentVal === "" ? runningTotal : currentVal);
        }
    }

    function clearAll() {
        currentVal = "";
        runningTotal = 0;
        formulaParts = [];
        isTaxApplied = false;
        quantityInput.value = 1;
        updateDisplay(0);
    }

    function pushCurrentItem() {
        let price = parseInt(currentVal);
        let qty = parseInt(quantityInput.value) || 1;
        
        if (!isNaN(price)) {
            let subtotal = price * qty;
            
            if (formulaParts.length === 0) {
                runningTotal = subtotal;
            } else {
                let lastOp = formulaParts[formulaParts.length - 1];
                if (lastOp === "+") runningTotal += subtotal;
                if (lastOp === "*") runningTotal *= subtotal;
            }

            let part = (qty > 1) ? `${price}×${qty}` : `${price}`;
            formulaParts.push(part);
        }
        currentVal = "";
        quantityInput.value = 1;
    }

    function handleOp(op) {
        if (isTaxApplied) return;
        if (currentVal !== "") {
            pushCurrentItem();
        } else if (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length-1];
            if (last === "+" || last === "*") formulaParts.pop();
        } else {
            return; 
        }
        
        formulaParts.push(op);
        updateDisplay(runningTotal);
    }

    function pressEqual() {
        if (currentVal !== "") {
            pushCurrentItem();
        }
        // 末尾の演算子を掃除
        while (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length-1];
            if (last === "+" || last === "*") {
                formulaParts.pop();
            } else {
                break;
            }
        }
        updateDisplay(runningTotal);
    }

    function applyTax() {
        // 入力中の数値があれば確定させる
        if (currentVal !== "") {
            pushCurrentItem();
        }
        
        // 末尾が演算子なら削除（100 * [税込] などのフリーズ防止）
        while (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length - 1];
            if (last === "+" || last === "*") {
                formulaParts.pop();
            } else {
                break;
            }
        }

        if (!isTaxApplied && runningTotal > 0) {
            runningTotal = Math.floor(runningTotal * 1.1);
            isTaxApplied = true;
            // 式全体をカッコで括って税表示を追加
            if (formulaParts.length > 0) {
                let baseFormula = formulaParts.join(" ");
                formulaParts = [`(${baseFormula})`, "×1.1(税)"];
            }
            updateDisplay(runningTotal);
        }
    }

    function finalize() {
        pressEqual(); 
        return true; 
    }

    updateDisplay(runningTotal);
</script>

</body>
</html>
