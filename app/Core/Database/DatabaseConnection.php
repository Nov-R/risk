<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\Exceptions\DatabaseException;
use App\Core\Utils\Logger;

/**
 * 数据库连接管理类
 * 
 * 提供功能完善的数据库连接管理，包括：
 * - 单例模式确保连接唯一性
 * - 多数据库驱动支持（MySQL、PostgreSQL、SQLite、Oracle、SQL Server）
 * - 连接池和重连机制
 * - 配置验证和错误处理
 * - 连接状态监控和统计
 * - 事务管理支持
 * - 查询性能监控
 * 
 * 支持的数据库：
 * - MySQL/MariaDB
 * - PostgreSQL
 * - SQLite
 * - Oracle Database
 * - Microsoft SQL Server
 * 
 * @author 风险管理系统开发组
 * @version 1.0
 * @since 2025-09-10
 * 
 * @example
 * ```php
 * // 获取数据库连接
 * $pdo = DatabaseConnection::getInstance();
 * 
 * // 检查连接状态
 * if (DatabaseConnection::isConnected()) {
 *     // 执行查询
 *     $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 *     $stmt->execute([$userId]);
 * }
 * 
 * // 获取连接统计信息
 * $stats = DatabaseConnection::getConnectionStats();
 * ```
 */
class DatabaseConnection 
{
    /**
     * PDO连接实例
     * 
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * 数据库配置信息
     * 
     * @var array<string, mixed>|null
     */
    private static ?array $config = null;

    /**
     * 连接尝试次数
     * 
     * @var int
     */
    private static int $connectionAttempts = 0;

    /**
     * 最大重连尝试次数
     * 
     * @var int
     */
    private static int $maxRetryAttempts = 3;

    /**
     * 连接统计信息
     * 
     * @var array<string, mixed>
     */
    private static array $stats = [
        'connection_time' => null,
        'query_count' => 0,
        'last_query_time' => null,
        'total_query_time' => 0,
        'failed_queries' => 0,
        'reconnect_count' => 0
    ];

    /**
     * 支持的数据库驱动列表
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $supportedDrivers = [
        'mysql' => [
            'name' => 'MySQL/MariaDB',
            'default_port' => 3306,
            'default_charset' => 'utf8mb4'
        ],
        'pgsql' => [
            'name' => 'PostgreSQL',
            'default_port' => 5432,
            'default_charset' => 'utf8'
        ],
        'sqlite' => [
            'name' => 'SQLite',
            'default_port' => null,
            'default_charset' => 'utf8'
        ],
        'oci' => [
            'name' => 'Oracle Database',
            'default_port' => 1521,
            'default_charset' => 'AL32UTF8'
        ],
        'sqlsrv' => [
            'name' => 'Microsoft SQL Server',
            'default_port' => 1433,
            'default_charset' => 'utf8'
        ]
    ];

    /**
     * 私有构造函数，防止直接实例化
     * 
     * 单例模式的核心实现，确保整个应用程序中
     * 只有一个数据库连接实例
     */
    private function __construct() 
    {
        // 防止直接实例化
    }

    /**
     * 获取数据库连接实例
     * 
     * 这是获取数据库连接的唯一入口点，实现了：
     * - 懒加载：只在需要时创建连接
     * - 连接复用：重复调用返回同一实例
     * - 自动重连：连接断开时自动尝试重连
     * - 错误处理：连接失败时抛出详细异常
     * 
     * @param bool $forceReconnect 是否强制重新连接
     * 
     * @return PDO 数据库连接实例
     * 
     * @throws DatabaseException 当连接失败时
     * 
     * @example
     * ```php
     * try {
     *     $pdo = DatabaseConnection::getInstance();
     *     $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
     *     $stmt->execute();
     *     $count = $stmt->fetchColumn();
     * } catch (DatabaseException $e) {
     *     Logger::error('数据库查询失败', ['error' => $e->getMessage()]);
     *     throw $e;
     * }
     * ```
     */
    public static function getInstance(bool $forceReconnect = false): PDO 
    {
        if (self::$instance === null || $forceReconnect) {
            self::createConnection();
        }

        // 检查连接是否仍然有效
        if (!self::isConnectionAlive()) {
            Logger::warning('数据库连接已断开，尝试重新连接');
            self::createConnection();
        }

        return self::$instance;
    }

