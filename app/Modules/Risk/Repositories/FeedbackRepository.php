<?php

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;

/**
 * 精简版反馈仓储类 - 仅保留基础CRUD操作
 */
class FeedbackRepository extends BaseRepository
{
    protected function getTableName(): string 
    {
        return 'feedbacks';
    }

    protected function getFillable(): array 
    {
        return [
            'content',
            'type',
            'priority',
            'risk_id',
            'node_id',
            'user_id',
            'status'
        ];
    }

    protected function supportsSoftDelete(): bool 
    {
        return true;
    }

    // ===========================================
    // 基础CRUD操作
    // ===========================================

    public function createFeedback(array $data): int 
    {
        return $this->create($data);
    }

    public function findFeedbackById(int $id): ?array 
    {
        return $this->findById($id);
    }

    public function findAllFeedbacks(): array 
    {
        return $this->findBy([]);
    }

    public function updateFeedback(int $id, array $data): bool 
    {
        return $this->update($id, $data);
    }

    public function deleteFeedback(int $id): bool 
    {
        return $this->delete($id, true);
    }

    // ===========================================
    // 基础业务查询
    // ===========================================

    public function findFeedbacksByRisk(int $riskId): array 
    {
        return $this->findBy(['risk_id' => $riskId], ['*'], 0, 0, ['created_at' => 'DESC']);
    }

    public function findFeedbacksByNode(int $nodeId): array 
    {
        return $this->findBy(['node_id' => $nodeId], ['*'], 0, 0, ['created_at' => 'DESC']);
    }

    public function findFeedbacksByType(string $type): array 
    {
        return $this->findBy(['type' => $type]);
    }

    public function findHighPriorityFeedbacks(): array 
    {
        return $this->findBy(['priority' => 'high'], ['*'], 0, 0, ['created_at' => 'DESC']);
    }

    public function countFeedbacks(): int 
    {
        return $this->count();
    }

    public function feedbackExists(int $id): bool 
    {
        return $this->exists(['id' => $id]);
    }

    /**
     * 根据风险ID查找反馈（兼容ApplicationService）
     */
    public function findFeedbacksByRiskId(int $riskId): array 
    {
        return $this->findFeedbacksByRisk($riskId);
    }
}