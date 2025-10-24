<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vps_store";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function executeQuery($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Lỗi chuẩn bị câu lệnh: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function insertData($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $values = array_values($data);
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = executeQuery($sql, $values);
    
    return $conn->insert_id;
}

function updateData($table, $data, $where, $whereParams = []) {
    $setClause = [];
    $params = [];
    
    foreach ($data as $key => $value) {
        $setClause[] = "$key = ?";
        $params[] = $value;
    }
    
    $setClause = implode(', ', $setClause);
    $params = array_merge($params, $whereParams);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    $stmt = executeQuery($sql, $params);
    
    return $stmt->affected_rows;
}

function deleteData($table, $where, $params = []) {
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = executeQuery($sql, $params);
    
    return $stmt->affected_rows;
}
?>