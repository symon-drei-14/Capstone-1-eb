<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = ($page - 1) * $limit;

// Get total count
$countResult = $conn->query("SELECT COUNT(*) as total FROM login_admin");
$totalRow = $countResult->fetch_assoc();
$total = $totalRow['total'];

// Get paginated data
$query = "SELECT admin_id, username, role FROM login_admin LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$admins = [];
while ($row = $result->fetch_assoc()) {
    $admins[] = $row;
}

echo json_encode([
    "success" => true,
    "admins" => $admins,
    "total" => $total
]);

$stmt->close();
$conn->close();
?>