<?php
/**
 * 应用程序入口文件
 * 
 * 处理所有传入的HTTP请求，包括：
 * 1. 自动加载类
 * 2. 错误处理配置
 * 3. 日志初始化
 * 4. 请求限制中间件
 * 5. 路由处理
 * 6. 控制器初始化和调用
 */

// 配置自动加载器
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 配置错误处理
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 初始化日志系统
\App\Core\Utils\Logger::init(__DIR__ . '/../logs/app.log');

// 初始化依赖注入容器
$container = \App\Core\Container::getInstance();

// 应用请求限制中间件
$requestLimitMiddleware = new \App\Core\Middleware\RequestLimitMiddleware();
if (!$requestLimitMiddleware->handle()) {
    exit();
}

// 解析请求URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// 路由处理
$routes = require __DIR__ . '/../app/Modules/Risk/routes.php';
$routeFound = false;

// 遍历路由配置，查找匹配的路由
foreach ($routes as $route => $handler) {
    $pattern = "@^" . preg_replace('/\{([a-zA-Z]+)\}/', '([^/]+)', $route) . "$@D";
    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches); // remove the full match
        
        if (isset($handler[$method])) {
            $routeFound = true;
            $action = $handler[$method];
            [$controllerName, $methodName] = explode('@', $action);
            
            // 按需初始化控制器
            switch ($controllerName) {
                case 'RiskController':
                    $controller = $container->make(\App\Modules\Risk\Controllers\RiskController::class);
                    break;
                case 'FeedbackController':
                    $controller = $container->make(\App\Modules\Risk\Controllers\FeedbackController::class);
                    break;
                case 'NodeController':
                    $controller = $container->make(\App\Modules\Risk\Controllers\NodeController::class);
                    break;
                default:
                    \App\Core\Http\Response::error('Controller not found', 404);
                    exit();
            }
            
            call_user_func_array([$controller, $methodName], $matches);
            break;
        }
    }
}

if (!$routeFound) {
    \App\Core\Http\Response::error('Route not found', 404);
}

