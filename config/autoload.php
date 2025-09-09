<?php

/**
 * 自动加载配置文件
 * 
 * 定义命名空间到目录的映射关系
 */

return [
    // PSR-4 自动加载映射
    'psr4' => [
        'App\\' => __DIR__ . '/../app/',
        // 可以添加更多命名空间映射
        // 'Vendor\\Package\\' => __DIR__ . '/../vendor/package/src/',
    ],
    
    // 类映射（用于优化性能的特定类）
    'classmap' => [
        // 'SpecialClass' => __DIR__ . '/../path/to/SpecialClass.php',
    ],
    
    // 文件包含（需要直接包含的文件）
    'files' => [
        // __DIR__ . '/../app/helpers.php',
    ],
];
