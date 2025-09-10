<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Risk\Services\NodeService;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;

/**
 * 节点控制器类
 * 
 * 该类负责处理与风险管理流程节点相关的所有HTTP请求，包括：
 * - 创建新的流程节点
 * - 更新现有节点
 * - 删除节点记录
 * - 查询单个节点详情
 * - 查询与风险或反馈相关的节点
 * - 审批或拒绝节点
 * - 查询待审核的节点
 */
class NodeController {
    /** @var NodeService 节点服务实例 */
    private NodeService $service;
    
    /** @var Request HTTP请求处理实例 */
    private Request $request;

    /**
     * 构造函数
     * 
     * @param NodeService $service 节点服务实例
     */
    public function __construct(NodeService $service) {
        $this->service = $service;
        $this->request = new Request();
    }

    /**
     * 创建新的流程节点
     * 
     * 接收POST请求，创建新的流程节点记录。请求体应包含：
     * - type: 节点类型（risk_review: 风险审核, feedback_review: 反馈审核）
     * - risk_id: 关联的风险ID（可选）
     * - feedback_id: 关联的反馈ID（可选）
     * - reviewer: 审核者ID（可选）
     * - comments: 节点备注（可选）
    * @return void
    */
    public function create(): void {
        try {
            $data = $this->request->getBodyParam();
            $nodeId = $this->service->createNode($data);
            Response::success(['id' => $nodeId], '节点创建成功');
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('节点创建失败', ['error' => $e->getMessage()]);
            Response::error('创建节点失败', 500);
        }
    }

    /**
     * 更新流程节点
     * 
     * 接收PUT请求，更新指定ID的流程节点记录。请求体可包含：
     * - reviewer: 审核者ID
     * - comments: 节点备注
     * 注意：不能修改节点类型和关联的风险/反馈ID
     * 
    * @param int $id 节点记录ID
    * @return void
     */
    public function update(int $id): void {
        try {
            $data = $this->request->getBodyParam();
            $this->service->updateNode($id, $data);
            Response::success(null, '节点更新成功');
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('节点更新失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('更新节点失败', 500);
        }
    }

    /**
     * 删除流程节点
     * 
     * 接收DELETE请求，删除指定ID的流程节点记录。
     * 只能删除状态为pending的节点，已审批或拒绝的节点不能删除。
     * 
    * @param int $id 要删除的节点记录ID
    * @return void
     */
    public function delete(int $id): void {
        try {
            $this->service->deleteNode($id);
            Response::success(null, '节点删除成功');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('节点删除失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('删除节点失败', 500);
        }
    }

    /**
     * 获取单个流程节点详情
     * 
     * 接收GET请求，返回指定ID的流程节点记录的详细信息，
     * 包括节点类型、状态、关联实体、审核信息和时间戳等。
     * 
    * @param int $id 要查询的节点记录ID
    * @return void
     */
    public function get(int $id): void {
        try {
            $node = $this->service->getNode($id);
            if (!$node) {
                Response::error('未找到指定节点', 404);
                return;
            }
            Response::success($node);
        } catch (\Exception $e) {
            Logger::error('节点获取失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('获取节点信息失败', 500);
        }
    }

    /**
     * 获取风险相关的所有流程节点
     * 
     * 接收GET请求，返回与指定风险ID关联的所有流程节点记录。
     * 结果包含所有状态的节点，按创建时间倒序排列。
     * 
    * @param int $riskId 风险记录ID
    * @return void
     */
    public function getByRisk(int $riskId): void {
        try {
            $nodes = $this->service->getNodesByRisk($riskId);
            Response::success($nodes);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('获取风险相关节点失败', ['risk_id' => $riskId, 'error' => $e->getMessage()]);
            Response::error('获取风险相关节点失败', 500);
        }
    }

    /**
     * 获取反馈相关的所有流程节点
     * 
     * 接收GET请求，返回与指定反馈ID关联的所有流程节点记录。
     * 结果包含所有状态的节点，按创建时间倒序排列。
     * 
    * @param int $feedbackId 反馈记录ID
    * @return void
     */
    public function getByFeedback(int $feedbackId): void {
        try {
            $nodes = $this->service->getNodesByFeedback($feedbackId);
            Response::success($nodes);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('获取反馈相关节点失败', ['feedback_id' => $feedbackId, 'error' => $e->getMessage()]);
            Response::error('获取反馈相关节点失败', 500);
        }
    }

    /**
     * 审批通过流程节点
     * 
     * 接收POST请求，将指定ID的流程节点标记为已通过。
     * 请求体可包含：
     * - reviewer: 审核者ID
     * - comments: 审批意见（可选）
     * 
    * @param int $id 要审批的节点记录ID
    * @return void
     */
    public function approve(int $id): void {
        try {
            $data = $this->request->getBodyParam();
            $comments = $data['comments'] ?? null;
            $reviewer = $data['reviewer'] ?? 'system';  // 默认审核者为system
            $this->service->approveNode($id, $reviewer, $comments);
            Response::success(null, '节点审批通过');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('节点审批失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('节点审批失败', 500);
        }
    }

    /**
     * 拒绝流程节点
     * 
     * 接收POST请求，将指定ID的流程节点标记为已拒绝。
     * 请求体必须包含：
     * - reviewer: 审核者ID
     * - comments: 拒绝原因（必填）
     * 
    * @param int $id 要拒绝的节点记录ID
    * @return void
     */
    public function reject(int $id): void {
        try {
            $data = $this->request->getBodyParam();
            if (empty($data['comments'])) {
                Response::validationError(['comments' => '拒绝时必须提供备注说明']);
                return;
            }
            $comments = $data['comments'];
            $reviewer = $data['reviewer'] ?? 'system';  // 默认审核者为system
            $this->service->rejectNode($id, $reviewer, $comments);
            Response::success(null, '节点已拒绝');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('节点拒绝失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('节点拒绝失败', 500);
        }
    }

    /**
     * 获取待审核的流程节点列表
     * 
     * 接收GET请求，返回指定类型的所有待审核节点记录。
     * 类型可以是：
     * - risk_review: 风险审核节点
     * - feedback_review: 反馈审核节点
     * 结果按创建时间排序，优先显示等待时间较长的节点。
     * 
    * @param string $type 节点类型
    * @return void
     */
    public function getPendingReviews(string $type): void {
        try {
            $nodes = $this->service->getPendingReviews($type);
            Response::success($nodes);
        } catch (\Exception $e) {
            Logger::error('获取待审核列表失败', ['type' => $type, 'error' => $e->getMessage()]);
            Response::error('获取待审核列表失败', 500);
        }
    }
}
