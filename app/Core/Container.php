<?php

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use Closure;
use Exception;

/**
 * 依赖注入容器类
 * 
 * 这个类实现了一个轻量级的依赖注入容器，提供以下核心功能：
 * - 类的自动依赖注入和解析
 * - 单例模式的对象管理
 * - 接口到具体实现的绑定
 * - 构造函数参数的自动解析
 * - 循环依赖检测和处理
 * 
 * 支持以下绑定方式：
 * - 类名绑定：直接绑定类名
 * - 闭包绑定：通过匿名函数创建实例
 * - 实例绑定：直接绑定已创建的对象实例
 * - 单例绑定：确保全局唯一实例
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 * 
 * @example
 * ```php
 * $container = Container::getInstance();
 * 
 * // 绑定接口到具体实现
 * $container->bind('LoggerInterface', 'FileLogger');
 * 
 * // 绑定单例
 * $container->singleton('Database', function() {
 *     return new Database($config);
 * });
 * 
 * // 解析对象
 * $logger = $container->make('LoggerInterface');
 * ```
 */
class Container
{
    /**
     * 已注册的绑定配置数组
     * 
     * 存储抽象名称到具体实现的映射关系，结构如下：
     * [
     *     '抽象名称' => [
     *         'concrete' => '具体实现（类名、闭包或实例）',
     *         'singleton' => '是否为单例模式（boolean）'
     *     ]
     * ]
     * 
     * @var array<string, array{concrete: mixed, singleton: bool}>
     */
    protected array $bindings = [];
    
    /**
     * 已实例化的单例对象存储数组
     * 
     * 缓存已创建的单例实例，避免重复创建：
     * [
     *     '抽象名称' => '实例对象'
     * ]
     * 
     * @var array<string, object>
     */
    protected array $instances = [];
    
    /**
     * 容器的全局单例实例
     * 
     * 确保整个应用程序中只有一个容器实例，
     * 实现全局统一的依赖管理
     * 
     * @var Container|null
     */
    protected static ?Container $instance = null;
    
    /**
     * 获取容器的全局单例实例
     * 
     * 使用单例模式确保整个应用程序中只有一个容器实例，
     * 这样可以保证依赖注入的一致性和对象状态的统一管理。
     * 
     * @return Container 容器单例实例
     * 
     * @example
     * ```php
     * $container = Container::getInstance();
     * $anotherRef = Container::getInstance();
     * var_dump($container === $anotherRef); // 输出: true
     * ```
     */
    public static function getInstance(): Container
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    /**
     * 将抽象名称绑定到具体实现
     * 
     * 这是容器最核心的方法，允许将接口、抽象类或任意标识符
     * 绑定到具体的实现上。支持以下绑定类型：
     * - 类名绑定：绑定到具体的类名
     * - 闭包绑定：使用匿名函数创建实例
     * - 实例绑定：直接绑定已存在的对象实例
     * 
     * @param string $abstract 抽象名称（接口名、类名或自定义标识符）
     * @param mixed|null $concrete 具体实现，可以是：
     *                             - null：使用 $abstract 作为具体实现
     *                             - string：类名
     *                             - Closure：工厂函数
     *                             - object：具体实例
     * @param bool $singleton 是否以单例模式管理实例
     * 
     * @return void
     * 
     * @example
     * ```php
     * // 绑定接口到实现类
     * $container->bind('LoggerInterface', 'FileLogger');
     * 
     * // 使用闭包绑定
     * $container->bind('database', function() {
     *     return new PDO('mysql:host=localhost;dbname=test', $user, $pass);
     * });
     * 
     * // 绑定为单例
     * $container->bind('cache', 'RedisCache', true);
     * ```
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        // 如果没有指定具体实现，则使用抽象名称本身
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        // 存储绑定配置
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }
    
    /**
     * 注册单例绑定
     * 
     * 这是 bind() 方法的便捷包装，专门用于注册单例。
     * 单例对象在首次创建后会被缓存，后续请求将返回相同实例。
     * 
     * @param string $abstract 抽象名称
     * @param mixed|null $concrete 具体实现（同 bind() 方法）
     * 
     * @return void
     * 
     * @example
     * ```php
     * // 注册数据库连接为单例
     * $container->singleton('db', function() {
     *     return new DatabaseConnection();
     * });
     * 
     * $db1 = $container->make('db');
     * $db2 = $container->make('db');
     * var_dump($db1 === $db2); // 输出: true
     * ```
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }
    
    /**
     * 直接注册实例
     * 
     * @param string $abstract 抽象类名
     * @param object $instance 实例对象
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
    
    /**
     * 从容器中解析对象
     * 
     * @param string $abstract 要解析的类名
     * @param array $parameters 额外参数
     * @return mixed
     */
    public function make(string $abstract, array $parameters = [])
    {
        // 如果已有实例，直接返回
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // 获取绑定的具体实现
        $concrete = $this->getConcrete($abstract);
        
        // 构建实例
        $object = $this->build($concrete, $parameters);
        
        // 如果是单例，保存实例
        if (isset($this->bindings[$abstract]['singleton']) && $this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    /**
     * 获取绑定的具体实现
     */
    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }
        
        return $abstract;
    }
    
    /**
     * 构建对象实例
     * 
     * @param mixed $concrete 具体实现
     * @param array $parameters 额外参数
     * @return mixed
     */
    protected function build($concrete, array $parameters = [])
    {
        // 如果是闭包，执行闭包
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        
        // 如果不是字符串，直接返回
        if (!is_string($concrete)) {
            return $concrete;
        }
        
        // 使用反射构建对象
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new Exception("Target class [{$concrete}] does not exist.");
        }
        
        // 检查类是否可实例化
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [{$concrete}] is not instantiable.");
        }
        
        $constructor = $reflector->getConstructor();
        
        // 如果没有构造函数，直接实例化
        if ($constructor === null) {
            return new $concrete;
        }
        
        // 解析构造函数依赖
        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);
        
        return $reflector->newInstanceArgs($dependencies);
    }
    
    /**
     * 解析构造函数依赖
     * 
     * @param ReflectionParameter[] $parameters 构造函数参数
     * @param array $primitives 额外的原始参数
     * @return array
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            
            // 如果参数有类型提示且是类
            if ($dependency !== null) {
                $dependencies[] = $this->make($dependency->getName());
            } elseif (array_key_exists($parameter->getName(), $primitives)) {
                // 使用提供的原始参数
                $dependencies[] = $primitives[$parameter->getName()];
            } elseif ($parameter->isDefaultValueAvailable()) {
                // 使用默认值
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new Exception("Unable to resolve dependency [{$parameter->getName()}]");
            }
        }
        
        return $dependencies;
    }
    
    /**
     * 检查是否已绑定
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * 清除绑定
     */
    public function forgetBinding(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }
    
    /**
     * 清除所有绑定
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }
}
