<?php

namespace App\Modules\Risk\Repositories;

use App\Core\Database\BaseRepository;
use App\Modules\Risk\Entities\Feedback;
use App\Modules\Risk\DTOs\FeedbackDTO;
use PDO;


/**
 * 风险反馈仓储类
 *
 * 负责处理与风险反馈相关的所有数据库操作，包括增删改查等。
 */
class FeedbackRepository extends BaseRepository {
    /**
     * 获取表名
     * @return string 表名
     */
    protected function getTableName(): string {
        return 'feedbacks';
    }

    /**
     * 创建反馈
     * @param FeedbackDTO $feedbackDTO 反馈数据传输对象
     * @return int 新建反馈ID
     */
    public function createFeedback(FeedbackDTO $feedbackDTO): int {
        return $this->create($feedbackDTO->toArray());
    }

    /**
     * 更新反馈
     * @param int $id 反馈ID
     * @param FeedbackDTO $feedbackDTO 反馈数据传输对象
     * @return bool 是否更新成功
     */
    public function updateFeedback(int $id, FeedbackDTO $feedbackDTO): bool {
        return $this->update($id, $feedbackDTO->toArray());
    }

    /**
     * 删除反馈
     * @param int $id 反馈ID
     * @return bool 是否删除成功
     */
    public function deleteFeedback(int $id): bool {
        return $this->delete($id);
    }

    /**
     * 通过ID查找反馈
     * @param int $id 反馈ID
     * @return Feedback|null 反馈实体或null
     */
    public function findFeedbackById(int $id): ?Feedback {
        $data = $this->findById($id);
        return $data ? $this->mapToEntity($data) : null;
    }

    /**
     * 获取所有反馈
     * @return Feedback[] 反馈实体数组
     */
    public function findAllFeedbacks(): array {
        $feedbacks = $this->findAll();
        return array_map([$this, 'mapToEntity'], $feedbacks);
    }

    /**
     * 根据风险ID查找反馈
     * @param int $riskId 风险ID
     * @return Feedback[] 反馈实体数组
     */
    public function findFeedbacksByRiskId(int $riskId): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE risk_id = ?";
        $stmt = $this->query($sql, [$riskId]);
        $feedbacks = $stmt->fetchAll();
        return array_map([$this, 'mapToEntity'], $feedbacks);
    }

    /**
     * 根据状态查找反馈
     * @param string $status 反馈状态
     * @return Feedback[] 反馈实体数组
     */
    public function findFeedbacksByStatus(string $status): array {
        $sql = "SELECT * FROM {$this->getTableName()} WHERE status = ?";
        $stmt = $this->query($sql, [$status]);
        $feedbacks = $stmt->fetchAll();
        return array_map([$this, 'mapToEntity'], $feedbacks);
    }

    /**
     * 将数据库记录映射为反馈实体对象
     * @param array $data 数据库记录
     * @return Feedback 反馈实体
     */
    private function mapToEntity(array $data): Feedback {
        $feedback = new Feedback(
            (int)$data['risk_id'],
            $data['content'],
            $data['type'],
            $data['created_by'],
            $data['status']
        );

        // 反射设置受保护属性
        $reflection = new \ReflectionClass($feedback);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($feedback, (int)$data['id']);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($feedback, new \DateTime($data['created_at']));

        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $updatedAtProperty->setAccessible(true);
        $updatedAtProperty->setValue($feedback, new \DateTime($data['updated_at']));

        return $feedback;
    }
}
