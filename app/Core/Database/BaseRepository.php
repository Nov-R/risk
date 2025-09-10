<?php

/**
 * 数据仓储基类
 * 
 * 提供通用的数据访问层功能，包括基础CRUD操作、事务管理、查询构建等
 * 遵循仓储模式(Repository Pattern)，为数据访问提供统一接口
 * 
 * @package App\Core\Database
 * @author Risk Management System
 * @version 1.0.0
 * @since 2024
 */

namespace App\Core\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;
use App\Core\Exceptions\DatabaseException;
use App\Core\Utils\Logger;

/**
 * 抽象仓储基类
 * 
 * 提供数据访问层的基础功能实现，包括：
 * - CRUD操作（创建、读取、更新、删除）
 * - 批量操作支持
 * - 事务管理
 * - 查询统计和性能监控
 * - 软删除支持
 * - 数据验证和字段过滤
 * 
 * @example
 * ```php
 * class UserRepository extends BaseRepository 
 * {
 *     protected function getTableName(): string 
 *     {
 *         return 'users';
 *     }
 * 
 *     public function findByEmail(string $email): ?array 
 *     {
 *         return $this->findOneBy(['email' => $email]);
 *     }
 * }
 * ```
 */
abstract class BaseRepository 
{
    /**
     * PDO数据库连接实例
     * 
     * @var PDO
     */
    protected PDO $db;

    /**
     * 查询执行统计
     * 
     * @var array<string, int>
     */
    private array $queryStats = [
        'select_count' => 0,
        'insert_count' => 0,
        'update_count' => 0,
        'delete_count' => 0,
        'total_query_time' => 0
    ];

    /**
     * 当前事务嵌套级别
     * 
     * @var int
     */
    private int $transactionLevel = 0;

    /**
     * 构造函数
     * 
     * 初始化数据库连接和基础配置
     * 
     * @throws DatabaseException 当数据库连接失败时
     */
    public function __construct() 
    {
        try {
            $this->db = DatabaseConnection::getInstance();
            $this->initializeRepository();
        } catch (Throwable $e) {
            Logger::exception($e, '仓储类初始化失败');
            throw new DatabaseException(
                '仓储类初始化失败', 
                500, 
                $e,
                ['repository_class' => static::class]
            );
        }
    }

    /**
     * 获取数据表名
     * 
     * 子类必须实现此方法以指定操作的数据表
     * 
     * @return string 数据表名
     */
    abstract protected function getTableName(): string;

    /**
     * 获取主键字段名
     * 
     * 默认为'id'，子类可以重写以指定其他主键字段
     * 
     * @return string 主键字段名
     */
    protected function getPrimaryKey(): string 
    {
        return 'id';
    }

    /**
     * 获取可填充字段列表
     * 
     * 用于批量赋值时的字段白名单，子类应重写此方法
     * 
     * @return array<string> 可填充字段数组
     */
    protected function getFillable(): array 
    {
        return [];
    }

    /**
     * 是否支持软删除
     * 
     * @return bool 是否支持软删除功能
     */
    protected function supportsSoftDelete(): bool 
    {
        return false;
    }

    /**
     * 初始化仓储
     * 
     * 子类可重写此方法进行自定义初始化
     * 
     * @return void
     */
    protected function initializeRepository(): void 
    {
        // 默认实现为空，子类可根据需要重写
    }

    /**
     * 创建新记录
     * 
     * 支持数据验证、自动时间戳和批量插入优化
     * 
     * @param array<string, mixed> $data 要插入的数据
     * @param bool $validateFillable 是否验证可填充字段
     * 
     * @return int 新插入记录的ID
     * 
     * @throws DatabaseException 当插入操作失败时
     * 
     * @example
     * ```php
     * // 创建新用户
     * $userId = $userRepository->create([
     *     'name' => '张三',
     *     'email' => 'zhangsan@example.com',
     *     'password' => 'hashed_password'
     * ]);
     * ```
     */
    protected function create(array $data, bool $validateFillable = true): int 
    {
        $startTime = microtime(true);
        
        try {
            // 过滤可填充字段
            if ($validateFillable) {
                $data = $this->filterFillableFields($data);
            }

            // 添加自动时间戳
            $data = $this->addTimestamps($data, 'create');

            // 验证数据完整性
            $this->validateData($data, 'create');

            // 构建SQL语句
            $columns = array_keys($data);
            $placeholders = array_map(fn($col) => ":{$col}", $columns);
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->getTableName(),
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            // 执行插入操作
            $stmt = $this->executeQuery($sql, $data, 'INSERT');
            $insertId = (int) $this->db->lastInsertId();
            
            $queryTime = microtime(true) - $startTime;
            $this->logQuery('INSERT', $sql, $data, $queryTime);
            
            Logger::info('记录创建成功', [
                'table' => $this->getTableName(),
                'insert_id' => $insertId,
                'query_time' => round($queryTime * 1000, 2) . 'ms'
            ]);

            return $insertId;

        } catch (PDOException $e) {
            $this->logFailedQuery('INSERT', $sql ?? null, $data, $e);
            throw DatabaseException::fromPDOException(
                $e,
                '创建记录失败',
                [
                    'table' => $this->getTableName(),
                    'data' => $this->sanitizeLogData($data)
                ]
            );
        }
    }

