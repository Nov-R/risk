<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Response;

/**
 * 问候控制器 - 用户问候功能
 */
class GreetingController extends BaseController
{
    /**
     * 返回中文问候语
     * GET /api/hello
     */
    public function hello(): void
    {
        Response::success(['message' => '你好'], '欢迎使用风险管理系统');
    }
}
