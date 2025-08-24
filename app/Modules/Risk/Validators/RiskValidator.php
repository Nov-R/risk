<?php

namespace App\Modules\Risk\Validators;

use App\Core\Exceptions\ValidationException;

/**
 * 风险数据验证器
 * 
 * 负责验证风险相关数据的完整性和有效性，包括：
 * - 必填字段的存在性检查
 * - 字段值的范围验证
 * - 状态值的有效性检查
 * - 可选字段的类型检查
 */
class RiskValidator {
    /**
     * 允许的风险状态列表
     * 
     * - identified: 已识别
     * - analyzing: 分析中
     * - mitigating: 缓解中
     * - monitoring: 监控中
     * - closed: 已关闭
     */
    private const ALLOWED_STATUSES = ['identified', 'analyzing', 'mitigating', 'monitoring', 'closed'];
    
    /**
     * 验证风险数据
     * 
     * @param array $data 要验证的风险数据
     * @throws ValidationException 当验证失败时
     */
    public function validate(array $data): void {
        $errors = [];

        // Required fields
        if (empty($data['name'])) {
            $errors['name'] = '风险名称不能为空';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = '风险名称不能超过255个字符';
        }

        if (empty($data['description'])) {
            $errors['description'] = '风险描述不能为空';
        }

        // Probability validation
        if (!isset($data['probability'])) {
            $errors['probability'] = '发生概率不能为空';
        } elseif (!is_numeric($data['probability']) || $data['probability'] < 1 || $data['probability'] > 5) {
            $errors['probability'] = '发生概率必须在1到5之间';
        }

        // Impact validation
        if (!isset($data['impact'])) {
            $errors['impact'] = '影响程度不能为空';
        } elseif (!is_numeric($data['impact']) || $data['impact'] < 1 || $data['impact'] > 5) {
            $errors['impact'] = '影响程度必须在1到5之间';
        }

        // Status validation
        if (empty($data['status'])) {
            $errors['status'] = '风险状态不能为空';
        } elseif (!in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors['status'] = '无效的风险状态值';
        }

        // Optional fields validation
        if (isset($data['mitigation']) && !is_string($data['mitigation'])) {
            $errors['mitigation'] = '缓解措施必须是字符串类型';
        }

        if (isset($data['contingency']) && !is_string($data['contingency'])) {
            $errors['contingency'] = '应急计划必须是字符串类型';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
