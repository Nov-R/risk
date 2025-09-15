<?php

namespace App\Modules\Risk\ApplicationServices;

use App\Modules\Risk\Repositories\RiskRepository;
use App\Modules\Risk\Repositories\NodeRepository;
use App\Modules\Risk\Repositories\FeedbackRepository;
use App\Modules\Risk\DomainServices\RiskEvaluationService;
use App\Modules\Risk\Validators\RiskValidator;
use App\Modules\Risk\Entities\Risk;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;

/**
 * 风险管理应用服务
 * 
 * 应用服务层的核心职责：
 * 1. 工作流编排（协调多个Repository和Domain Service）
 * 2. 事务边界管理（确保数据一致性）
 * 3. 跨聚合根操作（处理Risk、Node、Feedback之间的协作）
 * 4. 外部接口适配（为Controller提供简单易用的接口）
 * 
 * 设计原则：
 * - 薄层：不包含复杂业务逻辑，主要做编排
 * - 事务控制：管理数据操作的事务边界
 * - 异常处理：统一处理和转换异常
 * - 日志记录：记录关键业务流程的执行情况
 * 
 * 与其他层的关系：
 * - 调用Repository进行数据操作
 * - 调用Domain Service执行复杂业务逻辑
 * - 为Controller提供粗粒度的业务操作接口
 */
class RiskManagementService
{
    // 依赖的仓储层（数据访问）
    private RiskRepository $riskRepository;
    private NodeRepository $nodeRepository;
    private FeedbackRepository $feedbackRepository;
    
    // 依赖的领域服务（业务逻辑）
    private RiskEvaluationService $riskEvaluationService;
    
    // 依赖的验证器（数据验证）
    private RiskValidator $riskValidator;

    public function __construct(
        RiskRepository $riskRepository,
        NodeRepository $nodeRepository,
        FeedbackRepository $feedbackRepository,
        RiskEvaluationService $riskEvaluationService,
        RiskValidator $riskValidator
    ) {
        $this->riskRepository = $riskRepository;
        $this->nodeRepository = $nodeRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->riskEvaluationService = $riskEvaluationService;
        $this->riskValidator = $riskValidator;
    }

    /**
     * 完整的风险处理工作流
     * 
     * 应用服务的核心方法：编排完整的业务流程
     * 展示了如何协调多个组件完成复杂的业务操作
     * 
     * 流程步骤：
     * 1. 数据验证 → 2. 创建风险 → 3. 评估风险级别 → 4. 创建审批流程 → 5. 发送通知
     * 
     * @param array $riskData 风险基础数据
     * @param bool $requiresApproval 是否需要审批流程
     * @param array $notificationRecipients 通知接收者列表
     * @return array 处理结果和流程状态
     * @throws ValidationException|Exception
     */
    public function processRiskWorkflow(array $riskData, bool $requiresApproval = true, array $notificationRecipients = []): array
    {
        // 开始事务确保数据一致性
        $this->riskRepository->beginDatabaseTransaction();
        
        try {
            // === 步骤1：数据验证和风险创建 ===
            $this->riskValidator->validate($riskData);
            $risk = Risk::fromArray($riskData);
            $riskId = $this->riskRepository->createRiskFromEntity($risk);
            
            Logger::info('风险创建成功', ['risk_id' => $riskId]);
            
            // === 步骤2：风险评估和分级 ===
            $createdRisk = $this->riskRepository->findRiskById($riskId);
            $riskEntity = Risk::fromArray($createdRisk);
            
            // === 步骤3：工作流创建（根据风险级别决定） ===
            $workflowActions = [];
            
            if ($requiresApproval && $riskEntity->isHighRisk()) {
                // 为高风险项目创建审批节点
                $approvalNode = $this->createApprovalNode($riskId, 'risk_review');
                $workflowActions[] = '创建审批节点';
                
                Logger::info('为高风险项目创建审批节点', [
                    'risk_id' => $riskId,
                    'node_id' => $approvalNode['id']
                ]);
            }
            
            if ($riskEntity->requiresImmediateAction()) {
                // 触发紧急处理流程
                $this->triggerEmergencyResponse($riskId);
                $workflowActions[] = '触发紧急响应';
                
                Logger::warning('触发紧急风险响应', ['risk_id' => $riskId]);
            }
            
            // === 步骤4：发送通知 ===
            if (!empty($notificationRecipients)) {
                $this->sendRiskNotifications($riskId, $notificationRecipients, $riskEntity->isHighRisk());
                $workflowActions[] = '发送通知';
            }
            
            // === 步骤5：记录审计日志 ===
            $this->logRiskAuditTrail($riskId, 'created', $workflowActions);
            
            // 提交事务
            $this->riskRepository->commitDatabaseTransaction();
            
            return [
                'risk_id' => $riskId,
                'status' => 'success',
                'is_high_risk' => $riskEntity->isHighRisk(),
                'requires_immediate_action' => $riskEntity->requiresImmediateAction(),
                'workflow_actions' => $workflowActions,
                'message' => '风险处理工作流执行成功'
            ];

        } catch (\Exception $e) {
            // 回滚事务
            $this->riskRepository->rollbackDatabaseTransaction();
            
            Logger::error('风险工作流处理失败', [
                'error' => $e->getMessage(),
                'risk_data_keys' => array_keys($riskData)
            ]);
            
            throw $e;
        }
    }

