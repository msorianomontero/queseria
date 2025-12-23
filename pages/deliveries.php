<?php
// Handle multi-cheese delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delivery_multi') {
    if (!empty($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            $cheese_id      = $item['cheese_id'] ?? null;
            $units_received = $item['units_received'] ?? null;
            $owed           = $item['owed'] ?? 0;

            if ($cheese_id && $units_received) {
                // Update stock
                $pdo->prepare("UPDATE stock SET units = units + ? WHERE cheese_id = ?")
                    ->execute([$units_received, $cheese_id]);

                // Record delivery line
                $stmt = $pdo->prepare("
                    INSERT INTO deliveries (cheese_id, units_received, owed)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$cheese_id, $units_received, $owed]);
            }
        }
    }
}

// Cheeses for select
$cheeses = $pdo->query("
    SELECT id, name
    FROM cheeses
    WHERE active = 1
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Recent deliveries
$recent_deliveries = $pdo->query("
    SELECT d.*, c.name
    FROM deliveries d
    JOIN cheeses c ON d.cheese_id = c.id
    ORDER BY d.date DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section">
    <h2>🚚 Register Delivery (Multiple Cheeses)</h2>
    <form method="POST" id="deliveryForm" class="form-grid">
        <input type="hidden" name="action" value="delivery_multi">

        <div id="deliveryItems">
            <div class="delivery-item">
                <select name="items[0][cheese_id]" required>
                    <option value="">Cheese...</option>
                    <?php foreach ($cheeses as $cheese): ?>
                        <option value="<?= $cheese['id'] ?>"><?= htmlspecialchars($cheese['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="items[0][units_received]" type="number" placeholder="Units" required>
                <input name="items[0][owed]" type="number" step="1" placeholder="Owed" required>
                <button type="button" onclick="removeItem(this)">❌</button>
            </div>
        </div>

        <button type="button" onclick="addDeliveryItem()">➕ Add Cheese</button>
        <button type="submit">Save Delivery</button>
    </form>
</div>

<div class="section">
    <h2>📦 Recent Deliveries</h2>
    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Cheese</th>
            <th>Units</th>
            <th>Owed</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($recent_deliveries as $delivery): ?>
            <tr>
                <td><?= htmlspecialchars($delivery['date']) ?></td>
                <td><?= htmlspecialchars($delivery['name']) ?></td>
                <td><?= (int)$delivery['units_received'] ?></td>
                <td>$<?= number_format($delivery['owed'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
let itemCounter = 1;

function addDeliveryItem() {
    const container = document.getElementById('deliveryItems');
    const firstSelect = container.querySelector('select[name="items[0][cheese_id]"]');
    const optionsHtml = firstSelect.innerHTML;

    const div = document.createElement('div');
    div.className = 'delivery-item';
    div.innerHTML = `
        <select name="items[${itemCounter}][cheese_id]" required>
            ${optionsHtml}
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
