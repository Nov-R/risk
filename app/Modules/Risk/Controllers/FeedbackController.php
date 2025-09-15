<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Risk\Services\FeedbackService;

/**
 * 精简版反馈控制器 - 基础REST API
 */
class FeedbackController extends BaseController
{
    private FeedbackService $feedbackService;

    public function __construct(Request $request, FeedbackService $feedbackService)
    {
        parent::__construct($request);
        $this->feedbackService = $feedbackService;
    }

    /**
     * 获取所有反馈
     * GET /api/feedbacks
     */
    public function index(): void
    {
        try {
            $feedbacks = $this->feedbackService->getAllFeedbacks();
            
            Response::success([
                'feedbacks' => array_map(fn($feedback) => $feedback->toArray(), $feedbacks),
                'total' => count($feedbacks)
            ]);
        } catch (\Exception $e) {
            Response::error('获取反馈列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取单个反馈
     * GET /api/feedbacks/{id}
     */
    public function show(int $id): void
    {
        try {
            $feedback = $this->feedbackService->getFeedback($id);
            
            if (!$feedback) {
                Response::error('反馈不存在', 404);
                return;
            }
            
            Response::success(['feedback' => $feedback->toArray()]);
        } catch (\Exception $e) {
            Response::error('获取反馈详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建反馈
     * POST /api/feedbacks
     */
    public function store(): void
    {
        try {
            $data = $this->getBodyParam();
            $feedbackId = $this->feedbackService->createFeedback($data);
            
            Response::success(['feedback_id' => $feedbackId], '反馈创建成功', 201);
        } catch (\Exception $e) {
            Response::error('创建反馈失败: ' . $e->getMessage(), 400);
        }
    }

    /**
     * 更新反馈
     * PUT /api/feedbacks/{id}
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getBodyParam();
            $success = $this->feedbackService->updateFeedback($id, $data);
            
            if (!$success) {
                Response::error('反馈不存在或更新失败', 404);
                return;
            }
            
            Response::success(null, '反馈更新成功');
        } catch (\Exception $e) {
            Response::error('更新反馈失败: ' . $e->getMessage(), 400);
        }
    }

    /**
     * 删除反馈
     * DELETE /api/feedbacks/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->feedbackService->deleteFeedback($id);
            
            if (!$success) {
                Response::error('反馈不存在或删除失败', 404);
                return;
            }
            
            Response::success(null, '反馈删除成功');
        } catch (\Exception $e) {
            Response::error('删除反馈失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 根据风险ID获取反馈
     * GET /api/feedbacks/risk/{riskId}
     */
    public function byRisk(int $riskId): void
    {
        try {
            $feedbacks = $this->feedbackService->getFeedbacksByRisk($riskId);
            
            Response::success([
                'feedbacks' => array_map(fn($feedback) => $feedback->toArray(), $feedbacks),
                'risk_id' => $riskId,
                'total' => count($feedbacks)
            ]);
        } catch (\Exception $e) {
            Response::error('根据风险查询反馈失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 根据节点ID获取反馈
     * GET /api/feedbacks/node/{nodeId}
     */
    public function byNode(int $nodeId): void
    {
        try {
            $feedbacks = $this->feedbackService->getFeedbacksByNode($nodeId);
            
            Response::success([
                'feedbacks' => array_map(fn($feedback) => $feedback->toArray(), $feedbacks),
                'node_id' => $nodeId,
                'total' => count($feedbacks)
            ]);
        } catch (\Exception $e) {
            Response::error('根据节点查询反馈失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取高优先级反馈
     * GET /api/feedbacks/high-priority
     */
    public function highPriority(): void
    {
        try {
            $feedbacks = $this->feedbackService->getHighPriorityFeedbacks();
            
            Response::success([
                'feedbacks' => array_map(fn($feedback) => $feedback->toArray(), $feedbacks),
                'total' => count($feedbacks)
            ]);
        } catch (\Exception $e) {
            Response::error('获取高优先级反馈失败: ' . $e->getMessage(), 500);
        }
    }
}