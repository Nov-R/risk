<?php

namespace App\Modules\Risk\Entities;


/**
 * 风险实体类
 *
 * 表示系统中的风险信息，包括名称、描述、概率、影响、状态、应对措施等。
 */
class Risk {
    /** @var int 风险ID */
    private int $id;
    /** @var string 风险名称 */
    private string $name;
    /** @var string 风险描述 */
    private string $description;
    /** @var int 发生概率 */
    private int $probability;
    /** @var int 影响程度 */
    private int $impact;
    /** @var string 风险状态 */
    private string $status;
    /** @var string|null 缓解措施 */
    private ?string $mitigation;
    /** @var string|null 应急计划 */
    private ?string $contingency;
    /** @var \DateTime 创建时间 */
    private \DateTime $createdAt;
    /** @var \DateTime 更新时间 */
    private \DateTime $updatedAt;

    /**
     * 构造函数
     *
     * @param string $name 风险名称
     * @param string $description 风险描述
     * @param int $probability 发生概率
     * @param int $impact 影响程度
     * @param string $status 风险状态
     * @param string|null $mitigation 缓解措施（可选）
     * @param string|null $contingency 应急计划（可选）
     */
    public function __construct(
        string $name,
        string $description,
        int $probability,
        int $impact,
        string $status,
        ?string $mitigation = null,
        ?string $contingency = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->probability = $probability;
        $this->impact = $impact;
        $this->status = $status;
        $this->mitigation = $mitigation;
        $this->contingency = $contingency;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // 获取器
    /** 获取风险ID */
    public function getId(): int {
        return $this->id;
    }
    /** 获取风险名称 */
    public function getName(): string {
        return $this->name;
    }
    /** 获取风险描述 */
    public function getDescription(): string {
        return $this->description;
    }
    /** 获取发生概率 */
    public function getProbability(): int {
        return $this->probability;
    }
    /** 获取影响程度 */
    public function getImpact(): int {
        return $this->impact;
    }
    /** 获取风险状态 */
    public function getStatus(): string {
        return $this->status;
    }
    /** 获取缓解措施（可为 null） */
    public function getMitigation(): ?string {
        return $this->mitigation;
    }
    /** 获取应急计划（可为 null） */
    public function getContingency(): ?string {
        return $this->contingency;
    }
    /** 获取创建时间 */
    public function getCreatedAt(): \DateTime {
        return $this->createdAt;
    }
    /** 获取更新时间 */
    public function getUpdatedAt(): \DateTime {
        return $this->updatedAt;
    }

    // 设置器（自动更新时间戳）
    /** 设置名称并更新时间 */
    public function setName(string $name): void {
        $this->name = $name;
        $this->updateTimestamp();
    }
    /** 设置描述并更新时间 */
    public function setDescription(string $description): void {
        $this->description = $description;
        $this->updateTimestamp();
    }
    /** 设置发生概率并更新时间 */
    public function setProbability(int $probability): void {
        $this->probability = $probability;
        $this->updateTimestamp();
    }
    /** 设置影响程度并更新时间 */
    public function setImpact(int $impact): void {
        $this->impact = $impact;
        $this->updateTimestamp();
    }
    /** 设置状态并更新时间 */
    public function setStatus(string $status): void {
        $this->status = $status;
        $this->updateTimestamp();
    }
    /** 设置缓解措施并更新时间 */
    public function setMitigation(?string $mitigation): void {
        $this->mitigation = $mitigation;
        $this->updateTimestamp();
    }
    /** 设置应急计划并更新时间 */
    public function setContingency(?string $contingency): void {
        $this->contingency = $contingency;
        $this->updateTimestamp();
    }
    /** 更新更新时间戳（内部方法） */
    private function updateTimestamp(): void {
        $this->updatedAt = new \DateTime();
    }

    // 业务逻辑
    /** 计算风险分数（概率 * 影响） */
    public function calculateRiskScore(): int {
        return $this->probability * $this->impact;
    }
    /** 判断是否为高风险（分数 >= 15） */
    public function isHighRisk(): bool {
        return $this->calculateRiskScore() >= 15;
    }
    /** 判断是否需要立即处理（高风险且状态为已识别） */
    public function requiresImmediateAction(): bool {
        return $this->isHighRisk() && $this->status === 'identified';
    }
}
