<?php

/**
 * BaseRepository使用示例和验证测试
 * 
 * 这个文件演示了如何正确使用重构后的BaseRepository及其子类
 * 展示了企业级数据访问层的各种功能和最佳实践
 * 
 * @package App\Examples
 * @author Risk Management System
 * @version 1.0.0
 * @since 2024
 */

namespace App\Examples;

use App\Modules\Risk\Repositories\RiskRepository;
use App\Core\Exceptions\DatabaseException;
use App\Core\Utils\Logger;

/**
 * BaseRepository功能验证和使用示例类
 * 
 * 演示了从基础CRUD到高级业务操作的完整用法
 */
class BaseRepositoryExample 
{
    private RiskRepository $riskRepository;

    public function __construct() 
    {
        $this->riskRepository = new RiskRepository();
    }

    /**
     * 运行所有示例
     * 
     * @return void
     */
    public function runAllExamples(): void 
    {
        echo "=== BaseRepository功能验证开始 ===\n\n";

        try {
            // 1. 基础CRUD操作示例
            $this->demonstrateBasicCRUD();
            
            // 2. 批量操作示例
            $this->demonstrateBatchOperations();
            
            // 3. 查询功能示例
            $this->demonstrateQueryFeatures();
            
            // 4. 事务管理示例
            $this->demonstrateTransactions();
            
            // 5. 业务逻辑查询示例
            $this->demonstrateBusinessQueries();
            
            // 6. 性能监控示例
            $this->demonstratePerformanceMonitoring();
            
            // 7. 统计功能示例
            $this->demonstrateStatistics();

            echo "\n=== 所有示例执行完成 ===\n";

        } catch (\Throwable $e) {
            echo "示例执行失败: " . $e->getMessage() . "\n";
            Logger::exception($e, '示例执行失败');
        }
    }

