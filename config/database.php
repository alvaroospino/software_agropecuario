<?php
require_once __DIR__ . '/config.php';

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ':' . $field;
        }, $fields);
        
        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return $this->conn->lastInsertId();
    }
    
    public function update($table, $data, $condition, $params = []) {
        $sets = array_map(function($field) {
            return "$field = :$field";
        }, array_keys($data));
        
        $sql = "UPDATE $table SET " . implode(', ', $sets) . " WHERE $condition";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function delete($table, $condition, $params = []) {
        $sql = "DELETE FROM $table WHERE $condition";
        $stmt = $this->conn->prepare($sql);
    
        // Vincular valores con índices numéricos
        foreach (array_values($params) as $index => $value) {
            $stmt->bindValue($index + 1, $value); // bindValue empieza desde 1
        }
    
        return $stmt->execute();
    }
    
}
?>