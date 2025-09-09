<?php

namespace App\Core;

/**
 * PSR-4 自动加载器
 * 
 * 提供符合PSR-4标准的类自动加载功能
 * 支持多个命名空间前缀和路径映射
 */
class Autoloader {
    /**
     * 命名空间前缀到目录的映射
     * @var array
     */
    private static array $prefixes = [];
    
    /**
     * 是否已经注册自动加载器
     * @var bool
     */
    private static bool $registered = false;
    
    /**
     * 注册自动加载器
     * 
     * @return void
     */
    public static function register(): void {
        if (self::$registered) {
            return;
        }
        
        spl_autoload_register([self::class, 'loadClass']);
        self::$registered = true;
    }
    
    /**
     * 添加命名空间前缀
     * 
     * @param string $prefix 命名空间前缀
     * @param string $baseDir 基础目录路径
     * @param bool $prepend 是否添加到队列前面
     * @return void
     */
    public static function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void {
        // 标准化命名空间前缀
        $prefix = trim($prefix, '\\') . '\\';
        
        // 标准化基础目录路径
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';
        
        // 初始化命名空间前缀数组
        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }
        
        // 添加基础目录到命名空间前缀
        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $baseDir);
        } else {
            array_push(self::$prefixes[$prefix], $baseDir);
        }
    }
    
    /**
     * 自动加载类
     * 
     * @param string $class 完全限定的类名
     * @return bool|null 成功加载返回true，无法加载返回false，不处理返回null
     */
    public static function loadClass(string $class): ?bool {
        // 当前命名空间前缀
        $prefix = $class;
        
        // 向上查找映射的文件名，直到找到匹配的前缀
        while (false !== $pos = strrpos($prefix, '\\')) {
            // 保留命名空间分隔符在前缀中
            $prefix = substr($class, 0, $pos + 1);
            
            // 剩余的相对类名
            $relativeClass = substr($class, $pos + 1);
            
            // 尝试加载前缀和相对类的映射文件
            $mappedFile = self::loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return true;
            }
            
            // 删除命名空间分隔符为下一次迭代做准备
            $prefix = rtrim($prefix, '\\');
        }
        
        // 没有找到映射文件
        return false;
    }
    
    /**
     * 加载给定前缀和相对类的映射文件
     * 
     * @param string $prefix 命名空间前缀
     * @param string $relativeClass 相对类名
     * @return bool|null 加载成功返回文件路径，否则返回false
     */
    private static function loadMappedFile(string $prefix, string $relativeClass): ?bool {
        // 这个前缀有映射的基础目录吗？
        if (!isset(self::$prefixes[$prefix])) {
            return false;
        }
        
        // 查找前缀中的基础目录
        foreach (self::$prefixes[$prefix] as $baseDir) {
            // 将命名空间前缀替换为基础目录，
            // 将命名空间分隔符替换为目录分隔符，
            // 在相对类名后面附加.php
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            
            // 如果映射文件存在，就加载它
            if (self::requireFile($file)) {
                return true;
            }
        }
        
        // 没有找到映射文件
        return false;
    }
    
    /**
     * 如果文件存在，就从文件系统加载它
     * 
     * @param string $file 要加载的文件
     * @return bool 文件是否存在并被加载
     */
    private static function requireFile(string $file): bool {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
    
    /**
     * 获取所有注册的命名空间前缀
     * 
     * @return array 命名空间前缀到目录的映射数组
     */
    public static function getPrefixes(): array {
        return self::$prefixes;
    }
    
    /**
     * 初始化默认的应用程序自动加载配置
     * 
     * @param string $appDir 应用程序根目录
     * @return void
     */
    public static function initApp(string $appDir = null): void {
        if ($appDir === null) {
            $appDir = dirname(__DIR__, 2) . '/app';
        }
        
        self::register();
        
        // 尝试加载配置文件
        $configFile = dirname($appDir) . '/config/autoload.php';
        if (file_exists($configFile)) {
            self::loadConfig($configFile);
        } else {
            // 回退到默认配置
            self::addNamespace('App', $appDir);
        }
    }
    
    /**
     * 从配置文件加载自动加载配置
     * 
     * @param string $configFile 配置文件路径
     * @return void
     */
    public static function loadConfig(string $configFile): void {
        $config = require $configFile;
        
        // 加载PSR-4命名空间映射
        if (isset($config['psr4']) && is_array($config['psr4'])) {
            foreach ($config['psr4'] as $namespace => $path) {
                self::addNamespace($namespace, $path);
            }
        }
        
        // 加载类映射（可以在未来实现）
        if (isset($config['classmap']) && is_array($config['classmap'])) {
            // TODO: 实现类映射功能
        }
        
        // 包含文件
        if (isset($config['files']) && is_array($config['files'])) {
            foreach ($config['files'] as $file) {
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }
    }
    
    /**
     * 重置自动加载器（主要用于测试）
     * 
     * @return void
     */
    public static function reset(): void {
        if (self::$registered) {
            spl_autoload_unregister([self::class, 'loadClass']);
            self::$registered = false;
        }
        self::$prefixes = [];
    }
}
