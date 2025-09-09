<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Risk\Services\RiskService;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;

/**
 * 风险控制器类
 * 
 * 该类负责处理与风险相关的所有HTTP请求，包括：
 * - 创建新的风险记录
 * - 更新现有风险
 * - 删除风险记录
 * - 查询单个风险详情
 * - 获取所有风险列表
 * - 按状态查询风险
 * - 获取高风险项目
 */
class RiskController extends BaseController {
    /** @var RiskService 风险服务实例 */
    private RiskService $service;

    /**
     * 构造函数
     * 
     * @param Request $request HTTP请求实例
     * @param RiskService $service 风险服务实例
     */
    public function __construct(Request $request, RiskService $service) {
        parent::__construct($request);
        $this->service = $service;
    }

    /**
     * 创建新的风险记录
     * 
     * 接收POST请求，创建新的风险记录。请求体应包含：
     * - name: 风险名称
     * - description: 风险描述
     * - probability: 发生概率（1-5）
     * - impact: 影响程度（1-5）
     * - status: 风险状态
     * - mitigation: 缓解措施
     * - contingency: 应急计划
     */
    public function create(): void {
        try {
            $data = $this->getBodyParam();
            $riskId = $this->service->createRisk($data);
            Response::success(['id' => $riskId], '风险创建成功');
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\Exception $e) {
            Logger::error('风险创建失败', ['error' => $e->getMessage()]);
            Response::error('创建风险失败', 500);
        }
    }

    /**
     * 更新风险记录
     * 
     * 接收PUT请求，更新指定ID的风险记录。请求体可包含：
     * - name: 风险名称
     * - description: 风险描述
     * - probability: 发生概率（1-5）
     * - impact: 影响程度（1-5）
     * - status: 风险状态
     * - mitigation: 缓解措施
     * - contingency: 应急计划
     */
    public function update(): void {
        try {
            $id = (int)$this->getParam('id');
            $data = $this->getBodyParam();
            $this->service->updateRisk($id, $data);
            Response::success(null, '风险更新成功');
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('风险更新失败', ['id' => $id ?? 'unknown', 'error' => $e->getMessage()]);
            Response::error('更新风险失败', 500);
        }
    }

    /**
     * 删除风险记录
     * 
     * 接收DELETE请求，删除指定ID的风险记录。
     * 如果该风险已经与其他实体（如反馈或节点）关联，
     * 需要先解除这些关联才能删除。
     */
    public function delete(): void {
        try {
            $id = (int)$this->getParam('id');
            $this->service->deleteRisk($id);
            Response::success(null, '风险删除成功');
        } catch (\RuntimeException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Logger::error('风险删除失败', ['id' => $id ?? 'unknown', 'error' => $e->getMessage()]);
            Response::error('删除风险失败', 500);
        }
    }

    /**
     * 获取单个风险记录详情
     * 
     * 接收GET请求，返回指定ID的风险记录的详细信息，
     * 包括基本信息、风险评分、时间戳等。
     */
    public function get(): void {
        try {
            $id = (int)$this->getParam('id');
            $risk = $this->service->getRisk($id);
            if (!$risk) {
                Response::error('未找到指定风险', 404);
                return;
            }
            Response::success($risk);
        } catch (\Exception $e) {
            Logger::error('风险获取失败', ['id' => $id ?? 'unknown', 'error' => $e->getMessage()]);
            Response::error('获取风险信息失败', 500);
        }
    }

    /**
     * 获取所有风险记录
     * 
     * 接收GET请求，返回系统中所有的风险记录列表。
     * 结果按创建时间倒序排列。
     * 注意：为了性能考虑，可能需要考虑分页。
     */
    public function getAll(): void {
        try {
            $risks = $this->service->getAllRisks();
            Response::success($risks);
        } catch (\Exception $e) {
            Logger::error('风险列表获取失败', ['error' => $e->getMessage()]);
            Response::error('获取风险列表失败', 500);
        }
    }

    /**
     * 按状态获取风险记录
     * 
     * 接收GET请求，返回指定状态的所有风险记录。
     * 状态可以是：
     * - active: 活跃
     * - mitigated: 已缓解
     * - closed: 已关闭
     * - monitoring: 监控中
     */
    public function getByStatus(): void {
        try {
            $status = $this->getParam('status');
            $risks = $this->service->getRisksByStatus($status);
            Response::success($risks);
        } catch (\Exception $e) {
            Logger::error('按状态获取风险列表失败', ['status' => $status ?? 'unknown', 'error' => $e->getMessage()]);
            Response::error('按状态获取风险列表失败', 500);
        }
    }

    /**
     * 获取高风险项目列表
     * 
     * 接收GET请求，返回风险评分超过阈值的风险记录。
     * 风险评分 = 概率 * 影响程度
     * 可通过查询参数threshold指定阈值，默认为15。
     * 
     * 查询参数：
     * - threshold: 风险评分阈值，默认15
     */
    public function getHighRisks(): void {
        try {
            $threshold = (int)$this->getQuery('threshold', 15);
            $risks = $this->service->getHighRisks($threshold);
            Response::success($risks);
        } catch (\Exception $e) {
            Logger::error('高风险项目获取失败', ['error' => $e->getMessage()]);
            Response::error('获取高风险项目失败', 500);
        }
    }
}
