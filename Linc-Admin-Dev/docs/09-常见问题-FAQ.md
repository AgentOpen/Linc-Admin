# 09 · 常见问题 (FAQ)

## 安装相关

**Q1：安装向导提示「runtime/ 目录不可写」？**
给 PHP 进程用户写权限：Linux `chown -R www:www runtime config`（宝塔用户即 www）；Windows 确认目录未只读。

**Q2：提示「数据库连接失败」但账号密码正确？**
依次检查：MySQL 是否启动；`host` 从数据库容器内应填服务名而非 localhost；MySQL 8 默认 `caching_sha2_password` 插件 PHP 8 原生支持，老版本 PHP 需升级；远程数据库需授权 `%` 或对应来源 IP。

**Q3：可以跳过安装向导手工配置吗？**
可以：复制 `config/config.sample.php` 为 `config/config.php` 修改，手工导入 `database/V*.sql`（按版本号顺序），最后创建空的 `runtime/install.lock` 文件。管理员密码为种子 BCrypt 哈希（admin123），登录后自行修改。

**Q4：如何重新安装 / 换库？**
删除 `runtime/install.lock` 与 `config/config.php`，刷新页面即重新进入向导。目标库已有系统表时向导会提示（初始化脚本幂等，重复导入不会产生脏数据）。

**Q5：安装时「管理员账号」能改成别的用户名吗？**
能。向导第三步自定义账号与密码（密码 6-64 位），写入超管记录（id=1）。不填则默认 `admin / admin123`。

## 登录与会话

**Q6：验证码不显示 / 一直报「验证码错误或已过期」？**
验证码存服务端缓存（默认文件），浏览器多标签页共享没有问题；若多机部署且未共享缓存，验证码会落在 A 机、校验在 B 机——切 Redis 或共享 `runtime/cache`。

**Q7：登录后接口全部 401？**
同 Q6 的多机缓存问题；或 JWT 密钥各机不一致（`jwt.secret` 必须一致）；或服务器时间偏差过大（exp 校验失败）。

**Q8：改了密码后旧会话还能用吗？**
能，旧令牌在有效期内仍可用（有效期默认 7200 秒）。需要立即踢出全部会话时：更换 `jwt.secret` 或清空 `runtime/cache`（文件缓存）/ 对应 Redis 库。

**Q9：忘记 admin 密码？**
用其他超管账号重置；或直接更新库：
```sql
UPDATE sys_user SET password = '$2y$10$L7q28dpKkcfrrUzfvZebqe7pvxcKpaSgh7EUenf9Q5z2LiYKxDpIu' WHERE id = 1;
-- 该哈希对应 admin123（BCrypt, cost 10）
```

## 部署相关

**Q9.8：门户/后台报「Access denied for user … (using password: YES)」（错误 1045）？**
`config/config.php` 里的数据库账号密码与 MySQL 实际不符。修复：编辑 config/config.php 改成正确账号密码；或删除 runtime/install.lock 与 config/config.php 后访问 /install.html 重装。建议用面板创建的专属数据库账号而非 root。

**Q9.5：安装向导提示「环境检测失败：HTTP 404」或「后端接口不可达」？**
伪静态未配置（新手最常见问题）：页面能打开但 `/api/*` 全部 404。宝塔用户：站点设置 → 伪静态 → 下拉选 `laravel5` 模板保存；Nginx 用户：在 `location /` 中加 `try_files $uri $uri/ /index.php$is_args$args;`；Apache：启用 mod_rewrite 且 AllowOverride All。配置后刷新页面即可。

**Q10：首页空白或刷新 404？**
运行目录未指向 `public/`，或伪静态未配置（见《06》）。SPA 是 history 路由，任何路径都应回退到 `public/index.php`。

**Q11：二级目录部署（如 /admin）支持吗？**
需要：前端构建设置 `base: '/admin/'`（vite.config.ts）+ 后端各处 `/api` 前缀同步调整 + 伪静态按子目录改写。初次部署不建议，直接用独立域名 / 子域名最省心。

**Q12：HTTPS 部署要注意什么？**
在 Nginx 终止 TLS 即可；若前面还有一层代理，确认传递 `X-Forwarded-For`（操作日志 IP 归属依赖它）。

**Q13：多个站点共用一台 MySQL，库名冲突？**
安装向导的「数据库名」可自由填写（字母数字下划线），默认 `linc_admin`。

## 功能与开发

**Q14：新加的菜单超管能看，普通角色看不到？**
正常。超管直通全部启用菜单；普通角色需在「角色管理」中勾选菜单并保存，角色成员重新登录生效。

**Q15：删不掉用户 / 角色 / 部门 / 字典？**
都是业务保护规则（内置管理员不可删停、角色已分配用户、存在下级部门、字典类型下有数据、内置参数不可改删）——见《03》。确需删除先处理关联数据。

**Q16：软删除的账号重新创建同名报「系统内部错误」？**
已知边界：`username` 唯一索引仍被软删除行占用（经典实现同样如此）。恢复该账号：`UPDATE sys_user SET deleted = 0 WHERE username = 'xxx'`；或物理删除后重建。

**Q17：操作日志的 request_params 里 password 是明文吗？**
不是。`OperLog` 中间件自动将含 `password / token / secret / authorization` 的字段打码为 `******`，自定义日志请遵循同一规则。

**Q18：新主题不出现 / 颜色不生效？**
见《05》§5.2.5：检查 `SKINS` 注册、CSS 引入、选择器格式（`data-skin`）；页面写死字面量颜色会绕过主题。

**Q19：代码生成器能直接生成可用模块吗？**
不能，产物是**开发骨架**（实体 / 骨架接口 / 页面占位 / 菜单 SQL），业务 CRUD 按《05》§5.1 实现后接入——这是有意的安全边界。

**Q20：升级版本时数据库怎么办？**
版本脚本幂等：把新版 `database/V*.sql` 覆盖到部署目录后，对已装库手工执行**新增的**版本脚本即可；`install.lock` 不动。变更前先备份。
