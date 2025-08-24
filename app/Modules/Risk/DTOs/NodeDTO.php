<?php

namespace App\Modules\Risk\DTOs;


/**
 * 流程节点数据传输对象（DTO）
 *
 * 用于在系统各层之间传递节点相关数据的不可变对象。
 * 包含节点的基本信息、关联ID、状态和备注等。
 */
class NodeDTO {
    /**
     * 构造函数
     *
     * @param string $type 节点类型
     * @param string $reviewer 审核人
     * @param int|null $riskId 关联的风险ID（可选）
     * @param int|null $feedbackId 关联的反馈ID（可选）
     * @param string|null $comments 备注（可选）
     * @param string $status 节点状态，默认pending
     */
    public function __construct(
        public readonly string $type,
        public readonly string $reviewer,
        public readonly ?int $riskId = null,
        public readonly ?int $feedbackId = null,
        public readonly ?string $comments = null,
        public readonly string $status = 'pending'
    ) {}

    /**
     * 从数组创建节点DTO实例
     *
     * @param array $data 包含节点数据的关联数组
     * @return self 新的NodeDTO实例
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['type'],
            $data['reviewer'],
            $data['risk_id'] ?? null,
            $data['feedback_id'] ?? null,
            $data['comments'] ?? null,
            $data['status'] ?? 'pending'
        );
    }

    /**
     * 将节点DTO转换为数组
     *
     * @return array 包含节点数据的关联数组
     */
    public function toArray(): array {
        return [
            'type' => $this->type,
            'reviewer' => $this->reviewer,
            'risk_id' => $this->riskId,
            'feedback_id' => $this->feedbackId,
            'comments' => $this->comments,
            'status' => $this->status
        ];
    }
}
