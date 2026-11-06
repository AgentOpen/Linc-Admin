# 灵枢中控前端（Linc-Admin Frontend）

Vue 3 + TypeScript + Vite + Vue Router + GSAP 的管理后台前端，多主题（`data-skin` × 明暗 × 密度），自有「珍珠玻璃」组件体系。与 PHP 后端同源部署，接口统一走 `/api`。

## 开发

```bash
npm ci
npm run dev          # http://localhost:5173，/api 代理到 http://localhost:8090（VITE_API_PROXY_TARGET 可改）
npm run test:unit    # http 包络 / 会话存储单测
npm run build        # vue-tsc 类型检查 + 产物输出 dist/
```

构建后将 `dist/` 全部内容拷贝到后端项目 `public/` 目录即可完成前端发布。后端准备见 [根目录 README](../README.md)。

种子管理员 `admin` 的开发密码为 `admin123`，验证码使用当前页面显示的字符。非管理员菜单来自角色授权；无菜单账号显示明确的权限分配提示。

## 目录

| 路径 | 中文职责 |
| --- | --- |
| `src/api` | 统一 HTTP（包络解包 / 401 处理）、会话存储、各模块接口 |
| `src/router/index.ts` | 静态入口、动态菜单路由、登录守卫与会话失效跳转 |
| `src/composables/auth.ts` | 当前用户、权限、动态菜单与 `COMPONENT_MAP`（新页面必须在此登记） |
| `src/themes/index.ts` | 主题注册表（`SKINS` 元数据与预览色卡） |
| `src/components/ui` | 按钮、输入、下拉、表格、弹窗、抽屉、提示等基础控件 |
| `src/components/business` | ListPage、FormDialog、PermissionButton 及字典、部门、用户选择 |
| `src/components/layout` | 外壳、侧栏、顶栏、命令搜索、外观与密码弹窗 |
| `src/composables` | 列表、字典 / 部门缓存、外观、动效、焦点与确认框 |
| `src/styles` | 七层样式：tokens → base → materials → components → instruments → layout → pages；`themes/<皮肤>.css` 为主题覆盖包 |
| `src/views` | 登录、工作台、系统管理 / 监控 / 工具各页、主题中心、无权限与未匹配页面 |

## 约定

- 全站颜色、圆角、阴影只引用 `styles/tokens.css` 设计令牌，页面禁止手写字面量——这是多主题的前提；
- 新列表先复用 `ListPage`，新表单先复用 `FormDialog`，权限动作使用 `PermissionButton`，接口统一走 `api/http.ts`；
- 新页面的菜单组件（`sys_menu.component`）必须在 `composables/auth.ts` 的 `COMPONENT_MAP` 登记，再通过菜单管理配置路径、组件标识与权限码；
- 退出和 401 均通过统一会话清理撤销动态路由与缓存；不得在页面直接修改认证存储或路由就绪标记；
- 品牌信息统一在 `src/data/index.ts` 的 `APP` 对象维护；
- 设计规范与主题体系见 `docs/设计规范.md`，二次开发与主题开发教程见 `docs/05-扩展开发实战.md`。

本项目代码采用 MIT License；GSAP 依赖遵循其 [Standard "No Charge" License](https://gsap.com/community/standard-license/)。