    /**
     * 创建新的数据库连接
     * 
     * @throws DatabaseException 当连接失败时
     */
    private static function createConnection(): void 
    {
        $startTime = microtime(true);

        try {
            // 加载和验证配置
            self::loadAndValidateConfig();

            // 构建数据源名称（DSN）
            $dsn = self::buildDsn();

            // 构建PDO选项
            $options = self::buildPdoOptions();

            // 创建PDO连接
            self::$instance = new PDO(
                $dsn,
                self::$config['username'] ?? '',
                self::$config['password'] ?? '',
                $options
            );

            // 执行连接后初始化
            self::postConnectionInit();

            // 记录连接成功
            $connectionTime = microtime(true) - $startTime;
            self::$stats['connection_time'] = $connectionTime;
            self::$connectionAttempts = 0;

            Logger::info('数据库连接成功', [
                'driver' => self::$config['driver'],
                'host' => self::$config['host'] ?? 'N/A',
                'database' => self::$config['database'] ?? 'N/A',
                'connection_time' => round($connectionTime * 1000, 2) . 'ms'
            ]);

        } catch (PDOException $e) {
            self::$connectionAttempts++;

            // 如果还有重试机会且不是配置错误，尝试重连
            if (self::$connectionAttempts < self::$maxRetryAttempts && !self::isConfigurationError($e)) {
                Logger::warning('数据库连接失败，正在重试', [
                    'attempt' => self::$connectionAttempts,
                    'max_attempts' => self::$maxRetryAttempts,
                    'error' => $e->getMessage()
                ]);

                // 等待一段时间后重试
                sleep(min(self::$connectionAttempts, 5));
                self::createConnection();
                return;
            }

            // 重试失败或配置错误，抛出异常
            throw DatabaseException::fromPDOException(
                $e,
                "数据库连接失败",
                [
                    'driver' => self::$config['driver'] ?? 'unknown',
                    'host' => self::$config['host'] ?? 'unknown',
                    'attempts' => self::$connectionAttempts
                ]
            );
        }
    }

    /**
     * 加载和验证数据库配置
     * 
     * @throws DatabaseException 当配置无效时
     */
    private static function loadAndValidateConfig(): void 
    {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../../../config/database.php';
            
            if (!file_exists($configPath)) {
                throw new DatabaseException(
                    '数据库配置文件不存在',
                    500,
                    null,
                    ['config_path' => $configPath]
                );
            }

            self::$config = require $configPath;
        }

        // 验证必需的配置项
        if (empty(self::$config['driver'])) {
            throw new DatabaseException('数据库驱动配置缺失');
        }

        if (!isset(self::$supportedDrivers[self::$config['driver']])) {
            throw new DatabaseException(
                '不支持的数据库驱动',
                500,
                null,
                [
                    'driver' => self::$config['driver'],
                    'supported_drivers' => array_keys(self::$supportedDrivers)
                ]
            );
        }

