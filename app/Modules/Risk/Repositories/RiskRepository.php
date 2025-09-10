<?php

/**
 * 风险仓储类
 * 
 * 处理与风险相关的所有数据库操作，利用BaseRepository提供的企业级功能
 * 包括事务管理、批量操作、性能监控等高级特性
 * 
 * @package App\Modules\Risk\Repositories
 * @author Risk Management System  
 * @version 2.0.0 - 基于新BaseRepository重构
 * @since 2024
 */

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;
use App\Core\Exceptions\DatabaseException;
use App\Modules\Risk\Entities\Risk;
use App\Core\Utils\Logger;

/**
 * 风险数据仓储类
 * 
 * 继承BaseRepository的企业级功能：
 * - 自动时间戳管理
 * - 字段验证和过滤  
 * - 查询性能监控
 * - 事务支持
 * - 批量操作优化
 * - 完整的错误处理
 * 
 * @example
 * ```php
 * $riskRepository = new RiskRepository();
 * 
 * // 创建风险
 * $riskId = $riskRepository->createRisk([
 *     'title' => '服务器宕机风险',
 *     'probability' => 3,
 *     'impact' => 5,
 *     'status' => 'identified'
 * ]);
 * 
 * // 批量操作
 * $riskRepository->batchUpdateStatus([1, 2, 3], 'mitigated');
 * ```
 */
class RiskRepository extends BaseRepository 
{
    /**
     * 获取数据表名
     * 
     * @return string 风险数据表名
     */
    protected function getTableName(): string 
    {
        return 'risks';
    }

    /**
     * 获取可填充字段列表
     * 
     * 定义允许批量赋值的字段，提高数据安全性
     * 
     * @return array<string> 可填充字段数组
     */
    protected function getFillable(): array 
    {
        return [
            'title',
            'description', 
            'category',
            'probability',
            'impact',
            'status',
            'mitigation_plan',
            'owner',
            'due_date',
            'node_id'
        ];
    }

    /**
     * 是否支持软删除
     * 
     * 风险记录支持软删除以保持历史追踪
     * 
     * @return bool
     */
    protected function supportsSoftDelete(): bool 
    {
        return true;
    }

    // ================== 基础CRUD操作 ==================

    /**
     * 创建新风险记录
     * 
     * 利用BaseRepository的自动时间戳、字段验证等功能
     * 
     * @param array<string, mixed> $data 风险数据
     * 
     * @return int 新创建的风险ID
     * 
     * @throws DatabaseException 当创建失败时
     * 
     * @example
     * ```php
     * $riskId = $riskRepository->createRisk([
     *     'title' => '数据库服务器宕机',
     *     'description' => '主数据库服务器可能发生硬件故障',
     *     'probability' => 3,
     *     'impact' => 5,
     *     'status' => 'identified',
     *     'category' => 'technical',
     *     'owner' => 'IT团队'
     * ]);
     * ```
     */
    public function createRisk(array $data): int 
    {
        return $this->create($data);
    }

    /**
     * 创建风险实体对象
     * 
     * @param Risk $risk 风险实体对象
     * 
     * @return int 新创建的风险ID
     * 
     * @throws DatabaseException 当创建失败时
     */
    public function createRiskFromEntity(Risk $risk): int 
    {
        return $this->create($risk->toArray());
    }

    /**
     * 批量创建风险记录
     * 
     * 使用BaseRepository的批量优化功能提高性能
     * 
     * @param array<array<string, mixed>> $riskDataList 风险数据数组
     * @param int $batchSize 批次大小
     * 
     * @return array<int> 创建的风险ID数组
     * 
     * @throws DatabaseException 当批量创建失败时
     */
    public function batchCreateRisks(array $riskDataList, int $batchSize = 50): array 
    {
        return $this->batchCreate($riskDataList, $batchSize);
    }

    /**
     * 更新风险记录
     * 
     * 支持部分更新和条件更新
     * 
     * @param int $id 风险ID
     * @param array<string, mixed> $data 要更新的数据
     * @param array<string, mixed> $conditions 额外更新条件
     * 
     * @return bool 是否更新成功
     * 
     * @throws DatabaseException 当更新失败时
     */
    public function updateRisk(int $id, array $data, array $conditions = []): bool 
    {
        return $this->update($id, $data, true, $conditions);
    }

