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
     * 验证节点数据
     * 
     * @param array $data 要验证的节点数据
     * @throws ValidationException 当验证失败时
     */
    public function validate(array $data): void {
        $errors = [];

        // Validate type
        if (empty($data['type'])) {
            $errors['type'] = '节点类型不能为空';
        } elseif (!in_array($data['type'], self::ALLOWED_TYPES)) {
            $errors['type'] = '无效的节点类型';
        }

        // 验证审核人
        if (empty($data['reviewer'])) {
            $errors['reviewer'] = '审核人不能为空';
        } elseif (strlen($data['reviewer']) > 255) {
            $errors['reviewer'] = '审核人名称不能超过255个字符';
        }

        // 基于节点类型验证关联ID
        if (isset($data['type'])) {
            if ($data['type'] === 'risk_review') {
                if (empty($data['risk_id'])) {
                    $errors['risk_id'] = '风险审核节点必须提供风险ID';
                } elseif (isset($data['feedback_id'])) {
                    $errors['feedback_id'] = '风险审核节点不应设置反馈ID';
                }
            } elseif ($data['type'] === 'feedback_review') {
                if (empty($data['feedback_id'])) {
                    $errors['feedback_id'] = '反馈审核节点必须提供反馈ID';
                } elseif (isset($data['risk_id'])) {
                    $errors['risk_id'] = '反馈审核节点不应设置风险ID';
                }
            }
        }

        // 验证状态（如果提供）
        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES)) {
            $errors['status'] = '无效的状态值';
        }

        // 验证备注（如果提供）
        if (isset($data['comments']) && !is_string($data['comments'])) {
            $errors['comments'] = '备注必须是字符串类型';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}
