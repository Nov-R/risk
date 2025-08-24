<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\Exceptions\DatabaseException;

/**
 * 数据库连接类
 * 使用单例模式确保只有一个数据库连接实例
 */
class DatabaseConnection {
    private static ?PDO $instance = null;
    
    /**
     * 私有构造函数防止直接创建对象
     */
    private function __construct() {}
    
    /**
     * 获取数据库连接实例
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $config = require __DIR__ . '/../../../config/database.php';
                
                // 根据配置的数据库类型构建DSN
                switch ($config['driver'] ?? 'mysql') {
                    case 'pgsql':
                        $dsn = "pgsql:host={$config['host']};dbname={$config['database']}";
                        break;
                    case 'sqlite':
                        $dsn = "sqlite:" . ($config['database'] ?? __DIR__ . '/../../../database/database.sqlite');
                        break;
                    case 'sqlsrv':
                        $dsn = "sqlsrv:Server={$config['host']};Database={$config['database']}";
                        break;
                    case 'oci':
                        // Oracle连接字符串支持多种格式
                        if (!empty($config['tns'])) {
                            // 使用TNS名称连接
                            $dsn = "oci:dbname={$config['tns']}";
                        } else {
                            // 使用完整连接字符串
                            $dsn = "oci:dbname=//" . 
                                  $config['host'] . 
                                  ($config['port'] ? ":{$config['port']}" : ":1521") . 
                                  "/{$config['service_name']};charset=AL32UTF8";
                        }
                        break;
                    case 'mysql':
                    default:
                        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
                        break;
                }

                // 准备PDO选项
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                // 为不同数据库添加特定选项
                if ($config['driver'] === 'mysql') {
                    $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
                } elseif ($config['driver'] === 'oci') {
                    // Oracle特定选项
                    $options[PDO::ATTR_CASE] = PDO::CASE_LOWER; // 将列名转换为小写
                    $options[PDO::ATTR_AUTOCOMMIT] = true;      // 启用自动提交
                    
                    // 设置会话参数
                    $initCommands = [
                        "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'",
                        "ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'",
                        "ALTER SESSION SET NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS TZR'"
                    ];
                }

                self::$instance = new PDO(
                    $dsn,
                    $config['username'] ?? '',
                    $config['password'] ?? '',
                    $options
                );
            } catch (PDOException $e) {
                throw new DatabaseException(
                    "数据库连接失败：" . $e->getMessage(),
                    (int)$e->getCode(),
                    $e
                );
            }
        }
        return self::$instance;
    }
    
    /**
     * 防止对象被克隆
     */
    private function __clone() {}
    
    /**
     * 防止反序列化对象
     */
    private function __wakeup() {}
}
