<?php

namespace App\Core\Http;

use App\Core\Container;

/**
 * 路由处理器
 * 
 * 负责解析路由、匹配控制器和方法
 */
class Router {
    private Container $container;
    private array $routes;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    /**
     * 设置路由配置
     */
    public function setRoutes(array $routes): void {
        $this->routes = $routes;
    }
    
    /**
     * 处理请求
     */
    public function handleRequest(Request $request): void {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $request->getMethod();
        
        foreach ($this->routes as $route => $handler) {
            $pattern = "@^" . preg_replace('/\{([a-zA-Z]+)\}/', '([^/]+)', $route) . "$@D";
            
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // 移除完整匹配
                
                // 提取路由参数名
                preg_match_all('/\{([a-zA-Z]+)\}/', $route, $paramNames);
                $routeParams = [];
                if (!empty($paramNames[1]) && !empty($matches)) {
                    $routeParams = array_combine($paramNames[1], $matches);
                }
                
                // 将路由参数设置到 Request 对象
                $request->setParams($routeParams);
                
                if (isset($handler[$method])) {
                    $action = $handler[$method];
                    [$controllerName, $methodName] = explode('@', $action);
                    
                    // 获取完整的控制器类名
                    $controllerClass = $this->getControllerClass($controllerName);
                    
                    if (!$controllerClass) {
                        Response::error('Controller not found', 404);
                        return;
                    }
                    
                    // 通过容器实例化控制器，并注入 Request 对象
                    $controller = $this->container->make($controllerClass, [$request]);
                    
                    // 调用控制器方法（不再需要传递参数）
                    if (method_exists($controller, $methodName)) {
                        $controller->$methodName();
                    } else {
                        Response::error('Method not found', 404);
                    }
                    return;
                }
            }
        }
        
        Response::error('Route not found', 404);
    }
    
    /**
     * 根据控制器名获取完整类名
     */
    private function getControllerClass(string $controllerName): ?string {
        $controllerMap = [
            'RiskController' => \App\Modules\Risk\Controllers\RiskController::class,
            'FeedbackController' => \App\Modules\Risk\Controllers\FeedbackController::class,
            'NodeController' => \App\Modules\Risk\Controllers\NodeController::class,
        ];
        
        return $controllerMap[$controllerName] ?? null;
    }
}
