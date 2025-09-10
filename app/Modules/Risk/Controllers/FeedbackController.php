<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Risk\Services\FeedbackService;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;

/**
 * 反馈控制器类
 * 
 * 该类负责处理与风险反馈相关的所有HTTP请求，包括：
 * - 创建新的反馈
 * - 更新现有反馈
 * - 删除反馈记录
 * - 查询单个反馈详情
 * - 查询特定风险的所有反馈
 * - 按状态查询反馈
 */
class FeedbackController extends BaseController {
    /** @var FeedbackService 反馈服务实例 */
    private FeedbackService $service;

    /**
     * 构造函数
     * 
     * @param Request $request HTTP请求实例
     * @param FeedbackService $service 反馈服务实例
     */
    public function __construct(Request $request, FeedbackService $service) {
        parent::__construct($request);
        $this->service = $service;
    }

    /**
     * 创建新的反馈记录
     * 
     * 接收POST请求，创建新的反馈记录。请求体应包含：
     * - risk_id: 关联的风险ID
     * - content: 反馈内容
     * - type: 反馈类型（comment: 评论, suggestion: 建议, concern: 顾虑）
     * - created_by: 创建者ID或名称
    * @return void
    */
    public function create(): void {
        try {
            $data = $this->getBodyParam();
            $feedbackId = $this->service->createFeedback($data);
            Response::success(['id' => $feedbackId], '反馈创建成功');
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('反馈创建失败', ['error' => $e->getMessage()]);
            Response::error('创建反馈失败', 500);
        }
    }

    /**
     * 更新反馈记录
     * 
     * 接收PUT请求，更新指定ID的反馈记录。请求体可包含：
     * - content: 反馈内容
     * - type: 反馈类型（除非原始类型为general）
     * - status: 反馈状态（open: 开放, closed: 已关闭, resolved: 已解决）
     * 注意：不能修改关联的风险ID
     */
    public function update(): void {
        try {
            $id = (int)$this->getParam('id');
            $data = $this->getBodyParam();
            $this->service->updateFeedback($id, $data);
            Response::success(null, '反馈更新成功');
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('反馈更新失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('更新反馈失败', 500);
        }
    }

    /**
     * 删除反馈记录
     * 
     * 接收DELETE请求，删除指定ID的反馈记录。
     * 如果该反馈已经与节点关联，需要先解除关联才能删除。
     * 删除反馈不会影响关联的风险记录。
     */
    public function delete(): void {
        try {
            $id = (int)$this->getParam('id');
            $this->service->deleteFeedback($id);
            Response::success(null, '反馈删除成功');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('反馈删除失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('删除反馈失败', 500);
        }
    }

    /**
     * 获取单个反馈记录详情
     * 
     * 接收GET请求，返回指定ID的反馈记录的详细信息，
     * 包括反馈内容、类型、状态、创建者信息及时间戳等。
     */
    public function get(): void {
        try {
            $id = (int)$this->getParam('id');
            $feedback = $this->service->getFeedback($id);
            if (!$feedback) {
                Response::error('未找到指定反馈', 404);
                return;
            }
            Response::success($feedback);
        } catch (\Exception $e) {
            Logger::error('反馈获取失败', ['id' => $id, 'error' => $e->getMessage()]);
            Response::error('获取反馈信息失败', 500);
        }
    }

    /**
     * 获取指定风险的所有反馈
     * 
     * 接收GET请求，返回与指定风险ID关联的所有反馈记录。
     * 结果按创建时间倒序排列，包括所有状态的反馈。
     * 
     */
    public function getByRisk(): void {
        try {
            $riskId = (int)$this->getParam('riskId');
            $feedbacks = $this->service->getFeedbacksByRisk($riskId);
            Response::success($feedbacks);
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('获取风险相关反馈失败', ['risk_id' => $riskId, 'error' => $e->getMessage()]);
            Response::error('获取风险相关反馈失败', 500);
        }
    }

    /**
     * 按状态获取反馈记录
     * 
     * 接收GET请求，返回指定状态的所有反馈记录。
     * 状态可以是：
     * - open: 开放的反馈
     * - closed: 已关闭的反馈
     * - resolved: 已解决的反馈
     */
    public function getByStatus(): void {
        try {
            $status = $this->getParam('status');
            $feedbacks = $this->service->getFeedbacksByStatus($status);
            Response::success($feedbacks);
        } catch (\Exception $e) {
            Logger::error('按状态获取反馈失败', ['status' => $status, 'error' => $e->getMessage()]);
            Response::error('按状态获取反馈失败', 500);
        }
    }
}
