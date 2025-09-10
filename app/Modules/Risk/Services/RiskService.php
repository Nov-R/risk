<?php

namespace App\Modules\Risk\Services;

use App\Modules\Risk\Repositories\RiskRepository;
use App\Modules\Risk\Validators\RiskValidator;
use App\Modules\Risk\Entities\Risk;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;
use RuntimeException;

/**
 * 风险服务类
 * 
 * 该类负责处理所有与风险相关的业务逻辑，包括：
 * - 风险记录的创建、更新和删除
 * - 风险信息的查询和检索
 * - 风险状态的管理
 * - 高风险项目的识别
 * - 风险评分的计算
 */
class RiskService {
    /** @var RiskRepository 风险仓储实例 */
    private RiskRepository $repository;
    
    /** @var RiskValidator 风险验证器实例 */
    private RiskValidator $validator;

    /**
     * 构造函数
     * 
     * @param RiskRepository $repository 风险仓储实例
     * @param RiskValidator $validator 风险验证器实例
     */
    public function __construct(RiskRepository $repository, RiskValidator $validator) {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * 创建新的风险记录
     * 
     * @param array $data 风险数据，包含：
     *                    - name: 风险名称
     *                    - description: 风险描述
     *                    - probability: 发生概率（1-5）
     *                    - impact: 影响程度（1-5）
     *                    - status: 风险状态
     *                    - mitigation: 缓解措施
     *                    - contingency: 应急计划
     * @return int 新创建的风险记录ID
     * @throws ValidationException 当数据验证失败时
     * @throws \Exception 当创建过程中发生其他错误时
     */
    public function createRisk(array $data): int {
        try {
            $this->validator->validate($data);
            $risk = Risk::fromArray($data);
            $riskId = $this->repository->createRiskFromEntity($risk);
            
            Logger::info('风险创建成功', ['id' => $riskId]);
            return $riskId;
        } catch (ValidationException $e) {
            Logger::warning('风险数据验证失败', ['errors' => $e->getErrors()]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('风险创建失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新风险记录（部分更新）
     * 
     * @param int $id 要更新的风险记录ID
     * @param array $data 更新的数据，可包含：
     *                    - name: 风险名称
     *                    - description: 风险描述
     *                    - probability: 发生概率（1-5）
     *                    - impact: 影响程度（1-5）
     *                    - status: 风险状态
     *                    - mitigation: 缓解措施
     *                    - contingency: 应急计划
     * @return bool 更新是否成功
     * @throws \RuntimeException 当指定的风险不存在时
     * @throws ValidationException 当数据验证失败时
     * @throws \Exception 当更新过程中发生其他错误时
     */
    public function updateRisk(int $id, array $data): bool {
        try {
            if (!$this->repository->findRiskById($id)) {
                throw new \RuntimeException('未找到指定风险');
            }

            // 只校验传入的字段，不要求所有字段必填
            $this->validator->validate($data);
            $result = $this->repository->updateRisk($id, $data);
            
            Logger::info('风险更新成功', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('风险更新失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除风险记录
     * 
     * @param int $id 要删除的风险记录ID
     * @return bool 删除是否成功
     * @throws \RuntimeException 当指定的风险不存在时
     * @throws \Exception 当删除过程中发生其他错误时
     */
    public function deleteRisk(int $id): bool {
        try {
            if (!$this->repository->findRiskById($id)) {
                throw new \RuntimeException('未找到指定风险');
            }

            $result = $this->repository->deleteRisk($id);
            Logger::info('风险删除成功', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('风险删除失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取单个风险记录的详细信息
     * 
     * @param int $id 要查询的风险记录ID
     * @return array|null 风险记录详情，如果不存在则返回null
     * @throws \Exception 当查询过程中发生错误时
     */
    public function getRisk(int $id): ?array {
        try {
            $riskData = $this->repository->findRiskById($id);
            if (!$riskData) {
                return null;
            }

            // 将数组包装成实体对象进行业务逻辑处理
            $risk = Risk::fromArray($riskData);
            
            // 利用实体的业务方法进行数据增强
            $riskArray = $risk->toArray();
            $riskArray['risk_level'] = $risk->isHighRisk() ? 'high' : 'normal';
            $riskArray['needs_immediate_action'] = $risk->requiresImmediateAction();
            $riskArray['calculated_score'] = $risk->calculateRiskScore();
            
            // 最终返回增强后的数组给Controller
            return $riskArray;
        } catch (\Exception $e) {
            Logger::error('风险获取失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取所有风险记录列表
     * 
     * @return array 所有风险记录的数组
     * @throws \Exception 当查询过程中发生错误时
     */
    public function getAllRisks(): array {
        try {
            $risksData = $this->repository->findAllRisks();
            
            // 将数组数据包装成实体对象进行业务逻辑处理
            $risks = array_map(function($riskData) {
                $risk = Risk::fromArray($riskData);
                // 在这里可以进行业务逻辑处理
                // 例如：权限过滤、数据增强、状态计算等
                return $risk->toArray();
            }, $risksData);
            
            return $risks;
        } catch (\Exception $e) {
            Logger::error('风险列表获取失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 按状态获取风险记录列表
     * 
     * @param string $status 风险状态，可选值：
     *                       - active: 活跃
     *                       - mitigated: 已缓解
     *                       - closed: 已关闭
     *                       - monitoring: 监控中
     * @return array 指定状态的风险记录列表
     * @throws \Exception 当查询过程中发生错误时
     */
    public function getRisksByStatus(string $status): array {
        try {
            $risksData = $this->repository->findRisksByStatus($status);
            
            // 将数组数据包装成实体对象进行业务逻辑处理
            $risks = array_map(function($riskData) {
                $risk = Risk::fromArray($riskData);
                // 可以在这里进行状态特定的业务逻辑处理
                return $risk->toArray();
            }, $risksData);
            
            return $risks;
        } catch (\Exception $e) {
            Logger::error('按状态获取风险列表失败', ['status' => $status, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取高风险项目列表
     * 
     * @param int $threshold 风险阈值（1-25），默认为15
     *                      风险评分 = 概率 * 影响程度
     *                      当风险评分大于等于阈值时被视为高风险
     * @return array 高风险记录列表
     * @throws \InvalidArgumentException 当阈值不在有效范围内时
     * @throws \Exception 当查询过程中发生错误时
     */
    public function getHighRisks(int $threshold = 15): array {
        try {
            if ($threshold < 1 || $threshold > 25) {
                throw new \InvalidArgumentException('风险阈值必须在1到25之间');
            }
            
            $risksData = $this->repository->findHighRisks($threshold);
            
            // 将数组数据包装成实体对象进行业务逻辑处理
            $risks = array_map(function($riskData) {
                $risk = Risk::fromArray($riskData);
                
                // 利用实体的业务方法进行处理
                $riskArray = $risk->toArray();
                
                // 添加业务逻辑计算的字段
                $riskArray['risk_level'] = $risk->isHighRisk() ? 'high' : 'normal';
                $riskArray['needs_action'] = $risk->requiresImmediateAction();
                $riskArray['calculated_score'] = $risk->calculateRiskScore();
                
                return $riskArray;
            }, $risksData);
            
            return $risks;
        } catch (\Exception $e) {
            Logger::error('高风险列表获取失败', ['threshold' => $threshold, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
