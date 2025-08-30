<?php

namespace App\Modules\Risk\Entities;


/**
 * 风险反馈实体类
 *
 * 表示系统中的风险反馈信息，包括内容、类型、状态、创建者等。
 */
class Feedback {
    /** @var int 反馈ID */
    private int $id;
    /** @var int 关联的风险ID */
    private int $riskId;
    /** @var string 反馈内容 */
    private string $content;
    /** @var string 反馈类型 */
    private string $type;
    /** @var string 反馈状态 */
    private string $status;
    /** @var string 创建者 */
    private string $createdBy;
    /** @var \DateTime 创建时间 */
    private \DateTime $createdAt;
    /** @var \DateTime 更新时间 */
    private \DateTime $updatedAt;

    /**
     * 构造函数
     *
     * @param int $riskId 关联的风险ID
     * @param string $content 反馈内容
     * @param string $type 反馈类型
     * @param string $createdBy 创建者
     * @param string $status 反馈状态，默认pending
     */
    public function __construct(
        int $riskId,
        string $content,
        string $type,
        string $createdBy,
        string $status = 'pending'
    ) {
        $this->riskId = $riskId;
        $this->content = $content;
        $this->type = $type;
        $this->createdBy = $createdBy;
        $this->status = $status;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // 获取器
    /** 获取反馈ID */
    public function getId(): int {
        return $this->id;
    }
    /** 获取关联的风险ID */
    public function getRiskId(): int {
        return $this->riskId;
    }
    /** 获取反馈内容 */
    public function getContent(): string {
        return $this->content;
    }
    /** 获取反馈类型 */
    public function getType(): string {
        return $this->type;
    }
    /** 获取反馈状态 */
    public function getStatus(): string {
        return $this->status;
    }
    /** 获取创建者 */
    public function getCreatedBy(): string {
        return $this->createdBy;
    }
    /** 获取创建时间 */
    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }
    /** 获取更新时间 */
    public function getUpdatedAt(): \DateTime {
        return $this->updatedAt;
    }

    // 设置器
    /** 设置内容并更新时间 */
    public function setContent(string $content): void {
        $this->content = $content;
        $this->updateTimestamp();
    }
    /** 设置类型并更新时间 */
    public function setType(string $type): void {
        $this->type = $type;
        $this->updateTimestamp();
    }
    /** 设置状态并更新时间 */
    public function setStatus(string $status): void {
        $this->status = $status;
        $this->updateTimestamp();
    }
    /** 更新更新时间戳（内部方法） */
    private function updateTimestamp(): void {
        $this->updatedAt = new \DateTime();
    }

    // 业务逻辑
    /** 判断反馈是否已通过审核 */
    public function isApproved(): bool {
        return $this->status === 'approved';
    }
    /** 判断反馈是否待处理 */
    public function isPending(): bool {
        return $this->status === 'pending';
    }
    /** 判断反馈是否被拒绝 */
    public function isRejected(): bool {
        return $this->status === 'rejected';
    }
    /** 审核通过反馈 */
    public function approve(): void {
        $this->setStatus('approved');
    }
    /** 拒绝反馈 */
    public function reject(): void {
        $this->setStatus('rejected');
    }

    /**
     * 从数组创建 Feedback 实体
     * 
     * @param array $data 数据库记录数组
     * @return Feedback 反馈实体
     */
    public static function fromArray(array $data): Feedback {
        $feedback = new self(
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

    /**
     * 将实体转换为数组格式
     * 
     * @return array 格式化后的实体数据
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'risk_id' => $this->riskId ?? 0,
            'content' => $this->content ?? '',
            'type' => $this->type ?? 'general',
            'status' => $this->status ?? 'pending',
            'created_by' => $this->createdBy ?? 'system',
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}
