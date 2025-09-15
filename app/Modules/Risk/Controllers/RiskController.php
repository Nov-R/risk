<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Risk\Services\RiskService;

/**
 * 精简版风险控制器 - 基础REST API
 */
class RiskController extends BaseController
{
    private RiskService $riskService;

    public function __construct(Request $request, RiskService $riskService)
    {
        parent::__construct($request);
        $this->riskService = $riskService;
    }

    /**
     * 获取所有风险
     * GET /api/risks
     */
    public function index(): void
    {
        try {
            $risks = $this->riskService->getAllRisks();
            
            Response::success([
                'risks' => array_map(fn($risk) => $risk->toArray(), $risks),
                'total' => count($risks)
            ]);
        } catch (\Exception $e) {
            Response::error('获取风险列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取单个风险
     * GET /api/risks/{id}
     */
    public function show(int $id): void
    {
        try {
            $risk = $this->riskService->getRisk($id);
            
            if (!$risk) {
                Response::error('风险不存在', 404);
                return;
            }
            
            Response::success(['risk' => $risk->toArray()]);
        } catch (\Exception $e) {
            Response::error('获取风险详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建风险
     * POST /api/risks
     */
    public function store(): void
    {
        try {
            $data = $this->getBodyParam();
            $riskId = $this->riskService->createRisk($data);
            
            Response::success(['risk_id' => $riskId], '风险创建成功', 201);
        } catch (\Exception $e) {
            Response::error('创建风险失败: ' . $e->getMessage(), 400);
        }
    }

    /**
     * 更新风险
     * PUT /api/risks/{id}
     */
    public function update(int $id): void
    {
        try {
            $data = $this->getBodyParam();
            $success = $this->riskService->updateRisk($id, $data);
            
            if (!$success) {
                Response::error('风险不存在或更新失败', 404);
                return;
            }
            
            Response::success(null, '风险更新成功');
        } catch (\Exception $e) {
            Response::error('更新风险失败: ' . $e->getMessage(), 400);
        }
    }

    /**
     * 删除风险
     * DELETE /api/risks/{id}
     */
    public function destroy(int $id): void
    {
        try {
            $success = $this->riskService->deleteRisk($id);
            
            if (!$success) {
                Response::error('风险不存在或删除失败', 404);
                return;
            }
            
            Response::success(null, '风险删除成功');
        } catch (\Exception $e) {
            Response::error('删除风险失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取高风险项目
     * GET /api/risks/high
     */
    public function high(): void
    {
        try {
            $risks = $this->riskService->getHighRisks();
            
            Response::success([
                'risks' => array_map(fn($risk) => $risk->toArray(), $risks),
                'total' => count($risks)
            ]);
        } catch (\Exception $e) {
            Response::error('获取高风险项目失败: ' . $e->getMessage(), 500);
        }
    }
}