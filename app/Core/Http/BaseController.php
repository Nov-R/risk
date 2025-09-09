<?php

namespace App\Core\Http;

/**
 * 基础控制器类
 * 
 * 所有控制器都应继承此类，提供统一的请求处理接口
 */
abstract class BaseController {
    /** @var Request HTTP请求处理实例 */
    protected Request $request;

    /**
     * 构造函数
     * 
     * @param Request $request HTTP请求实例
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * 获取请求实例
     * 
     * @return Request
     */
    protected function getRequest(): Request {
        return $this->request;
    }

    /**
     * 获取路由参数
     * 
     * @param string|null $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getParam(string $key = null, $default = null) {
        return $this->request->getParam($key, $default);
    }

    /**
     * 获取查询参数
     * 
     * @param string|null $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getQuery(string $key = null, $default = null) {
        return $this->request->getQuery($key, $default);
    }

    /**
     * 获取请求体参数
     * 
     * @param string|null $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getBodyParam(string $key = null, $default = null) {
        return $this->request->getBodyParam($key, $default);
    }

    /**
     * 获取请求头
     * 
     * @param string|null $key 头部名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getHeader(string $key = null, $default = null) {
        return $this->request->getHeader($key, $default);
    }

    /**
     * 获取上传文件
     * 
     * @param string|null $key 文件字段名
     * @return mixed
     */
    protected function getFile(string $key = null) {
        return $this->request->getFile($key);
    }

    /**
     * 获取请求方法
     * 
     * @return string
     */
    protected function getMethod(): string {
        return $this->request->getMethod();
    }
}
