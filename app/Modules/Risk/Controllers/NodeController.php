<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Risk\Services\NodeService;

/**
 * 精简版节点控制器 - 基础REST API
 */
class NodeController extends BaseController
{
    private NodeService $nodeService;

    public function __construct(Request $request, NodeService $nodeService)
    {
        parent::__construct($request);
        $this->nodeService = $nodeService;
    }

    /**
     * 获取所有节点
     * GET /api/nodes
     */
    public function index(): void
    {
        try {
            $nodes = $this->nodeService->getAllNodes();
            
            Response::success([
                'nodes' => array_map(fn($node) => $node->toArray(), $nodes),
                'total' => count($nodes)
            ]);
        } catch (\Exception $e) {
            Response::error('获取节点列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取单个节点
     * GET /api/nodes/{id}
     */
    public function show(int $id): void
    {
        try {
            $node = $this->nodeService->getNode($id);
            
            if (!$node) {
                Response::error('节点不存在', 404);
                return;
            }
            
            Response::success(['node' => $node->toArray()]);
        } catch (\Exception $e) {
            Response::error('获取节点详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建节点
     * POST /api/nodes
     */
    public function store(): void
    {
        try {
            $data = $this->getBodyParam();
            $nodeId = $this->nodeService->createNode($data);
            
            Response::success(['node_id' => $nodeId], '节点创建成功', 201);
        } catch (\Exception $e) {
            Response::error('创建节点失败: ' . $e->getMessage(), 400);
        }
    }

    /**
     * 更新节点
     * PUT /api/nodes/{id}
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getBodyParam();
            $success = $this->nodeService->updateNode($id, $data);
            
            if (!$success) {
                Response::error('节点不存在或更新失败', 404);
                return;
            }
            
            Response::success(null, '节点更新成功');
        } catch (\Exception $e) {
            Response::error('更新节点失败: ' . $e->getMessage(), 400);
        }
    }

    /**
     * 删除节点
     * DELETE /api/nodes/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->nodeService->deleteNode($id);
            
            if (!$success) {
                Response::error('节点不存在或删除失败', 404);
                return;
            }
            
            Response::success(null, '节点删除成功');
        } catch (\Exception $e) {
            Response::error('删除节点失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 根据类型获取节点
     * GET /api/nodes/type/{type}
     */
    public function byType(string $type): void
    {
        try {
            $nodes = $this->nodeService->getNodesByType($type);
            
            Response::success([
                'nodes' => array_map(fn($node) => $node->toArray(), $nodes),
                'type' => $type,
                'total' => count($nodes)
            ]);
        } catch (\Exception $e) {
            Response::error('根据类型查询节点失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取根节点
     * GET /api/nodes/root
     */
    public function root(): void
    {
        try {
            $nodes = $this->nodeService->getRootNodes();
            
            Response::success([
                'nodes' => array_map(fn($node) => $node->toArray(), $nodes),
                'total' => count($nodes)
            ]);
        } catch (\Exception $e) {
            Response::error('获取根节点失败: ' . $e->getMessage(), 500);
        }
    }
}