<?php
// Handle add / update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_cheese') {
        $stmt = $pdo->prepare("
            INSERT INTO cheeses (name, cost_price, sell_price)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['cost_price'],
            $_POST['sell_price']
        ]);
        $cheese_id = $pdo->lastInsertId();
        $pdo->prepare("INSERT IGNORE INTO stock (cheese_id, units) VALUES (?, 0)")
            ->execute([$cheese_id]);
    } elseif ($_POST['action'] === 'update_price') {
        $stmt = $pdo->prepare("
            UPDATE cheeses SET cost_price = ?, sell_price = ? WHERE id = ?
        ");
        $stmt->execute([
            $_POST['cost_price'],
            $_POST['sell_price'],
            $_POST['cheese_id']
        ]);
    }
}

// Reload cheeses
$cheeses = $pdo->query("
    SELECT c.*, COALESCE(s.units, 0) AS stock_units
    FROM cheeses c
    LEFT JOIN stock s ON c.id = s.cheese_id
    ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);
?>

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
    <h2>📚 Cheeses & Prices</h2>
    <table>
        <thead>
        <tr>
            <th>Cheese</th>
            <th>Stock</th>
            <th>Cost / Sell</th>
            <th>Update Price</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cheeses as $cheese): ?>
            <tr>
                <td><?= htmlspecialchars($cheese['name']) ?></td>
                <td><?= (int)$cheese['stock_units'] ?> units</td>
                <td>
                    $<?= number_format($cheese['cost_price'], 2) ?>
                    /
                    $<?= number_format($cheese['sell_price'], 2) ?>
                </td>
                <td>
                    <form method="POST" style="display:inline-flex; gap:0.3rem; align-items:center;">
                        <input type="hidden" name="action" value="update_price">
                        <input type="hidden" name="cheese_id" value="<?= $cheese['id'] ?>">
                        <input type="number" name="cost_price" value="<?= $cheese['cost_price'] ?>" step="0.01" style="width:90px;">
                        /
                        <input type="number" name="sell_price" value="<?= $cheese['sell_price'] ?>" step="0.01" style="width:90px;">
                        <button type="submit" style="padding:0.25rem 0.6rem; font-size:0.8rem;">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
