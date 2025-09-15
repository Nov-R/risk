<?php

namespace App\Modules\Risk\DomainServices;

use App\Modules\Risk\Entities\Risk;
use App\Modules\Risk\Entities\Node;
use App\Modules\Risk\Entities\Feedback;

/**
 * 精简版风险评估领域服务
 * 
 * 学习用途：展示跨实体的复杂业务逻辑
 * 主要功能：
 * 1. 项目级风险评估（聚合多个风险的整体状况）
 * 2. 风险升级判断（什么情况下需要特殊处理）
 * 3. 优先级计算（如何排序处理顺序）
 * 4. 影响范围分析（风险的波及程度）
 * 
 * 这是典型的领域服务：聚合多个实体，执行复杂的业务计算
 */
class RiskEvaluationService
{
    /**
     * 评估项目整体风险状况
     * 
     * 跨实体业务逻辑：综合多个风险记录，计算项目级别的风险指标
     * 这种聚合计算是领域服务的典型职责
     */
    public function evaluateProjectRisk(array $risks, array $nodes, array $feedbacks): array
    {
        if (empty($risks)) {
            return [
                'overall_risk_level' => 'none',
                'risk_score' => 0,
                'high_risk_count' => 0,
                'total_risks' => 0,
                'summary' => '项目暂无风险记录'
            ];
        }

        // 计算风险分数统计
        $riskScores = array_map(fn($risk) => $risk->calculateRiskScore(), $risks);
        $totalScore = array_sum($riskScores);
        $averageScore = $totalScore / count($risks);
        $maxScore = max($riskScores);
        
        // 分类统计
        $highRiskCount = count(array_filter($riskScores, fn($score) => $score >= 15));
        $mediumRiskCount = count(array_filter($riskScores, fn($score) => $score >= 10 && $score < 15));
        $lowRiskCount = count($risks) - $highRiskCount - $mediumRiskCount;
        
        // 确定整体风险等级（不仅看平均值，也要考虑极值和分布）
        $overallLevel = $this->determineOverallRiskLevel($averageScore, $maxScore, $highRiskCount);
        
        return [
            'overall_risk_level' => $overallLevel,
            'risk_score' => round($averageScore, 2),
            'max_risk_score' => $maxScore,
            'high_risk_count' => $highRiskCount,
            'medium_risk_count' => $mediumRiskCount,
            'low_risk_count' => $lowRiskCount,
            'total_risks' => count($risks),
            'summary' => $this->generateRiskSummary($overallLevel, $averageScore, $highRiskCount, count($risks))
        ];
    }

    /**
     * 检查是否需要风险升级
     * 
     * 跨实体业务规则：综合风险本身和相关工作流状态来判断
     * 体现了领域服务的价值：封装复杂的判断逻辑
     */
    public function requiresEscalation(Risk $risk, array $relatedNodes): bool
    {
        $riskScore = $risk->calculateRiskScore();
        
        // 规则1：极高风险必须升级
        if ($riskScore >= 20) {
            return true;
        }
        
        // 规则2：高风险且有阻塞节点需要升级
        if ($riskScore >= 15) {
            $blockedNodes = array_filter($relatedNodes, function($node) {
                return $node->getStatus() === 'pending' || $node->getStatus() === 'blocked';
            });
            
            if (count($blockedNodes) > 0) {
                return true;
            }
        }
        
        // 规则3：任何风险停滞时间过长都需要升级（这里简化为检查节点数量）
        if (count($relatedNodes) > 5) { // 超过5个节点说明流程复杂，可能有问题
            return true;
        }
        
        return false;
    }

    /**
     * 计算风险优先级
     * 
     * 综合风险分数和外部反馈来确定处理优先级
     * 这种计算逻辑的封装让业务规则更容易维护和测试
     */
    public function calculateRiskPriority(Risk $risk, array $feedbacks): string
    {
        $score = $risk->calculateRiskScore();
        $feedbackCount = count($feedbacks);
        
        // 计算反馈的紧急程度
        $urgentFeedbacks = array_filter($feedbacks, function($feedback) {
            return $feedback->getType() === 'urgent' || $feedback->getPriority() === 'high';
        });
        $urgentCount = count($urgentFeedbacks);
        
        // 优先级决策矩阵
        if ($score >= 20 || $urgentCount >= 2) {
            return 'urgent';    // 紧急处理
        } elseif ($score >= 15 || $urgentCount >= 1 || $feedbackCount >= 5) {
            return 'high';      // 高优先级
        } elseif ($score >= 10 || $feedbackCount >= 2) {
            return 'medium';    // 中优先级
        } else {
            return 'low';       // 低优先级
        }
    }

