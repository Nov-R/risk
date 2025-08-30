<?php

namespace App\Modules\Risk\Entities;

/**
 * 流程节点实体类
 * 
 * 表示系统中的审核流程节点，可以是风险审核或反馈审核
 * - 每个节点都有一个类型（风险审核/反馈审核）
 * - 节点状态包括：待审核、已通过、已拒绝
 * - 节点记录审核人、审核意见等信息
 */
class Node {
    /** @var int 节点ID */
    private int $id;
    
    /** @var int|null 关联的风险ID */
    private ?int $riskId;
    
    /** @var int|null 关联的反馈ID */
    private ?int $feedbackId;
    
    /** @var string 节点类型（risk_review/feedback_review） */
    private string $type;
    
    /** @var string 节点状态（pending/approved/rejected） */
    private string $status;
    
    /** @var string 审核人 */
    private string $reviewer;
    
    /** @var string|null 审核意见 */
    private ?string $comments;
    
    /** @var \DateTime 创建时间 */
    private \DateTime $createdAt;
    
    /** @var \DateTime 更新时间 */
    private \DateTime $updatedAt;

    public function __construct(
        string $type,
        string $reviewer,
        ?int $riskId = null,
        ?int $feedbackId = null,
        ?string $comments = null,
        string $status = 'pending'
    ) {
        if ($type === 'risk_review' && $riskId === null) {
            throw new \InvalidArgumentException('风险审核节点必须提供风险ID');
        }
        if ($type === 'feedback_review' && $feedbackId === null) {
            throw new \InvalidArgumentException('反馈审核节点必须提供反馈ID');
        }

        $this->type = $type;
        $this->reviewer = $reviewer;
        $this->riskId = $riskId;
        $this->feedbackId = $feedbackId;
        $this->comments = $comments;
        $this->status = $status;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters
    /** 获取节点ID */
    public function getId(): int {
        return $this->id;
    }

    /** 获取关联的风险ID（如无则为 null） */
    public function getRiskId(): ?int {
        return $this->riskId;
    }

    /** 获取关联的反馈ID（如无则为 null） */
    public function getFeedbackId(): ?int {
        return $this->feedbackId;
    }

    /** 获取节点类型 */
    public function getType(): string {
        return $this->type;
    }

    /** 获取节点状态 */
    public function getStatus(): string {
        return $this->status;
    }

    /** 获取审核者标识 */
    public function getReviewer(): string {
        return $this->reviewer;
    }

    /** 获取审核备注（可为 null） */
    public function getComments(): ?string {
        return $this->comments;
    }

    /** 获取创建时间 */
    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }

    /** 获取更新时间 */
    public function getUpdatedAt(): \DateTime {
        return $this->updatedAt;
    }

    // Setters
    /** 设置节点状态并更新更新时间 */
    public function setStatus(string $status): void {
        $this->status = $status;
        $this->updateTimestamp();
    }

    /** 设置审核备注并更新时间 */
    public function setComments(?string $comments): void {
        $this->comments = $comments;
        $this->updateTimestamp();
    }

    /** 设置审核者并更新时间 */
    public function setReviewer(string $reviewer): void {
        $this->reviewer = $reviewer;
        $this->updateTimestamp();
    }

    /** 更新实体的更新时间戳（内部方法） */
    private function updateTimestamp(): void {
        $this->updatedAt = new \DateTime();
    }

    // Business logic
    /** 判断是否为风险审核节点 */
    public function isRiskReview(): bool {
        return $this->type === 'risk_review';
    }

    /** 判断是否为反馈审核节点 */
    public function isFeedbackReview(): bool {
        return $this->type === 'feedback_review';
    }

    /** 判断是否处于待处理状态 */
    public function isPending(): bool {
        return $this->status === 'pending';
    }

    /** 判断是否已通过 */
    public function isApproved(): bool {
        return $this->status === 'approved';
    }

    /** 判断是否被拒绝 */
    public function isRejected(): bool {
        return $this->status === 'rejected';
    }

    /** 审批通过当前节点（可附带备注） */
    public function approve(string $comments = null): void {
        $this->setStatus('approved');
        if ($comments !== null) {
            $this->setComments($comments);
        }
    }

    /** 拒绝当前节点并记录拒绝原因 */
    public function reject(string $comments): void {
        $this->setStatus('rejected');
        $this->setComments($comments);
    }

    /**
     * 从数组创建 Node 实体
     * 
     * @param array $data 数据库记录数组
     * @return Node 节点实体
     */
    public static function fromArray(array $data): Node {
        $node = new self(
            $data['type'],
            $data['reviewer'],
            $data['risk_id'] ? (int)$data['risk_id'] : null,
            $data['feedback_id'] ? (int)$data['feedback_id'] : null,
            $data['comments'],
            $data['status']
        );

        // 反射设置受保护属性
        $reflection = new \ReflectionClass($node);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($node, (int)$data['id']);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($node, new \DateTime($data['created_at']));

        $updatedAtProperty = $reflection->getProperty('updatedAt');
        $updatedAtProperty->setAccessible(true);
        $updatedAtProperty->setValue($node, new \DateTime($data['updated_at']));

        return $node;
    }

    /**
     * 将实体转换为数组格式
     * 
     * @return array 格式化后的实体数据
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'risk_id' => $this->riskId,
            'feedback_id' => $this->feedbackId,
            'type' => $this->type ?? 'unknown',
            'status' => $this->status ?? 'pending',
            'reviewer' => $this->reviewer ?? '',
            'comments' => $this->comments,
            'created_at' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected()
        ];
    }
}
