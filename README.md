# 风险管理系统（PHP）
# 111333
本项目是一个基于原生 PHP 的风险管理系统，采用模块化、分层架构，支持 RESTful API，适合中小型企业或团队的风险流程管理。

## 主要功能

- 风险（Risk）全生命周期管理：创建、查询、更新、删除、状态跟踪、风险评估
- 反馈（Feedback）收集与处理：风险反馈、评审意见、状态流转
- 节点（Node）审核流程：多节点审批、状态管理、流转控制
- 严格数据验证与异常处理，详细日志记录
- 支持 API 速率/体积限制（通过中间件实现）
- 完善的模块分层（控制器、服务、仓储、实体、DTO、验证器、异常）

## 目录结构

```
project/
├── app/
│   ├── Core/                # 核心功能（数据库、HTTP、异常、工具等）
│   └── Modules/
│       └── Risk/            # 风险管理业务模块
│           ├── Controllers/ # 控制器
│           ├── Services/    # 业务服务
│           ├── Repositories/# 数据仓储
│           ├── Entities/    # 领域实体
│           ├── DTOs/        # 数据传输对象
│           ├── Validators/  # 数据验证
│           ├── Exceptions/  # 业务异常
│           └── routes.php   # 路由配置
├── config/                  # 配置文件
├── database/                # 数据库脚本
├── logs/                    # 日志目录（需可写）
├── public/                  # Web 入口（index.php）
└── README.md
```

## 安装与部署

1. **环境要求**
   - PHP 7.4 及以上
   - MySQL 5.7 及以上
   - Apache/Nginx

2. **数据库初始化**
   - 创建数据库
   - 导入 `database/schema.sql`
   - 配置数据库连接 `config/database.php`

3. **Web 服务器配置**
   - 站点根目录指向 `public/`
   - `logs/` 目录需有写权限
   - 配置 URL 重写（见下方示例）

#### Apache .htaccess 示例

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx 配置片段

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## API 说明

### 风险 Risk

- `GET    /api/risks`                获取所有风险
- `POST   /api/risks`                创建新风险
- `GET    /api/risks/{id}`           获取指定风险
- `PUT    /api/risks/{id}`           更新风险
- `DELETE /api/risks/{id}`           删除风险
- `GET    /api/risks/status/{status}`按状态获取风险
- `GET    /api/risks/high`           获取高风险项

### 反馈 Feedback

- `POST   /api/feedbacks`                创建反馈
- `GET    /api/feedbacks/{id}`           获取指定反馈
- `PUT    /api/feedbacks/{id}`           更新反馈
- `DELETE /api/feedbacks/{id}`           删除反馈
- `GET    /api/risks/{riskId}/feedbacks` 获取某风险的所有反馈
- `GET    /api/feedbacks/status/{status}`按状态获取反馈

### 节点 Node

- `POST   /api/nodes`                        创建节点
- `GET    /api/nodes/{id}`                   获取指定节点
- `PUT    /api/nodes/{id}`                   更新节点
- `DELETE /api/nodes/{id}`                   删除节点
- `POST   /api/nodes/{id}/approve`           审批通过节点
- `POST   /api/nodes/{id}/reject`            拒绝节点
- `GET    /api/risks/{riskId}/nodes`         获取某风险的所有节点
- `GET    /api/feedbacks/{feedbackId}/nodes` 获取某反馈的所有节点
- `GET    /api/nodes/pending/{type}`         获取待处理节点

## 开发规范与最佳实践

- 遵循 PSR-1/PSR-4/PSR-12 编码规范
- 类/方法/属性命名统一（StudlyCaps/camelCase/UPPER_CASE）
- 充分的中文注释与文档
- 单一职责、依赖注入、接口隔离、SOLID/DRY 原则
- 关键业务逻辑有详细注释

## 其他说明

- 日志文件不会提交到仓库，仅保留 logs 目录和 .gitkeep 占位
- 路由配置详见 `app/Modules/Risk/routes.php`
- 所有请求限制、速率限制等由中间件实现，详见 `app/Core/Middleware/RequestLimitMiddleware.php`
