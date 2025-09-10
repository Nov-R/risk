<?php

/**
 * 反馈仓储类
 * 
 * 处理与反馈相关的所有数据库操作，利用BaseRepository提供的企业级功能
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
use App\Modules\Risk\Entities\Feedback;
use App\Core\Utils\Logger;

class FeedbackRepository extends BaseRepository 
{
    /**
     * 获取数据表名称
     *
     * @return string 反馈数据表名
     */
    protected function getTableName(): string 
    {
        return 'feedbacks';
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
            'content',
            'type',
            'status',
            'created_by'
        ];
    }

    /**
     * 是否支持软删除
     * 
     * 反馈记录支持软删除以保持历史追踪
     * 
     * @return bool
     */
    protected function supportsSoftDelete(): bool 
    {
        return true;
    }

    // ================== 基础CRUD操作 ==================

    /**
     * 创建新反馈记录
     * 
     * 利用BaseRepository的自动时间戳、字段验证等功能
     * 
     * @param array<string, mixed> $data 反馈数据
     * 
     * @return int 新创建的反馈ID
     * 
     * @throws DatabaseException 当创建失败时
     * 
     * @example
     * ```php
     * $feedbackId = $feedbackRepository->createFeedback([
     *     'risk_id' => 1,
     *     'content' => '这个风险需要更多关注',
     *     'type' => 'suggestion',
     *     'created_by' => 'user123',
     *     'status' => 'pending'
     * ]);
     * ```
     */
    public function createFeedback(array $data): int 
    {
        return $this->create($data);
    }

    /**
     * 通过实体对象创建反馈记录
     * 
     * 提供实体对象接口，内部转换为数组调用基础方法
     * 
     * @param Feedback $feedback 反馈实体对象
     * @return int 新创建的反馈ID
     * 
     * @throws DatabaseException 当创建失败时
     */
    public function createFeedbackFromEntity(Feedback $feedback): int 
    {
        return $this->createFeedback($feedback->toArray());
    }

    /**
     * 更新反馈（部分更新）
     * 
     * @param int $id 反馈ID
     * @param array<string, mixed> $data 要更新的数据数组（部分字段）
     * @return bool 是否更新成功
     * 
     * @throws DatabaseException 当更新失败时
     */
    public function updateFeedback(int $id, array $data): bool 
    {
        return $this->update($id, $data);
    }

    /**
     * 删除反馈
     * 
     * @param int $id 反馈ID
     * @return bool 是否删除成功
     * 
     * @throws DatabaseException 当删除失败时
     */
    public function deleteFeedback(int $id): bool 
    {
        return $this->delete($id);
    }

    /**
     * 通过ID查找反馈
     * 
     * @param int $id 反馈ID
     * @param array<string> $columns 要查询的字段，默认为所有字段
     * @return array<string, mixed>|null 反馈数据，未找到返回null
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findFeedbackById(int $id, array $columns = ['*']): ?array 
    {
        return $this->findById($id, $columns);
    }

    /**
     * 获取所有反馈记录
     * 
     * @param array<string> $columns 要查询的字段，默认为所有字段
     * @param array<string, string> $orderBy 排序规则，默认按创建时间降序
     * @return array<array<string, mixed>> 反馈数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findAllFeedbacks(array $columns = ['*'], array $orderBy = ['created_at' => 'DESC']): array 
    {
        return $this->findBy([], $columns, 0, 0, $orderBy);
    }

    /**
     * 检查反馈是否存在
     * 
     * @param int $id 反馈ID
     * @return bool 是否存在
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function feedbackExists(int $id): bool 
    {
        return $this->exists(['id' => $id]);
    }

    /**
     * 根据风险ID查找反馈
     * 
     * @param int $riskId 风险ID
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 反馈数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findFeedbacksByRiskId(
        int $riskId, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['risk_id' => $riskId], $columns, 0, 0, $orderBy);
    }

    /**
     * 根据状态查找反馈
     * 
     * @param string $status 反馈状态
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 反馈数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findFeedbacksByStatus(
        string $status, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['status' => $status], $columns, 0, 0, $orderBy);
    }

    /**
     * 根据反馈类型查找反馈
     * 
     * @param string $type 反馈类型
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 反馈数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findFeedbacksByType(
        string $type, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['type' => $type], $columns, 0, 0, $orderBy);
    }

    /**
     * 根据创建者查找反馈
     * 
     * @param string $createdBy 创建者
     * @param array<string> $columns 要查询的字段
     * @param array<string, string> $orderBy 排序规则
     * @return array<array<string, mixed>> 反馈数据数组
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function findFeedbacksByCreator(
        string $createdBy, 
        array $columns = ['*'], 
        array $orderBy = ['created_at' => 'DESC']
    ): array {
        return $this->findBy(['created_by' => $createdBy], $columns, 0, 0, $orderBy);
    }

    /**
     * 批量创建反馈记录
     * 
     * 利用BaseRepository的批量插入优化功能
     * 
     * @param array<array<string, mixed>> $feedbackDataList 反馈数据列表
     * @param int $batchSize 批处理大小，默认50条
     * @return array<int> 新创建的反馈ID列表
     * 
     * @throws DatabaseException 当批量创建失败时
     */
    public function batchCreateFeedbacks(array $feedbackDataList, int $batchSize = 50): array 
    {
        return $this->batchCreate($feedbackDataList, $batchSize);
    }

    /**
     * 根据复合条件查询反馈统计信息
     * 
     * @param array<string, mixed> $conditions 查询条件
     * @return array<string, int> 统计信息
     * 
     * @throws DatabaseException 当查询失败时
     */
    public function getFeedbackStatistics(array $conditions = []): array 
    {
        $stats = [];
        
        // 总数统计
        $stats['total'] = $this->count($conditions);
        
        // 按状态统计
        foreach (['pending', 'reviewed', 'resolved', 'rejected'] as $status) {
            $statusConditions = array_merge($conditions, ['status' => $status]);
            $stats["status_{$status}"] = $this->count($statusConditions);
        }
        
        // 按类型统计
        foreach (['suggestion', 'concern', 'question', 'issue'] as $type) {
            $typeConditions = array_merge($conditions, ['type' => $type]);
            $stats["type_{$type}"] = $this->count($typeConditions);
        }
        
        return $stats;
    }
}
