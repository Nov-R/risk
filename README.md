# 风险管理系统（PHP）

[![CI/CD Pipeline](https://github.com/Nov-R/risk/actions/workflows/ci.yml/badge.svg)](https://github.com/Nov-R/risk/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E7.4%7C%5E8.0%7C%5E8.1%7C%5E8.2%7C%5E8.3-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

本项目是一个基于原生 PHP 的现代化风险管理系统，采用模块化、分层架构，支持 RESTful API，配备完整的测试和 CI/CD 流程，适合中小型企业或团队的风险流程管理。

## ✨ 主要功能

- 🔍 **风险全生命周期管理**：创建、查询、更新、删除、状态跟踪、风险评估
- 💬 **反馈收集与处理**：风险反馈、评审意见、状态流转
- 🔄 **节点审核流程**：多节点审批、状态管理、流转控制
- 🛡️ **数据验证与异常处理**：严格数据验证、详细日志记录
- ⚡ **API 限流保护**：通过中间件实现请求速率和体积限制
- 🏗️ **模块化架构**：控制器、服务、仓储、实体、DTO、验证器分层设计

## 🚀 快速开始

### 使用 Docker（推荐）

1. **克隆项目**
   ```bash
   git clone https://github.com/Nov-R/risk.git
   cd risk
   ```

2. **环境配置**
   ```bash
   cp .env.example .env
   # 编辑 .env 文件，根据需要调整配置
   ```

3. **启动服务**
   ```bash
   docker-compose up -d
   ```

4. **访问应用**
   - API 服务：http://localhost:8080
   - phpMyAdmin：http://localhost:8081
   - 数据库：localhost:3306

### 传统安装方式

#### 环境要求

- PHP 7.4 及以上
- MySQL 5.7 及以上
- Apache/Nginx
- Composer

#### 安装步骤

1. **安装依赖**
   ```bash
   composer install
   ```

2. **环境配置**
   ```bash
   cp .env.example .env
   # 编辑 .env 文件配置数据库等信息
   ```

3. **数据库初始化**
   ```bash
   # 创建数据库
   mysql -u root -p -e "CREATE DATABASE risk_management;"
   
   # 导入数据表结构
   mysql -u root -p risk_management < database/schema.sql
   ```

4. **Web 服务器配置**
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

## 📁 目录结构

```
project/
├── .github/workflows/     # GitHub Actions CI/CD 配置
├── app/
│   ├── Core/              # 核心功能（数据库、HTTP、异常、工具等）
│   │   ├── Config/        # 配置管理
│   │   ├── Database/      # 数据库相关
│   │   ├── Http/          # HTTP 请求响应处理
│   │   ├── Middleware/    # 中间件
│   │   └── Utils/         # 工具类
│   └── Modules/
│       └── Risk/          # 风险管理业务模块
│           ├── Controllers/ # 控制器
│           ├── Services/    # 业务服务
│           ├── Repositories/# 数据仓储
│           ├── Entities/    # 领域实体
│           ├── DTOs/        # 数据传输对象
│           ├── Validators/  # 数据验证
│           ├── Exceptions/  # 业务异常
│           └── routes.php   # 路由配置
├── config/                # 配置文件
├── database/              # 数据库脚本
├── docker/                # Docker 配置
├── docs/                  # 项目文档
│   └── api/              # API 文档
├── logs/                  # 日志目录（需可写）
├── public/                # Web 入口（index.php）
├── tests/                 # 测试文件
│   ├── Unit/             # 单元测试
│   └── Integration/      # 集成测试
├── .env.example          # 环境配置示例
├── composer.json         # Composer 配置
├── docker-compose.yml    # Docker Compose 配置
├── Dockerfile           # Docker 镜像配置
├── phpunit.xml          # PHPUnit 配置
└── README.md
```

## 📚 API 文档

### 风险 Risk

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/risks` | 获取所有风险 |
| POST | `/api/risks` | 创建新风险 |
| GET | `/api/risks/{id}` | 获取指定风险 |
| PUT | `/api/risks/{id}` | 更新风险 |
| DELETE | `/api/risks/{id}` | 删除风险 |
| GET | `/api/risks/status/{status}` | 按状态获取风险 |
| GET | `/api/risks/high` | 获取高风险项 |

### 反馈 Feedback

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/feedbacks` | 创建反馈 |
| GET | `/api/feedbacks/{id}` | 获取指定反馈 |
| PUT | `/api/feedbacks/{id}` | 更新反馈 |
| DELETE | `/api/feedbacks/{id}` | 删除反馈 |
| GET | `/api/risks/{riskId}/feedbacks` | 获取某风险的所有反馈 |
| GET | `/api/feedbacks/status/{status}` | 按状态获取反馈 |

### 节点 Node

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/nodes` | 创建节点 |
| GET | `/api/nodes/{id}` | 获取指定节点 |
| PUT | `/api/nodes/{id}` | 更新节点 |
| DELETE | `/api/nodes/{id}` | 删除节点 |
| POST | `/api/nodes/{id}/approve` | 审批通过节点 |
| POST | `/api/nodes/{id}/reject` | 拒绝节点 |
| GET | `/api/risks/{riskId}/nodes` | 获取某风险的所有节点 |
| GET | `/api/feedbacks/{feedbackId}/nodes` | 获取某反馈的所有节点 |
| GET | `/api/nodes/pending/{type}` | 获取待处理节点 |

**详细 API 文档**: [OpenAPI 规范](docs/api/openapi.yml)

## 🧪 测试

```bash
# 运行所有测试
composer test

# 运行测试并生成覆盖率报告
composer test-coverage

# 代码质量检查
composer quality

# 分别运行各种检查
composer phpstan    # 静态分析
composer phpcs      # 代码风格检查
composer phpcbf     # 自动修复代码风格
```

## 🔧 开发工具

### 代码质量

- **PHPStan**: 静态代码分析，发现潜在错误
- **PHPCS**: PSR-12 代码风格检查
- **PHPUnit**: 单元测试和集成测试

### CI/CD

- **GitHub Actions**: 自动化测试、代码质量检查、Docker 镜像构建
- **Codecov**: 代码覆盖率报告

### 监控与日志

- **详细日志记录**: 所有关键操作和错误都有日志记录
- **中间件保护**: API 限流和请求体积控制

## 🛠️ 开发规范

- 遵循 **PSR-1/PSR-4/PSR-12** 编码规范
- 类/方法/属性命名统一（StudlyCaps/camelCase/UPPER_CASE）
- 充分的中文注释与文档
- 单一职责、依赖注入、接口隔离
- 遵循 **SOLID/DRY** 原则
- 关键业务逻辑有详细注释

## 📈 项目改进建议

### 已实现的改进

- ✅ 现代化 PHP 依赖管理 (Composer)
- ✅ 环境变量配置支持 (.env)
- ✅ Docker 容器化部署
- ✅ 自动化测试框架 (PHPUnit)
- ✅ 代码质量工具 (PHPStan, PHPCS)
- ✅ CI/CD 自动化流程 (GitHub Actions)
- ✅ API 文档规范 (OpenAPI)

### 规划中的功能

- 🔄 用户认证和授权系统
- 🔄 JWT Token 认证
- 🔄 Redis 缓存支持
- 🔄 API 分页功能
- 🔄 Web 管理界面
- 🔄 数据可视化图表
- 🔄 邮件通知系统

## 🤝 贡献指南

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 打开 Pull Request

## 📄 许可证

本项目基于 MIT 许可证开源 - 查看 [LICENSE](LICENSE) 文件了解详情

## 📞 支持与联系

- 项目地址：[https://github.com/Nov-R/risk](https://github.com/Nov-R/risk)
- 问题反馈：[Issues](https://github.com/Nov-R/risk/issues)
- 邮箱：support@example.com

---

**注意**: 日志文件不会提交到仓库，仅保留 logs 目录和 .gitkeep 占位文件。所有配置项都可通过环境变量自定义。
