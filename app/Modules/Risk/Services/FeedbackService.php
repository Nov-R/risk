<?php

namespace App\Modules\Risk\Services;

use App\Modules\Risk\DTOs\FeedbackDTO;
use App\Modules\Risk\Repositories\FeedbackRepository;
use App\Modules\Risk\Repositories\RiskRepository;
use App\Modules\Risk\Validators\FeedbackValidator;
use App\Core\Exceptions\ValidationException;
use App\Core\Utils\Logger;
use RuntimeException;


/**
 * 风险反馈服务类
 *
 * 负责处理与风险反馈相关的业务逻辑，包括：
 * - 创建反馈
 * - 更新反馈
 * - 删除反馈
 * - 查询单个反馈
 * - 查询指定风险的所有反馈
 * - 按状态查询反馈
 */
class FeedbackService {
    /** @var FeedbackRepository 反馈仓储实例 */
    private FeedbackRepository $repository;
    /** @var RiskRepository 风险仓储实例 */
    private RiskRepository $riskRepository;
    /** @var FeedbackValidator 反馈验证器实例 */
    private FeedbackValidator $validator;

    /**
     * 构造函数
     *
     * @param FeedbackRepository $repository 反馈仓储实例
     * @param RiskRepository $riskRepository 风险仓储实例
     * @param FeedbackValidator $validator 反馈验证器实例
     */
    public function __construct(
        FeedbackRepository $repository,
        RiskRepository $riskRepository,
        FeedbackValidator $validator
    ) {
        $this->repository = $repository;
        $this->riskRepository = $riskRepository;
        $this->validator = $validator;
    }

    /**
     * 创建新的反馈记录
     *
     * @param array $data 反馈数据，包含：
     *  - risk_id: 关联的风险ID
     *  - content: 反馈内容
     *  - type: 反馈类型
     *  - created_by: 创建者
     *  - status: 状态（可选）
     * @return int 新建反馈ID
     * @throws ValidationException|\Exception
     */
    public function createFeedback(array $data): int {
        try {
            // 校验风险是否存在
            if (!$this->riskRepository->findRiskById($data['risk_id'])) {
                throw new \RuntimeException('引用的风险不存在');
            }
            // 内容安全处理
            if (isset($data['content'])) {
                $data['content'] = htmlspecialchars(trim($data['content']), ENT_QUOTES, 'UTF-8');
            }
            $this->validator->validate($data);
            $feedbackDTO = FeedbackDTO::fromArray($data);
            $feedbackId = $this->repository->createFeedback($feedbackDTO);
            Logger::info('反馈创建成功', ['id' => $feedbackId, 'risk_id' => $data['risk_id']]);
            return $feedbackId;
        } catch (ValidationException $e) {
            Logger::warning('反馈数据校验失败', ['errors' => $e->getErrors()]);
            throw $e;
        } catch (\Exception $e) {
            Logger::error('反馈创建失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新反馈记录
     *
     * @param int $id 反馈ID
     * @param array $data 更新数据
     * @return bool 是否更新成功
     * @throws \Exception
     */
    public function updateFeedback(int $id, array $data): bool {
        try {
            $feedback = $this->repository->findFeedbackById($id);
            if (!$feedback) {
                throw new \RuntimeException('未找到反馈');
            }
            if (isset($data['risk_id']) && $data['risk_id'] !== $feedback->getRiskId()) {
                throw new \RuntimeException('不能更改关联的风险');
            }
            // 内容安全处理
            if (isset($data['content'])) {
                $data['content'] = htmlspecialchars(trim($data['content']), ENT_QUOTES, 'UTF-8');
            }
            $mergedData = array_merge(['risk_id' => $feedback->getRiskId()], $data);
            $this->validator->validate($mergedData);
            $feedbackDTO = FeedbackDTO::fromArray($mergedData);
            $result = $this->repository->updateFeedback($id, $feedbackDTO);
            Logger::info('反馈更新成功', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('反馈更新失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除反馈记录
     *
     * @param int $id 反馈ID
     * @return bool 是否删除成功
     * @throws \Exception
     */
    public function deleteFeedback(int $id): bool {
        try {
            if (!$this->repository->findFeedbackById($id)) {
                throw new \RuntimeException('未找到反馈');
            }
            $result = $this->repository->deleteFeedback($id);
            Logger::info('反馈删除成功', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            Logger::error('反馈删除失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取单个反馈详情
     *
     * @param int $id 反馈ID
     * @return array|null 反馈详情
     * @throws \Exception
     */
    public function getFeedback(int $id): ?array {
        try {
            $feedback = $this->repository->findFeedbackById($id);
            if (!$feedback) {
                return null;
            }
            return $this->formatFeedbackResponse($feedback);
        } catch (\Exception $e) {
            Logger::error('获取反馈失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取指定风险的所有反馈
     *
     * @param int $riskId 风险ID
     * @return array 反馈数组
     * @throws \Exception
     */
    public function getFeedbacksByRisk(int $riskId): array {
        try {
            if (!$this->riskRepository->findRiskById($riskId)) {
                throw new \RuntimeException('未找到指定的风险');
            }
            $feedbacks = $this->repository->findFeedbacksByRiskId($riskId);
            return array_map([$this, 'formatFeedbackResponse'], $feedbacks);
        } catch (\Exception $e) {
            Logger::error('获取风险相关反馈失败', ['risk_id' => $riskId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 按状态获取反馈列表
     *
     * @param string $status 反馈状态
     * @return array 反馈数组
     * @throws \Exception
     */
    public function getFeedbacksByStatus(string $status): array {
        try {
            $feedbacks = $this->repository->findFeedbacksByStatus($status);
            return array_map([$this, 'formatFeedbackResponse'], $feedbacks);
        } catch (\Exception $e) {
            Logger::error('按状态获取反馈失败', ['status' => $status, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 格式化反馈响应
     *
     * @param mixed $feedback 反馈实体对象
     * @return array 格式化后的反馈数据
     */
    private function formatFeedbackResponse($feedback): array {
        if (!$feedback) {
            return [];
        }
        $createdAt = $feedback->getCreatedAt();
        $updatedAt = $feedback->getUpdatedAt();
        return [
            'id' => $feedback->getId(),
            'risk_id' => $feedback->getRiskId() ?? 0,
            'content' => $feedback->getContent() ?? '',
            'type' => $feedback->getType() ?? 'general',
            'status' => $feedback->getStatus() ?? 'pending',
            'created_by' => $feedback->getCreatedBy() ?? 'system',
            'created_at' => $createdAt ? $createdAt->format('Y-m-d H:i:s') : null,
            'updated_at' => $updatedAt ? $updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}
