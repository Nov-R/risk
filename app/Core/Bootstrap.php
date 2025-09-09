<?php

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Router;
use App\Core\Middleware\RequestLimitMiddleware;
use App\Core\Utils\Logger;

/**
 * 应用程序启动引导类
 * 
 * 负责应用程序的初始化和启动过程，包括：
 * - 自动加载器配置
 * - 错误处理配置
 * - 日志系统初始化
 * - 依赖注入容器初始化
 * - 中间件配置
 * - 路由配置和处理
 */
class Bootstrap {
    /**
     * 应用程序根目录
     * @var string
     */
    private string $appDir;
    
    /**
     * 依赖注入容器
     * @var Container
     */
    private Container $container;
    
    /**
     * 路由器实例
     * @var Router
     */
    private Router $router;
    
    /**
     * 构造函数
     * 
     * @param string $appDir 应用程序根目录
     */
    public function __construct(string $appDir = null) {
        $this->appDir = $appDir ?? dirname(__DIR__, 2) . '/app';
    }
    
    /**
     * 初始化应用程序
     * 
     * @return self
     */
    public function init(): self {
        $this->initAutoloader();
        $this->initErrorHandling();
        $this->initLogger();
        $this->initContainer();
        $this->initRouter();
        
        return $this;
    }
    
    /**
     * 初始化自动加载器
     * 
     * @return void
     */
    private function initAutoloader(): void {
        Autoloader::initApp($this->appDir);
    }
    
    /**
     * 初始化错误处理
     * 
     * @return void
     */
    private function initErrorHandling(): void {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // 可以在这里添加自定义错误处理器
        // set_error_handler([$this, 'handleError']);
        // set_exception_handler([$this, 'handleException']);
    }
    
    /**
     * 初始化日志系统
     * 
     * @return void
     */
    private function initLogger(): void {
        $logDir = dirname($this->appDir) . '/logs';
        $logFile = $logDir . '/app.log';
        
        // 确保日志目录存在
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        Logger::init($logFile);
    }
    
    /**
     * 初始化依赖注入容器
     * 
     * @return void
     */
    private function initContainer(): void {
        $this->container = Container::getInstance();
        
        // 注册核心服务到容器
        $this->registerCoreServices();
    }
    
    /**
     * 注册核心服务到容器
     * 
     * @return void
     */
    private function registerCoreServices(): void {
        // 注册Request服务
        $this->container->bind('request', function() {
            return new Request();
        });
        
        // 注册Response服务
        $this->container->bind('response', function() {
            return new Response();
        });
        
        // 可以在这里注册更多核心服务
    }
    
    /**
     * 初始化路由器
     * 
     * @return void
     */
    private function initRouter(): void {
        $this->router = new Router($this->container);
        $this->loadRoutes();
    }
    
    /**
     * 加载路由配置
     * 
     * @return void
     */
    private function loadRoutes(): void {
        // 加载风险模块路由
        $riskRoutes = require $this->appDir . '/Modules/Risk/routes.php';
        $this->router->setRoutes($riskRoutes);
        
        // 可以在这里加载更多模块的路由
        // $userRoutes = require $this->appDir . '/Modules/User/routes.php';
        // $this->router->addRoutes($userRoutes);
    }
    
    /**
     * 运行应用程序
     * 
     * @return void
     */
    public function run(): void {
        try {
            // 应用请求限制中间件
            $this->applyMiddleware();
            
            // 创建请求对象
            $request = $this->container->make('request');
            
            // 处理请求（Router直接处理响应输出）
            $this->router->handleRequest($request);
            
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * 应用中间件
     * 
     * @return void
     */
    private function applyMiddleware(): void {
        $requestLimitMiddleware = new RequestLimitMiddleware();
        if (!$requestLimitMiddleware->handle()) {
            http_response_code(429);
            echo json_encode(['error' => 'Too Many Requests']);
            exit();
        }
        
        // 可以在这里添加更多中间件
    }
    

    
    /**
     * 处理异常
     * 
     * @param \Throwable $e 异常对象
     * @return void
     */
    private function handleException(\Throwable $e): void {
        Logger::error('Uncaught exception: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        http_response_code($e->getCode() ?: 500);
        
        $response = [
            'success' => false,
            'error' => 'Internal Server Error'
        ];
        
        // 开发环境显示详细错误信息
        if (ini_get('display_errors')) {
            $response['debug'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            ];
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 获取容器实例
     * 
     * @return Container
     */
    public function getContainer(): Container {
        return $this->container;
    }
    
    /**
     * 获取路由器实例
     * 
     * @return Router
     */
    public function getRouter(): Router {
        return $this->router;
    }
}
