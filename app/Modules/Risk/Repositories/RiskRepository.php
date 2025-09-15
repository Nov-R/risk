<?php

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;
use App\Modules\Risk\Entities\Risk;

/**
 * 精简版风险仓储类 - 仅保留基础CRUD操作
 * 用于学习领域驱动设计架构
 */
class RiskRepository extends BaseRepository
{
    /**
     * 获取表名
     */
    protected function getTableName(): string 
    {
        return 'risks';
    }

    /**
     * 获取可填充字段
     */
    protected function getFillable(): array 
    {
        return [
            'title',
            'description', 
            'category',
            'priority_level',
            'impact_score',
            'probability_score',
            'risk_score',
            'status',
            'owner',
            'due_date',
            'mitigation_plan'
        ];
    }

    /**
     * 是否支持软删除
     */
    protected function supportsSoftDelete(): bool 
    {
        return true;
    }

    // ===========================================
    // 基础CRUD操作
    // ===========================================

    /**
     * 创建风险
     */
    public function createRisk(array $data): int 
    {
        return $this->create($data);
    }

    /**
     * 从实体创建风险（兼容ApplicationService）
     */
    public function createRiskFromEntity(Risk $risk): int 
    {
        return $this->create($risk->toArray());
    }

    /**
     * 根据ID查找风险
     */
    public function findRiskById(int $id): ?array 
    {
        return $this->findById($id);
    }

    /**
     * 查找所有风险
     */
    public function findAllRisks(): array 
    {
        return $this->findBy([]);
    }

    /**
     * 更新风险
     */
    public function updateRisk(int $id, array $data): bool 
    {
        return $this->update($id, $data);
    }

    /**
     * 删除风险（软删除）
     */
    public function deleteRisk(int $id): bool 
    {
        return $this->delete($id, true);
    }

    // ===========================================
    // 基础业务查询（学习用）
    // ===========================================

    /**
     * 根据状态查找风险
     */
    public function findRisksByStatus(string $status): array 
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * 查找高风险项目（风险分数>=15）
     */
    public function findHighRisks(): array 
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE risk_score >= 15 AND deleted_at IS NULL ORDER BY risk_score DESC";
        return $this->executeQuery($sql, [], 'SELECT')->fetchAll();
    }

    /**
     * 根据负责人查找风险
     */
    public function findRisksByOwner(string $owner): array 
    {
        return $this->findBy(['owner' => $owner]);
    }

    /**
     * 统计风险数量
     */
    public function countRisks(): int 
    {
        return $this->count();
    }

    /**
     * 检查风险是否存在
     */
    public function riskExists(int $id): bool 
    {
        return $this->exists(['id' => $id]);
    }

    // ===========================================
    // 事务管理方法（兼容ApplicationService）
    // ===========================================

    /**
     * 开始数据库事务
     */
    public function beginDatabaseTransaction(): void
    {
        $this->beginTransaction();
    }

    /**
     * 提交数据库事务
     */
    public function commitDatabaseTransaction(): void
    {
        $this->commit();
    }

    /**
     * 回滚数据库事务
     */
    public function rollbackDatabaseTransaction(): void
    {
        $this->rollback();
    }
}