    /**
     * 计算影响范围（兼容ApplicationService）
     * 
     * 分析风险的影响范围和波及程度
     * 通过分析相关节点和反馈的类型来评估影响的广度和深度
     */
    public function calculateImpactScope(Risk $risk, array $relatedNodes, array $relatedFeedbacks): array
    {
        // 分析影响的节点类型（业务领域）
        $nodeTypes = array_unique(array_map(fn($node) => $node->getType(), $relatedNodes));
        
        // 分析反馈的类别（关注点）
        $feedbackTypes = array_unique(array_map(fn($feedback) => $feedback->getType(), $relatedFeedbacks));
        
        // 计算影响程度
        $nodeTypeCount = count($nodeTypes);
        $feedbackTypeCount = count($feedbackTypes);
        
        // 确定影响级别
        $impactLevel = $this->determineImpactLevel($nodeTypeCount, $feedbackTypeCount, count($relatedNodes));
        
        return [
            'affected_areas' => $nodeTypes,           // 受影响的业务领域
            'feedback_categories' => $feedbackTypes, // 反馈涉及的类别
            'impact_level' => $impactLevel,           // 影响程度：limited/moderate/wide/extensive
            'node_count' => count($relatedNodes),     // 涉及节点数量
            'feedback_count' => count($relatedFeedbacks), // 收到反馈数量
            'scope_score' => $this->calculateScopeScore($nodeTypeCount, $feedbackTypeCount, count($relatedNodes))
        ];
    }

    /**
     * 确定影响级别
     * 
     * 私有方法：根据各种指标确定影响的广度
     */
    private function determineImpactLevel(int $nodeTypeCount, int $feedbackTypeCount, int $totalNodes): string
    {
        // 影响范围的判断逻辑
        if ($nodeTypeCount >= 4 || $totalNodes >= 10) {
            return 'extensive';  // 广泛影响
        } elseif ($nodeTypeCount >= 3 || $totalNodes >= 6) {
            return 'wide';       // 较大影响
        } elseif ($nodeTypeCount >= 2 || $totalNodes >= 3) {
            return 'moderate';   // 中等影响
        } else {
            return 'limited';    // 有限影响
        }
    }

    /**
     * 计算范围评分
     * 
     * 私有方法：量化影响范围的评分
     */
    private function calculateScopeScore(int $nodeTypeCount, int $feedbackTypeCount, int $totalNodes): float
    {
        // 简单的权重计算：类型多样性 + 数量规模
        $diversityScore = ($nodeTypeCount * 2) + $feedbackTypeCount;
        $scaleScore = min($totalNodes, 10); // 最多10分
        
        return round(($diversityScore + $scaleScore) / 2, 1);
    }

    /**
     * 私有方法：确定风险等级
     */
    private function determineRiskLevel(float $averageScore, int $highRiskCount): string
    {
        if ($averageScore >= 20 || $highRiskCount >= 3) {
            return 'critical';
        } elseif ($averageScore >= 15 || $highRiskCount >= 2) {
            return 'high';
        } elseif ($averageScore >= 10 || $highRiskCount >= 1) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * 确定整体风险等级（新方法，考虑更多因素）
     * 
     * 私有方法：综合平均值、最大值、高风险数量来判断
     */
    private function determineOverallRiskLevel(float $averageScore, float $maxScore, int $highRiskCount): string
    {
        // 有任何极高风险（>=20）就是关键级别
        if ($maxScore >= 20) {
            return 'critical';
        }
        
        // 高风险数量多或平均分高
        if ($averageScore >= 15 || $highRiskCount >= 3) {
            return 'high';
        }
        
        // 中等风险
        if ($averageScore >= 10 || $highRiskCount >= 1) {
            return 'medium';
        }
        
        return 'low';
    }

    /**
     * 生成风险评估摘要
     * 
     * 私有方法：根据评估结果生成人性化的描述
     */
    private function generateRiskSummary(string $overallLevel, float $averageScore, int $highRiskCount, int $totalRisks): string
    {
        $levelTexts = [
            'critical' => '关键',
            'high' => '高',
            'medium' => '中',
            'low' => '低'
        ];
        
        $levelText = $levelTexts[$overallLevel] ?? '未知';
        
        return "项目整体风险等级：{$levelText}，共{$totalRisks}个风险项，其中{$highRiskCount}个高风险，平均风险分数：" . round($averageScore, 2);
    }
}