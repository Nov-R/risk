<?php

namespace App\Modules\Risk\Validators;

use App\Core\Exceptions\ValidationException;

/**
 * 流程节点验证器
 * 
 * 负责验证流程节点数据的完整性和有效性，包括：
 * - 节点类型的有效性
 * - 审核人信息的验证
 * - 关联ID的逻辑验证
 * - 节点状态的有效性
 * - 节点备注的有效性
 */
class NodeValidator {
    /**
     * 允许的节点类型
     * 
     * - risk_review: 风险审核
     * - feedback_review: 反馈审核
     */
    private const ALLOWED_TYPES = ['risk_review', 'feedback_review'];
    
    /**
     * 允许的节点状态
     * 
     * - pending: 待审核
     * - approved: 已通过
     * - rejected: 已拒绝
     */
    private const ALLOWED_STATUSES = ['pending', 'approved', 'rejected'];
    
    /**
     * 验证节点数据（自适应所有字段）
     * 
     * @param array $data 要验证的节点数据
     * @throws ValidationException 当验证失败时
     */
    public function validate(array $data): void {
        $errors = [];

        // Type validation
        if (array_key_exists('type', $data)) {
            if (empty($data['type'])) {
                $errors['type'] = '节点类型不能为空';
            } elseif (!in_array($data['type'], self::ALLOWED_TYPES)) {
                $errors['type'] = '无效的节点类型';
            }
        }

        // Reviewer validation
        if (array_key_exists('reviewer', $data)) {
            if (empty($data['reviewer'])) {
                $errors['reviewer'] = '审核人不能为空';
            } elseif (strlen($data['reviewer']) > 255) {
                $errors['reviewer'] = '审核人名称不能超过255个字符';
            }
        }

        // Risk ID validation
        if (array_key_exists('risk_id', $data) && $data['risk_id'] !== null) {
            if (!is_numeric($data['risk_id']) || $data['risk_id'] < 1) {
                $errors['risk_id'] = '无效的风险ID';
            }
        }

        // Feedback ID validation
        if (array_key_exists('feedback_id', $data) && $data['feedback_id'] !== null) {
            if (!is_numeric($data['feedback_id']) || $data['feedback_id'] < 1) {
                $errors['feedback_id'] = '无效的反馈ID';
            }
        }

        // ID conflict validation
        if (array_key_exists('risk_id', $data) && array_key_exists('feedback_id', $data) 
            && $data['risk_id'] !== null && $data['feedback_id'] !== null) {
            $errors['id_conflict'] = '不能同时设置风险ID和反馈ID';
        }

        // Type-specific validation
        if (array_key_exists('type', $data) && !empty($data['type'])) {
            if ($data['type'] === 'risk_review') {
                if (array_key_exists('risk_id', $data) && empty($data['risk_id'])) {
                    $errors['risk_id'] = '风险审核节点必须提供风险ID';
                }
                if (array_key_exists('feedback_id', $data) && $data['feedback_id'] !== null) {
                    $errors['feedback_id'] = '风险审核节点不应设置反馈ID';
                }
            } elseif ($data['type'] === 'feedback_review') {
                if (array_key_exists('feedback_id', $data) && empty($data['feedback_id'])) {
                    $errors['feedback_id'] = '反馈审核节点必须提供反馈ID';
                }
                if (array_key_exists('risk_id', $data) && $data['risk_id'] !== null) {
                    $errors['risk_id'] = '反馈审核节点不应设置风险ID';
                }
            }
        }

        // Status validation
        if (array_key_exists('status', $data)) {
            if (!in_array($data['status'], self::ALLOWED_STATUSES)) {
                $errors['status'] = '无效的状态值';
            }
        }

        // Comments validation
        if (array_key_exists('comments', $data) && $data['comments'] !== null && !is_string($data['comments'])) {
            $errors['comments'] = '备注必须是字符串类型';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
