<?php

namespace App\Core\Http;

/**
 * 请求处理类
 * 处理所有的HTTP请求
 */
class Request {
    private array $params;
    private array $query;
    private array $body;
    private array $files;
    private array $headers;
    
    public function __construct() {
        $this->params = [];
        $this->query = $_GET ?? [];
        $this->headers = $this->getHeaders();
        $this->body = $this->getBody();
        $this->files = $_FILES ?? [];
    }
    
    /**
     * 获取所有请求头
     *
     * @return array 请求头键值对
     */
    private function getHeaders(): array {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
    
    /**
     * 获取请求体
     *
     * @return array 解析后的请求体数据（JSON 或表单数据）
     */
    private function getBody(): array {
        $body = [];
        
        if ($this->getMethod() === 'GET') {
            return $body;
        }
        
        if ($this->isJson()) {
            $input = file_get_contents('php://input');
            $body = json_decode($input, true) ?? [];
        } else {
            $body = $_POST ?? [];
        }
        
        return $body;
    }
    
    /**
     * 检查是否为 JSON 请求
     *
     * @return bool 如果 Content-Type 中包含 application/json 则返回 true
     */
    private function isJson(): bool {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }
    
    /**
     * 获取请求方法
     *
     * @return string HTTP 方法（GET/POST/PUT/DELETE 等）
     */
    public function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
    
    /**
     * 获取查询参数（URL 参数）
     *
     * @param string|null $key 参数名，若为 null 返回所有查询参数
     * @param mixed $default 未命中时的默认值
     * @return mixed 参数值或数组
     */
    public function getQuery(string $key = null, $default = null) {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }
    
    /**
     * 获取请求体参数
     *
     * @param string|null $key 参数名，若为 null 返回全部请求体数据
     * @param mixed $default 未命中时的默认值
     * @return mixed 参数值或数组
     */
    public function getBodyParam(string $key = null, $default = null) {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }
    
    /**
     * 获取路由参数（通常由路由器设置）
     *
     * @param string|null $key 参数名，若为 null 返回所有路由参数
     * @param mixed $default 未命中时的默认值
     * @return mixed 参数值或数组
     */
    public function getParam(string $key = null, $default = null) {
        if ($key === null) {
            return $this->params;
        }
        return $this->params[$key] ?? $default;
    }
    
    /**
     * 设置路由参数（由路由解析器调用）
     *
     * @param array $params 路由参数键值对
     */
    public function setParams(array $params): void {
        $this->params = $params;
    }
    
    /**
     * 获取上报的文件信息
     *
     * @param string|null $key 文件字段名，若为 null 返回所有文件信息
     * @return array|null 单个文件信息或文件数组
     */
    public function getFile(string $key = null) {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }
    
    /**
     * 获取请求头
     *
     * @param string|null $key 头部名（大小写敏感），若为 null 返回所有头部
     * @param mixed $default 未命中时的默认值
     * @return mixed 头部值或数组
     */
    public function getHeader(string $key = null, $default = null) {
        if ($key === null) {
            return $this->headers;
        }
        return $this->headers[$key] ?? $default;
    }
}
