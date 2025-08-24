<?php

namespace App\Modules\Risk\DTOs;

/**
 * 风险数据传输对象
 * 
 * 用于在系统各层之间传递风险相关数据的不可变对象。
 * 包含风险的基本信息、评估数据和应对措施。
 */
class RiskDTO {
    /**
     * 构造函数
     * 
     * @param string $name 风险名称
     * @param string $description 风险描述
     * @param int $probability 发生概率（1-5）
     * @param int $impact 影响程度（1-5）
     * @param string $status 风险状态（active/mitigated/closed/monitoring）
     * @param string|null $mitigation 缓解措施（可选）
     * @param string|null $contingency 应急计划（可选）
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $probability,
        public readonly int $impact,
        public readonly string $status,
        public readonly ?string $mitigation = null,
        public readonly ?string $contingency = null
    ) {}

    /**
     * 从数组创建风险DTO实例
     * 
     * @param array $data 包含风险数据的关联数组
     * @return self 新的RiskDTO实例
     */
    public static function fromArray(array $data): self {
        return new self(
            $data['name'],
            $data['description'],
            (int)$data['probability'],
            (int)$data['impact'],
            $data['status'],
            $data['mitigation'] ?? null,
            $data['contingency'] ?? null
        );
    }

    /**
     * 将风险DTO转换为数组
     * 
     * @return array 包含风险数据的关联数组
     */
    public function toArray(): array {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'probability' => $this->probability,
            'impact' => $this->impact,
            'status' => $this->status,
            'mitigation' => $this->mitigation,
            'contingency' => $this->contingency,
        ];
    }
}
