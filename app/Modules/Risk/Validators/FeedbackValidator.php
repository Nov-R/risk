<?php

namespace App\Modules\Risk\Validators;

use App\Core\Exceptions\ValidationException;

/**
 * 风险反馈验证器
 * 
 * 负责验证风险反馈数据的完整性和有效性，包括：
 * - 关联风险ID的有效性
 * - 反馈内容的必填检查
 * - 反馈类型的有效性
 * - 创建者信息的验证
 * - 状态值的有效性检查
 */
class FeedbackValidator {
    /**
     * 允许的反馈类型
     * 
     * - comment: 一般评论
     * - assessment: 风险评估
     * - mitigation_proposal: 缓解方案建议
     * - status_update: 状态更新
     */
    private const ALLOWED_TYPES = ['comment', 'assessment', 'mitigation_proposal', 'status_update'];
    
    /**
     * 允许的反馈状态
     * 
     * - pending: 待处理
     * - approved: 已批准
     * - rejected: 已拒绝
     */
    private const ALLOWED_STATUSES = ['pending', 'approved', 'rejected'];
    
    /**
     * 验证反馈数据
     * 
     * @param array $data 要验证的反馈数据
     * @throws ValidationException 当验证失败时
     */
    public function validate(array $data): void {
        $errors = [];

        // Validate risk_id
        if (empty($data['risk_id'])) {
            $errors['risk_id'] = '必须关联风险ID';
        } elseif (!is_numeric($data['risk_id']) || $data['risk_id'] < 1) {
            $errors['risk_id'] = '无效的风险ID';
        }

        // Validate content
        if (empty($data['content'])) {
            $errors['content'] = '反馈内容不能为空';
        }

        // Validate type
        if (empty($data['type'])) {
            $errors['type'] = '反馈类型不能为空';
        } elseif (!in_array($data['type'], self::ALLOWED_TYPES)) {
            $errors['type'] = '无效的反馈类型';
        }

        // Validate created_by
        if (empty($data['created_by'])) {
            $errors['created_by'] = '创建者信息不能为空';
        } elseif (strlen($data['created_by']) > 255) {
            $errors['created_by'] = '创建者名称不能超过255个字符';
        }

        // Validate status if provided
        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors['status'] = '无效的状态值';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * 验证部分更新的反馈数据
     * 
     * @param array $data 要验证的反馈数据（只包含需要更新的字段）
     * @throws ValidationException 当验证失败时
     */
    public function validatePartialUpdate(array $data): void {
        $errors = [];

        // Validate risk_id (if provided, but usually shouldn't be changed)
        if (isset($data['risk_id'])) {
            if (!is_numeric($data['risk_id']) || $data['risk_id'] < 1) {
                $errors['risk_id'] = '无效的风险ID';
            }
        }

        // Validate content (if provided)
        if (isset($data['content']) && empty($data['content'])) {
            $errors['content'] = '反馈内容不能为空';
        }

        // Validate type (if provided)
        if (isset($data['type'])) {
            if (empty($data['type'])) {
                $errors['type'] = '反馈类型不能为空';
            } elseif (!in_array($data['type'], self::ALLOWED_TYPES)) {
                $errors['type'] = '无效的反馈类型';
            }
        }

        // Validate created_by (if provided, but usually shouldn't be changed)
        if (isset($data['created_by'])) {
            if (empty($data['created_by'])) {
                $errors['created_by'] = '创建者信息不能为空';
            } elseif (strlen($data['created_by']) > 255) {
                $errors['created_by'] = '创建者名称不能超过255个字符';
            }
        }

        // Validate status (if provided)
        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors['status'] = '无效的状态值';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
