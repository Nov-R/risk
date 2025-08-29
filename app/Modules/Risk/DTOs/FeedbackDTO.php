<?php

namespace App\Modules\Risk\DTOs;



/**
 * 风险反馈数据传输对象（DTO）
 *
 * 用于在系统各层之间传递风险反馈相关数据的不可变对象。
 * 包含反馈的基本信息、类型、状态和创建者等。
 */
class FeedbackDTO {
    public int $riskId;
    public string $content;
    public string $type;
    public string $createdBy;
    public string $status;

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
    }
    
    /**
     * 从数组创建反馈DTO实例
     *
     * @param array $data 包含反馈数据的关联数组
     * @return self 新的FeedbackDTO实例
     */
    public static function fromArray(array $data): self {
        return new self(
            (int)$data['risk_id'],
            $data['content'],
            $data['type'],
            $data['created_by'],
            $data['status'] ?? 'pending'
        );
    }

    /**
     * 将反馈DTO转换为数组
     *
     * @return array 包含反馈数据的关联数组
     */
    public function toArray(): array {
        return [
            'risk_id' => $this->riskId,
            'content' => $this->content,
            'type' => $this->type,
            'created_by' => $this->createdBy,
            'status' => $this->status
        ];
    }
}