    /**
     * 风险升级工作流
     * 
     * 应用服务职责：编排风险升级的完整流程
     * 包含跨实体的复杂操作和事务管理
     * 
     * 协调多个实体和服务进行风险升级处理
     * 
     * @param int $riskId 风险ID
     * @param string $escalationReason 升级原因
     * @param string $escalatedBy 升级人
     * @return array 升级结果
     */
    public function escalateRiskWorkflow(int $riskId, string $escalationReason, string $escalatedBy): array
    {
        try {
            $this->riskRepository->beginDatabaseTransaction();

            // 第1步：获取风险和相关数据
            $riskData = $this->riskRepository->findRiskById($riskId);
            if (!$riskData) {
                throw new \RuntimeException('风险不存在');
            }

            $risk = Risk::fromArray($riskData);
            $relatedNodes = $this->getEntitiesFromData(
                $this->nodeRepository->findNodesByRiskId($riskId),
                'Node'
            );
            $relatedFeedbacks = $this->getEntitiesFromData(
                $this->feedbackRepository->findFeedbacksByRiskId($riskId),
                'Feedback'
            );

            // 第2步：使用领域服务检查是否确实需要升级
            $requiresEscalation = $this->riskEvaluationService->requiresEscalation(
                $risk, 
                $relatedNodes
            );

            if (!$requiresEscalation) {
                Logger::warning('风险不满足升级条件', ['risk_id' => $riskId]);
                return [
                    'status' => 'skipped',
                    'message' => '风险不满足升级条件'
                ];
            }

            // 第3步：更新风险状态
            $this->riskRepository->updateRisk($riskId, [
                'status' => 'escalated',
                'mitigation' => $escalationReason
            ]);

            // 第4步：创建升级审批节点
            $escalationNode = $this->createEscalationNode($riskId, $escalatedBy, $escalationReason);

            // 第5步：通知相关人员
            $this->sendEscalationNotifications($riskId, $escalationNode['id'], $escalatedBy);

            // 第6步：分析影响范围
            $impactScope = $this->riskEvaluationService->calculateImpactScope(
                $risk,
                $relatedNodes,
                $relatedFeedbacks
            );

            // 第7步：记录升级审计日志
            $this->logRiskAuditTrail($riskId, 'escalated', [
                'escalated_by' => $escalatedBy,
                'reason' => $escalationReason,
                'impact_scope' => $impactScope['impact_level'],
                'node_id' => $escalationNode['id']
            ]);

            $this->riskRepository->commitDatabaseTransaction();

            Logger::info('风险升级成功', [
                'risk_id' => $riskId,
                'escalated_by' => $escalatedBy,
                'impact_level' => $impactScope['impact_level']
            ]);

            return [
                'status' => 'success',
                'risk_id' => $riskId,
                'escalation_node_id' => $escalationNode['id'],
                'impact_scope' => $impactScope,
                'message' => '风险升级工作流执行成功'
            ];

        } catch (\Exception $e) {
            $this->riskRepository->rollbackDatabaseTransaction();
            
            Logger::error('风险升级工作流失败', [
                'risk_id' => $riskId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * 项目风险评估工作流
     * 
     * 协调领域服务进行完整的项目风险评估
     * 
     * @param int $projectId 项目ID（这里简化为按某个标识查找相关风险）
     * @return array 评估结果
     */
    public function performProjectRiskAssessment(int $projectId): array
    {
        try {
            // 第1步：收集项目相关的所有数据
            // 注意：这里简化处理，实际项目中需要根据具体的项目-风险关联来查询
            $allRisks = $this->riskRepository->findAllRisks();
            $allNodes = $this->nodeRepository->findAllNodes();
            $allFeedbacks = $this->feedbackRepository->findAllFeedbacks();

            // 转换为实体对象
            $riskEntities = $this->getEntitiesFromData($allRisks, 'Risk');
            $nodeEntities = $this->getEntitiesFromData($allNodes, 'Node');
            $feedbackEntities = $this->getEntitiesFromData($allFeedbacks, 'Feedback');

            // 第2步：使用领域服务进行风险评估
            $assessment = $this->riskEvaluationService->evaluateProjectRisk(
                $riskEntities,
                $nodeEntities,
                $feedbackEntities
            );

            // 第3步：根据评估结果决定后续行动
            $followUpActions = [];
            
            if ($assessment['requires_attention']) {
                // 创建关注清单
                $this->createAttentionList($projectId, $assessment);
                $followUpActions[] = '创建风险关注清单';

                // 如果是严重情况，发送警报
                if ($assessment['overall_risk_level'] === 'critical') {
                    $this->sendCriticalRiskAlert($projectId, $assessment);
                    $followUpActions[] = '发送严重风险警报';
                }
            }

            // 第4步：生成评估报告
            $reportId = $this->generateRiskAssessmentReport($projectId, $assessment, $followUpActions);
            $followUpActions[] = '生成评估报告';

            // 第5步：记录评估活动
            $this->logProjectAssessmentActivity($projectId, $assessment, $reportId);

            Logger::info('项目风险评估完成', [
                'project_id' => $projectId,
                'overall_level' => $assessment['overall_risk_level'],
                'report_id' => $reportId
            ]);

            return [
                'status' => 'success',
                'project_id' => $projectId,
                'assessment' => $assessment,
                'follow_up_actions' => $followUpActions,
                'report_id' => $reportId,
                'assessment_date' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            Logger::error('项目风险评估失败', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    // ================== 私有辅助方法 ==================

    /**
     * 创建审批节点
     */
    private function createApprovalNode(int $riskId, string $reviewType): array
    {
        $nodeData = [
            'type' => $reviewType,
            'risk_id' => $riskId,
            'status' => 'pending',
            'comments' => '系统自动创建的审批节点'
        ];

        $nodeId = $this->nodeRepository->createNode($nodeData);
        
        return [
            'id' => $nodeId,
            'type' => $reviewType,
            'status' => 'pending'
        ];
    }

    /**
     * 创建升级节点
     */
    private function createEscalationNode(int $riskId, string $escalatedBy, string $reason): array
    {
        $nodeData = [
            'type' => 'escalation_review',
            'risk_id' => $riskId,
            'reviewer' => $escalatedBy,
            'status' => 'pending',
            'comments' => "升级原因：{$reason}"
        ];

        $nodeId = $this->nodeRepository->createNode($nodeData);
        
        return [
            'id' => $nodeId,
            'type' => 'escalation_review',
            'escalated_by' => $escalatedBy
        ];
    }

    /**
     * 触发紧急响应
     */
    private function triggerEmergencyResponse(int $riskId): void
    {
        // 这里可以集成外部系统、发送紧急通知等
        Logger::warning('触发紧急风险响应流程', [
            'risk_id' => $riskId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // 示例：可以调用外部服务
        // $this->emergencyNotificationService->send($riskId);
    }

    /**
     * 发送风险通知
     */
    private function sendRiskNotifications(int $riskId, array $recipients, bool $isHighRisk): void
    {
        $urgency = $isHighRisk ? 'high' : 'normal';
        
        Logger::info('发送风险通知', [
            'risk_id' => $riskId,
            'recipients' => count($recipients),
            'urgency' => $urgency
        ]);
        
        // 实际项目中这里会调用通知服务
        // $this->notificationService->sendRiskNotification($riskId, $recipients, $urgency);
    }

    /**
     * 发送升级通知
     */
    private function sendEscalationNotifications(int $riskId, int $nodeId, string $escalatedBy): void
    {
        Logger::info('发送风险升级通知', [
            'risk_id' => $riskId,
            'node_id' => $nodeId,
            'escalated_by' => $escalatedBy
        ]);
        
        // 实际实现会调用通知服务
        // $this->notificationService->sendEscalationNotification($riskId, $nodeId);
    }

    /**
     * 记录审计日志
     */
    private function logRiskAuditTrail(int $riskId, string $action, array $details): void
    {
        Logger::info('风险审计日志', [
            'risk_id' => $riskId,
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        // 实际项目中会保存到审计日志表
        // $this->auditLogRepository->create([...]);
    }

    /**
     * 创建关注清单
     */
    private function createAttentionList(int $projectId, array $assessment): void
    {
        Logger::info('创建项目风险关注清单', [
            'project_id' => $projectId,
            'risk_level' => $assessment['overall_risk_level']
        ]);
        
        // 实际实现会创建关注清单记录
    }

    /**
     * 发送严重风险警报
     */
    private function sendCriticalRiskAlert(int $projectId, array $assessment): void
    {
        Logger::warning('发送严重风险警报', [
            'project_id' => $projectId,
            'assessment' => $assessment
        ]);
        
        // 实际实现会发送警报通知
    }

    /**
     * 生成风险评估报告
     */
    private function generateRiskAssessmentReport(int $projectId, array $assessment, array $actions): int
    {
        // 模拟生成报告ID
        $reportId = time() + $projectId;
        
        Logger::info('生成风险评估报告', [
            'project_id' => $projectId,
            'report_id' => $reportId,
            'assessment_summary' => $assessment['summary']
        ]);
        
        // 实际实现会保存报告到数据库或文件系统
        return $reportId;
    }

    /**
     * 记录项目评估活动
     */
    private function logProjectAssessmentActivity(int $projectId, array $assessment, int $reportId): void
    {
        Logger::info('记录项目评估活动', [
            'project_id' => $projectId,
            'report_id' => $reportId,
            'risk_level' => $assessment['overall_risk_level']
        ]);
    }

    /**
     * 从数据数组创建实体对象
     */
    private function getEntitiesFromData(array $dataList, string $entityType): array
    {
        return array_map(function($data) use ($entityType) {
            $className = "App\\Modules\\Risk\\Entities\\{$entityType}";
            return $className::fromArray($data);
        }, $dataList);
    }
}