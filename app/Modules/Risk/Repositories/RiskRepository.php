<?php

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;
use App\Modules\Risk\Entities\Risk;
use App\Modules\Risk\DTOs\RiskDTO;
use PDO;

/**
 * 风险仓储类
 * 处理与风险相关的所有数据库操作
 */
class RiskRepository extends BaseRepository {
    /**
     * 获取表名
    * @return string 表名
     */
    protected function getTableName(): string {
        return 'risks';
    }

    /**
     * 创建风险
     *
     * @param RiskDTO $riskDTO 风险数据传输对象
     * @return int 新创建的风险ID
     */
    public function createRisk(RiskDTO $riskDTO): int {
        return $this->create($riskDTO->toArray());
    }

    /**
     * 更新风险
     *
     * @param int $id 风险ID
     * @param RiskDTO $riskDTO 风险数据传输对象
     * @return bool 是否更新成功
     */
    public function updateRisk(int $id, RiskDTO $riskDTO): bool {
        return $this->update($id, $riskDTO->toArray());
    }

    /**
     * 删除风险
     *
     * @param int $id 风险ID
     * @return bool 是否删除成功
     */
    public function deleteRisk(int $id): bool {
        return $this->delete($id);
    }

    /**
     * 通过ID查找风险
     *
     * @param int $id 风险ID
     * @return Risk|null 风险实体或null
     */
    public function findRiskById(int $id): ?Risk {
        $data = $this->findById($id);
        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * 获取所有风险
     *
     * @return Risk[] 风险实体数组
     */
    public function findAllRisks(): array {
        $risks = $this->findAll();
        return array_map([$this, 'mapToEntity'], $risks);
    }

    /**
     * 按状态查找风险
     *
     * @param string $status 风险状态
     * @return Risk[] 风险实体数组
     */
    public function findRisksByStatus(string $status): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE status = ?";
        $stmt = $this->query($sql, [$status]);
        $risks = $stmt->fetchAll();
        
        return array_map([$this, 'mapToEntity'], $risks);
    }

    /**
     * 查找高风险项目
     *
     * @param int $threshold 风险阈值
     * @return Risk[] 风险实体数组
     */
    public function findHighRisks(int $threshold = 15): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE (probability * impact) >= ?";
        $stmt = $this->query($sql, [$threshold]);
        $risks = $stmt->fetchAll();
        
        return array_map([$this, 'mapToEntity'], $risks);
    }

    /**
     * 按日期范围查找风险
     * 
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return Risk[] 风险实体数组
     */
    public function findRisksByDateRange(string $startDate, string $endDate): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE created_at BETWEEN ? AND ?";
        $stmt = $this->query($sql, [$startDate, $endDate]);
        $risks = $stmt->fetchAll();
        
        return array_map([$this, 'mapToEntity'], $risks);
    }

    /**
     * 查找需要立即处理的风险
     * 
     * @return Risk[] 风险实体数组
     */
    public function findUrgentRisks(): array {
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE status = 'identified' 
                AND (probability * impact) >= 15";
        $stmt = $this->query($sql);
        $risks = $stmt->fetchAll();
        
        return array_map([$this, 'mapToEntity'], $risks);
    }

    /**
     * 按风险分数范围查找风险
     * 
     * @param int $minScore 最小分数
     * @param int $maxScore 最大分数
     * @return Risk[] 风险实体数组
     */
    public function findRisksByScoreRange(int $minScore, int $maxScore): array {
        $sql = "SELECT * FROM {$this->getTableName()} 
                WHERE (probability * impact) BETWEEN ? AND ?";
        $stmt = $this->query($sql, [$minScore, $maxScore]);
        $risks = $stmt->fetchAll();
        
        return array_map([$this, 'mapToEntity'], $risks);
    }

    /**
     * 将数据库记录映射到实体对象
     *
     * @param array $data 数据库记录
     * @return Risk 风险实体
     */
    private function mapToEntity(array $data): Risk {
        $risk = new Risk(
            $data['name'],
            $data['description'],
            (int)$data['probability'],
            (int)$data['impact'],
            $data['status'],
            $data['mitigation'],
            $data['contingency']
        );

        // 使用反射设置protected属性
        $reflection = new \ReflectionClass($risk);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($risk, (int)$data['id']);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($risk, new \DateTime($data['created_at']));

        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $updatedAtProperty->setAccessible(true);
        $updatedAtProperty->setValue($risk, new \DateTime($data['updated_at']));

        return $risk;
    }

    /**
     * 批量更新风险状态
     * 
     * @param array $ids 风险ID数组
     * @param string $status 新状态
     * @return bool 是否更新成功
     */
    public function updateBulkStatus(array $ids, string $status): bool {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE {$this->getTableName()} SET status = ? WHERE id IN ($placeholders)";
        
        $params = array_merge([$status], $ids);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * 获取风险统计信息
     * 
     * @return array 统计信息
     */
    public function getRiskStatistics(): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN probability * impact >= 15 THEN 1 ELSE 0 END) as high_risks,
                    AVG(probability * impact) as avg_score,
                    status,
                    COUNT(*) as status_count
                FROM {$this->getTableName()}
                GROUP BY status";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }
}
