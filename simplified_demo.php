<?php

// 手动包含必要的文件进行测试
require_once __DIR__ . '/app/Core/Database/DatabaseConnection.php';
require_once __DIR__ . '/app/Core/Database/BaseRepository.php';
require_once __DIR__ . '/app/Core/Exceptions/AppException.php';
require_once __DIR__ . '/app/Core/Exceptions/DatabaseException.php';
require_once __DIR__ . '/app/Core/Exceptions/ValidationException.php';
require_once __DIR__ . '/app/Core/Utils/Logger.php';

require_once __DIR__ . '/app/Modules/Risk/Entities/Risk.php';
require_once __DIR__ . '/app/Modules/Risk/Entities/Node.php';
require_once __DIR__ . '/app/Modules/Risk/Entities/Feedback.php';

require_once __DIR__ . '/app/Modules/Risk/Repositories/RiskRepository.php';
require_once __DIR__ . '/app/Modules/Risk/Services/RiskService.php';
require_once __DIR__ . '/app/Modules/Risk/DomainServices/RiskEvaluationService.php';

echo "🚀 精简版风险管理系统架构演示\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "📋 精简后的架构说明:\n";
echo "- Repository: 基础CRUD操作 (从686行精简到98行)\n";
echo "- Service: 核心业务逻辑 (从254行精简到155行)\n";
echo "- Domain Services: 跨实体业务规则\n";
echo "- Controller: 标准REST API (简化响应处理)\n";
echo "- 删除了复杂的分析、报告、智能功能\n";
echo "- 保留了完整的DDD架构模式！\n\n";

echo "📁 文件大小对比:\n";

// 显示文件行数对比
$files = [
    'Repository层' => [
        '精简前' => ['RiskRepository_Complex.php' => 686],
        '精简后' => ['RiskRepository.php' => 98]
    ],
    'Service层' => [
        '精简前' => ['RiskService_Complex.php' => 254],
        '精简后' => ['RiskService.php' => 155]
    ],
    'Controller层' => [
        '精简前' => ['RiskController_Complex.php' => 360],
        '精简后' => ['RiskController.php' => 113]
    ]
];

foreach ($files as $layer => $comparison) {
    echo "  {$layer}:\n";
    foreach ($comparison['精简前'] as $file => $lines) {
        echo "    精简前: {$file} ({$lines}行)\n";
    }
    foreach ($comparison['精简后'] as $file => $lines) {
        echo "    精简后: {$file} ({$lines}行)\n";
    }
    
    $beforeLines = array_sum($comparison['精简前']);
    $afterLines = array_sum($comparison['精简后']);
    $reduction = round((($beforeLines - $afterLines) / $beforeLines) * 100, 1);
    echo "    精简度: {$reduction}% ↓\n\n";
}

echo "🎯 保留的核心功能:\n";
echo "✅ Repository层:\n";
echo "  • createRisk() - 创建风险\n";
echo "  • findRiskById() - 查找风险\n";
echo "  • findAllRisks() - 获取所有风险\n";
echo "  • updateRisk() - 更新风险\n";
echo "  • deleteRisk() - 删除风险\n";
echo "  • findHighRisks() - 查找高风险\n";
echo "  • findRisksByStatus() - 按状态查找\n";
echo "  • countRisks() - 统计数量\n\n";

echo "✅ Service层:\n";
echo "  • createRisk() - 业务逻辑验证\n";
echo "  • getRisk() - 实体转换\n";
echo "  • getAllRisks() - 批量处理\n";
echo "  • updateRisk() - 业务规则\n";
echo "  • getHighRisks() - 高风险筛选\n";
echo "  • calculateRiskScore() - 分数计算\n";
echo "  • validateRiskData() - 数据验证\n\n";

echo "✅ Domain Services:\n";
echo "  • evaluateProjectRisk() - 项目风险评估\n";
echo "  • requiresEscalation() - 升级判断\n";
echo "  • calculateRiskPriority() - 优先级计算\n";
echo "  • determineNextNode() - 工作流编排\n";
echo "  • canSkipNode() - 节点跳过规则\n";
echo "  • isWorkflowComplete() - 完成状态检查\n\n";

echo "✅ Controller层:\n";
echo "  • index() - GET /api/risks\n";
echo "  • show() - GET /api/risks/{id}\n";
echo "  • store() - POST /api/risks\n";
echo "  • update() - PUT /api/risks/{id}\n";
echo "  • destroy() - DELETE /api/risks/{id}\n";
echo "  • high() - GET /api/risks/high\n\n";

echo "🗑️ 删除的复杂功能:\n";
echo "❌ 复杂查询 (按日期范围、评分区间等)\n";
echo "❌ 批量操作 (批量创建、更新、删除)\n";
echo "❌ 智能分析 (趋势分析、预测模型)\n";
echo "❌ 详细报告 (统计报表、图表生成)\n";
echo "❌ 高级工作流 (多级审批、条件分支)\n";
echo "❌ 性能优化 (缓存、索引提示)\n";
echo "❌ Enhanced服务 (复杂业务包装)\n";
echo "❌ 大量测试文件和文档\n\n";

echo "🎓 学习价值:\n";
echo "• 清晰展示DDD分层架构\n";
echo "• Repository模式的实现\n";
echo "• Service层的职责划分\n";
echo "• Domain Services的跨实体逻辑\n";
echo "• 实体Entity的设计模式\n";
echo "• 基础的工作流编排概念\n";
echo "• 数据验证和异常处理\n";
echo "• REST API的标准实现\n\n";

echo "📖 建议学习顺序:\n";
echo "1. 先理解Entity实体类设计\n";
echo "2. 学习Repository的数据访问模式\n";
echo "3. 掌握Service的业务逻辑封装\n";
echo "4. 理解Domain Services的跨实体逻辑\n";
echo "5. 学习Controller的API设计\n";
echo "6. 实践工作流编排的基本概念\n\n";

echo "✨ 精简完成！现在您有一个干净、易学的DDD架构示例。\n";

?>