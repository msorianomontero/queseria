<?php
// Handle sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'sale'){
        
        $cheese_id     = $_POST['cheese_id'];
        $customer_name = $_POST['customer_name'];
        $units_sold    = (int)$_POST['units_sold'];

        // Get current sell price
        $stmt = $pdo->prepare("SELECT sell_price FROM cheeses WHERE id = ?");
        $stmt->execute([$cheese_id]);
        $sell_price = (float)$stmt->fetchColumn();

        $total = $units_sold * $sell_price;

        // Update stock
        $pdo->prepare("UPDATE stock SET units = units - ? WHERE cheese_id = ?")
            ->execute([$units_sold, $cheese_id]);

        // Record sale
        $stmt = $pdo->prepare("
            INSERT INTO sales (customer_name, cheese_id, units_sold, total)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$customer_name, $cheese_id, $units_sold, $total]);

    } elseif ($_POST['action'] === 'delete' && isset($_POST['sale_id'])) {
        $sale_id = (int)$_POST['sale_id'];
        // Fetch sale details to reverse stock
        $stmt = $pdo->prepare("SELECT cheese_id, units_sold FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sale) {
            // Restore stock
            $pdo->prepare("UPDATE stock SET units = units + ? WHERE cheese_id = ?")
                ->execute([$sale['units_sold'], $sale['cheese_id']]);
            // Soft delete (preferred) - add deleted=1 column to sales table first
            //$pdo->prepare("UPDATE sales SET deleted = 1 WHERE id = ?")->execute([$sale_id]);
            // Or hard delete: 
            $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sale_id]);
        }
        header('Location: sales.php');
        exit;
    }   
}


// Cheeses for select
$cheeses = $pdo->query("
    SELECT id, name, sell_price 
    FROM cheeses 
    WHERE active = 1
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Recent sales
$recent_sales = $pdo->query("
    SELECT s.*, c.name
    FROM sales s
    JOIN cheeses c ON s.cheese_id = c.id
    ORDER BY s.date DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section">
    <h2>💰 Register Sale</h2>
    <form method="POST" class="form-grid">
        <input type="hidden" name="action" value="sale">
        <input name="customer_name" placeholder="Customer name" required>
        <select name="cheese_id" required>
            <option value="">Select cheese...</option>
            <?php foreach ($cheeses as $cheese): ?>
                <option value="<?= $cheese['id'] ?>">
                    <?= htmlspecialchars($cheese['name']) ?> ($<?= number_format($cheese['sell_price'], 2) ?>/unit)
                </option>
            <?php endforeach; ?>
        </select>
        <input name="units_sold" type="number" placeholder="Units sold" required>
        <button type="submit">Record Sale</button>
    </form>
</div>

<div class="section">
    <h2>🧾 Recent Sales</h2>
    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Customer</th>
            <th>Cheese</th>
            <th>Units</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_sales as $sale): ?>
            <tr>
                <td><?= htmlspecialchars($sale['date']) ?></td>
                <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                <td><?= htmlspecialchars($sale['name']) ?></td>
                <td><?= (int)$sale['units_sold'] ?></td>
                <td>$<?= number_format($sale['total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
