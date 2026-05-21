<?php
function getPdo()
{
    $dsn = 'mysql:dbname=yse_pos_db;host=localhost;charset=utf8mb4';
    $user = 'root';
    $password = '';

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function ensureAppSchema(PDO $pdo)
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            `key` varchar(50) NOT NULL,
            `value` varchar(255) NOT NULL,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sale_items (
            id int(11) NOT NULL AUTO_INCREMENT,
            sale_id int(11) NOT NULL,
            item_id int(11) NOT NULL,
            item_name varchar(255) NOT NULL,
            unit_price int(11) NOT NULL,
            quantity int(11) NOT NULL,
            discount_rate decimal(5,2) NOT NULL DEFAULT 0,
            discount_amount int(11) NOT NULL DEFAULT 0,
            discount int(11) NOT NULL DEFAULT 0,
            subtotal int(11) NOT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY sale_id (sale_id),
            KEY item_id (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    ensureColumn($pdo, 'sale_items', 'discount_rate', 'decimal(5,2) NOT NULL DEFAULT 0 AFTER quantity');
    ensureColumn($pdo, 'sale_items', 'discount_amount', 'int(11) NOT NULL DEFAULT 0 AFTER discount_rate');

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES ('tax_rate', '10')");
    $stmt->execute();
}

function ensureColumn(PDO $pdo, $table, $column, $definition)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);

    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensureDefaultAdmin(PDO $pdo)
{
    $adminId = 'adm';
    $adminPassword = 'adm26626';
    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT id FROM users WHERE user_id = ?');
    $stmt->execute([$adminId]);
    $adminDbId = $stmt->fetchColumn();

    if ($adminDbId) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, role = 1 WHERE id = ?');
        $stmt->execute([$passwordHash, $adminDbId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (user_id, password_hash, role, created_at) VALUES (?, ?, 1, NOW())');
        $stmt->execute([$adminId, $passwordHash]);
    }

    $stmt = $pdo->prepare('UPDATE users SET role = 0 WHERE role = 1 AND user_id <> ?');
    $stmt->execute([$adminId]);
}

function getTaxRate(PDO $pdo)
{
    ensureAppSchema($pdo);
    $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'tax_rate'");
    $stmt->execute();
    $rate = $stmt->fetchColumn();

    if ($rate === false || !is_numeric($rate)) {
        return 10.0;
    }

    return max(0, min(100, (float)$rate));
}

function setTaxRate(PDO $pdo, $rate)
{
    ensureAppSchema($pdo);
    $rate = max(0, min(100, (float)$rate));
    $stmt = $pdo->prepare("
        INSERT INTO settings (`key`, `value`) VALUES ('tax_rate', ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");
    $stmt->execute([(string)$rate]);
}
