<?php

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;

/**
 * 精简版节点仓储类 - 仅保留基础CRUD操作
 */
class NodeRepository extends BaseRepository
{
    protected function getTableName(): string 
    {
        return 'nodes';
    }

    protected function getFillable(): array 
    {
        return [
            'name',
            'description',
            'type',
            'status',
            'parent_id',
            'sort_order'
        ];
    }

    protected function supportsSoftDelete(): bool 
    {
        return true;
    }

    // ===========================================
    // 基础CRUD操作
    // ===========================================

    public function createNode(array $data): int 
    {
        return $this->create($data);
    }

    public function findNodeById(int $id): ?array 
    {
        return $this->findById($id);
    }

    public function findAllNodes(): array 
    {
        return $this->findBy([]);
    }

    public function updateNode(int $id, array $data): bool 
    {
        return $this->update($id, $data);
    }

    public function deleteNode(int $id): bool 
    {
        return $this->delete($id, true);
    }

    // ===========================================
    // 基础业务查询
    // ===========================================

    public function findNodesByType(string $type): array 
    {
        return $this->findBy(['type' => $type]);
    }

    public function findChildNodes(int $parentId): array 
    {
        return $this->findBy(['parent_id' => $parentId], ['*'], 0, 0, ['sort_order' => 'ASC']);
    }

    public function findRootNodes(): array 
    {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE parent_id IS NULL AND deleted_at IS NULL ORDER BY sort_order ASC";
        return $this->executeQuery($sql, [], 'SELECT')->fetchAll();
    }

    public function countNodes(): int 
    {
        return $this->count();
    }

    public function nodeExists(int $id): bool 
    {
        return $this->exists(['id' => $id]);
    }

    /**
     * 根据风险ID查找相关节点（兼容ApplicationService）
     */
    public function findNodesByRiskId(int $riskId): array 
    {
        // 简化版本：假设通过中间表或外键关联
        $sql = "SELECT * FROM {$this->getTableName()} WHERE risk_id = :risk_id AND deleted_at IS NULL";
        return $this->executeQuery($sql, ['risk_id' => $riskId], 'SELECT')->fetchAll();
    }
}