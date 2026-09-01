<?php
require __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$ids_raw = trim($_GET['ids'] ?? '');
if ($ids_raw === '') {
    echo json_encode(["error" => "no_ids"]);
    exit;
}

$parts = array_filter(array_map('trim', explode(',', $ids_raw)), function($v){ return $v !== ''; });
$ids = [];
foreach ($parts as $p) {
    if (!ctype_digit($p)) continue;
    $ids[] = (int)$p;
}

if (empty($ids)) {
    echo json_encode(["error" => "invalid_ids"]);
    exit;
}

$placeholders = implode(',', $ids);
$sql = "SELECT id, personalization FROM products WHERE id IN ($placeholders) AND active = 1";
$result = $db->query($sql);

$out = [];
while ($row = $result->fetch_assoc()) {
    $out[(int)$row['id']] = $row['personalization'];
}

echo json_encode($out);
exit;

?>