    /**
     * 基础CRUD操作示例
     * 
     * 演示create, update, delete, find等基本操作
     * 
     * @return void
     */
    private function demonstrateBasicCRUD(): void 
    {
        echo "1. === 基础CRUD操作示例 ===\n";

        try {
            // 创建新风险
            $riskData = [
                'title' => '服务器宕机风险',
                'description' => '主数据库服务器可能发生硬件故障导致系统不可用',
                'category' => 'technical',
                'probability' => 3,
                'impact' => 5,
                'status' => 'identified',
                'mitigation_plan' => '部署备份服务器，实现高可用架构',
                'owner' => 'IT运维团队',
                'due_date' => date('Y-m-d', strtotime('+30 days'))
            ];

            echo "创建风险记录...\n";
            $riskId = $this->riskRepository->createRisk($riskData);
            echo "✓ 成功创建风险，ID: {$riskId}\n";

            // 查询创建的风险
            echo "查询风险记录...\n";
            $risk = $this->riskRepository->findRiskById($riskId);
            if ($risk) {
                echo "✓ 成功查询到风险: {$risk['title']}\n";
            }

            // 更新风险
            echo "更新风险记录...\n";
            $updateData = [
                'status' => 'analyzing',
                'mitigation_plan' => '已启动备份服务器部署项目，预计2周内完成'
            ];
            $updated = $this->riskRepository->updateRisk($riskId, $updateData);
            echo $updated ? "✓ 风险更新成功\n" : "✗ 风险更新失败\n";

            // 验证更新结果
            $updatedRisk = $this->riskRepository->findRiskById($riskId);
            echo "更新后状态: {$updatedRisk['status']}\n";

            // 软删除风险
            echo "软删除风险记录...\n";
            $deleted = $this->riskRepository->deleteRisk($riskId, true);
            echo $deleted ? "✓ 风险软删除成功\n" : "✗ 风险软删除失败\n";

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ CRUD操作失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 批量操作示例
     * 
     * 演示批量创建、更新、删除等操作
     * 
     * @return void
     */
    private function demonstrateBatchOperations(): void 
    {
        echo "2. === 批量操作示例 ===\n";

        try {
            // 批量创建风险
            echo "批量创建风险记录...\n";
            $riskDataList = [
                [
                    'title' => '网络安全威胁',
                    'description' => 'DDoS攻击可能导致服务不可用',
                    'category' => 'security',
                    'probability' => 4,
                    'impact' => 4,
                    'status' => 'identified',
                    'owner' => '安全团队'
                ],
                [
                    'title' => '数据备份失败',
                    'description' => '自动备份系统可能出现故障',
                    'category' => 'technical',
                    'probability' => 2,
                    'impact' => 5,
                    'status' => 'identified', 
                    'owner' => 'DBA团队'
                ],
                [
                    'title' => '供应商延期交付',
                    'description' => '关键组件供应商可能延期交付',
                    'category' => 'business',
                    'probability' => 3,
                    'impact' => 3,
                    'status' => 'identified',
                    'owner' => '采购部门'
                ]
            ];

            $createdIds = $this->riskRepository->batchCreateRisks($riskDataList, 10);
            echo "✓ 批量创建成功，创建了 " . count($createdIds) . " 条风险记录\n";
            echo "创建的ID: " . implode(', ', $createdIds) . "\n";

            // 批量更新状态
            if (!empty($createdIds)) {
                echo "批量更新风险状态...\n";
                $affectedCount = $this->riskRepository->batchUpdateStatus($createdIds, 'reviewing');
                echo "✓ 批量更新成功，影响了 {$affectedCount} 条记录\n";
            }

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ 批量操作失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 查询功能示例
     * 
     * 演示各种查询方法的使用
     * 
     * @return void
     */
    private function demonstrateQueryFeatures(): void 
    {
        echo "3. === 查询功能示例 ===\n";

        try {
            // 按状态查询
            echo "按状态查询风险...\n";
            $reviewingRisks = $this->riskRepository->findRisksByStatus('reviewing');
            echo "✓ 找到 " . count($reviewingRisks) . " 个'reviewing'状态的风险\n";

            // 查询高风险项目
            echo "查询高风险项目...\n";
            $highRisks = $this->riskRepository->findHighRisks(12);
            echo "✓ 找到 " . count($highRisks) . " 个高风险项目(分数>=12)\n";

            // 按类别查询
            echo "按类别查询风险...\n";
            $technicalRisks = $this->riskRepository->findRisksByCategory('technical');
            echo "✓ 找到 " . count($technicalRisks) . " 个技术类风险\n";

            // 查询即将到期的风险
            echo "查询即将到期的风险...\n";
            $dueSoonRisks = $this->riskRepository->findRisksDueSoon(30);
            echo "✓ 找到 " . count($dueSoonRisks) . " 个30天内到期的风险\n";

            // 统计风险数量
            echo "统计风险总数...\n";
            $totalRisks = $this->riskRepository->countRisks();
            echo "✓ 当前风险总数: {$totalRisks}\n";

            // 检查风险是否存在
            echo "检查特定条件风险是否存在...\n";
            $exists = $this->riskRepository->riskExists(['category' => 'security', 'status' => 'reviewing']);
            echo $exists ? "✓ 存在安全类且状态为reviewing的风险\n" : "✗ 不存在符合条件的风险\n";

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ 查询操作失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 事务管理示例
     * 
     * 演示事务的使用和错误回滚
     * 
     * @return void
     */
    private function demonstrateTransactions(): void 
    {
        echo "4. === 事务管理示例 ===\n";

        try {
            echo "执行事务操作...\n";
            
            $result = $this->riskRepository->executeTransaction(function($repository) {
                // 在事务中执行多个操作
                $riskId1 = $repository->createRisk([
                    'title' => '事务测试风险1',
                    'description' => '用于测试事务功能',
                    'category' => 'test',
                    'probability' => 1,
                    'impact' => 1,
                    'status' => 'identified',
                    'owner' => '测试团队'
                ]);

                $riskId2 = $repository->createRisk([
                    'title' => '事务测试风险2', 
                    'description' => '用于测试事务功能',
                    'category' => 'test',
                    'probability' => 2,
                    'impact' => 2,
                    'status' => 'identified',
                    'owner' => '测试团队'
                ]);

                // 批量更新
                $repository->batchUpdateStatus([$riskId1, $riskId2], 'closed');

                return ['risk1' => $riskId1, 'risk2' => $riskId2];
            });

            echo "✓ 事务执行成功，创建了风险ID: {$result['risk1']}, {$result['risk2']}\n";

            // 演示事务回滚
            echo "演示事务回滚...\n";
            try {
                $this->riskRepository->executeTransaction(function($repository) {
                    // 创建一个风险
                    $riskId = $repository->createRisk([
                        'title' => '将被回滚的风险',
                        'description' => '这个风险会在事务回滚时被撤销',
                        'category' => 'test',
                        'probability' => 1,
                        'impact' => 1,
                        'status' => 'identified',
                        'owner' => '测试团队'
                    ]);

                    // 人为抛出异常触发回滚
                    throw new \Exception('测试事务回滚');
                });
            } catch (\Exception $e) {
                echo "✓ 事务回滚成功: " . $e->getMessage() . "\n";
            }

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ 事务操作失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 业务逻辑查询示例
     * 
     * 演示复杂的业务查询场景
     * 
     * @return void
     */
    private function demonstrateBusinessQueries(): void 
    {
        echo "5. === 业务逻辑查询示例 ===\n";

        try {
            // 查找紧急风险
            echo "查找需要立即处理的紧急风险...\n";
            $urgentRisks = $this->riskRepository->findUrgentRisks();
            echo "✓ 找到 " . count($urgentRisks) . " 个紧急风险\n";

            // 按风险分数范围查询
            echo "查找中等风险(分数9-15)...\n";
            $mediumRisks = $this->riskRepository->findRisksByScoreRange(9, 15);
            echo "✓ 找到 " . count($mediumRisks) . " 个中等风险\n";

            // 按负责人查询
            echo "查询测试团队负责的风险...\n";
            $testTeamRisks = $this->riskRepository->findRisksByOwner('测试团队');
            echo "✓ 找到 " . count($testTeamRisks) . " 个测试团队负责的风险\n";

            // 按日期范围查询
            echo "查询最近7天创建的风险...\n";
            $recentRisks = $this->riskRepository->findRisksByDateRange(
                date('Y-m-d', strtotime('-7 days')),
                date('Y-m-d')
            );
            echo "✓ 找到 " . count($recentRisks) . " 个最近7天创建的风险\n";

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ 业务查询失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 性能监控示例
     * 
     * 演示查询性能统计功能
     * 
     * @return void
     */
    private function demonstratePerformanceMonitoring(): void 
    {
        echo "6. === 性能监控示例 ===\n";

        try {
            // 重置统计
            echo "重置查询统计...\n";
            $this->riskRepository->resetQueryStats();
            echo "✓ 统计已重置\n";

            // 执行一些查询操作
            echo "执行查询操作以生成统计数据...\n";
            $this->riskRepository->countRisks();
            $this->riskRepository->findRisksByStatus('identified');
            $this->riskRepository->findHighRisks(15);

            // 获取查询统计
            $stats = $this->riskRepository->getQueryStats();
            echo "✓ 查询统计信息:\n";
            echo "  - SELECT查询: {$stats['select_count']} 次\n";
            echo "  - INSERT查询: {$stats['insert_count']} 次\n";
            echo "  - UPDATE查询: {$stats['update_count']} 次\n";
            echo "  - DELETE查询: {$stats['delete_count']} 次\n";

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ 性能监控失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 统计功能示例
     * 
     * 演示数据统计和分析功能
     * 
     * @return void
     */
    private function demonstrateStatistics(): void 
    {
        echo "7. === 统计功能示例 ===\n";

        try {
            // 获取风险统计信息
            echo "获取风险统计信息...\n";
            $statistics = $this->riskRepository->getRiskStatistics();
            
            echo "✓ 风险统计摘要:\n";
            if (!empty($statistics['summary'])) {
                $summary = $statistics['summary'];
                echo "  - 总风险数: {$summary['total_risks']}\n";
                echo "  - 高风险数: {$summary['high_risk_count']}\n";
                echo "  - 中风险数: {$summary['medium_risk_count']}\n";
                echo "  - 低风险数: {$summary['low_risk_count']}\n";
                echo "  - 平均分数: {$summary['avg_risk_score']}\n";
            }

            echo "✓ 按状态统计:\n";
            foreach ($statistics['by_status'] as $statusStat) {
                echo "  - {$statusStat['status']}: {$statusStat['count']} 个 (平均分: {$statusStat['avg_score']})\n";
            }

            echo "✓ 按类别统计:\n";
            foreach ($statistics['by_category'] as $categoryStat) {
                echo "  - {$categoryStat['category']}: {$categoryStat['count']} 个 (平均分: {$categoryStat['avg_score']})\n";
            }

            // 获取趋势数据
            echo "\n获取风险趋势数据...\n";
            $trendData = $this->riskRepository->getRiskTrendData(3); // 最近3个月
            echo "✓ 最近3个月风险趋势:\n";
            foreach ($trendData as $trend) {
                echo "  - {$trend['month']}: 创建 {$trend['total_created']} 个风险 (高风险: {$trend['high_risk_created']} 个)\n";
            }

            echo "\n";

        } catch (DatabaseException $e) {
            echo "✗ 统计功能失败: " . $e->getMessage() . "\n\n";
        }
    }

    /**
     * 清理测试数据
     * 
     * @return void
     */
    public function cleanupTestData(): void 
    {
        echo "=== 清理测试数据 ===\n";
        
        try {
            // 删除测试类别的风险
            $testRisks = $this->riskRepository->findRisksByCategory('test');
            $testIds = array_column($testRisks, 'id');
            
            if (!empty($testIds)) {
                $deletedCount = $this->riskRepository->batchSoftDeleteRisks($testIds);
                echo "✓ 清理了 {$deletedCount} 个测试风险记录\n";
            } else {
                echo "✓ 没有需要清理的测试数据\n";
            }

        } catch (DatabaseException $e) {
            echo "✗ 清理测试数据失败: " . $e->getMessage() . "\n";
        }
    }
}

// 如果直接运行此文件，执行示例
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "BaseRepository功能验证脚本\n";
    echo "================================\n\n";
    
    try {
        // 加载必要的文件
        require_once __DIR__ . '/../config/autoload.php';
        
        $example = new BaseRepositoryExample();
        $example->runAllExamples();
        $example->cleanupTestData();
        
    } catch (\Throwable $e) {
        echo "脚本执行失败: " . $e->getMessage() . "\n";
        echo "错误文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
