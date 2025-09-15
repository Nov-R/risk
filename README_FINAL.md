# 风险管理系统 - 精简学习版

## 📖 项目简介

这是一个专为学习**领域驱动设计（DDD）**而精心打造的风险管理系统。项目经过深度精简，保留了DDD的核心架构模式，同时删除了过度复杂的业务逻辑，非常适合初学者理解和掌握DDD的精髓。

## 🎯 学习目标

- 🏛️ **理解DDD分层架构**：清晰的职责分离
- 📦 **掌握Repository模式**：数据访问层的最佳实践  
- ⚙️ **学习Service分层**：业务逻辑的合理组织
- 🧩 **实体设计模式**：业务逻辑的封装方式
- 🔄 **工作流编排**：复杂业务流程的处理
- 🔒 **事务管理**：数据一致性的保证

## 🏗️ 架构设计

### 分层架构图
```
┌─────────────────────────────────────┐
│          Controller Layer          │ ← REST API接口层
├─────────────────────────────────────┤
│      Application Service Layer     │ ← 工作流编排层
├─────────────────────────────────────┤
│  Domain Service  │  Entity Layer  │ ← 业务逻辑层
├─────────────────────────────────────┤
│        Repository Layer            │ ← 数据访问层
├─────────────────────────────────────┤
│         Database Layer             │ ← 数据存储层
└─────────────────────────────────────┘
```

### 核心组件

| 组件 | 职责 | 文件示例 |
|------|------|----------|
| **Entity** | 封装业务数据和核心逻辑 | `Risk.php`, `Node.php`, `Feedback.php` |
| **Repository** | 数据访问和持久化 | `RiskRepository.php` |
| **Service** | 单一聚合的业务逻辑 | `RiskService.php` |
| **Domain Service** | 跨实体的复杂业务逻辑 | `RiskEvaluationService.php` |
| **Application Service** | 工作流编排和事务管理 | `RiskManagementService.php` |
| **Controller** | HTTP接口和请求处理 | `RiskController.php` |

## 📊 代码统计

经过精简后的代码统计：

| 层次 | 文件数 | 代码行数 | 主要功能 |
|------|--------|----------|----------|
| **Entity层** | 3 | 650行 | 业务实体和核心逻辑 |
| **Repository层** | 3 | 378行 | 数据访问和查询 |
| **Service层** | 3 | 367行 | 业务逻辑处理 |
| **Controller层** | 3 | 459行 | REST API接口 |
| **Domain Service层** | 2 | 453行 | 跨实体业务逻辑 |
| **Application Service层** | 1 | 501行 | 工作流编排 |
| **Validator层** | 3 | 298行 | 数据验证 |
| **其他** | 1 | 67行 | 路由配置 |
| **总计** | **19** | **3173行** | **完整DDD架构** |

## 🚀 快速开始

### 环境要求
- PHP 8.0+
- MySQL 5.7+
- 支持PDO扩展

### 安装步骤

1. **克隆项目**
```bash
git clone <repository-url>
cd risk-management
```

2. **配置数据库**
```bash
# 编辑数据库配置
cp config/database.php.example config/database.php
# 修改数据库连接信息
```

3. **初始化数据库**
```bash
mysql -u root -p < database/schema.sql
```

4. **运行演示**
```bash
php simplified_demo.php
```

5. **查看报告**
```bash
php final_refactoring_report.php
```

## 📚 学习路径

### 第一阶段：理解基础概念
1. 📖 **阅读Entity层**：`app/Modules/Risk/Entities/`
   - 理解业务实体的设计
   - 学习业务逻辑的封装方式

2. 🗃️ **学习Repository层**：`app/Modules/Risk/Repositories/`
   - 掌握数据访问模式
   - 理解接口与实现的分离

### 第二阶段：掌握业务逻辑
3. ⚙️ **研究Service层**：`app/Modules/Risk/Services/`
   - 学习业务逻辑的组织
   - 理解单一职责原则

4. 🧩 **探索Domain Service**：`app/Modules/Risk/DomainServices/`
   - 理解跨实体的业务逻辑
   - 学习复杂业务规则的处理

### 第三阶段：掌握工作流编排
5. 🔄 **学习Application Service**：`app/Modules/Risk/ApplicationServices/`
   - 理解工作流编排
   - 掌握事务边界管理

6. 🌐 **实践Controller层**：`app/Modules/Risk/Controllers/`
   - 学习REST API设计
   - 理解请求响应处理

## 💡 核心特性

### ✅ 已实现功能
- 🔨 **基础CRUD操作**：创建、读取、更新、删除
- 🔄 **简单工作流**：审批流程和状态管理
- 🧩 **跨实体业务逻辑**：风险评估和影响分析
- 🔍 **数据验证**：完整的业务规则验证
- 🚨 **异常处理**：统一的错误处理机制
- 📝 **日志记录**：关键操作的审计追踪

### 🎯 学习重点
- **职责分离**：各层职责清晰，低耦合
- **依赖注入**：灵活的依赖关系管理
- **接口设计**：面向接口编程
- **业务封装**：业务逻辑的合理封装
- **数据一致性**：事务管理和数据完整性

## 🔧 代码示例

### Entity设计示例
```php
class Risk {
    private int $id;
    private string $title;
    private int $impactScore;
    private int $probabilityScore;
    
    // 业务逻辑封装在实体内部
    public function calculateRiskScore(): int {
        return $this->impactScore * $this->probabilityScore;
    }
    
    public function isHighRisk(): bool {
        return $this->calculateRiskScore() >= 15;
    }
}
```

### Repository模式示例
```php
class RiskRepository extends BaseRepository {
    public function findHighRisks(): array {
        $sql = "SELECT * FROM risks WHERE risk_score >= 15";
        return $this->executeQuery($sql, [], 'SELECT')->fetchAll();
    }
}
```

### Service层示例
```php
class RiskService {
    public function createRisk(array $data): int {
        $this->validator->validate($data);
        $risk = Risk::fromArray($data);
        return $this->repository->createRiskFromEntity($risk);
    }
}
```

## 🛠️ 扩展建议

### 短期扩展
- 📊 添加数据可视化
- 🔐 实现用户权限控制
- 📧 增加邮件通知功能
- 🔍 添加搜索和过滤功能

### 长期扩展
- 🎯 实现更复杂的工作流引擎
- 📈 添加风险分析和预测功能
- 🔄 引入事件驱动架构
- 🚀 实现微服务架构

## 📖 相关文档

- 📋 [架构设计文档](docs/architecture.md)
- 🔧 [API接口文档](docs/api.md)
- 🧪 [测试指南](docs/testing.md)
- 🚀 [部署指南](docs/deployment.md)

## 🤝 贡献指南

欢迎贡献代码！请遵循以下原则：
- 保持代码简洁易读
- 遵循DDD设计原则
- 添加必要的注释和文档
- 确保代码质量和测试覆盖

## 📄 许可证

本项目采用 MIT 许可证。详见 [LICENSE](LICENSE) 文件。

## 💬 联系方式

如有问题或建议，请通过以下方式联系：
- 📧 Email: [你的邮箱]
- 🐛 Issues: [GitHub Issues]
- 💬 讨论: [GitHub Discussions]

---

> 🎓 **学习提示**：这个项目是为学习DDD而设计的，建议结合相关书籍和文档深入理解领域驱动设计的理念和实践。

📚 **推荐阅读**：
- 《领域驱动设计》- Eric Evans
- 《实现领域驱动设计》- Vaughn Vernon
- 《架构整洁之道》- Robert C. Martin