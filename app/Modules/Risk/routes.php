<?php

/**
 * 风险管理系统 API 路由配置
 *
 * 说明：
 * - 本文件仅定义 URL 路由到控制器方法的映射关系。
 * - 请求大小、速率等限制应由中间件或服务器配置实现，不应写在路由文件中。
 * - 可选：可在此处注明接口版本号（如 Version: 1.0.0）。
 */


// API Routes Configuration
return [
    '/api/risks' => [
        'GET' => 'RiskController@getAll',
        'POST' => 'RiskController@create'
    ],
    '/api/risks/high' => [
        'GET' => 'RiskController@getHighRisks'
    ],
    '/api/risks/status/{status}' => [
        'GET' => 'RiskController@getByStatus'
    ],
    '/api/risks/{id}' => [
        'GET' => 'RiskController@get',
        'PUT' => 'RiskController@update',
        'DELETE' => 'RiskController@delete'
    ],
    '/api/feedbacks' => [
        'POST' => 'FeedbackController@create'
    ],
    '/api/feedbacks/{id}' => [
        'GET' => 'FeedbackController@get',
        'PUT' => 'FeedbackController@update',
        'DELETE' => 'FeedbackController@delete'
    ],
    '/api/risks/{riskId}/feedbacks' => [
        'GET' => 'FeedbackController@getByRisk'
    ],
    '/api/feedbacks/status/{status}' => [
        'GET' => 'FeedbackController@getByStatus'
    ],
    '/api/nodes' => [
        'POST' => 'NodeController@create'
    ],
    '/api/nodes/{id}' => [
        'GET' => 'NodeController@get',
        'PUT' => 'NodeController@update',
        'DELETE' => 'NodeController@delete'
    ],
    '/api/nodes/{id}/approve' => [
        'POST' => 'NodeController@approve'
    ],
    '/api/nodes/{id}/reject' => [
        'POST' => 'NodeController@reject'
    ],
    '/api/risks/{riskId}/nodes' => [
        'GET' => 'NodeController@getByRisk'
    ],
    '/api/feedbacks/{feedbackId}/nodes' => [
        'GET' => 'NodeController@getByFeedback'
    ],
    '/api/nodes/pending/{type}' => [
        'GET' => 'NodeController@getPendingReviews'
    ]
];
