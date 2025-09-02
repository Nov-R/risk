<?php

namespace App\Core\Database;

use PDO;
use PDOStatement;
use PDOException;
use App\Core\Exceptions\DatabaseException;

/**
 * 基础仓储类
 * 提供通用的数据库操作方法
 */
abstract class BaseRepository {
    protected PDO $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance();
    }
    
    /**
     * 获取表名
     */
    abstract protected function getTableName(): string;
    
    /**
     * 创建记录
     *
     * @param array $data 要插入的字段键值对
     * @return int 新插入记录的ID
     * @throws DatabaseException 当底层 PDO 出错时抛出
     */
    protected function create(array $data): int {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->getTableName()} ($columns) VALUES ($values)";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new DatabaseException("创建记录失败：" . $e->getMessage());
        }
    }
    
    /**
     * 更新记录
     *
     * @param int $id 记录ID
     * @param array $data 要更新的字段键值对
     * @return bool 操作是否成功
     * @throws DatabaseException 当底层 PDO 出错时抛出
     */
    protected function update(int $id, array $data): bool {
        $setClauses = array_map(fn($key) => "$key = ?", array_keys($data));
        $sql = "UPDATE {$this->getTableName()} SET " . implode(', ', $setClauses) . " WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([...array_values($data), $id]);
        } catch (PDOException $e) {
            throw new DatabaseException("更新记录失败：" . $e->getMessage());
        }
    }
    
    /**
     * 删除记录
     *
     * @param int $id 记录ID
     * @return bool 删除是否成功
     * @throws DatabaseException 当底层 PDO 出错时抛出
     */
    protected function delete(int $id): bool {
        $sql = "DELETE FROM {$this->getTableName()} WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new DatabaseException("删除记录失败：" . $e->getMessage());
        }
    }
    
    /**
     * 通过 ID 查找记录
     *
     * @param int $id 记录ID
     * @return array|null 返回记录数据或 null
     * @throws DatabaseException 当底层 PDO 出错时抛出
     */
    protected function findById(int $id): ?array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE id = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new DatabaseException("查找记录失败：" . $e->getMessage());
        }
    }
    
    /**
     * 查找所有记录
     *
     * @return array 记录数组
     * @throws DatabaseException 当底层 PDO 出错时抛出
     */
    protected function findAll(): array {
        $sql = "SELECT * FROM {$this->getTableName()}";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException("获取记录列表失败：" . $e->getMessage());
        }
    }
    
    /**
     * 执行自定义查询并返回 PDOStatement
     *
     * @param string $sql SQL 语句
     * @param array $params 绑定参数
    * @return PDOStatement 已执行的 PDOStatement
     * @throws DatabaseException 当底层 PDO 出错时抛出
     */
    protected function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException("查询执行失败：" . $e->getMessage());
        }
    }
}
