<?php

namespace App\Modules\Risk\Controllers;

use App\Core\Http\BaseController;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * 问候控制器
 * 
 * 提供用户问候功能的API接口
 */
class GreetingController extends BaseController
{
    /**
     * 返回中文问候语
     * GET /api/greet
     */
    public function greet(): void
    {
        Response::success(['message' => '你好']);
    }
}
