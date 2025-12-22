<?php
// Handle add / update / delete / toggle active
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

    } elseif ($_POST['action'] === 'toggle_active') {
        // Activate/deactivate cheese
        $cheese_id = (int)$_POST['cheese_id'];
        $new_status = (int)$_POST['new_status']; // 1 or 0

        $stmt = $pdo->prepare("UPDATE cheeses SET active = ? WHERE id = ?");
        $stmt->execute([$new_status, $cheese_id]);

    } elseif ($_POST['action'] === 'delete_cheese') {
        // Hard delete only if not used in deliveries or sales
        $cheese_id = (int)$_POST['cheese_id'];

        // Count references in deliveries and sales
        $stmt = $pdo->prepare("
            SELECT 
              (SELECT COUNT(*) FROM deliveries d WHERE d.cheese_id = ?) AS deliveries_count,
              (SELECT COUNT(*) FROM sales s      WHERE s.cheese_id = ?) AS sales_count
        ");
        $stmt->execute([$cheese_id, $cheese_id]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($counts && ($counts['deliveries_count'] == 0 && $counts['sales_count'] == 0)) {
            // Safe to delete stock row and cheese
            $pdo->prepare("DELETE FROM stock WHERE cheese_id = ?")->execute([$cheese_id]);
            $pdo->prepare("DELETE FROM cheeses WHERE id = ?")->execute([$cheese_id]);
            $delete_error = null;
        } else {
            // Not allowed – keep a simple message in memory (optional)
            $delete_error = "Cannot delete: this cheese has deliveries or sales registered.";
        }
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
        <input name="cost_price" type="number" step="1" placeholder="Cost per unit" required>
        <input name="sell_price" type="number" step="1" placeholder="Sell price" required>
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
        <th>Status</th>
        <th>Actions</th>
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
                    <input type="hidden" name="cheese_id" value="<?= (int)$cheese['id'] ?>">
                    <input type="number" name="cost_price" value="<?= htmlspecialchars($cheese['cost_price']) ?>" step="1" style="width:90px;">
                    /
                    <input type="number" name="sell_price" value="<?= htmlspecialchars($cheese['sell_price']) ?>" step="1" style="width:90px;">
                    <button type="submit" style="padding:0.25rem 0.6rem; font-size:0.8rem;">Update</button>
                </form>
            </td>
            <td>
                <?php if (!isset($cheese['active']) || $cheese['active']): ?>
                    <span class="positive">Active</span>
                <?php else: ?>
                    <span class="negative">Inactive</span>
                <?php endif; ?>
            </td>
            <td style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                <!-- Toggle active / inactive -->
                <form method="POST">
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="cheese_id" value="<?= (int)$cheese['id'] ?>">
                    <input type="hidden" name="new_status" 
                           value="<?= (!isset($cheese['active']) || $cheese['active']) ? 0 : 1 ?>">
                    <button type="submit" style="padding:0.25rem 0.6rem; font-size:0.8rem;">
                        <?= (!isset($cheese['active']) || $cheese['active']) ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>

                <!-- Hard delete (blocked if referenced) -->
                <form method="POST" onsubmit="return confirm('Delete this cheese? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete_cheese">
                    <input type="hidden" name="cheese_id" value="<?= (int)$cheese['id'] ?>">
                    <button type="submit" style="padding:0.25rem 0.6rem; font-size:0.8rem; background:#ef4444;">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
