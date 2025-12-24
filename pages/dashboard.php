<?php
// Fetch cheeses with stock
$cheeses = $pdo->query("
    SELECT c.*, COALESCE(s.units, 0) AS stock_units,
           c.cost_price * COALESCE(s.units, 0) AS stock_value
    FROM cheeses c
    LEFT JOIN stock s ON c.id = s.cheese_id
    ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);

// Recent deliveries and sales
$recent_deliveries = $pdo->query("
    SELECT d.id, d.origin, d.date,
           GROUP_CONCAT(c.name SEPARATOR ', ') as cheese_names,
           SUM(dd.units_received) as total_units,
           SUM(dd.owed - dd.paid) as balance_owed
    FROM deliveries d 
    JOIN del_detail dd ON d.id = dd.delivery_id 
    JOIN cheeses c ON dd.cheese_id = c.id 
    GROUP BY d.id 
    ORDER BY d.date DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$recent_sales = $pdo->query("
    SELECT s.*, c.name
    FROM sales s
    JOIN cheeses c ON s.cheese_id = c.id
    ORDER BY s.date DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section">
    <h2>📦 Current Stock</h2>
    <table>
        <thead>
        <tr>
            <th>Cheese</th>
            <th>Units</th>
            <th>Value (Cost)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cheeses as $cheese): ?>
            <tr>
                <td><strong><?= htmlspecialchars($cheese['name']) ?></strong></td>
                <td class="<?= $cheese['stock_units'] < 5 ? 'negative' : 'positive' ?>">
                    <?= (int)$cheese['stock_units'] ?> units
                </td>
                <td>$<?= number_format($cheese['stock_value'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>📋 Recent Activity</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <h3>Latest Deliveries</h3>
            <?php foreach ($recent_deliveries as $delivery): ?>
                <div style="padding: 0.5rem; background: #f8fafc; margin-bottom: 0.25rem; border-radius: 6px;">
                    <strong><?php echo htmlspecialchars($delivery['origin']); ?></strong> 
                    <?php echo (int)$delivery['total_units']; ?> units 
                    (<?php echo htmlspecialchars($delivery['cheese_names']); ?>)
                    <br>Balance: <?php echo number_format($delivery['balance_owed'], 2); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div>
            <h3>Latest Sales</h3>
            <?php foreach ($recent_sales as $sale): ?>
                <div style="padding: 0.5rem; background: #f8fafc; margin-bottom: 0.25rem; border-radius: 6px;">
                    <?= htmlspecialchars($sale['customer_name']) ?> –
                    <?= htmlspecialchars($sale['name']) ?>:
                    -<?= (int)$sale['units_sold'] ?> units
                    ($<?= number_format($sale['total'], 2) ?>)
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