    /**
     * 删除风险记录
     * 
     * 支持软删除和硬删除
     * 
     * @param int $id 风险ID
     * @param bool $softDelete 是否软删除，默认true
     * 
     * @return bool 是否删除成功
     * 
     * @throws DatabaseException 当删除失败时
     */
    public function deleteRisk(int $id, bool $softDelete = true): bool 
    {
        return $this->delete($id, $softDelete);
    }

    /**
     * 根据ID查找风险
     * 
     * @param int $id 风险ID
     * @param array<string> $columns 要查询的列
     * 
     * @return array|null 风险数据，不存在时返回null
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRiskById(int $id, array $columns = ['*']): ?array 
    {
        return $this->findById($id, $columns);
    }



    /**
     * 获取所有风险记录
     * 
     * @param array<string> $columns 要查询的列
     * @param array<string, string> $orderBy 排序条件
     * 
     * @return array<array<string, mixed>> 风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findAllRisks(array $columns = ['*'], array $orderBy = ['created_at' => 'DESC']): array 
    {
        return $this->findBy([], $columns, 0, 0, $orderBy);
    }

    /**
     * 检查风险是否存在
     * 
     * @param array<string, mixed> $conditions 查找条件
     * 
     * @return bool 风险是否存在
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function riskExists(array $conditions): bool 
    {
        return $this->exists($conditions);
    }

    /**
     * 统计风险数量
     * 
     * @param array<string, mixed> $conditions 统计条件
     * 
     * @return int 风险数量
     * 
     * @throws DatabaseException 当统计失败时
     */
    public function countRisks(array $conditions = []): int 
    {
        return $this->count($conditions);
    }

    // ================== 业务逻辑查询方法 ==================

    /**
     * 按状态查找风险
     * 
     * @param string $status 风险状态
     * @param array<string> $columns 要查询的列
     * @param array<string, string> $orderBy 排序条件
     * 
     * @return array<array<string, mixed>> 风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRisksByStatus(
        string $status, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['status' => $status], $columns, 0, 0, $orderBy);
    }

    /**
     * 查找高风险项目
     * 
     * 基于概率和影响计算的风险分数筛选高风险项目
     * 
     * @param int $threshold 风险阈值，默认15
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 高风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findHighRisks(int $threshold = 15, array $columns = ['*']): array 
    {
        // 使用自定义SQL查询复杂条件
        return $this->executeCustomQuery(
            "SELECT " . implode(', ', $columns) . " FROM {$this->getTableName()} 
             WHERE (probability * impact) >= :threshold 
             AND deleted_at IS NULL
             ORDER BY (probability * impact) DESC, created_at DESC",
            ['threshold' => $threshold]
        );
    }

    /**
     * 按日期范围查找风险
     * 
     * @param string $startDate 开始日期 (Y-m-d格式)
     * @param string $endDate 结束日期 (Y-m-d格式)
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRisksByDateRange(
        string $startDate, 
        string $endDate,
        array $columns = ['*']
    ): array {
        return $this->executeCustomQuery(
            "SELECT " . implode(', ', $columns) . " FROM {$this->getTableName()} 
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date 
             AND deleted_at IS NULL
             ORDER BY created_at DESC",
            [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        );
    }

    /**
     * 查找需要立即处理的紧急风险
     * 
     * 查找状态为'identified'且风险分数>=15的高优先级风险
     * 
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 紧急风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findUrgentRisks(array $columns = ['*']): array 
    {
        return $this->executeCustomQuery(
            "SELECT " . implode(', ', $columns) . " FROM {$this->getTableName()} 
             WHERE status = :status 
             AND (probability * impact) >= :min_score 
             AND deleted_at IS NULL
             ORDER BY (probability * impact) DESC, created_at ASC",
            [
                'status' => 'identified',
                'min_score' => 15
            ]
        );
    }

    /**
     * 按风险分数范围查找风险
     * 
     * @param int $minScore 最小分数
     * @param int $maxScore 最大分数
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRisksByScoreRange(
        int $minScore, 
        int $maxScore,
        array $columns = ['*']
    ): array {
        return $this->executeCustomQuery(
            "SELECT " . implode(', ', $columns) . " FROM {$this->getTableName()} 
             WHERE (probability * impact) BETWEEN :min_score AND :max_score 
             AND deleted_at IS NULL
             ORDER BY (probability * impact) DESC, created_at DESC",
            [
                'min_score' => $minScore,
                'max_score' => $maxScore
            ]
        );
    }

    /**
     * 按类别查找风险
     * 
     * @param string $category 风险类别
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRisksByCategory(string $category, array $columns = ['*']): array 
    {
        return $this->findBy(['category' => $category], $columns, 0, 0, ['created_at' => 'DESC']);
    }

    /**
     * 按负责人查找风险
     * 
     * @param string $owner 负责人
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 风险数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRisksByOwner(string $owner, array $columns = ['*']): array 
    {
        return $this->findBy(['owner' => $owner], $columns, 0, 0, ['created_at' => 'DESC']);
    }

    /**
     * 查找即将到期的风险
     * 
     * @param int $daysThreshold 天数阈值，默认7天
     * @param array<string> $columns 要查询的列
     * 
     * @return array<array<string, mixed>> 即将到期的风险数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findRisksDueSoon(int $daysThreshold = 7, array $columns = ['*']): array 
    {
        $futureDate = date('Y-m-d', strtotime("+{$daysThreshold} days"));
        
        return $this->executeCustomQuery(
            "SELECT " . implode(', ', $columns) . " FROM {$this->getTableName()} 
             WHERE due_date IS NOT NULL 
             AND due_date <= :future_date 
             AND status NOT IN ('closed', 'cancelled')
             AND deleted_at IS NULL
             ORDER BY due_date ASC",
            ['future_date' => $futureDate]
        );
    }

    // ================== 批量操作方法 ==================

    /**
     * 批量更新风险状态
     * 
     * 使用事务确保数据一致性
     * 
     * @param array<int> $ids 风险ID数组
     * @param string $status 新状态
     * 
     * @return int 受影响的记录数
     * 
     * @throws DatabaseException 当批量更新失败时
     */
    public function batchUpdateStatus(array $ids, string $status): int 
    {
        if (empty($ids)) {
            return 0;
        }

        return $this->transaction(function() use ($ids, $status) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status, date('Y-m-d H:i:s')], $ids);
            
