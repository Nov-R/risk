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
     * 验证风险数据（自适应所有字段）
     * 
     * @param array $data 要验证的风险数据
     * @throws ValidationException 当验证失败时
     */
    public function validate(array $data): void {
        $errors = [];

        // Name validation
        if (array_key_exists('name', $data)) {
            if (empty($data['name'])) {
                $errors['name'] = '风险名称不能为空';
            } elseif (strlen($data['name']) > 255) {
                $errors['name'] = '风险名称不能超过255个字符';
            }
        }

        // Description validation
        if (array_key_exists('description', $data)) {
            if (empty($data['description'])) {
                $errors['description'] = '风险描述不能为空';
            }
        }

        // Probability validation
        if (array_key_exists('probability', $data)) {
            if (!is_numeric($data['probability']) || $data['probability'] < 1 || $data['probability'] > 5) {
                $errors['probability'] = '发生概率必须在1到5之间';
            }
        }

        // Impact validation
        if (array_key_exists('impact', $data)) {
            if (!is_numeric($data['impact']) || $data['impact'] < 1 || $data['impact'] > 5) {
                $errors['impact'] = '影响程度必须在1到5之间';
            }
        }

        // Status validation
        if (array_key_exists('status', $data)) {
            if (empty($data['status'])) {
                $errors['status'] = '风险状态不能为空';
            } elseif (!in_array($data['status'], self::ALLOWED_STATUSES)) {
                $errors['status'] = '无效的风险状态值';
            }
        }

        // Mitigation validation
        if (array_key_exists('mitigation', $data) && $data['mitigation'] !== null && !is_string($data['mitigation'])) {
            $errors['mitigation'] = '缓解措施必须是字符串类型';
        }

        // Contingency validation
        if (array_key_exists('contingency', $data) && $data['contingency'] !== null && !is_string($data['contingency'])) {
            $errors['contingency'] = '应急计划必须是字符串类型';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
