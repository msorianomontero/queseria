<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'deliverymulti') {
        $origin = $_POST['origin'] ?? '';
        if (!empty($_POST['items']) && is_array($_POST['items']) && !empty($origin)) {
            // Create master delivery record
            $stmt = $pdo->prepare("INSERT INTO deliveries (origin) VALUES (?)");
            $stmt->execute([$origin]);
            $deliveryid = $pdo->lastInsertId();
            
            foreach ($_POST['items'] as $item) {
                $cheeseid = $item['cheeseid'] ?? null;
                $unitsreceived = $item['unitsreceived'] ?? null;
                $owed = $item['owed'] ?? 0;
                if ($cheeseid && $unitsreceived) {
                    // Update stock
                    $stmt = $pdo->prepare("UPDATE stock SET units = units + ? WHERE cheeseid = ?");
                    $stmt->execute([$unitsreceived, $cheeseid]);
                    // Record detail line
                    $stmt = $pdo->prepare("INSERT INTO del_detail (deliveryid, cheeseid, unitsreceived, owed) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$deliveryid, $cheeseid, $unitsreceived, $owed]);
                }
            }
        }
    } elseif ($_POST['action'] === 'deletedelivery') {
        $deliveryid = (int)($_POST['deliveryid'] ?? 0);
        if ($deliveryid > 0) {
            // Get detail lines for reversal
            $stmt = $pdo->prepare("SELECT cheeseid, unitsreceived FROM del_detail WHERE deliveryid = ?");
            $stmt->execute([$deliveryid]);
            while ($detail = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Revert stock
                $stmt = $pdo->prepare("UPDATE stock SET units = units - ? WHERE cheeseid = ?");
                $stmt->execute([$detail['unitsreceived'], $detail['cheeseid']]);
            }
            // Delete cascades to details
            $stmt = $pdo->prepare("DELETE FROM deliveries WHERE id = ?");
            $stmt->execute([$deliveryid]);
        }
    }
}

// Cheeses for select
$cheeses = $pdo->query("SELECT id, name FROM cheeses WHERE active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Recent deliveries (aggregated)
$recentdeliveries = $pdo->query("
    SELECT d.id, d.origin, d.date, 
           GROUP_CONCAT(c.name SEPARATOR ', ') as cheeses, 
           SUM(dd.unitsreceived) as totalunits, 
           SUM(dd.owed) as totalowed 
    FROM deliveries d 
    JOIN del_detail dd ON d.id = dd.deliveryid 
    JOIN cheeses c ON dd.cheeseid = c.id 
    GROUP BY d.id 
    ORDER BY d.date DESC LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>



<div class="section">
    <h2> 🚚 Register Delivery (Multiple Cheeses)</h2>
    <form method="POST" id="deliveryForm" class="form-grid">
        <input type="hidden" name="action" value="delivery_multi">
        <input type="text" name="origin" placeholder="Supplier/Origin" required style="grid-column: 1 / -1;">
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
        <thead><tr><th>Date</th><th>Origin</th><th>Cheeses</th><th>Total Units</th><th>Total Owed</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($recentdeliveries as $delivery): ?>
            <tr>
                <td><?php echo htmlspecialchars($delivery['date']); ?></td>
                <td><?php echo htmlspecialchars($delivery['origin']); ?></td>
                <td><?php echo htmlspecialchars($delivery['cheeses']); ?></td>
                <td><?php echo (int)$delivery['totalunits']; ?></td>
                <td><?php echo number_format($delivery['totalowed'], 2); ?></td>
                <td>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this delivery? This will subtract units from stock.');">
                        <input type="hidden" name="action" value="deletedelivery">
                        <input type="hidden" name="deliveryid" value="<?php echo $delivery['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
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
