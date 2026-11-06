-- V4：公告管理（门户版前台内容源）与主题应用权限
-- 幂等：CREATE TABLE IF NOT EXISTS / INSERT IGNORE，重复执行无副作用。

CREATE TABLE IF NOT EXISTS biz_announce (
    id BIGINT NOT NULL AUTO_INCREMENT COMMENT '公告 ID',
    title VARCHAR(200) NOT NULL COMMENT '公告标题',
    content TEXT COMMENT '公告内容',
    status TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1 发布（前台可见），0 草稿',
    publish_time DATETIME DEFAULT NULL COMMENT '发布时间（缺省取创建时间）',
    deleted TINYINT NOT NULL DEFAULT 0 COMMENT '删除标记：1 删除，0 正常',
    create_by VARCHAR(64) DEFAULT NULL COMMENT '创建人',
    update_by VARCHAR(64) DEFAULT NULL COMMENT '更新人',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    update_time DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (id),
    KEY idx_biz_announce_status (status),
    KEY idx_biz_announce_publish (publish_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告表（门户前台展示）';

-- 菜单：公告管理（顶级）+ 按钮
INSERT IGNORE INTO sys_menu (id, parent_id, menu_name, menu_type, icon, path, component, permission, sort, visible, status, create_by, update_by)
VALUES (500, 0, '公告管理', 'menu', 'log', '/biz/announce', 'biz/announce/index', 'biz:announce:list', 5, 1, 1, 'system', 'system');
INSERT IGNORE INTO sys_menu (id, parent_id, menu_name, menu_type, permission, sort, visible, status, create_by, update_by)
VALUES (501, 500, '新增公告', 'btn', 'biz:announce:add', 1, 1, 1, 'system', 'system'),
       (502, 500, '编辑公告', 'btn', 'biz:announce:edit', 2, 1, 1, 'system', 'system'),
       (503, 500, '删除公告', 'btn', 'biz:announce:remove', 3, 1, 1, 'system', 'system');

-- 主题应用按钮权限（挂主题中心菜单下）
INSERT IGNORE INTO sys_menu (id, parent_id, menu_name, menu_type, permission, sort, visible, status, create_by, update_by)
VALUES (401, 400, '应用门户主题', 'btn', 'system:theme:edit', 1, 1, 1, 'system', 'system');

-- 超级管理员授权全部新菜单
INSERT IGNORE INTO sys_role_menu (role_id, menu_id)
SELECT 1, id FROM sys_menu WHERE id IN (401, 500, 501, 502, 503);
