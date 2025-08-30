<?php

namespace App\Modules\Risk\Services;

use App\Modules\Risk\Repositories\NodeRepository;
use App\Modules\Risk\Repositories\RiskRepository;
use App\Modules\Risk\Repositories\FeedbackRepository;
use App\Modules\Risk\Validators\NodeValidator;
use App\Modules\Risk\Entities\Node;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;
use RuntimeException;

/**
 * 流程节点服务类
 *
 * 处理与流程节点（Node）相关的业务逻辑：创建、更新、删除、查询、审批/拒绝等。
 */
class NodeService {
    private NodeRepository $repository;
    private RiskRepository $riskRepository;
    private FeedbackRepository $feedbackRepository;
    private NodeValidator $validator;
    
    private const REVIEW_PERMISSIONS = [
        'risk_review' => ['risk_reviewer', 'admin'],
        'feedback_review' => ['feedback_reviewer', 'admin'],
        'general_review' => ['reviewer', 'admin']
    ];

    public function __construct(
        NodeRepository $repository,
        RiskRepository $riskRepository,
        FeedbackRepository $feedbackRepository,
        NodeValidator $validator
    ) {
        $this->repository = $repository;
        $this->riskRepository = $riskRepository;
        $this->feedbackRepository = $feedbackRepository;
        $this->validator = $validator;
    }

