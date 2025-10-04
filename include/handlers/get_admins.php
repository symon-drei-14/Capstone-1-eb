<?php
header("Content-Type: application/json");
require 'dbhandler.php';


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$show_deleted = isset($_GET['show_deleted']) ? filter_var($_GET['show_deleted'], FILTER_VALIDATE_BOOLEAN) : false;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

$baseQuery = "FROM login_admin a LEFT JOIN login_admin b ON a.deleted_by = b.admin_id";
$whereClause = $show_deleted ? " WHERE a.is_deleted = TRUE" : " WHERE a.is_deleted = FALSE";

$params = [];
$types = '';

if (!empty($search)) {
    $searchTerm = "%" . $search . "%";
    // Add admin_email to what's being searched
    $whereClause .= " AND (a.username LIKE ? OR a.role LIKE ? OR a.admin_email LIKE ? OR b.username LIKE ? OR a.delete_reason LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $types .= 'sssss'; // Now 5 string parameters
}

$countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
$stmtCount = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$totalRow = $countResult->fetch_assoc();
$total = $totalRow['total'];
$stmtCount->close();

$query = "SELECT 
            a.admin_id, 
            a.username, 
            a.role,
            a.admin_email,
            a.admin_pic,
            a.is_deleted,
            a.deleted_at,
            a.delete_reason,
            b.username as deleted_by_name
          " . $baseQuery . $whereClause . " ORDER BY a.is_deleted, a.admin_id LIMIT ? OFFSET ?";


array_push($params, $limit, $offset);
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$admins = [];
while ($row = $result->fetch_assoc()) {
    $row['is_deleted'] = (bool)$row['is_deleted'];
    $row['deleted_by'] = $row['deleted_by_name'];
    $admins[] = $row;
}

echo json_encode([
    "success" => true,
    "admins" => $admins,
    "total" => $total,
    "page" => $page,
    "limit" => $limit
]);

$stmt->close();
$conn->close();
?>