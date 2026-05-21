<?php
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
        $subtotal += lineSubtotal($line);
    }
    return $subtotal;
}

function lineDiscountRate(array $line)
{
    return max(0, min(100, (float)($line['discount_rate'] ?? 0)));
}

function lineDiscountAmount(array $line)
{
    return max(0, (int)($line['discount_amount'] ?? ($line['discount'] ?? 0)));
}

function lineDiscountTotal(array $line)
{
    $base = (int)$line['price'] * (int)$line['quantity'];
    $rateDiscount = (int)floor($base * (lineDiscountRate($line) / 100));
    return min($base, $rateDiscount + lineDiscountAmount($line));
}

function lineSubtotal(array $line)
{
    $base = (int)$line['price'] * (int)$line['quantity'];
    return max(0, $base - lineDiscountTotal($line));
}

function cartCount(array $cart)
{
    $count = 0;
    foreach ($cart as $line) {
        $count += (int)$line['quantity'];
    }
    return $count;
}
?>