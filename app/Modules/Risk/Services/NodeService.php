<?php

namespace App\Modules\Risk\Services;

use App\Modules\Risk\Repositories\NodeRepository;
use App\Modules\Risk\Entities\Node;
use App\Core\Exceptions\ValidationException;

/**
 * 精简版节点服务类 - 基础业务逻辑
 */
class NodeService
{
    private NodeRepository $nodeRepository;

    public function __construct(NodeRepository $nodeRepository) 
    {
        $this->nodeRepository = $nodeRepository;
    }

    // ===========================================
    // 基础CRUD业务方法
    // ===========================================

    public function createNode(array $data): int 
    {
        $this->validateNodeData($data);
        return $this->nodeRepository->createNode($data);
    }

    public function getNode(int $id): ?Node 
    {
        $data = $this->nodeRepository->findNodeById($id);
        
        if (!$data) {
            return null;
        }
        
        return Node::fromArray($data);
    }

    public function getAllNodes(): array 
    {
        $nodesData = $this->nodeRepository->findAllNodes();
        return array_map(fn($data) => Node::fromArray($data), $nodesData);
    }

    public function updateNode(int $id, array $data): bool 
    {
        $this->validateNodeData($data, false);
        return $this->nodeRepository->updateNode($id, $data);
    }

    public function deleteNode(int $id): bool 
    {
        return $this->nodeRepository->deleteNode($id);
    }

    // ===========================================
    // 业务查询方法
    // ===========================================

    public function getNodesByType(string $type): array 
    {
        $nodesData = $this->nodeRepository->findNodesByType($type);
        return array_map(fn($data) => Node::fromArray($data), $nodesData);
    }

    public function getChildNodes(int $parentId): array 
    {
        $nodesData = $this->nodeRepository->findChildNodes($parentId);
        return array_map(fn($data) => Node::fromArray($data), $nodesData);
    }

    public function getRootNodes(): array 
    {
        $nodesData = $this->nodeRepository->findRootNodes();
        return array_map(fn($data) => Node::fromArray($data), $nodesData);
    }

    // ===========================================
    // 辅助方法
    // ===========================================

    private function validateNodeData(array $data, bool $isCreate = true): void 
    {
        $errors = [];

        if ($isCreate) {
            if (empty($data['name'])) {
                $errors['name'] = '节点名称不能为空';
            }
            if (empty($data['type'])) {
                $errors['type'] = '节点类型不能为空';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}