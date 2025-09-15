<?php

namespace App\Modules\Risk\Services;

use App\Modules\Risk\Repositories\FeedbackRepository;
use App\Modules\Risk\Entities\Feedback;
use App\Core\Exceptions\ValidationException;

/**
 * 精简版反馈服务类 - 基础业务逻辑
 */
class FeedbackService
{
    private FeedbackRepository $feedbackRepository;

    public function __construct(FeedbackRepository $feedbackRepository) 
    {
        $this->feedbackRepository = $feedbackRepository;
    }

    // ===========================================
    // 基础CRUD业务方法
    // ===========================================

    public function createFeedback(array $data): int 
    {
        $this->validateFeedbackData($data);
        return $this->feedbackRepository->createFeedback($data);
    }

    public function getFeedback(int $id): ?Feedback 
    {
        $data = $this->feedbackRepository->findFeedbackById($id);
        
        if (!$data) {
            return null;
        }
        
        return Feedback::fromArray($data);
    }

    public function getAllFeedbacks(): array 
    {
        $feedbacksData = $this->feedbackRepository->findAllFeedbacks();
        return array_map(fn($data) => Feedback::fromArray($data), $feedbacksData);
    }

    public function updateFeedback(int $id, array $data): bool 
    {
        $this->validateFeedbackData($data, false);
        return $this->feedbackRepository->updateFeedback($id, $data);
    }

    public function deleteFeedback(int $id): bool 
    {
        return $this->feedbackRepository->deleteFeedback($id);
    }

    // ===========================================
    // 业务查询方法
    // ===========================================

    public function getFeedbacksByRisk(int $riskId): array 
    {
        $feedbacksData = $this->feedbackRepository->findFeedbacksByRisk($riskId);
        return array_map(fn($data) => Feedback::fromArray($data), $feedbacksData);
    }

    public function getFeedbacksByNode(int $nodeId): array 
    {
        $feedbacksData = $this->feedbackRepository->findFeedbacksByNode($nodeId);
        return array_map(fn($data) => Feedback::fromArray($data), $feedbacksData);
    }

    public function getHighPriorityFeedbacks(): array 
    {
        $feedbacksData = $this->feedbackRepository->findHighPriorityFeedbacks();
        return array_map(fn($data) => Feedback::fromArray($data), $feedbacksData);
    }

    // ===========================================
    // 辅助方法
    // ===========================================

    private function validateFeedbackData(array $data, bool $isCreate = true): void 
    {
        $errors = [];

        if ($isCreate) {
            if (empty($data['content'])) {
                $errors['content'] = '反馈内容不能为空';
            }
            if (empty($data['type'])) {
                $errors['type'] = '反馈类型不能为空';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}