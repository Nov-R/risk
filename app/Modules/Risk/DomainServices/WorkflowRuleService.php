<?php

namespace App\Modules\Risk\DomainServices;

use App\Modules\Risk\Entities\Risk;
use App\Modules\Risk\Entities\Node;
use App\Modules\Risk\Entities\Feedback;

/**
 * 精简版工作流规则领域服务
 * 
 * 学习用途：展示工作流编排的核心业务逻辑
 * 主要功能：
 * 1. 节点流转规则（下一步去哪里）
 * 2. 审批规则（是否可以跳过、如何决定通过/拒绝）
 * 3. 进度追踪（工作流完成度计算）
 * 
 * 这是典型的领域服务：包含复杂的业务规则，但不涉及数据持久化
 */
class WorkflowRuleService
{
    /**
     * 确定下一个审批节点
     * 
     * 核心工作流逻辑：根据当前节点和风险情况，决定下一步流向
     * 这里使用最简单的顺序审批规则，实际项目中可能更复杂
     */
    public function determineNextNode(Risk $risk, Node $currentNode, array $allNodes): ?Node
    {
        $currentId = $currentNode->getId();
        
        // 过滤出候选节点：ID更大且状态允许
        $candidateNodes = array_filter($allNodes, function($node) use ($currentId) {
            return $node->getId() > $currentId && 
                   $node->getStatus() !== 'completed' &&
                   $node->getStatus() !== 'skipped';
        });
        
        if (empty($candidateNodes)) {
            return null; // 工作流结束
        }
        
        // 按ID排序，返回最小的（顺序执行）
        usort($candidateNodes, fn($a, $b) => $a->getId() <=> $b->getId());
        return $candidateNodes[0];
    }

    /**
     * 检查是否可以跳过当前节点
     * 
     * 业务规则示例：在某些条件下可以简化流程
     * 实际项目中这里会有更复杂的权限、条件判断
     */
    public function canSkipNode(Risk $risk, Node $node): bool
    {
        // 规则1：低风险的可选节点可以跳过
        $isLowRisk = $risk->calculateRiskScore() < 10;
        $isOptionalNode = $node->getType() === 'optional';
        
        if ($isLowRisk && $isOptionalNode) {
            return true;
        }
        
        // 规则2：紧急风险可以跳过某些审批环节
        $isUrgent = $risk->calculateRiskScore() >= 20;
        $isRoutineApproval = $node->getType() === 'routine_approval';
        
        if ($isUrgent && $isRoutineApproval) {
            return true;
        }
        
        return false;
    }

    /**
     * 确定审批结果
     * 
     * 根据反馈信息决定节点的最终状态
     * 这里体现了业务规则：如何从多个意见中得出最终决策
     */
    public function determineApprovalResult(Node $node, array $feedbacks): string
    {
        if (empty($feedbacks)) {
            return 'pending'; // 无反馈，继续等待
        }
        
        // 统计不同类型的反馈
        $approvalCount = 0;
        $rejectionCount = 0;
        $neutralCount = 0;
        
        foreach ($feedbacks as $feedback) {
            switch ($feedback->getType()) {
                case 'approval':
                case 'positive':
                    $approvalCount++;
                    break;
                case 'rejection':
                case 'negative':
                    $rejectionCount++;
                    break;
                default:
                    $neutralCount++;
            }
        }
        
        // 决策逻辑：简单多数原则
        if ($rejectionCount > $approvalCount) {
            return 'rejected';
        } elseif ($approvalCount > $rejectionCount) {
            return 'approved';
        } else {
            return 'pending'; // 平局或全是中性，继续等待
        }
    }

    /**
     * 检查工作流是否完成
     * 
     * 判断所有必要节点是否都已处理完毕
     */
    public function isWorkflowComplete(array $nodes): bool
    {
        foreach ($nodes as $node) {
            $status = $node->getStatus();
            // 只要有节点还在进行中就未完成
            if ($status === 'pending' || $status === 'in_progress') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 获取工作流进度统计
     * 
     * 提供工作流执行的量化指标，便于监控和展示
     */
    public function calculateProgress(array $nodes): array
    {
        $totalNodes = count($nodes);
        if ($totalNodes === 0) {
            return [
                'total_nodes' => 0,
                'completed_nodes' => 0,
                'progress_percent' => 0,
                'current_status' => 'not_started'
            ];
        }
        
        // 统计各种状态的节点数量
        $completedNodes = 0;
        $skippedNodes = 0;
        $pendingNodes = 0;
        
        foreach ($nodes as $node) {
            switch ($node->getStatus()) {
                case 'completed':
                case 'approved':
                    $completedNodes++;
                    break;
                case 'skipped':
                    $skippedNodes++;
                    break;
                case 'pending':
                case 'in_progress':
                    $pendingNodes++;
                    break;
            }
        }
        
        // 计算进度百分比（完成+跳过都算进度）
        $progressCount = $completedNodes + $skippedNodes;
        $progressPercent = ($progressCount / $totalNodes) * 100;
        
        return [
            'total_nodes' => $totalNodes,
            'completed_nodes' => $completedNodes,
            'skipped_nodes' => $skippedNodes,
            'pending_nodes' => $pendingNodes,
            'progress_percent' => round($progressPercent, 1),
            'current_status' => $this->determineWorkflowStatus($progressPercent, $pendingNodes)
        ];
    }

    /**
     * 确定工作流整体状态
     * 
     * 私有方法：根据进度和待处理节点确定状态
     */
    private function determineWorkflowStatus(float $progressPercent, int $pendingNodes): string
    {
        if ($progressPercent >= 100) {
            return 'completed';
        } elseif ($progressPercent > 0) {
            return 'in_progress';
        } else {
            return 'not_started';
        }
    }
}