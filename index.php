<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheese Stock Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1200px; margin: 0 auto; padding: 1rem; background: #f8fafc; }
        .section { background: white; margin: 1rem 0; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h2 { color: #1e293b; margin-bottom: 1rem; font-size: 1.3rem; }
        
        /* Make form sections stack nicely on smaller screens */
        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Delivery item: stack fields on narrow screens */
        .delivery-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 0.5rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
                
        input, select, button { padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        button { background: #3b82f6; color: white; border: none; cursor: pointer; font-weight: 600; }
        button:hover { background: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .positive { color: #059669; font-weight: 600; }
        .negative { color: #dc2626; }
        .delivery-item { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.5rem; padding: 0.75rem; background: #f8fafc; border-radius: 8px; margin-bottom: 0.5rem; }

        @media (max-width: 900px) {
            .delivery-item {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php

    // Replace these 3 values with your hosting panel info
    $host = 'mysql.hostinger.com';  // or ip like 'mysql.hostinger.com'
    $dbname = 'u585809268_cheese_db';  // usually username_dbname format
    $username = 'u585809268_root';    // your MySQL username
    $password = 'Queseria2025.1';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_TIMEOUT => 10,  // 10s timeout
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } 
    catch(PDOException $e) {
        die("DB Connection failed: " . $e->getMessage() . 
            "<br>Check cPanel → MySQL Databases for correct credentials");
    }
    //$pdo = new PDO('mysql:host=mysql.hostinger.com;dbname=u585809268_cheese_db', 'u585809268_root', 'Queseria2025.1');
    //$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cheeses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            cost_price DECIMAL(10,2) NOT NULL,
            sell_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stock (
            cheese_id INT PRIMARY KEY,
            units INT NOT NULL DEFAULT 0,
            FOREIGN KEY (cheese_id) REFERENCES cheeses(id)
        )");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deliveries (
            id INT PRIMARY KEY AUTO_INCREMENT,
            cheese_id INT NOT NULL,
            units_received INT NOT NULL,
            owed DECIMAL(10,2) NOT NULL,
            paid DECIMAL(10,2) DEFAULT 0,
            date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cheese_id) REFERENCES cheeses(id)
        )");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sales (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_name VARCHAR(100) NOT NULL,
            cheese_id INT NOT NULL,
            units_sold INT NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cheese_id) REFERENCES cheeses(id)
        )");

    // Handle form submissions
    if ($_POST) {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_cheese':
                    $stmt = $pdo->prepare("INSERT INTO cheeses (name, cost_price, sell_price) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['cost_price'], $_POST['sell_price']]);
                    $cheese_id = $pdo->lastInsertId();
                    $pdo->prepare("INSERT IGNORE INTO stock (cheese_id, units) VALUES (?, 0)")->execute([$cheese_id]);
                    break;
                
                case 'update_price':
                    $stmt = $pdo->prepare("UPDATE cheeses SET cost_price = ?, sell_price = ? WHERE id = ?");
                    $stmt->execute([$_POST['cost_price'], $_POST['sell_price'], $_POST['cheese_id']]);
                    break;
                
                case 'delivery':
                    $pdo->prepare("UPDATE stock SET units = units + ? WHERE cheese_id = ?")
                        ->execute([$_POST['units_received'], $_POST['cheese_id']]);
                    $stmt = $pdo->prepare("INSERT INTO deliveries (cheese_id, units_received, owed) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['cheese_id'], $_POST['units_received'], $_POST['owed']]);
                    break;
                
                case 'sale':
                    $stmt = $pdo->prepare("SELECT sell_price FROM cheeses WHERE id = ?");
                    $stmt->execute([$_POST['cheese_id']]);
                    $sell_price = $stmt->fetchColumn();
                    
                    $total = $_POST['units_sold'] * $sell_price;
                    $pdo->prepare("UPDATE stock SET units = units - ? WHERE cheese_id = ?")
                        ->execute([$_POST['units_sold'], $_POST['cheese_id']]);
                    
                    $stmt = $pdo->prepare("INSERT INTO sales (customer_name, cheese_id, units_sold, total) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_POST['customer_name'], $_POST['cheese_id'], $_POST['units_sold'], $total]);
                    break;
                case 'delivery_multi':
                    foreach ($_POST['items'] as $item) {
                        if ($item['cheese_id'] && $item['units_received']) {
                            // Update stock
                            $pdo->prepare("UPDATE stock SET units = units + ? WHERE cheese_id = ?")
                                ->execute([$item['units_received'], $item['cheese_id']]);
                            
                            // Record delivery line
                            $stmt = $pdo->prepare("INSERT INTO deliveries (cheese_id, units_received, owed) VALUES (?, ?, ?)");
                            $stmt->execute([$item['cheese_id'], $item['units_received'], $item['owed']]);
                        }
                    }
                    break;
            }
        }
    }

    // Get data for display
    $cheeses = $pdo->query("
        SELECT c.*, COALESCE(s.units, 0) as stock_units, 
               c.cost_price * COALESCE(s.units, 0) as stock_value
        FROM cheeses c LEFT JOIN stock s ON c.id = s.cheese_id
        ORDER BY c.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recent_deliveries = $pdo->query("
        SELECT d.*, c.name 
        FROM deliveries d JOIN cheeses c ON d.cheese_id = c.id 
        ORDER BY d.date DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    $recent_sales = $pdo->query("
        SELECT s.*, c.name 
        FROM sales s JOIN cheeses c ON s.cheese_id = c.id 
        ORDER BY s.date DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="section">
        <h2>📦 Current Stock</h2>
        <table>
            <thead><tr><th>Cheese</th><th>Units</th><th>Value (Cost)</th><th>Update Price</th></tr></thead>
            <tbody>
                <?php foreach ($cheeses as $cheese): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($cheese['name']) ?></strong></td>
                    <td class="<?= $cheese['stock_units'] < 5 ? 'negative' : 'positive' ?>">
                        <?= $cheese['stock_units'] ?> units
                    </td>
                    <td>$<?= number_format($cheese['stock_value'], 2) ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_price">
                            <input type="hidden" name="cheese_id" value="<?= $cheese['id'] ?>">
                            <input type="number" name="cost_price" value="<?= $cheese['cost_price'] ?>" step="0.01" size="6">
                            /
                            <input type="number" name="sell_price" value="<?= $cheese['sell_price'] ?>" step="0.01" size="6">
                            <button type="submit" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>🧀 Add New Cheese Type</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="action" value="add_cheese">
            <input name="name" placeholder="Cheese name" required>
            <input name="cost_price" type="number" step="0.01" placeholder="Cost per unit" required>
            <input name="sell_price" type="number" step="0.01" placeholder="Sell price" required>
            <button type="submit">Add Cheese</button>
        </form>
    </div>

    <div class="section">
        <h2>🚚 Register Delivery (Multiple Cheeses)</h2>
        <form method="POST" id="deliveryForm" class="form-grid">
            <input type="hidden" name="action" value="delivery_multi">
            
            <div id="deliveryItems">
                <div class="delivery-item">
                    <select name="items[0][cheese_id]" required>
                        <option value="">Cheese 1...</option>
                        <?php foreach ($cheeses as $cheese): ?>
                        <option value="<?= $cheese['id'] ?>"><?= htmlspecialchars($cheese['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input name="items[0][units_received]" type="number" placeholder="Units" required>
                    <input name="items[0][owed]" type="number" step="0.01" placeholder="Owed" required>
                    <button type="button" onclick="removeItem(this)">❌</button>
                </div>
            </div>
            
            <button type="button" onclick="addDeliveryItem()">➕ Add Cheese</button>
            <button type="submit">Save Delivery</button>
        </form>
    </div>

    <div class="section">
        <h2>💰 Register Sale</h2>
        <form method="POST" class="form-grid">
            <input type="hidden" name="action" value="sale">
            <input name="customer_name" placeholder="Customer name" required>
            <select name="cheese_id" required>
                <option value="">Select cheese...</option>
                <?php foreach ($cheeses as $cheese): ?>
                <option value="<?= $cheese['id'] ?>"><?= htmlspecialchars($cheese['name']) ?> ($<?= $cheese['sell_price'] ?>/unit)</option>
                <?php endforeach; ?>
            </select>
            <input name="units_sold" type="number" placeholder="Units sold" required>
            <button type="submit">Record Sale</button>
        </form>
    </div>

    <div class="section">
        <h2>📋 Recent Activity</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h3 style="margin-bottom: 0.5rem;">Latest Deliveries</h3>
                <?php foreach (array_slice($recent_deliveries, 0, 5) as $delivery): ?>
                <div style="padding: 0.5rem; background: #f8fafc; margin-bottom: 0.25rem; border-radius: 6px;">
                    <?= htmlspecialchars($delivery['name']) ?>: +<?= $delivery['units_received'] ?> units (owed: $<?= number_format($delivery['owed'], 2) ?>)
                </div>
                <?php endforeach; ?>
            </div>
            <div>
                <h3 style="margin-bottom: 0.5rem;">Latest Sales</h3>
                <?php foreach (array_slice($recent_sales, 0, 5) as $sale): ?>
                <div style="padding: 0.5rem; background: #f8fafc; margin-bottom: 0.25rem; border-radius: 6px;">
                    <?= htmlspecialchars($sale['name']) ?>: -<?= $sale['units_sold'] ?> units ($<?= number_format($sale['total'], 2) ?>)
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh every 30s for live stock updates
        setInterval(() => location.reload(), 30000);

        let itemCounter = 1;
        function addDeliveryItem() {
            const container = document.getElementById('deliveryItems');
            const div = document.createElement('div');
            div.className = 'delivery-item';
            div.innerHTML = `
                <select name="items[${itemCounter}][cheese_id]" required>
                    <option value="">Cheese...</option>
                    ${document.querySelector('select[name="items[0][cheese_id]"]').innerHTML}
                </select>
                <input name="items[${itemCounter}][units_received]" type="number" placeholder="Units" required>
                <input name="items[${itemCounter}][owed]" type="number" step="0.01" placeholder="Owed" required>
                <button type="button" onclick="removeItem(this)">❌</button>
            `;
            container.appendChild(div);
            itemCounter++;
        }

        function removeItem(btn) {
            btn.closest('.delivery-item').remove();
        }

    </script>
</body>
</html>
