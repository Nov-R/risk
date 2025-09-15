<?php

namespace App\Modules\Risk\Services;

use App\Modules\Risk\Repositories\RiskRepository;
use App\Modules\Risk\Entities\Risk;
use App\Core\Exceptions\ValidationException;

/**
 * 精简版风险服务类 - 基础业务逻辑
 */
class RiskService
{
    private RiskRepository $riskRepository;

    public function __construct(RiskRepository $riskRepository) 
    {
        $this->riskRepository = $riskRepository;
    }

    // ===========================================
    // 基础CRUD业务方法
    // ===========================================

    /**
     * 创建风险
     */
    public function createRisk(array $data): int 
    {
        $this->validateRiskData($data);
        
        // 计算风险分数
        $data['risk_score'] = $this->calculateRiskScore($data);
        
        return $this->riskRepository->createRisk($data);
    }

    /**
     * 获取风险详情
     */
    public function getRisk(int $id): ?Risk 
    {
        $data = $this->riskRepository->findRiskById($id);
        
        if (!$data) {
            return null;
        }
        
        return Risk::fromArray($data);
    }

    /**
     * 获取所有风险
     */
    public function getAllRisks(): array 
    {
        $risksData = $this->riskRepository->findAllRisks();
        
        return array_map(fn($data) => Risk::fromArray($data), $risksData);
    }

    /**
     * 更新风险
     */
    public function updateRisk(int $id, array $data): bool 
    {
        $this->validateRiskData($data, false);
        
        // 重新计算风险分数（如果相关字段有更新）
        if (isset($data['impact_score']) || isset($data['probability_score'])) {
            $currentRisk = $this->riskRepository->findRiskById($id);
            if ($currentRisk) {
                $mergedData = array_merge($currentRisk, $data);
                $data['risk_score'] = $this->calculateRiskScore($mergedData);
            }
        }
        
        return $this->riskRepository->updateRisk($id, $data);
    }

    /**
     * 删除风险
     */
    public function deleteRisk(int $id): bool 
    {
        return $this->riskRepository->deleteRisk($id);
    }

    // ===========================================
    // 业务查询方法
    // ===========================================

    /**
     * 获取高风险项目
     */
    public function getHighRisks(): array 
    {
        $risksData = $this->riskRepository->findHighRisks();
        
        return array_map(fn($data) => Risk::fromArray($data), $risksData);
    }

    /**
     * 根据状态获取风险
     */
    public function getRisksByStatus(string $status): array 
    {
        $risksData = $this->riskRepository->findRisksByStatus($status);
        
        return array_map(fn($data) => Risk::fromArray($data), $risksData);
    }

    /**
     * 根据负责人获取风险
     */
    public function getRisksByOwner(string $owner): array 
    {
        $risksData = $this->riskRepository->findRisksByOwner($owner);
        
        return array_map(fn($data) => Risk::fromArray($data), $risksData);
    }

    // ===========================================
    // 辅助方法
    // ===========================================

    /**
     * 验证风险数据
     */
    private function validateRiskData(array $data, bool $isCreate = true): void 
    {
        $errors = [];

        if ($isCreate) {
            if (empty($data['title'])) {
                $errors['title'] = '风险标题不能为空';
            }
            if (empty($data['description'])) {
                $errors['description'] = '风险描述不能为空';
            }
        }

        if (isset($data['impact_score']) && ($data['impact_score'] < 1 || $data['impact_score'] > 5)) {
            $errors['impact_score'] = '影响分数必须在1-5之间';
        }

        if (isset($data['probability_score']) && ($data['probability_score'] < 1 || $data['probability_score'] > 5)) {
            $errors['probability_score'] = '概率分数必须在1-5之间';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * 计算风险分数
     */
    private function calculateRiskScore(array $data): int 
    {
        $impact = $data['impact_score'] ?? 1;
        $probability = $data['probability_score'] ?? 1;
        
        return (int)($impact * $probability);
    }
}