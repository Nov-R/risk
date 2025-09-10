<?php

/**
 * 流程节点仓储类
 * 
 * 处理与流程节点相关的所有数据库操作，利用BaseRepository提供的企业级功能
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
use App\Modules\Risk\Entities\Node;
use App\Core\Utils\Logger;

class NodeRepository extends BaseRepository 
{
    /**
     * 获取数据表名称
     *
     * @return string 节点数据表名
     */
    protected function getTableName(): string 
    {
        return 'nodes';
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
            'risk_id',
            'feedback_id', 
            'type',
            'status',
            'reviewer',
            'comments'
        ];
    }

    /**
     * 是否支持软删除
     * 
     * 节点记录支持软删除以保持审核历史追踪
     * 
     * @return bool
     */
    protected function supportsSoftDelete(): bool 
    {
        return true;
    }

    // ================== 基础CRUD操作 ==================

    /**
     * 创建新节点记录
     * 
     * 利用BaseRepository的自动时间戳、字段验证等功能
     * 
     * @param array<string, mixed> $data 节点数据
     * 
     * @return int 新创建的节点ID
     * 
     * @throws DatabaseException 当创建失败时
     * 
     * @example
     * ```php
     * $nodeId = $nodeRepository->createNode([
     *     'risk_id' => 1,
     *     'type' => 'risk_review',
     *     'reviewer' => 'admin',
     *     'status' => 'pending'
     * ]);
     * ```
     */
    public function createNode(array $data): int 
    {
        return $this->create($data);
    }

    /**
     * 通过实体对象创建节点记录
     * 
     * 提供实体对象接口，内部转换为数组调用基础方法
     * 
     * @param Node $node 节点实体对象
     * @return int 新创建的节点ID
     * 
     * @throws DatabaseException 当创建失败时
     */
    public function createNodeFromEntity(Node $node): int 
    {
        return $this->createNode($node->toArray());
    }

    /**
     * 更新指定 ID 的节点（部分更新）
     *
     * @param int $id 节点 ID
     * @param array<string, mixed> $data 要更新的数据数组（部分字段）
     * @return bool 是否更新成功
     * 
     * @throws DatabaseException 当更新失败时
     */
    public function updateNode(int $id, array $data): bool 
    {
        return $this->update($id, $data);
    }

    /**
     * 删除指定 ID 的节点
     *
     * @param int $id 节点 ID
     * @return bool 是否删除成功
     * 
     * @throws DatabaseException 当删除失败时
     */
    public function deleteNode(int $id): bool 
    {
        return $this->delete($id);
    }

    /**
     * 根据 ID 查找节点
     *
     * @param int $id 节点 ID
     * @param array<string> $columns 要查询的字段，默认为所有字段
     * @return array<string, mixed>|null 节点数据，未找到返回 null
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findNodeById(int $id, array $columns = ['*']): ?array 
    {
        return $this->findById($id, $columns);
    }

    /**
     * 获取所有节点记录
     * 
     * @param array<string> $columns 要查询的字段，默认为所有字段
     * @param array<string, string> $orderBy 排序规则，默认按创建时间降序
     * @return array<array<string, mixed>> 节点数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findAllNodes(array $columns = ['*'], array $orderBy = ['created_at' => 'DESC']): array 
    {
        return $this->findBy([], $columns, 0, 0, $orderBy);
    }

    /**
     * 检查节点是否存在
     * 
     * @param int $id 节点ID
     * @return bool 是否存在
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function nodeExists(int $id): bool 
    {
        return $this->exists(['id' => $id]);
    }

    /**
     * 根据风险 ID 查找节点列表
     *
     * @param int $riskId 风险 ID
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 节点数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findNodesByRiskId(
        int $riskId, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['risk_id' => $riskId], $columns, 0, 0, $orderBy);
    }

    /**
     * 根据反馈 ID 查找节点列表
     *
     * @param int $feedbackId 反馈 ID
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 节点数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findNodesByFeedbackId(
        int $feedbackId, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['feedback_id' => $feedbackId], $columns, 0, 0, $orderBy);
    }

    /**
     * 根据状态查找节点列表
     *
     * @param string $status 节点状态
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 节点数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findNodesByStatus(
        string $status, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['status' => $status], $columns, 0, 0, $orderBy);
    }

    /**
     * 根据节点类型查找节点列表
     *
     * @param string $type 节点类型
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 节点数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findNodesByType(
        string $type, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['type' => $type], $columns, 0, 0, $orderBy);
    }

    /**
     * 查找指定类型且处于待审核状态的节点
     *
     * @param string $type 节点类型
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 节点数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findPendingNodesByType(
        string $type, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'ASC']
    ): array {
        return $this->findBy([
            'type' => $type, 
            'status' => 'pending'
        ], $columns, 0, 0, $orderBy);
    }

    /**
     * 批量创建节点记录
     * 
     * 利用BaseRepository的批量插入优化功能
     * 
     * @param array<array<string, mixed>> $nodeDataList 节点数据列表
     * @param int $batchSize 批处理大小，默认50条
     * @return array<int> 新创建的节点ID列表
     * 
     * @throws DatabaseException 当批量创建失败时
     */
    public function batchCreateNodes(array $nodeDataList, int $batchSize = 50): array 
    {
        return $this->batchCreate($nodeDataList, $batchSize);
    }

    /**
     * 根据复合条件查询节点统计信息
     * 
     * @param array<string, mixed> $conditions 查询条件
     * @return array<string, int> 统计信息
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function getNodeStatistics(array $conditions = []): array 
    {
        $stats = [];
        
        // 总数统计
        $stats['total'] = $this->count($conditions);
        
        // 按状态统计
        foreach (['pending', 'approved', 'rejected'] as $status) {
            $statusConditions = array_merge($conditions, ['status' => $status]);
            $stats["status_{$status}"] = $this->count($statusConditions);
        }
        
        // 按类型统计
        foreach (['risk_review', 'feedback_review'] as $type) {
            $typeConditions = array_merge($conditions, ['type' => $type]);
            $stats["type_{$type}"] = $this->count($typeConditions);
        }
        
        return $stats;
    }
}
