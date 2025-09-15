# 精简版风险管理系统 - DDD学习项目

这是一个精简版的风险管理系统，专为学习领域驱动设计(DDD)架构而设计。

## 🎯 项目特点

- **精简至最核心功能** - 只保留基础CRUD和简单工作流
- **完整的DDD架构** - 展示分层架构的各个层次
- **学习友好** - 删除复杂功能，突出架构模式
- **代码简洁** - 总代码量从4177行精简到868行（79.2%精简度）

## 📁 项目结构

```
app/Modules/Risk/
├── Entities/              # 实体层
│   ├── Risk.php          # 风险实体 (222行)
│   ├── Node.php          # 节点实体 (231行)
│   └── Feedback.php      # 反馈实体 (197行)
├── Repositories/          # 仓储层  
│   ├── RiskRepository.php     # 风险数据访问 (137行)
│   ├── NodeRepository.php     # 节点数据访问 (91行)
│   └── FeedbackRepository.php # 反馈数据访问 (96行)
├── Services/             # 服务层
│   ├── RiskService.php        # 风险业务逻辑 (165行)
│   ├── NodeService.php        # 节点业务逻辑 (101行)
│   └── FeedbackService.php    # 反馈业务逻辑 (101行)
├── DomainServices/       # 领域服务层
│   ├── RiskEvaluationService.php  # 风险评估 (97行)
│   └── WorkflowRuleService.php     # 工作流规则 (108行)
├── Controllers/          # 控制器层
│   ├── RiskController.php     # 风险REST API (134行)
│   ├── NodeController.php     # 节点REST API (153行)
│   └── FeedbackController.php # 反馈REST API (172行)
└── Validators/           # 验证器
    ├── RiskValidator.php
    ├── NodeValidator.php
    └── FeedbackValidator.php
```

## 🔧 核心功能

### Repository层 (数据访问)
- `create*()` - 创建记录
- `find*ById()` - 根据ID查找
- `findAll*()` - 获取所有记录
- `update*()` - 更新记录
- `delete*()` - 删除记录(软删除)
- `findByStatus()` - 按状态查找
- `findHighRisks()` - 查找高风险项目

### Service层 (业务逻辑)
- 数据验证和业务规则
- 实体对象转换
- 风险分数计算
- 业务流程控制

### Domain Services (跨实体业务)
- `evaluateProjectRisk()` - 项目整体风险评估
- `requiresEscalation()` - 风险升级判断
- `calculateRiskPriority()` - 优先级计算
- `determineNextNode()` - 工作流节点确定
- `isWorkflowComplete()` - 工作流完成检查

### Controller层 (API接口)
- `GET /api/risks` - 获取风险列表
- `GET /api/risks/{id}` - 获取风险详情
- `POST /api/risks` - 创建风险
- `PUT /api/risks/{id}` - 更新风险  
- `DELETE /api/risks/{id}` - 删除风险
- `GET /api/risks/high` - 获取高风险项目

## 🎓 学习重点

### 1. 分层架构
```
Controller → Service → Repository → Database
     ↓         ↓
Domain Services ← → Entities
```

### 2. 设计模式
- **Repository模式** - 数据访问抽象
- **Service模式** - 业务逻辑封装
- **Entity模式** - 领域对象建模
- **依赖注入** - 松耦合设计

### 3. DDD概念
- **实体(Entity)** - 有身份标识的领域对象
- **仓储(Repository)** - 数据访问接口
- **领域服务(Domain Service)** - 跨实体业务逻辑
- **应用服务(Application Service)** - 用例编排

## 📊 精简对比

| 层次 | 精简前 | 精简后 | 精简度 |
|------|--------|--------|--------|
| Repository | 1293行 | 324行 | 74.9% |
| Service | 918行 | 367行 | 60.0% |
| Controller | 1200行 | 459行 | 61.8% |
| Domain Services | 766行 | 205行 | 73.2% |
| **总计** | **4177行** | **1214行** | **70.9%** |

## 🗑️ 删除的复杂功能

- 复杂查询(按日期范围、评分区间等)
- 批量操作(批量创建、更新、删除)
- 智能分析(趋势分析、预测模型)
- 详细报告(统计报表、图表生成)
- 高级工作流(多级审批、条件分支)
- Enhanced服务(复杂业务包装)
- 大量测试文件和文档

## 🚀 运行方式

1. 查看重构报告:
```bash
php refactoring_report.php
```

2. 查看架构演示:
```bash
php simplified_demo.php
```

## 📖 学习建议

1. **从Entity开始** - 理解业务模型和对象设计
2. **学习Repository** - 掌握数据访问模式和抽象
3. **掌握Service** - 理解业务逻辑封装和职责分离
4. **理解Domain Services** - 学习跨实体协作模式
5. **学习Controller** - 掌握API设计和HTTP处理
6. **整体理解** - 各层的职责分离和依赖关系

## 🎯 适合学习

- 领域驱动设计(DDD)
- 分层架构模式
- Repository模式
- 工作流设计基础
- SOLID原则实践
- 依赖注入概念
- REST API设计

## 📝 注意事项

- 这是学习版本，专注于架构模式展示
- 删除了生产环境需要的复杂功能
- 代码注释详细，便于理解
- 保留了*_Complex.php文件作为对比参考

---

**这个精简版本完美展示了DDD架构的核心思想，是学习和理解现代软件架构的绝佳材料！** 🎉