            $result = $this->executeCustomQuery(
                "UPDATE {$this->getTableName()} 
                 SET status = ?, updated_at = ? 
                 WHERE id IN ({$placeholders}) AND deleted_at IS NULL",
                $params
            );
            
            Logger::info('批量更新风险状态', [
                'ids' => $ids,
                'new_status' => $status,
                'affected_count' => count($result)
            ]);
            
            return count($result);
        });
    }

    /**
     * 批量软删除风险
     * 
     * @param array<int> $ids 风险ID数组
     * 
     * @return int 受影响的记录数
     * 
     * @throws DatabaseException 当批量删除失败时
     */
    public function batchSoftDeleteRisks(array $ids): int 
    {
        if (empty($ids)) {
            return 0;
        }

        $conditions = [];
        $params = [];
        foreach ($ids as $index => $id) {
            $conditions[] = "id = :id_{$index}";
            $params["id_{$index}"] = $id;
        }
        
        return $this->batchUpdate(
            ['deleted_at' => date('Y-m-d H:i:s')],
            $params
        );
    }

    // ================== 统计和聚合方法 ==================

    /**
     * 获取风险统计信息
     * 
     * 包含总数、高风险数量、平均分数、各状态统计等
     * 
     * @return array<string, mixed> 统计信息
     * 
     * @throws DatabaseException 当统计失败时
     */
    public function getRiskStatistics(): array 
    {
        $stats = $this->executeCustomQuery(
            "SELECT 
                COUNT(*) as total_risks,
                SUM(CASE WHEN (probability * impact) >= 15 THEN 1 ELSE 0 END) as high_risk_count,
                SUM(CASE WHEN (probability * impact) >= 9 AND (probability * impact) < 15 THEN 1 ELSE 0 END) as medium_risk_count,
                SUM(CASE WHEN (probability * impact) < 9 THEN 1 ELSE 0 END) as low_risk_count,
                ROUND(AVG(probability * impact), 2) as avg_risk_score,
                MAX(probability * impact) as max_risk_score,
                MIN(probability * impact) as min_risk_score
             FROM {$this->getTableName()} 
             WHERE deleted_at IS NULL",
            []
        );

        $statusStats = $this->executeCustomQuery(
            "SELECT 
                status,
                COUNT(*) as count,
                ROUND(AVG(probability * impact), 2) as avg_score
             FROM {$this->getTableName()} 
             WHERE deleted_at IS NULL
             GROUP BY status
             ORDER BY count DESC",
            []
        );

        $categoryStats = $this->executeCustomQuery(
            "SELECT 
                category,
                COUNT(*) as count,
                ROUND(AVG(probability * impact), 2) as avg_score
             FROM {$this->getTableName()} 
             WHERE deleted_at IS NULL
             GROUP BY category
             ORDER BY count DESC",
            []
        );

        return [
            'summary' => $stats[0] ?? [],
            'by_status' => $statusStats,
            'by_category' => $categoryStats,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 获取风险趋势数据
     * 
     * 按月统计风险创建趋势
     * 
     * @param int $months 统计月数，默认12个月
     * 
     * @return array<array<string, mixed>> 趋势数据
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function getRiskTrendData(int $months = 12): array 
    {
        $startDate = date('Y-m-01', strtotime("-{$months} months"));
        
        return $this->executeCustomQuery(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_created,
                SUM(CASE WHEN (probability * impact) >= 15 THEN 1 ELSE 0 END) as high_risk_created,
                ROUND(AVG(probability * impact), 2) as avg_score
             FROM {$this->getTableName()} 
             WHERE created_at >= :start_date AND deleted_at IS NULL
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            ['start_date' => $startDate]
        );
    }

    // ================== 自定义查询辅助方法 ==================

    /**
     * 执行自定义SQL查询
     * 
     * 为复杂业务查询提供底层支持
     * 
     * @param string $sql SQL语句
     * @param array<string, mixed> $params 参数
     * 
     * @return array<array<string, mixed>> 查询结果
     * 
     * @throws DatabaseException 当查询失败时
     */
    private function executeCustomQuery(string $sql, array $params): array 
    {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $queryTime = microtime(true) - $startTime;
            
            Logger::debug('执行自定义风险查询', [
                'sql' => $sql,
                'params' => $this->sanitizeLogData($params),
                'result_count' => count($results),
                'query_time' => round($queryTime * 1000, 2) . 'ms'
            ]);
            
            return $results;
            
        } catch (\PDOException $e) {
            Logger::error('自定义风险查询失败', [
                'sql' => $sql,
                'params' => $this->sanitizeLogData($params),
                'error' => $e->getMessage()
            ]);
            
            throw DatabaseException::fromPDOException(
                $e,
                '执行风险查询失败',
                ['sql' => $sql]
            );
        }
    }

    /**
     * 清理敏感日志数据
     * 
     * @param array<string, mixed> $data 原始数据
     * 
     * @return array<string, mixed> 清理后的数据
     */
    private function sanitizeLogData(array $data): array 
    {
        // 风险模块暂无敏感字段，直接返回
        return $data;
    }

    // ================== 公共事务方法 ==================

    /**
     * 执行事务操作
     * 
     * 为外部调用者提供事务支持的公共接口
     * 
     * @param callable $callback 事务中执行的回调函数
     * 
     * @return mixed 回调函数的返回值
     * 
     * @throws DatabaseException|Throwable 当事务执行失败时
     * 
     * @example
     * ```php
     * $result = $riskRepository->executeTransaction(function($repo) {
     *     $id1 = $repo->createRisk($data1);
     *     $id2 = $repo->createRisk($data2);
     *     $repo->batchUpdateStatus([$id1, $id2], 'approved');
     *     return [$id1, $id2];
     * });
     * ```
     */
    public function executeTransaction(callable $callback) 
    {
        return $this->transaction($callback);
    }

    /**
     * 手动开启事务
     * 
     * @return void
     * 
     * @throws DatabaseException 当事务开启失败时
     */
    public function beginDatabaseTransaction(): void 
    {
        $this->beginTransaction();
    }

    /**
     * 手动提交事务
     * 
     * @return void
     * 
     * @throws DatabaseException 当事务提交失败时
     */
    public function commitDatabaseTransaction(): void 
    {
        $this->commit();
    }

    /**
     * 手动回滚事务
     * 
     * @return void
     * 
     * @throws DatabaseException 当事务回滚失败时
     */
    public function rollbackDatabaseTransaction(): void 
    {
        $this->rollback();
    }
}