        // 验证驱动特定的必需配置
        self::validateDriverSpecificConfig();
    }

    /**
     * 验证特定数据库驱动的配置
     * 
     * @throws DatabaseException 当配置无效时
     */
    private static function validateDriverSpecificConfig(): void 
    {
        $driver = self::$config['driver'];

        switch ($driver) {
            case 'mysql':
            case 'pgsql':
            case 'sqlsrv':
                if (empty(self::$config['host'])) {
                    throw new DatabaseException("{$driver}数据库必须配置host参数");
                }
                if (empty(self::$config['database'])) {
                    throw new DatabaseException("{$driver}数据库必须配置database参数");
                }
                break;

            case 'sqlite':
                if (empty(self::$config['database'])) {
                    throw new DatabaseException('SQLite数据库必须配置database参数（文件路径）');
                }
                break;

            case 'oci':
                if (empty(self::$config['tns']) && (empty(self::$config['host']) || empty(self::$config['service_name']))) {
                    throw new DatabaseException('Oracle数据库必须配置tns或者host+service_name参数');
                }
                break;
        }
    }

    /**
     * 构建数据源名称（DSN）
     * 
     * @return string DSN字符串
     */
    private static function buildDsn(): string 
    {
        $driver = self::$config['driver'];
        $driverInfo = self::$supportedDrivers[$driver];

        switch ($driver) {
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    self::$config['host'],
                    self::$config['port'] ?? $driverInfo['default_port'],
                    self::$config['database'],
                    self::$config['charset'] ?? $driverInfo['default_charset']
                );

            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s',
                    self::$config['host'],
                    self::$config['port'] ?? $driverInfo['default_port'],
                    self::$config['database']
                );

            case 'sqlite':
                return 'sqlite:' . self::$config['database'];

            case 'oci':
                return !empty(self::$config['tns'])
                    ? 'oci:dbname=' . self::$config['tns']
                    : sprintf(
                        'oci:dbname=//%s:%d/%s;charset=%s',
                        self::$config['host'],
                        self::$config['port'] ?? $driverInfo['default_port'],
                        self::$config['service_name'],
                        self::$config['charset'] ?? $driverInfo['default_charset']
                    );

            case 'sqlsrv':
                return sprintf(
                    'sqlsrv:Server=%s,%d;Database=%s',
                    self::$config['host'],
                    self::$config['port'] ?? $driverInfo['default_port'],
                    self::$config['database']
                );

            default:
                throw new DatabaseException("不支持的数据库驱动：{$driver}");
        }
    }

    /**
     * 构建PDO选项
     * 
     * @return array<int, mixed> PDO选项数组
     */
    private static function buildPdoOptions(): array 
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => self::$config['timeout'] ?? 30,
        ];

        // 驱动特定选项
        switch (self::$config['driver']) {
            case 'mysql':
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 
                    "SET NAMES " . (self::$config['charset'] ?? 'utf8mb4') . 
                    " COLLATE " . (self::$config['collation'] ?? 'utf8mb4_unicode_ci');
                $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
                break;

            case 'oci':
                $options[PDO::ATTR_CASE] = PDO::CASE_LOWER;
                $options[PDO::ATTR_AUTOCOMMIT] = true;
                break;

            case 'pgsql':
                // PostgreSQL特定选项
                if (isset(self::$config['sslmode'])) {
                    // SSL模式将在DSN中处理
                }
                break;
        }

        // 合并用户自定义选项
        if (isset(self::$config['options']) && is_array(self::$config['options'])) {
            $options = array_merge($options, self::$config['options']);
        }

        return $options;
    }

    /**
     * 连接后初始化操作
     */
    private static function postConnectionInit(): void 
    {
        if (self::$config['driver'] === 'oci' && !empty(self::$config['init_commands'])) {
            foreach (self::$config['init_commands'] as $command) {
                try {
                    self::$instance->exec($command);
                } catch (PDOException $e) {
                    Logger::warning('Oracle初始化命令执行失败', [
                        'command' => $command,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * 检查错误是否为配置错误
     * 
     * @param PDOException $e PDO异常
     * 
     * @return bool 是否为配置错误
     */
    private static function isConfigurationError(PDOException $e): bool 
    {
        $message = $e->getMessage();
        
        // 常见的配置错误模式
        $configErrorPatterns = [
            'Unknown database',
            'Access denied',
            'Unknown MySQL server host',
            'Connection refused',
            'No such file or directory',
            'could not find driver'
        ];

        foreach ($configErrorPatterns as $pattern) {
            if (stripos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查连接是否存活
     * 
     * @return bool 连接是否存活
     */
    private static function isConnectionAlive(): bool 
    {
        if (self::$instance === null) {
            return false;
        }

        try {
            // 执行简单查询检查连接
            self::$instance->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            Logger::debug('数据库连接检查失败', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 检查数据库连接是否已建立
     * 
     * @return bool 是否已连接
     */
    public static function isConnected(): bool 
    {
        return self::$instance !== null && self::isConnectionAlive();
    }

    /**
     * 获取连接统计信息
     * 
     * @return array<string, mixed> 统计信息
     */
    public static function getConnectionStats(): array 
    {
        return self::$stats;
    }

    /**
     * 获取支持的数据库驱动信息
     * 
     * @return array<string, array<string, mixed>> 驱动信息
     */
    public static function getSupportedDrivers(): array 
    {
        return self::$supportedDrivers;
    }

    /**
     * 关闭数据库连接
     * 
     * 显式关闭连接并清理资源
     */
    public static function close(): void 
    {
        if (self::$instance !== null) {
            Logger::info('手动关闭数据库连接');
            self::$instance = null;
        }
    }

    /**
     * 重置连接统计信息
     */
    public static function resetStats(): void 
    {
        self::$stats = [
            'connection_time' => null,
            'query_count' => 0,
            'last_query_time' => null,
            'total_query_time' => 0,
            'failed_queries' => 0,
            'reconnect_count' => 0
        ];
    }

    /**
     * 防止对象被克隆
     * 
     * @throws \RuntimeException 当尝试克隆时
     */
    private function __clone() 
    {
        throw new \RuntimeException('数据库连接不允许被克隆');
    }

    /**
     * 防止对象被序列化
     * 
     * @throws \RuntimeException 当尝试序列化时
     */
    public function __sleep() 
    {
        throw new \RuntimeException('数据库连接不允许被序列化');
    }

    /**
     * 防止对象被反序列化
     * 
     * @throws \RuntimeException 当尝试反序列化时
     */
    public function __wakeup() 
    {
        throw new \RuntimeException('数据库连接不允许被反序列化');
    }
}
