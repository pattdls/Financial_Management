<?php
if (isset($_GET['allocated']) && isset($_GET['materials']) && isset($_GET['labor']) && isset($_GET['other'])) {
    $allocated = floatval($_GET['allocated']);
    $materials = floatval($_GET['materials']);
    $labor = floatval($_GET['labor']);
    $other = floatval($_GET['other']);

    $total = $materials + $labor + $other;

    $response = [
        'isUnderBudget' => $total <= $allocated
    ];

    echo json_encode($response);
}
?>