    /**
     * 批量创建记录
     * 
     * 优化的批量插入操作，提高大量数据插入的性能
     * 
     * @param array<array<string, mixed>> $dataList 要插入的数据数组
     * @param int $batchSize 批次大小，0表示一次性插入所有数据
     * 
     * @return array<int> 插入的记录ID数组
     * 
     * @throws DatabaseException 当批量插入失败时
     */
    protected function batchCreate(array $dataList, int $batchSize = 100): array 
    {
        if (empty($dataList)) {
            return [];
        }

        $insertedIds = [];
        $chunks = $batchSize > 0 ? array_chunk($dataList, $batchSize) : [$dataList];
        
        foreach ($chunks as $chunk) {
            $chunkIds = $this->executeBatchInsert($chunk);
            $insertedIds = array_merge($insertedIds, $chunkIds);
        }

        Logger::info('批量创建成功', [
            'table' => $this->getTableName(),
            'total_records' => count($dataList),
            'batch_size' => $batchSize,
            'inserted_ids_count' => count($insertedIds)
        ]);

        return $insertedIds;
    }

    /**
     * 更新记录
     *
     * 支持条件更新、字段过滤和乐观锁
     * 
     * @param int|string $id 记录ID
     * @param array<string, mixed> $data 要更新的字段键值对
     * @param bool $validateFillable 是否验证可填充字段
     * @param array<string, mixed> $conditions 额外的更新条件
     * 
     * @return bool 操作是否成功
     * 
     * @throws DatabaseException 当更新操作失败时
     * 
     * @example
     * ```php
     * // 基本更新
     * $userRepository->update(123, ['name' => '李四', 'email' => 'lisi@example.com']);
     * 
     * // 带条件更新
     * $userRepository->update(123, ['status' => 'active'], true, ['version' => 1]);
     * ```
     */
    protected function update($id, array $data, bool $validateFillable = true, array $conditions = []): bool 
    {
        $startTime = microtime(true);
        
        try {
            // 过滤可填充字段
            if ($validateFillable) {
                $data = $this->filterFillableFields($data);
            }

            // 添加自动时间戳
            $data = $this->addTimestamps($data, 'update');

            // 验证数据
            $this->validateData($data, 'update');

            if (empty($data)) {
                Logger::warning('更新数据为空', ['table' => $this->getTableName(), 'id' => $id]);
                return false;
            }

            // 构建SET子句
            $setClause = [];
            $params = [];
            foreach ($data as $column => $value) {
                $setClause[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }

            // 构建WHERE子句
            $primaryKey = $this->getPrimaryKey();
            $whereConditions = ["{$primaryKey} = :{$primaryKey}"];
            $params[$primaryKey] = $id;

            // 添加额外条件
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "{$column} = :{$column}_cond";
                $params["{$column}_cond"] = $value;
            }

            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s",
                $this->getTableName(),
                implode(', ', $setClause),
                implode(' AND ', $whereConditions)
            );

            // 执行更新
            $stmt = $this->executeQuery($sql, $params, 'UPDATE');
            $affectedRows = $stmt->rowCount();
            
            $queryTime = microtime(true) - $startTime;
            
            if ($affectedRows > 0) {
                $this->logQuery('UPDATE', $sql, $params, $queryTime);
                Logger::info('记录更新成功', [
                    'table' => $this->getTableName(),
                    'id' => $id,
                    'affected_rows' => $affectedRows,
                    'query_time' => round($queryTime * 1000, 2) . 'ms'
                ]);
                return true;
            } else {
                Logger::warning('更新操作未影响任何记录', [
                    'table' => $this->getTableName(),
                    'id' => $id,
                    'conditions' => $conditions
                ]);
                return false;
            }

        } catch (PDOException $e) {
            $this->logFailedQuery('UPDATE', $sql ?? null, $params ?? [], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '更新记录失败',
                [
                    'table' => $this->getTableName(),
                    'id' => $id,
                    'data' => $this->sanitizeLogData($data)
                ]
            );
        }
    }

    /**
     * 批量更新记录
     * 
     * @param array<string, mixed> $data 要更新的数据
     * @param array<string, mixed> $conditions 更新条件
     * @param int $limit 限制更新数量，0表示不限制
     * 
     * @return int 受影响的记录数
     * 
     * @throws DatabaseException 当批量更新失败时
     */
    protected function batchUpdate(array $data, array $conditions, int $limit = 0): int 
    {
        $startTime = microtime(true);
        
        try {
            // 过滤和验证数据
            $data = $this->filterFillableFields($data);
            $data = $this->addTimestamps($data, 'update');
            $this->validateData($data, 'update');

            if (empty($data) || empty($conditions)) {
                throw new DatabaseException('批量更新的数据和条件不能为空');
            }

            // 构建SET子句
            $setClause = [];
            $params = [];
            foreach ($data as $column => $value) {
                $setClause[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }

            // 构建WHERE子句
            $whereConditions = [];
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "{$column} = :{$column}_where";
                $params["{$column}_where"] = $value;
            }

            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s%s",
                $this->getTableName(),
                implode(', ', $setClause),
                implode(' AND ', $whereConditions),
                $limit > 0 ? " LIMIT {$limit}" : ''
            );

            $stmt = $this->executeQuery($sql, $params, 'UPDATE');
            $affectedRows = $stmt->rowCount();
            
            $queryTime = microtime(true) - $startTime;
            $this->logQuery('BATCH_UPDATE', $sql, $params, $queryTime);
            
            Logger::info('批量更新成功', [
                'table' => $this->getTableName(),
                'affected_rows' => $affectedRows,
                'conditions' => $conditions,
                'query_time' => round($queryTime * 1000, 2) . 'ms'
            ]);

            return $affectedRows;

        } catch (PDOException $e) {
            $this->logFailedQuery('BATCH_UPDATE', $sql ?? null, $params ?? [], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '批量更新记录失败',
                [
                    'table' => $this->getTableName(),
                    'conditions' => $conditions
                ]
            );
        }
    }

    /**
     * 删除记录
     *
     * 支持软删除和硬删除，以及条件删除
     * 
     * @param int|string $id 记录ID
     * @param bool $softDelete 是否软删除
     * @param array<string, mixed> $conditions 额外的删除条件
     * 
     * @return bool 操作是否成功
     * 
     * @throws DatabaseException 当删除操作失败时
     * 
     * @example
     * ```php
     * // 硬删除
     * $userRepository->delete(123);
     * 
     * // 软删除
     * $userRepository->delete(123, true);
     * 
     * // 条件删除
     * $userRepository->delete(123, false, ['status' => 'inactive']);
     * ```
     */
    protected function delete($id, bool $softDelete = false, array $conditions = []): bool 
    {
        $startTime = microtime(true);
        
        try {
            $primaryKey = $this->getPrimaryKey();
            $params = [$primaryKey => $id];
            
            // 软删除 - 更新deleted_at字段
            if ($softDelete && $this->supportsSoftDelete()) {
                $data = [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                return $this->update($id, $data, false, $conditions);
            }
            
            // 硬删除 - 物理删除记录
            $whereConditions = ["{$primaryKey} = :{$primaryKey}"];
            
            // 添加额外条件
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "{$column} = :{$column}_cond";
                $params["{$column}_cond"] = $value;
            }
            
            $sql = sprintf(
                "DELETE FROM %s WHERE %s",
                $this->getTableName(),
                implode(' AND ', $whereConditions)
            );
            
            $stmt = $this->executeQuery($sql, $params, 'DELETE');
            $affectedRows = $stmt->rowCount();
            
            $queryTime = microtime(true) - $startTime;
            
            if ($affectedRows > 0) {
                $this->logQuery('DELETE', $sql, $params, $queryTime);
                Logger::info('记录删除成功', [
                    'table' => $this->getTableName(),
                    'id' => $id,
                    'soft_delete' => $softDelete,
                    'affected_rows' => $affectedRows,
                    'query_time' => round($queryTime * 1000, 2) . 'ms'
                ]);
                return true;
            } else {
                Logger::warning('删除操作未影响任何记录', [
                    'table' => $this->getTableName(),
                    'id' => $id,
                    'conditions' => $conditions
                ]);
                return false;
            }

        } catch (PDOException $e) {
            $this->logFailedQuery('DELETE', $sql ?? null, $params ?? [], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '删除记录失败',
                [
                    'table' => $this->getTableName(),
                    'id' => $id,
                    'soft_delete' => $softDelete
                ]
            );
        }
    }

    /**
     * 批量删除记录
     * 
     * @param array<string, mixed> $conditions 删除条件
     * @param bool $softDelete 是否软删除
     * @param int $limit 限制删除数量，0表示不限制
     * 
     * @return int 受影响的记录数
     * 
     * @throws DatabaseException 当批量删除失败时
     */
    protected function batchDelete(array $conditions, bool $softDelete = false, int $limit = 0): int 
    {
        $startTime = microtime(true);
        
        try {
            if (empty($conditions)) {
                throw new DatabaseException('批量删除必须提供删除条件以防止误删除');
            }

            // 软删除
            if ($softDelete && $this->supportsSoftDelete()) {
                $data = [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                return $this->batchUpdate($data, $conditions, $limit);
            }

            // 硬删除
            $whereConditions = [];
            $params = [];
            foreach ($conditions as $column => $value) {
                $whereConditions[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }

            $sql = sprintf(
                "DELETE FROM %s WHERE %s%s",
                $this->getTableName(),
                implode(' AND ', $whereConditions),
                $limit > 0 ? " LIMIT {$limit}" : ''
            );

            $stmt = $this->executeQuery($sql, $params, 'DELETE');
            $affectedRows = $stmt->rowCount();
            
            $queryTime = microtime(true) - $startTime;
            $this->logQuery('BATCH_DELETE', $sql, $params, $queryTime);
            
            Logger::info('批量删除成功', [
                'table' => $this->getTableName(),
                'affected_rows' => $affectedRows,
                'conditions' => $conditions,
                'soft_delete' => $softDelete,
                'query_time' => round($queryTime * 1000, 2) . 'ms'
            ]);

            return $affectedRows;

        } catch (PDOException $e) {
            $this->logFailedQuery('BATCH_DELETE', $sql ?? null, $params ?? [], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '批量删除记录失败',
                [
                    'table' => $this->getTableName(),
                    'conditions' => $conditions,
                    'soft_delete' => $softDelete
                ]
            );
        }
    }

    /**
     * 根据条件查找单个记录
     *
     * @param array<string, mixed> $conditions 查找条件
     * @param array<string> $columns 要查询的列，默认为*
     * @return array|null 记录数组，不存在时返回null
     * 
     * @throws DatabaseException 当查询失败时
     * 
     * @example
     * ```php
     * // 根据邮箱查找用户
     * $user = $userRepository->findOneBy(['email' => 'admin@example.com']);
     * 
     * // 查找指定列
     * $user = $userRepository->findOneBy(['id' => 123], ['name', 'email']);
     * ```
     */
    protected function findOneBy(array $conditions, array $columns = ['*']): ?array 
    {
        $records = $this->findBy($conditions, $columns, 1);
        return $records[0] ?? null;
    }

    /**
     * 根据条件查找多个记录
     * 
     * @param array<string, mixed> $conditions 查找条件
     * @param array<string> $columns 要查询的列
     * @param int $limit 限制数量，0表示不限制
     * @param int $offset 偏移量
     * @param array<string, string> $orderBy 排序条件 ['column' => 'ASC|DESC']
     * 
     * @return array<array<string, mixed>> 记录数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    protected function findBy(
        array $conditions, 
        array $columns = ['*'], 
        int $limit = 0, 
        int $offset = 0,
        array $orderBy = []
    ): array {
        $startTime = microtime(true);
        
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($conditions)) {
                $whereConditions = [];
                foreach ($conditions as $column => $value) {
                    $whereConditions[] = "{$column} = :{$column}";
                    $params[$column] = $value;
                }
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }

            // 添加软删除过滤
            if ($this->supportsSoftDelete()) {
                $whereClause = $whereClause ? 
                    $whereClause . ' AND deleted_at IS NULL' : 
                    'WHERE deleted_at IS NULL';
            }

            // 构建ORDER BY子句
            $orderClause = '';
            if (!empty($orderBy)) {
                $orderConditions = [];
                foreach ($orderBy as $column => $direction) {
                    $direction = strtoupper($direction);
                    if (!in_array($direction, ['ASC', 'DESC'])) {
                        $direction = 'ASC';
                    }
                    $orderConditions[] = "{$column} {$direction}";
                }
                $orderClause = 'ORDER BY ' . implode(', ', $orderConditions);
            }

            // 构建LIMIT子句
            $limitClause = '';
            if ($limit > 0) {
                $limitClause = "LIMIT {$limit}";
                if ($offset > 0) {
                    $limitClause .= " OFFSET {$offset}";
                }
            }

            $sql = sprintf(
                "SELECT %s FROM %s %s %s %s",
                implode(', ', $columns),
                $this->getTableName(),
                $whereClause,
                $orderClause,
                $limitClause
            );

            $stmt = $this->executeQuery($sql, $params, 'SELECT');
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $queryTime = microtime(true) - $startTime;
            $this->logQuery('SELECT', $sql, $params, $queryTime);
            
            Logger::debug('查询记录', [
                'table' => $this->getTableName(),
                'conditions' => $conditions,
                'record_count' => count($records),
                'query_time' => round($queryTime * 1000, 2) . 'ms'
            ]);

            return $records;

        } catch (PDOException $e) {
            $this->logFailedQuery('SELECT', $sql ?? null, $params ?? [], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '查询记录失败',
                [
                    'table' => $this->getTableName(),
                    'conditions' => $conditions
                ]
            );
        }
    }

    /**
     * 根据ID查找记录
     * 
     * @param int|string $id 记录ID
     * @param array<string> $columns 要查询的列
     * 
     * @return array|null 记录数组，不存在时返回null
     * 
     * @throws DatabaseException 当查询失败时
     */
    protected function findById($id, array $columns = ['*']): ?array 
    {
        return $this->findOneBy([$this->getPrimaryKey() => $id], $columns);
    }

    /**
     * 检查记录是否存在
     * 
     * @param array<string, mixed> $conditions 查找条件
     * 
     * @return bool 记录是否存在
     * 
     * @throws DatabaseException 当查询失败时
     */
    protected function exists(array $conditions): bool 
    {
        $record = $this->findOneBy($conditions, [$this->getPrimaryKey()]);
        return $record !== null;
    }

    /**
     * 统计记录数量
     * 
     * @param array<string, mixed> $conditions 统计条件
     * 
     * @return int 记录数量
     * 
     * @throws DatabaseException 当统计失败时
     */
    protected function count(array $conditions = []): int 
    {
        $startTime = microtime(true);
        
        try {
            $whereClause = '';
            $params = [];
            
            if (!empty($conditions)) {
                $whereConditions = [];
                foreach ($conditions as $column => $value) {
                    $whereConditions[] = "{$column} = :{$column}";
                    $params[$column] = $value;
                }
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }

            // 添加软删除过滤
            if ($this->supportsSoftDelete()) {
                $whereClause = $whereClause ? 
                    $whereClause . ' AND deleted_at IS NULL' : 
                    'WHERE deleted_at IS NULL';
            }

            $sql = sprintf(
                "SELECT COUNT(*) as count FROM %s %s",
                $this->getTableName(),
                $whereClause
            );

            $stmt = $this->executeQuery($sql, $params, 'SELECT');
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $queryTime = microtime(true) - $startTime;
            $this->logQuery('COUNT', $sql, $params, $queryTime);

            return (int) $result['count'];

        } catch (PDOException $e) {
            $this->logFailedQuery('COUNT', $sql ?? null, $params ?? [], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '统计记录失败',
                [
                    'table' => $this->getTableName(),
                    'conditions' => $conditions
                ]
            );
        }
    }

    /**
     * 开启事务
     * 
     * 支持嵌套事务管理
     * 
     * @return void
     * 
     * @throws DatabaseException 当事务开启失败时
     */
    protected function beginTransaction(): void 
    {
        try {
            if ($this->transactionLevel === 0) {
                $this->db->beginTransaction();
                Logger::debug('开始事务', ['table' => $this->getTableName()]);
            }
            $this->transactionLevel++;
        } catch (PDOException $e) {
            throw DatabaseException::fromPDOException($e, '开启事务失败');
        }
    }

    /**
     * 提交事务
     * 
     * @return void
     * 
     * @throws DatabaseException 当事务提交失败时
     */
    protected function commit(): void 
    {
        try {
            $this->transactionLevel--;
            if ($this->transactionLevel === 0) {
                $this->db->commit();
                Logger::debug('提交事务', ['table' => $this->getTableName()]);
            }
        } catch (PDOException $e) {
            throw DatabaseException::fromPDOException($e, '提交事务失败');
        }
    }

    /**
     * 回滚事务
     * 
     * @return void
     * 
     * @throws DatabaseException 当事务回滚失败时
     */
    protected function rollback(): void 
    {
        try {
            $this->transactionLevel = 0;
            if ($this->db->inTransaction()) {
                $this->db->rollback();
                Logger::debug('回滚事务', ['table' => $this->getTableName()]);
            }
        } catch (PDOException $e) {
            throw DatabaseException::fromPDOException($e, '回滚事务失败');
        }
    }

    /**
     * 执行事务操作
     * 
     * @param callable $callback 要在事务中执行的回调函数
     * 
     * @return mixed 回调函数的返回值
     * 
     * @throws DatabaseException|Throwable 当事务执行失败时
     */
    protected function transaction(callable $callback) 
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            Logger::exception($e, '事务执行失败');
            throw $e;
        }
    }

    /**
     * 获取查询统计信息
     * 
     * @return array<string, int> 查询统计数据
     */
    public function getQueryStats(): array 
    {
        return $this->queryStats;
    }

    /**
     * 重置查询统计
     * 
     * @return void
     */
    public function resetQueryStats(): void 
    {
        $this->queryStats = [
            'select_count' => 0,
            'insert_count' => 0,
            'update_count' => 0,
            'delete_count' => 0,
            'total_query_time' => 0
        ];
    }

    // ================== 私有辅助方法 ==================

    /**
     * 执行批量插入操作
     * 
     * @param array<array<string, mixed>> $dataList 要插入的数据数组
     * 
     * @return array<int> 插入的记录ID数组
     * 
     * @throws DatabaseException 当批量插入失败时
     */
    private function executeBatchInsert(array $dataList): array 
    {
        $startTime = microtime(true);
        $insertedIds = [];
        
        try {
            // 获取所有字段名（使用第一条记录的字段）
            $firstRecord = reset($dataList);
            $firstRecord = $this->filterFillableFields($firstRecord);
            $firstRecord = $this->addTimestamps($firstRecord, 'create');
            $columns = array_keys($firstRecord);
            
            // 验证所有记录具有相同的字段结构
            foreach ($dataList as $index => $data) {
                $data = $this->filterFillableFields($data);
                $data = $this->addTimestamps($data, 'create');
                $this->validateData($data, 'create');
                
                if (array_keys($data) !== $columns) {
                    throw new DatabaseException("批量插入数据结构不一致，记录索引：{$index}");
                }
                $dataList[$index] = $data;
            }
            
            // 构建批量插入SQL
            $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $allPlaceholders = implode(', ', array_fill(0, count($dataList), $placeholders));
            
            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES %s",
                $this->getTableName(),
                implode(', ', $columns),
                $allPlaceholders
            );
            
            // 准备参数数组
            $params = [];
            foreach ($dataList as $data) {
                foreach ($columns as $column) {
                    $params[] = $data[$column];
                }
            }
            
            // 执行批量插入
            $stmt = $this->executeQuery($sql, $params, 'INSERT');
            
            // 获取插入的ID范围（适用于自增主键）
            $lastInsertId = (int) $this->db->lastInsertId();
            if ($lastInsertId > 0) {
                for ($i = 0; $i < count($dataList); $i++) {
                    $insertedIds[] = $lastInsertId + $i;
                }
            }
            
            $queryTime = microtime(true) - $startTime;
            $this->logQuery('BATCH_INSERT', $sql, ['record_count' => count($dataList)], $queryTime);
            
            return $insertedIds;

        } catch (PDOException $e) {
            $this->logFailedQuery('BATCH_INSERT', $sql ?? null, ['record_count' => count($dataList)], $e);
            throw DatabaseException::fromPDOException(
                $e,
                '批量插入记录失败',
                [
                    'table' => $this->getTableName(),
                    'record_count' => count($dataList)
                ]
            );
        }
    }

    /**
     * 过滤可填充字段
     * 
     * @param array<string, mixed> $data 原始数据
     * 
     * @return array<string, mixed> 过滤后的数据
     */
    private function filterFillableFields(array $data): array 
    {
        $fillable = $this->getFillable();
        
        if (empty($fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($fillable));
    }

    /**
     * 添加自动时间戳
     * 
     * @param array<string, mixed> $data 数据
     * @param string $operation 操作类型 create|update
     * 
     * @return array<string, mixed> 添加时间戳后的数据
     */
    private function addTimestamps(array $data, string $operation): array 
    {
        $now = date('Y-m-d H:i:s');
        
        if ($operation === 'create') {
            if (!isset($data['created_at'])) {
                $data['created_at'] = $now;
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = $now;
            }
        } elseif ($operation === 'update') {
            $data['updated_at'] = $now;
        }
        
        return $data;
    }

    /**
     * 验证数据
     * 
     * @param array<string, mixed> $data 要验证的数据
     * @param string $operation 操作类型
     * 
     * @return void
     * 
     * @throws DatabaseException 当数据验证失败时
     */
    private function validateData(array $data, string $operation): void 
    {
        // 基础验证：检查数据是否为空
        if (empty($data)) {
            throw new DatabaseException("数据不能为空，操作类型：{$operation}");
        }
        
        // 子类可重写此方法进行自定义验证
    }

    /**
     * 执行SQL查询
     * 
     * @param string $sql SQL语句
     * @param array<string, mixed> $params 参数
     * @param string $queryType 查询类型
     * 
     * @return PDOStatement 执行结果
     * 
     * @throws PDOException 当查询执行失败时
     */
    private function executeQuery(string $sql, array $params, string $queryType): PDOStatement 
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        // 更新查询统计
        $this->updateQueryStats($queryType);
        
        return $stmt;
    }

    /**
     * 记录查询日志
     * 
     * @param string $queryType 查询类型
     * @param string $sql SQL语句
     * @param array<string, mixed> $params 参数
     * @param float $queryTime 查询时间（秒）
     * 
     * @return void
     */
    private function logQuery(string $queryType, string $sql, array $params, float $queryTime): void 
    {
        Logger::debug("数据库查询[{$queryType}]", [
            'table' => $this->getTableName(),
            'sql' => $sql,
            'params' => $this->sanitizeLogData($params),
            'query_time' => round($queryTime * 1000, 2) . 'ms'
        ]);
    }

    /**
     * 记录失败的查询
     * 
     * @param string $queryType 查询类型
     * @param string|null $sql SQL语句
     * @param array<string, mixed> $params 参数
     * @param PDOException $exception 异常信息
     * 
     * @return void
     */
    private function logFailedQuery(string $queryType, ?string $sql, array $params, PDOException $exception): void 
    {
        Logger::error("数据库查询失败[{$queryType}]", [
            'table' => $this->getTableName(),
            'sql' => $sql,
            'params' => $this->sanitizeLogData($params),
            'error' => $exception->getMessage(),
            'error_code' => $exception->getCode()
        ]);
    }

    /**
     * 清理日志数据（移除敏感信息）
     * 
     * @param array<string, mixed> $data 原始数据
     * 
     * @return array<string, mixed> 清理后的数据
     */
    private function sanitizeLogData(array $data): array 
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth'];
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveFields as $field) {
                if (strpos($lowerKey, $field) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            $sanitized[$key] = $isSensitive ? '***' : $value;
        }
        
        return $sanitized;
    }

    /**
     * 更新查询统计
     * 
     * @param string $queryType 查询类型
     * 
     * @return void
     */
    private function updateQueryStats(string $queryType): void 
    {
        $type = strtolower($queryType);
        
        switch ($type) {
            case 'select':
            case 'count':
                $this->queryStats['select_count']++;
                break;
            case 'insert':
            case 'batch_insert':
                $this->queryStats['insert_count']++;
                break;
            case 'update':
            case 'batch_update':
                $this->queryStats['update_count']++;
                break;
            case 'delete':
            case 'batch_delete':
                $this->queryStats['delete_count']++;
                break;
        }
    }
}
