<?php

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use Closure;
use Exception;

/**
 * 依赖注入容器
 * 
 * 提供自动依赖注入和对象管理功能，替代框架的核心容器功能
 */
class Container
{
    /**
     * 已注册的绑定
     */
    protected array $bindings = [];
    
    /**
     * 单例实例
     */
    protected array $instances = [];
    
    /**
     * 容器的单例实例
     */
    protected static ?Container $instance = null;
    
    /**
     * 获取容器的单例实例
     */
    public static function getInstance(): Container
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    
    /**
     * 绑定类或接口到具体实现
     * 
     * @param string $abstract 抽象类名或接口名
     * @param mixed $concrete 具体实现（类名、闭包或实例）
     * @param bool $singleton 是否为单例
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }
    
    /**
     * 注册单例
     * 
     * @param string $abstract 抽象类名
     * @param mixed $concrete 具体实现
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
