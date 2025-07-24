<?php
header("Content-Type: application/json");
require 'dbhandler.php';

// Handle parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$show_deleted = isset($_GET['show_deleted']) ? filter_var($_GET['show_deleted'], FILTER_VALIDATE_BOOLEAN) : false;
$offset = ($page - 1) * $limit;

// Get total count (filter by deleted status if needed)
$countQuery = "SELECT COUNT(*) as total FROM login_admin";
if (!$show_deleted) {
    $countQuery .= " WHERE is_deleted = FALSE";
}
$countResult = $conn->query($countQuery);
$totalRow = $countResult->fetch_assoc();
$total = $totalRow['total'];

// Get paginated data with deletion info
$query = "SELECT 
            a.admin_id, 
            a.username, 
            a.role, 
            a.is_deleted,
            a.deleted_at,
            a.deleted_by,
            a.delete_reason,
            b.username as deleted_by_name
          FROM login_admin a
          LEFT JOIN login_admin b ON a.deleted_by = b.admin_id";

if (!$show_deleted) {
    $query .= " WHERE a.is_deleted = FALSE";
}

$query .= " ORDER BY a.is_deleted, a.admin_id LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$admins = [];
while ($row = $result->fetch_assoc()) {
    // Convert is_deleted to boolean for easier handling in JavaScript
    $row['is_deleted'] = (bool)$row['is_deleted'];
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