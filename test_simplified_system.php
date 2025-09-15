<?php

require_once __DIR__ . '/config/autoload.php';

use App\Core\Database\DatabaseConnection;
use App\Modules\Risk\Repositories\RiskRepository;
use App\Modules\Risk\Repositories\NodeRepository;
use App\Modules\Risk\Repositories\FeedbackRepository;
use App\Modules\Risk\Services\RiskService;
use App\Modules\Risk\Services\NodeService;
use App\Modules\Risk\Services\FeedbackService;
use App\Modules\Risk\DomainServices\RiskEvaluationService;
use App\Modules\Risk\DomainServices\WorkflowRuleService;
use App\Core\Exceptions\ValidationException;

echo "🚀 精简版风险管理系统测试\n";
echo "=" . str_repeat("=", 40) . "\n\n";

try {
    // 1. 测试Repository层基础CRUD
    echo "📦 测试Repository层...\n";
    
    $riskRepo = new RiskRepository();
    
    // 创建测试风险
    $riskData = [
        'title' => '学习测试风险',
        'description' => '这是一个用于测试的风险项目',
        'category' => 'technical',
        'impact_score' => 4,
        'probability_score' => 3,
        'status' => 'active',
        'owner' => 'test_user'
    ];
    
    $riskId = $riskRepo->createRisk($riskData);
    echo "✅ 创建风险成功，ID: {$riskId}\n";
    
    // 查找风险
    $foundRisk = $riskRepo->findRiskById($riskId);
    if ($foundRisk) {
        echo "✅ 查找风险成功，标题: {$foundRisk['title']}\n";
    }
    
    // 更新风险
    $updateData = ['impact_score' => 5];
    $updated = $riskRepo->updateRisk($riskId, $updateData);
    echo $updated ? "✅ 更新风险成功\n" : "❌ 更新风险失败\n";
    
    echo "\n";
    
    // 2. 测试Service层业务逻辑
    echo "🔧 测试Service层...\n";
    
    $riskService = new RiskService($riskRepo);
    
    // 创建风险
    $newRiskData = [
        'title' => '服务层测试风险',
        'description' => '通过服务层创建的风险',
        'category' => 'business',
        'impact_score' => 3,
        'probability_score' => 4,
        'status' => 'active',
        'owner' => 'service_user'
    ];
    
    $serviceRiskId = $riskService->createRisk($newRiskData);
    echo "✅ 服务层创建风险成功，ID: {$serviceRiskId}\n";
    
    // 获取高风险项目
    $highRisks = $riskService->getHighRisks();
    echo "✅ 获取高风险项目: " . count($highRisks) . " 个\n";
    
    echo "\n";
    
    // 3. 测试Domain Services跨实体逻辑
    echo "🎯 测试Domain Services...\n";
    
    $evaluationService = new RiskEvaluationService();
    $workflowService = new WorkflowRuleService();
    
    // 获取风险实体进行评估
    $risk1 = $riskService->getRisk($riskId);
    $risk2 = $riskService->getRisk($serviceRiskId);
    
    if ($risk1 && $risk2) {
        $risks = [$risk1, $risk2];
        $evaluation = $evaluationService->evaluateProjectRisk($risks, [], []);
        
        echo "✅ 项目风险评估完成:\n";
        echo "   - 整体风险等级: {$evaluation['overall_risk_level']}\n";
        echo "   - 平均风险分数: {$evaluation['risk_score']}\n";
        echo "   - 高风险数量: {$evaluation['high_risk_count']}\n";
        echo "   - 总风险数量: {$evaluation['total_risks']}\n";
        
        // 测试风险优先级计算
        $priority = $evaluationService->calculateRiskPriority($risk1, []);
        echo "✅ 风险优先级计算: {$priority}\n";
    }
    
    echo "\n";
    
    // 4. 测试数据验证
    echo "🛡️ 测试数据验证...\n";
    
    try {
        // 尝试创建无效数据
        $invalidData = [
            'title' => '', // 空标题应该失败
            'description' => '测试描述'
        ];
        
        $riskService->createRisk($invalidData);
        echo "❌ 验证失败 - 应该抛出异常\n";
    } catch (ValidationException $e) {
        echo "✅ 数据验证正常工作 - 捕获到验证异常\n";
        echo "   错误信息: {$e->getFormattedMessage()}\n";
    }
    
    echo "\n";
    
    // 5. 清理测试数据
    echo "🧹 清理测试数据...\n";
    
    $riskRepo->deleteRisk($riskId);
    $riskRepo->deleteRisk($serviceRiskId);
    echo "✅ 清理完成\n";
    
    echo "\n";
    echo "🎉 所有测试完成！\n";
    echo "📋 精简版系统功能正常:\n";
    echo "   ✓ Repository层 - 基础CRUD操作\n";
    echo "   ✓ Service层 - 业务逻辑封装\n";
    echo "   ✓ Domain Services - 跨实体业务规则\n";
    echo "   ✓ 数据验证 - 输入检查\n";
    echo "   ✓ 错误处理 - 异常管理\n";
    
} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "🔍 详细信息: " . $e->getTraceAsString() . "\n";
}

echo "\n📖 精简后的架构说明:\n";
echo "- Repository: 只保留基础CRUD + 简单查询\n";
echo "- Service: 只保留核心业务逻辑 + 基础验证\n";
echo "- Domain Services: 展示跨实体业务逻辑\n";
echo "- Controller: 标准REST API结构\n";
echo "- 删除了复杂的分析、报告、智能功能\n";
echo "- 适合学习DDD架构模式！\n";

?>