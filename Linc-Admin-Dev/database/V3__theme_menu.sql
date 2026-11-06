-- V3：主题中心菜单（灵枢中控 v1.1.0 新增多主题能力）
-- 幂等：INSERT IGNORE，重复执行无副作用。
-- 主题中心为页面级功能（无数据接口），权限码 system:theme:list 仅控制菜单与页面可见性；
-- 超级管理员默认可见，普通角色需在「角色管理」中授权。

INSERT IGNORE INTO sys_menu (id, parent_id, menu_name, menu_type, icon, path, component, permission, sort, visible, status, create_by, update_by)
VALUES (400, 0, '主题中心', 'menu', 'theme', '/theme', 'theme/index', 'system:theme:list', 5, 1, 1, 'system', 'system');

INSERT IGNORE INTO sys_role_menu (role_id, menu_id)
SELECT 1, 400;
