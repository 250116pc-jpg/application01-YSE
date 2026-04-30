<?php
session_start();
$amount = $_SESSION['amount'] ?? '';
$_SESSION['amount'] = "";
//追加↑空箱に
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>YSEレジ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="pos-machine">
    <h1>YSEレジ</h1>
    
    <div class="screen">
        <div class="op-indicator" id="op-ind"></div>
        <div id="disp">0</div>
    </div>

    <form id="pos-form" method="POST" action="process.php">
        <input type="hidden" name="amount" id="hidden_amount" value="<?= htmlspecialchars($amount) ?>">
        <input type="hidden" name="quantity" value="1">
        <input type="hidden" name="action" id="hidden_action" value="">
        
        <div class="keypad">
            <button type="button" class="span-2" onclick="clearAll()">AC</button>
            <button type="button" class="span-2" onclick="applyTax()">税込み</button>
            
            <button type="button" onclick="addNum('7')">7</button>
            <button type="button" onclick="addNum('8')">8</button>
            <button type="button" onclick="addNum('9')">9</button>
            <button type="button" onclick="handleOp('*')">×</button>
            
            <button type="button" onclick="addNum('4')">4</button>
            <button type="button" onclick="addNum('5')">5</button>
            <button type="button" onclick="addNum('6')">6</button>
            <button type="button" onclick="handleOp('+')">+</button>
            
            <button type="button" onclick="addNum('1')">1</button>
            <button type="button" onclick="addNum('2')">2</button>
            <button type="button" onclick="addNum('3')">3</button>
            <button type="button" class="btn-equal" onclick="pressEqual()">＝</button>
            
            <button type="button" onclick="addNum('0')">0</button>
            <button type="button" onclick="submitAction('calc')">売上</button>
            <button type="button" onclick="submitAction('keijo')">計上</button>
        </div>
    </form>
</div>

<script>
    const disp = document.getElementById('disp');
    const opInd = document.getElementById('op-ind');
    const hiddenInput = document.getElementById('hidden_amount');
    const hiddenAction = document.getElementById('hidden_action');
    const form = document.getElementById('pos-form');
    
    let currentVal = "";
    let runningTotal = <?= $amount ?: 0 ?>;
    let formulaParts = [];
    let isTaxApplied = false;
    let preTaxTotal = 0; // ★追加：税抜きの金額を一時的に記憶する変数

    function updateDisplay(val) {
        let num = parseInt(val);
        if (isNaN(num)) num = 0;
        disp.innerText = num;
        hiddenInput.value = num;
    }

    function addNum(n) {
        if (isTaxApplied) return;
        if (currentVal.length < 9) {
            if (currentVal === "" && n === "0") return;
            currentVal += n;
            updateDisplay(currentVal);
        }
    }

    function clearAll() {
        currentVal = "";
        runningTotal = 0;
        formulaParts = [];
        isTaxApplied = false;
        preTaxTotal = 0; // ★追加
        opInd.innerText = "";
        updateDisplay(0);
    }

    function pushCurrentItem() {
        let price = parseInt(currentVal);
        if (!isNaN(price)) {
            if (formulaParts.length === 0) {
                runningTotal = price;
            } else {
                let lastOp = formulaParts[formulaParts.length - 1];
                if (lastOp === "+") runningTotal += price;
                if (lastOp === "*") runningTotal *= price;
            }
            formulaParts.push(price);
        }
        currentVal = "";
    }

    function handleOp(op) {
        if (currentVal !== "") {
            pushCurrentItem();
        } else if (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length-1];
            if (last === "+" || last === "*") formulaParts.pop();
        } else {
            return;
        }
        formulaParts.push(op);
        opInd.innerText = op === '*' ? '×' : op;
        updateDisplay(runningTotal);
    }

    function pressEqual() {
        if (currentVal !== "") {
            pushCurrentItem();
        }
        if (formulaParts.length > 0) {
            let last = formulaParts[formulaParts.length-1];
            if (last === "+" || last === "*") formulaParts.pop();
        }
        opInd.innerText = "";
        updateDisplay(runningTotal);
    }

    // ★変更：税込みのオン・オフを切り替えられるようにしました
    function applyTax() {
        if (currentVal !== "") pressEqual();
        
        if (!isTaxApplied && runningTotal > 0) {
            preTaxTotal = runningTotal; // 元の金額を記憶
            runningTotal = Math.round(runningTotal * 1.1);
            //floorからroundへ
            isTaxApplied = true;
            opInd.innerText = "税込";
            updateDisplay(runningTotal);
        } else if (isTaxApplied) {
            // すでに税込みなら、記憶しておいた元の金額に戻す
            runningTotal = preTaxTotal;
            isTaxApplied = false;
            opInd.innerText = "";
            updateDisplay(runningTotal);
        }
    }

    function submitAction(actionStr) {
        pressEqual(); 
        hiddenAction.value = actionStr;
        form.submit();
    }

    // 初期表示
    updateDisplay(runningTotal);
</script>

</body>
</html>