    /**
     * 创建流程节点
     *
     * @param array $data 节点数据（type, risk_id?, feedback_id?, reviewer?, comments?）
     * @return int 新创建节点的ID
     * @throws ValidationException 验证失败
     * @throws \RuntimeException 引用的实体不存在或其他运行时错误
     */
    public function createNode(array $data): int {
        try {
            // Validate referenced entities exist
            if (isset($data['risk_id']) && !$this->riskRepository->findRiskById($data['risk_id'])) {
                throw new \RuntimeException('引用的风险不存在');
            }
            if (isset($data['feedback_id']) && !$this->feedbackRepository->findFeedbackById($data['feedback_id'])) {
                throw new \RuntimeException('引用的反馈不存在');
            }

            $this->validator->validate($data);
            $node = Node::fromArray($data);
            $nodeId = $this->repository->createNode($node);
            
            Logger::info('节点创建成功', ['id' => $nodeId, 'type' => $data['type']]);
            return $nodeId;
        } catch (ValidationException $e) {
            Logger::warning('节点创建校验失败', ['errors' => $e->getErrors()]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('节点创建失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新流程节点
     *
     * @param int $id 节点ID
     * @param array $data 更新数据
     * @return bool 更新是否成功
     * @throws \RuntimeException 节点不存在或试图修改不可变字段
     */
    public function updateNode(int $id, array $data): bool {
        try {
            $node = $this->repository->findNodeById($id);
            if (!$node) {
                throw new \RuntimeException('未找到指定节点');
            }

            // Prevent changing immutable properties
            if (isset($data['type']) && $data['type'] !== $node->getType()) {
                throw new \RuntimeException('不能更改节点类型');
            }
            if (isset($data['risk_id']) && $data['risk_id'] !== $node->getRiskId()) {
                throw new \RuntimeException('不能更改关联的风险');
            }
            if (isset($data['feedback_id']) && $data['feedback_id'] !== $node->getFeedbackId()) {
                throw new \RuntimeException('不能更改关联的反馈');
            }

            // Sanitize comments if present
            if (isset($data['comments'])) {
                $data['comments'] = htmlspecialchars(trim($data['comments']), ENT_QUOTES, 'UTF-8');
            }

            // Merge existing immutable data with update data
            $mergedData = array_merge([
                'type' => $node->getType(),
                'risk_id' => $node->getRiskId(),
                'feedback_id' => $node->getFeedbackId(),
            ], $data);

            // 只校验传入的字段，不要求所有字段必填
            $this->validator->validatePartialUpdate($data);
            $result = $this->repository->updateNode($id, $data);
            
            Logger::info('节点更新成功', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('节点更新失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除流程节点（仅允许删除 pending 状态节点）
     *
     * @param int $id 节点ID
     * @return bool 删除是否成功
     * @throws \RuntimeException 节点不存在或删除失败
     */
    public function deleteNode(int $id): bool {
        try {
            if (!$this->repository->findNodeById($id)) {
                throw new \RuntimeException('未找到指定节点');
            }

            $result = $this->repository->deleteNode($id);
            Logger::info('节点删除成功', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('节点删除失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取单个节点详细信息
     *
     * @param int $id 节点ID
     * @return array|null 节点数据或 null
     */
    public function getNode(int $id): ?array {
        try {
            $node = $this->repository->findNodeById($id);
            if (!$node) {
                return null;
            }

            return $node->toArray();
        } catch (\Exception $e) {
            Logger::error('节点获取失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取指定风险关联的节点列表
     *
     * @param int $riskId 风险ID
     * @return array 节点数组
     * @throws \RuntimeException 风险不存在
     */
    public function getNodesByRisk(int $riskId): array {
        try {
            if (!$this->riskRepository->findRiskById($riskId)) {
                throw new \RuntimeException('未找到指定风险');
            }

            $nodes = $this->repository->findNodesByRiskId($riskId);
            return array_map(fn($node) => $node->toArray(), $nodes);
        } catch (\Exception $e) {
            Logger::error('获取风险关联节点失败', ['risk_id' => $riskId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取指定反馈关联的节点列表
     *
     * @param int $feedbackId 反馈ID
     * @return array 节点数组
     * @throws \RuntimeException 反馈不存在
     */
    public function getNodesByFeedback(int $feedbackId): array {
        try {
            if (!$this->feedbackRepository->findFeedbackById($feedbackId)) {
                throw new \RuntimeException('未找到指定反馈');
            }

            $nodes = $this->repository->findNodesByFeedbackId($feedbackId);
            return array_map(fn($node) => $node->toArray(), $nodes);
        } catch (\Exception $e) {
            Logger::error('获取反馈关联节点失败', ['feedback_id' => $feedbackId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取待审核的节点列表（按类型）
     *
     * @param string $type 节点类型
     * @return array 待审核节点数组
     */
    public function getPendingReviews(string $type): array {
        try {
            $nodes = $this->repository->findPendingNodesByType($type);
            return array_map(fn($node) => $node->toArray(), $nodes);
        } catch (\Exception $e) {
            Logger::error('获取待审查节点失败', ['type' => $type, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 审批通过节点
     *
     * @param int $id 节点ID
     * @param string $reviewerId 审核者标识（格式示例: role:userId）
     * @param string|null $comments 审核备注
     * @return bool 审批操作是否成功
     * @throws \RuntimeException 节点不存在或权限不足等
     */
    public function approveNode(int $id, string $reviewerId, ?string $comments = null): bool {
        try {
            $node = $this->repository->findNodeById($id);
            if (!$node) {
                throw new \RuntimeException('未找到指定节点');
            }

            if (!$node->isPending()) {
                throw new \RuntimeException('节点不处于待处理状态');
            }
            
            // 检查审核人权限
            if (!$this->hasReviewPermission($reviewerId, $node->getType())) {
                throw new \RuntimeException('审核人没有权限审批此节点');
            }

            $data = [
                'type' => $node->getType(),
                'reviewer' => $reviewerId,
                'status' => 'approved',
                'comments' => $comments ? htmlspecialchars(trim($comments), ENT_QUOTES, 'UTF-8') : $node->getComments()
            ];

            if ($node->getRiskId()) {
                $data['risk_id'] = $node->getRiskId();
            }
            if ($node->getFeedbackId()) {
                $data['feedback_id'] = $node->getFeedbackId();
            }

            $result = $this->repository->updateNode($id, $data);
            
            Logger::info('节点审核通过', ['id' => $id, 'reviewer' => $reviewerId]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('节点审核失败', ['id' => $id, 'reviewer' => $reviewerId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    /**
     * Summary of rejectNode
     * @param int $id
     * @param string $reviewerId
     * @param string $comments
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool
     */
    /**
     * 拒绝节点
     *
     * @param int $id 节点ID
     * @param string $reviewerId 审核者标识
     * @param string $comments 拒绝理由（必填）
     * @return bool 拒绝操作是否成功
     * @throws \InvalidArgumentException 缺少审核意见
     * @throws \RuntimeException 节点不存在或权限不足
     */
    public function rejectNode(int $id, string $reviewerId, string $comments): bool {
        try {
            if (empty(trim($comments))) {
                throw new \InvalidArgumentException('拒绝时必须提供审核意见');
            }

            $node = $this->repository->findNodeById($id);
            if (!$node) {
                throw new \RuntimeException('未找到指定节点');
            }

            if (!$node->isPending()) {
                throw new \RuntimeException('节点不处于待处理状态');
            }
            
            // 检查审核人权限
            if (!$this->hasReviewPermission($reviewerId, $node->getType())) {
                throw new \RuntimeException('审核人没有权限拒绝此节点');
            }

            $data = [
                'type' => $node->getType(),
                'reviewer' => $reviewerId,
                'status' => 'rejected',
                'comments' => htmlspecialchars(trim($comments), ENT_QUOTES, 'UTF-8')
            ];

            if ($node->getRiskId()) {
                $data['risk_id'] = $node->getRiskId();
            }
            if ($node->getFeedbackId()) {
                $data['feedback_id'] = $node->getFeedbackId();
            }

            $result = $this->repository->updateNode($id, $data);
            
            Logger::info('节点已被拒绝', ['id' => $id, 'reviewer' => $reviewerId]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('节点拒绝操作失败', ['id' => $id, 'reviewer' => $reviewerId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 检查审核者是否对指定类型的节点拥有审批权限
     *
     * @param string $reviewerId 审核者标识
     * @param string $nodeType 节点类型
     * @return bool 是否有权限
     */
    private function hasReviewPermission(string $reviewerId, string $nodeType): bool {
        try {
            // 这里通常会通过用户服务或数据库检查权限
            // 当前假定 reviewerId 格式为 'role:userId'
            $parts = explode(':', $reviewerId);
            if (count($parts) !== 2) {
                return false;
            }
            
            $role = $parts[0];
            
            // Check if role has permission for this type of node
            return isset(self::REVIEW_PERMISSIONS[$nodeType]) && 
                   in_array($role, self::REVIEW_PERMISSIONS[$nodeType]);
        } catch (\Exception $e) {
            Logger::error('权限检查失败', ['reviewer' => $reviewerId, 'type' => $nodeType]);
            return false;
        }
    }
}
