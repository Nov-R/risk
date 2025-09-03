<?php

namespace App\Core\Config;

/**
 * 环境配置管理类
 * 
 * 负责加载和管理环境变量配置，支持 .env 文件
 */
class Environment
{
    private static ?Environment $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnvironment();
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): Environment
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 加载环境配置
     */
    private function loadEnvironment(): void
    {
        $envFile = dirname(__DIR__, 3) . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue; // 跳过注释行
                }
                
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // 移除引号
                    $value = trim($value, '"\'');
                    
                    $this->config[$key] = $value;
                    putenv("{$key}={$value}");
                }
            }
        }
        
        // 设置默认值
        $this->setDefaults();
    }

    /**
     * 设置默认配置值
     */
    private function setDefaults(): void
    {
        $defaults = [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'DB_HOST' => 'localhost',
            'DB_DATABASE' => 'risk_management',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'LOG_LEVEL' => 'info',
            'LOG_PATH' => 'logs/app.log',
            'API_RATE_LIMIT' => '100',
            'API_RATE_LIMIT_WINDOW' => '3600'
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * 获取配置值
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置值
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * 检查是否为调试模式
     */
    public function isDebug(): bool
    {
        return $this->get('APP_DEBUG') === 'true';
    }

    /**
     * 获取所有配置
     */
    public function all(): array
    {
        return $this->config;
    }
}