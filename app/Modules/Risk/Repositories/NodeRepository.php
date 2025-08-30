<?php

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;
use App\Modules\Risk\Entities\Node;
use PDO;

class NodeRepository extends BaseRepository {
    /**
     * 获取数据表名称
     *
     * @return string 表名
     */
    protected function getTableName(): string {
        return 'nodes';
    }

    /**
     * 创建节点记录
     *
     * @param Node $node 节点实体对象
     * @return int 新创建记录的 ID
     */
    public function createNode(Node $node): int {
        return $this->create($node->toArray());
    }

    /**
     * 更新指定 ID 的节点（部分更新）
     *
     * @param int $id 节点 ID
     * @param array $data 要更新的数据数组（部分字段）
     * @return bool 是否更新成功
     */
    public function updateNode(int $id, array $data): bool {
        return $this->update($id, $data);
    }

    /**
     * 删除指定 ID 的节点
     *
     * @param int $id 节点 ID
     * @return bool 是否删除成功
     */
    public function deleteNode(int $id): bool {
        return $this->delete($id);
    }

    /**
     * 根据 ID 查找节点实体
     *
     * @param int $id 节点 ID
     * @return Node|null 找到返回 Node 实体，未找到返回 null
     */
    public function findNodeById(int $id): ?Node {
        $data = $this->findById($id);
        return $data ? Node::fromArray($data) : null;
    }

    /**
     * 获取所有节点实体
     *
     * @return Node[] 节点实体数组
     */
    public function findAllNodes(): array {
        $nodes = $this->findAll();
        return array_map([Node::class, 'fromArray'], $nodes);
    }

    /**
     * 根据风险 ID 查找节点列表
     *
     * @param int $riskId 风险 ID
     * @return Node[] 节点实体数组
     */
    public function findNodesByRiskId(int $riskId): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE risk_id = ?";
        $stmt = $this->query($sql, [$riskId]);
        $nodes = $stmt->fetchAll();
        
        return array_map([Node::class, 'fromArray'], $nodes);
    }

    /**
     * 根据反馈 ID 查找节点列表
     *
     * @param int $feedbackId 反馈 ID
     * @return Node[] 节点实体数组
     */
    public function findNodesByFeedbackId(int $feedbackId): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE feedback_id = ?";
        $stmt = $this->query($sql, [$feedbackId]);
        $nodes = $stmt->fetchAll();
        
        return array_map([Node::class, 'fromArray'], $nodes);
    }

    /**
     * 根据状态查找节点列表
     *
     * @param string $status 节点状态
     * @return Node[] 节点实体数组
     */
    public function findNodesByStatus(string $status): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE status = ?";
        $stmt = $this->query($sql, [$status]);
        $nodes = $stmt->fetchAll();
        
        return array_map([Node::class, 'fromArray'], $nodes);
    }

    /**
     * 查找指定类型且处于待审的节点
     *
     * @param string $type 节点类型
     * @return Node[] 节点实体数组
     */
    public function findPendingNodesByType(string $type): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE type = ? AND status = 'pending'";
        $stmt = $this->query($sql, [$type]);
        $nodes = $stmt->fetchAll();
        
        return array_map([Node::class, 'fromArray'], $nodes);
    }
}
