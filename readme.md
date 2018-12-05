# 晓乎后端API文档 v1.0.0
## 常规API调用原则
- 所有的API都以`domain.com/api/...`开头
- API分为两个部分，如`domain.com/api/part_1/part_2`
	- `part_1`为model名称，如`user`或`question`
	- `part_2`为行为的名称，如`reset_password`
- CRUD
	- 每个model中都会有增删改查四个方法，分别对应为`add`、`remove`、`change`、`read`

## Model
### Question
#### 字段解释
- `id`
- `title`：标题
- `desc`描述

#### `add`
- 权限：已登录
- 传参：
	- 必填：`title`
	- 可选：`desc`

#### `change`
- 权限：已登录且为问题的所有者
- 传参：
	- 必填：`id`
	- 可选：`title`, `desc`