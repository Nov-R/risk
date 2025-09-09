# 自动加载器解耦文档

## 概述

本次重构将自动加载器从 `index.php` 中解耦出来，创建了一个独立的、符合PSR-4标准的自动加载系统。同时引入了Bootstrap类来管理应用程序的初始化过程。

## 架构改进

### 1. 自动加载器类 (`app/Core/Autoloader.php`)

**特性：**
- ✅ 符合PSR-4标准
- ✅ 支持多命名空间映射
- ✅ 支持配置文件加载
- ✅ 单例模式管理
- ✅ 支持重置（便于测试）

**主要方法：**
- `register()`: 注册自动加载器
- `addNamespace($prefix, $baseDir)`: 添加命名空间映射
- `loadConfig($configFile)`: 从配置文件加载设置
- `initApp($appDir)`: 初始化应用程序自动加载

### 2. Bootstrap引导类 (`app/Core/Bootstrap.php`)

**职责：**
- ✅ 自动加载器初始化
- ✅ 错误处理配置
- ✅ 日志系统初始化
- ✅ 依赖注入容器配置
- ✅ 中间件应用
- ✅ 路由配置和处理
- ✅ 异常处理

**主要方法：**
- `init()`: 初始化应用程序
- `run()`: 运行应用程序
- `getContainer()`: 获取容器实例
- `getRouter()`: 获取路由器实例

### 3. 配置文件 (`config/autoload.php`)

**功能：**
- ✅ PSR-4命名空间映射配置
- ✅ 类映射配置（预留）
- ✅ 文件包含配置

## 文件结构

```
config/
├── autoload.php          # 自动加载配置文件
├── database.php          # 数据库配置

app/Core/
├── Autoloader.php        # PSR-4自动加载器
├── Bootstrap.php         # 应用程序引导类
├── Container.php         # 依赖注入容器
└── Http/
    ├── Request.php       # HTTP请求处理
    ├── Response.php      # HTTP响应处理
    └── Router.php        # 路由处理器

public/
└── index.php             # 简化的应用入口

test_autoloader.php       # 自动加载器测试脚本
```

## 使用方式

### 1. 基本使用

```php
// 在入口文件中
require_once __DIR__ . '/../app/Core/Autoloader.php';
require_once __DIR__ . '/../app/Core/Bootstrap.php';

// 初始化并运行应用程序
$app = new \App\Core\Bootstrap(__DIR__ . '/../app');
$app->init()->run();
```

### 2. 自定义配置

```php
// 手动配置自动加载器
\App\Core\Autoloader::register();
\App\Core\Autoloader::addNamespace('App\\', '/path/to/app/');
\App\Core\Autoloader::addNamespace('Custom\\', '/path/to/custom/');
```

### 3. 配置文件使用

```php
// config/autoload.php
return [
    'psr4' => [
        'App\\' => __DIR__ . '/../app/',
        'Vendor\\Package\\' => __DIR__ . '/../vendor/package/src/',
    ],
    'files' => [
        __DIR__ . '/../app/helpers.php',
    ],
];
```

## 优势对比

### 重构前 (index.php中直接实现)

❌ 代码耦合度高  
❌ 难以测试  
❌ 不支持配置文件  
❌ 功能混杂在入口文件中  
❌ 难以扩展  

### 重构后 (解耦后的架构)

✅ **职责分离**: 每个类职责单一明确  
✅ **可测试性**: 独立的类便于单元测试  
✅ **可配置性**: 支持配置文件灵活配置  
✅ **可扩展性**: 轻松添加新的命名空间  
✅ **标准化**: 符合PSR-4标准  
✅ **可维护性**: 代码结构清晰，便于维护  

## 性能考虑

1. **延迟加载**: 只有在需要时才加载类文件
2. **缓存友好**: 可以轻松添加类映射缓存
3. **内存效率**: 避免预加载不需要的类
4. **错误处理**: 完善的错误处理和日志记录

## 测试

运行自动加载器测试：

```bash
php test_autoloader.php
```

启动开发服务器：

```bash
php -S localhost:8000 -t public
```

## 扩展建议

1. **添加缓存机制**: 为生产环境添加类映射缓存
2. **性能监控**: 添加自动加载性能监控
3. **开发工具**: 创建自动加载映射生成工具
4. **命名空间验证**: 添加命名空间规范验证

## 总结

通过将自动加载器解耦出来，我们获得了：

- 更清晰的代码架构
- 更好的可测试性
- 更强的可扩展性
- 符合PSR-4标准的实现
- 简化的入口文件
- 统一的应用程序初始化流程

这个架构为未来的功能扩展和维护提供了solid的基础。
