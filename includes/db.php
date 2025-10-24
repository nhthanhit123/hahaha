<?php
// Database connection file
require_once __DIR__ . '/../config/config.php';

// Check if connection exists
if (!isset($pdo)) {
    die("Database connection not established. Please check config.php");
}

// Function to execute query with error handling
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage() . " SQL: " . $sql);
        throw new Exception("Lỗi khi thực hiện truy vấn: " . $e->getMessage());
    }
}

// Function to get single row
function getRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Function to get multiple rows
function getRows($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Function to insert data
function insertData($table, $data) {
    global $pdo;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $pdo->lastInsertId();
    } catch(PDOException $e) {
        error_log("Insert Error: " . $e->getMessage());
        throw new Exception("Lỗi khi thêm dữ liệu: " . $e->getMessage());
    }
}

// Function to update data
function updateData($table, $data, $where, $whereParams = []) {
    global $pdo;
    
    $setClause = [];
    foreach ($data as $key => $value) {
        $setClause[] = "$key = ?";
    }
    $setClause = implode(', ', $setClause);
    
    $sql = "UPDATE $table SET $setClause WHERE $where";
    $params = array_merge(array_values($data), $whereParams);
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        error_log("Update Error: " . $e->getMessage());
        throw new Exception("Lỗi khi cập nhật dữ liệu: " . $e->getMessage());
    }
}

// Function to delete data
function deleteData($table, $where, $params = []) {
    global $pdo;
    
    $sql = "DELETE FROM $table WHERE $where";
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        error_log("Delete Error: " . $e->getMessage());
        throw new Exception("Lỗi khi xóa dữ liệu: " . $e->getMessage());
    }
}

// Function to check if table exists
function tableExists($tableName) {
    global $pdo;
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch(PDOException $e) {
        return false;
    }
}

// Function to get table columns
function getTableColumns($tableName) {
    global $pdo;
    try {
        $result = $pdo->query("DESCRIBE $tableName");
        return $result->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {
        return [];
    }
}
?>