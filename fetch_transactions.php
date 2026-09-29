<?php
include('dbcon.php');
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$transaction_data = [];

if ($project_id > 0) {
    $expenses_query = "SELECT 'Expense' AS type, category, amount, DATE(date) AS trans_date FROM expenses WHERE project_id = $project_id";
    $payments_query = "SELECT 'Payment' AS type, 'Client Down Payment' AS category, amount, DATE(date) AS trans_date FROM payment_clients WHERE project_id = $project_id";
    $combined_query = "($expenses_query) UNION ($payments_query) ORDER BY trans_date DESC";

    $result = mysqli_query($conn, $combined_query);
    while ($row = mysqli_fetch_assoc($result)) {
        $transaction_data[] = $row;
    }
}
?>
  <?php if (!empty($transaction_data)) { ?>
        <?php foreach ($transaction_data as $entry) {
            $is_payment = strtolower($entry['type']) === 'payment';
            $amount_color = $is_payment ? '#28a745' : '#dc3545'; // green or red
        ?>
            <div style="margin-bottom: 10px;">
                <!-- Proper flex layout -->
                <div class="d-flex justify-content-between" style="width: 112%;">
                    <span style="flex: 1;"><?php echo ucwords($entry['category']); ?></span>
                    <span style="font-weight: bold; color: <?php echo $amount_color; ?>;">
                        ₱<?php echo number_format($entry['amount'], 2); ?>
                    </span>
                </div>
                <small class="text-muted d-block"><?php echo date("M d, Y", strtotime($entry['trans_date'])); ?></small>
            </div>
        <?php } ?>
    <?php } else { ?>
        <p class="text-muted">No transactions yet for this project.</p>
    <?php } ?>
