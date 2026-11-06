<?php

declare(strict_types=1);

namespace Linc\Service;

use Linc\Core\AuthContext;
use Linc\Core\BusinessException;
use Linc\Core\PageQuery;
use Linc\Core\PageResult;
use Linc\Core\StringUtil;
use Linc\Core\ZipBuilder;
use Linc\Repository\GeneratorRepository;

/**
 * 代码生成业务：导入数据库表元数据，输出 7 个业务模块开发骨架（PHP 后端 + Vue 前端 + 菜单 SQL）。
 * 产物是开发骨架：业务 CRUD 需按 docs/05-扩展开发实战.md 完成后接入。
 */
final class GeneratorService
{
    public static function tables(PageQuery $query, ?string $keyword): array
    {
        $pageNum = $query->normalizedPageNum();
        $pageSize = $query->normalizedPageSize();
        $rows = self::booleanRows(
            GeneratorRepository::selectTables($keyword, ($pageNum - 1) * $pageSize, $pageSize),
            ['synced']
        );
        return PageResult::of($rows, GeneratorRepository::countTables($keyword), $pageNum, $pageSize);
    }

    public static function columns(int $tableId): array
    {
        self::ensureTable($tableId);
        return self::booleanRows(
            GeneratorRepository::selectColumns($tableId),
            ['insert', 'edit', 'list', 'query', 'required']
        );
    }

    public static function importableTables(?string $keyword): array
    {
        return self::booleanRows(GeneratorRepository::selectImportableTables($keyword), ['imported']);
    }

    /** @return array{imported: int, ids: list<int>} */
    public static function importTables(?array $tableNames): array
    {
        if ($tableNames === null || $tableNames === []) {
            throw new BusinessException('请选择要导入的数据库表');
        }
        $ids = [];
        foreach ($tableNames as $tableName) {
            $existingId = GeneratorRepository::selectTableIdByName((string) $tableName);
            if ($existingId !== null) {
                $ids[] = $existingId;
                continue;
            }
            $dbTable = GeneratorRepository::selectDatabaseTable((string) $tableName);
            if ($dbTable === null) {
                throw new BusinessException('数据库表不存在：' . $tableName);
            }
            $row = self::tableRow($dbTable);
            $tableId = GeneratorRepository::insertTable($row, AuthContext::operator());
            foreach (GeneratorRepository::selectDatabaseColumns((string) $tableName) as $column) {
                GeneratorRepository::insertColumn(self::columnRow($tableId, $column));
            }
            $ids[] = $tableId;
        }
        return ['imported' => count($ids), 'ids' => $ids];
    }

    /** @return array<string,mixed> */
    public static function preview(int $tableId): array
    {
        $table = self::ensureTable($tableId);
        $className = (string) $table['className'];
        $activeFile = $className . 'Controller.php';
        $files = [];
        $activeCode = '';
        foreach (self::renderFiles($table, GeneratorRepository::selectColumns($tableId)) as $path => $code) {
            $name = substr($path, strrpos($path, '/') + 1);
            $files[] = [
                'name' => $name,
                'path' => $path,
                'code' => $code,
            ];
            if ($name === $activeFile) {
                $activeCode = $code;
            }
        }
        return [
            'files' => $files,
            'activeFile' => $activeFile,
            'code' => $activeCode,
        ];
    }

    public static function updateTable(int $id, ?array $request): void
    {
        $tableComment = $request === null ? null : self::stringOrNull($request['tableComment'] ?? null);
        $synced = null;
        if ($request !== null && array_key_exists('synced', $request)) {
            $synced = ($request['synced'] === true) ? 1 : 0;
        }
        if (GeneratorRepository::updateTable($id, $tableComment, $synced, AuthContext::operator()) === 0) {
            throw new BusinessException('代码生成表不存在');
        }
    }

    /** @return array<string,mixed> */
    public static function generate(int $tableId): array
    {
        $table = self::ensureTable($tableId);
        return [
            'tableName' => $table['tableName'],
            'message' => '代码压缩包已准备就绪',
        ];
    }

    public static function download(int $tableId): string
    {
        $table = self::ensureTable($tableId);
        $columns = GeneratorRepository::selectColumns($tableId);
        $files = self::renderFiles($table, $columns);
        $zip = new ZipBuilder();
        foreach ($files as $name => $code) {
            $zip->add($name, $code);
        }
        return $zip->build();
    }

    /** @return array<string,mixed> */
    private static function ensureTable(int $tableId): array
    {
        $table = GeneratorRepository::selectTableById($tableId);
        if ($table === null) {
            throw new BusinessException('代码生成表不存在');
        }
        return $table;
    }

    /** @return array<string,mixed> */
    private static function tableRow(array $dbTable): array
    {
        $tableName = (string) $dbTable['tableName'];
        return [
            'tableName' => $tableName,
            'tableComment' => $dbTable['tableComment'],
            'className' => (string) StringUtil::upperCamel($tableName),
            'module' => self::moduleName($tableName),
            'businessName' => (string) StringUtil::lowerCamel(self::businessName($tableName)),
        ];
    }

    /** @return array<string,mixed> */
    private static function columnRow(int $tableId, array $column): array
    {
        $columnName = (string) $column['columnName'];
        $dataType = strtolower((string) $column['dataType']);
        $primaryLike = $columnName === 'id';
        return [
            'tableId' => $tableId,
            'columnName' => $columnName,
            'fieldName' => (string) StringUtil::lowerCamel($columnName),
            'fieldType' => self::fieldType($dataType),
            'dataType' => $dataType,
            'columnComment' => $column['columnComment'],
            'insert' => $primaryLike ? 0 : 1,
            'edit' => $primaryLike ? 0 : 1,
            'list' => $primaryLike ? 0 : 1,
            'query' => self::queryField($columnName) ? 1 : 0,
            'queryType' => self::queryField($columnName) ? 'LIKE' : '=',
            'formType' => self::formType($dataType),
            'required' => (($column['nullable'] ?? '') === 'NO' && !$primaryLike) ? 1 : 0,
            'sort' => $column['sort'],
        ];
    }

    /**
     * 7 个输出文件：PHP 后端骨架（仓储 / 服务 / 控制器 / 路由片段）+ Vue 前端骨架 + 菜单 SQL。
     * 路径即放置位置，放入项目根对应目录后按 docs/05-扩展开发实战.md 接入。
     * @return array<string,string> path => code
     */
    private static function renderFiles(array $table, array $columns): array
    {
        $tableName = (string) $table['tableName'];
        $className = (string) $table['className'];
        $module = (string) $table['module'];
        $resource = (string) StringUtil::decapitalize($className);
        $files = [];
        $files[sprintf('backend/app/Repository/%sRepository.php', $className)]
            = self::repositoryCode($className, $tableName, $columns);
        $files[sprintf('backend/app/Service/%sService.php', $className)]
            = self::serviceCode($className, $columns);
        $files[sprintf('backend/app/Controller/%sController.php', $className)]
            = self::controllerCode($className, $resource);
        $files[sprintf('backend/app/Application/%sRoutes.php', $className)]
            = self::routesCode($className, $module, $resource);
        $files[sprintf('frontend/src/api/%s/%s.ts', $module, $resource)]
            = self::apiCode($module, $resource);
        $files[sprintf('frontend/src/views/%s/%s/index.vue', $module, $resource)]
            = self::viewCode($tableName, $className, $module, $resource, $columns);
        $files[sprintf('sql/%s_menu.sql', $tableName)]
            = self::menuSql($tableName, $className, $module, $resource);
        return $files;
    }

    private static function repositoryCode(string $className, string $tableName, array $columns): string
    {
        $fieldDocs = '';
        foreach ($columns as $column) {
            $comment = $column['comment'] ?? null;
            $fieldDocs .= '     * - ' . $column['col'] . ' ' . ($comment === null ? '' : (string) $comment) . "\n";
        }
        $fields = '';
        foreach ($columns as $column) {
            if ((int) ($column['col'] === 'id')) {
                continue;
            }
            $fields .= ($fields === '' ? '' : ', ') . $column['col'];
        }
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Linc\Repository;

        use Linc\Core\Database;

        /**
         * {$className} 数据访问骨架（表 {$tableName}）。
         * 字段：
         {$fieldDocs}     *
         * 按 docs/05-扩展开发实战.md 补全业务查询；SQL 集中在本层，禁止散落到控制器或服务。
         */
        final class {$className}Repository
        {
            public static function count(?string \$keyword): int
            {
                return (int) Database::scalar(
                    "SELECT COUNT(*) FROM {$tableName} WHERE deleted = 0
                      AND (? IS NULL OR ? = '' OR " . self::keywordColumn() . " LIKE CONCAT('%', ?, '%'))",
                    [\$keyword, \$keyword, \$keyword]
                );
            }

            /** @return list<array<string,mixed>> */
            public static function select(?string \$keyword, int \$offset, int \$pageSize): array
            {
                return Database::all(
                    "SELECT id, {$fields}, DATE_FORMAT(create_time, '%Y-%m-%d %H:%i') AS createTime
                     FROM {$tableName}
                     WHERE deleted = 0
                       AND (? IS NULL OR ? = '' OR " . self::keywordColumn() . " LIKE CONCAT('%', ?, '%'))
                     ORDER BY id DESC LIMIT ?, ?",
                    [\$keyword, \$keyword, \$keyword, \$offset, \$pageSize]
                );
            }

            /** @param array<string,mixed> \$row */
            public static function insert(array \$row, string \$operator): int
            {
                return Database::insert(
                    "INSERT INTO {$tableName} ({$fields}, create_by, update_by)
                     VALUES (?, ?, ?, ?)",
                    [/* 按列顺序填充字段值 */ \$operator, \$operator]
                );
            }

            public static function softDelete(int \$id, string \$operator): int
            {
                return Database::execute(
                    "UPDATE {$tableName} SET deleted = 1, update_by = ? WHERE id = ? AND deleted = 0",
                    [\$operator, \$id]
                );
            }

            private static function keywordColumn(): string
            {
                return 'id'; // TODO：改为标题 / 名称类字段的列名
            }
        }
        PHP;
    }

    private static function serviceCode(string $className, array $columns): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Linc\Service;

        use Linc\Core\AuthContext;
        use Linc\Core\BusinessException;
        use Linc\Core\PageQuery;
        use Linc\Core\PageResult;
        use Linc\Repository\\{$className}Repository;

        /**
         * {$className} 业务骨架：校验与业务规则集中在这一层。
         */
        final class {$className}Service
        {
            public static function page(PageQuery \$query, ?string \$keyword): array
            {
                \$pageNum = \$query->normalizedPageNum();
                \$pageSize = \$query->normalizedPageSize();
                return PageResult::of(
                    {$className}Repository::select(\$keyword, (\$pageNum - 1) * \$pageSize, \$pageSize),
                    {$className}Repository::count(\$keyword),
                    \$pageNum,
                    \$pageSize
                );
            }

            /** @param array<string,mixed> \$request */
            public static function create(array \$request): array
            {
                // TODO：required() 必填校验、唯一性校验、字段默认值……
                \$id = {$className}Repository::insert(\$request, AuthContext::operator());
                return ['id' => \$id];
            }

            public static function remove(int \$id): void
            {
                if ({$className}Repository::softDelete(\$id, AuthContext::operator()) === 0) {
                    throw new BusinessException('记录不存在或已删除');
                }
            }
        }
        PHP;
    }

    private static function controllerCode(string $className, string $resource): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Linc\Controller;

        use Linc\Service\\{$className}Service;
        use Psr\Http\Message\ResponseInterface as Response;
        use Psr\Http\Message\ServerRequestInterface as Request;

        final class {$className}Controller
        {
            public function index(Request \$request, Response \$response): Response
            {
                \$keyword = self::strParam(\$request, 'keyword');
                return AuthController::ok(\$response, {$className}Service::page(AuthController::pageQuery(\$request), \$keyword));
            }

            public function create(Request \$request, Response \$response): Response
            {
                return AuthController::ok(\$response, {$className}Service::create(json_body((string) \$request->getBody())));
            }

            public function remove(Request \$request, Response \$response, array \$args): Response
            {
                {$className}Service::remove(AuthController::pathId(\$args));
                return AuthController::ok(\$response);
            }

            private static function strParam(Request \$request, string \$name): ?string
            {
                \$value = \$request->getQueryParams()[\$name] ?? null;
                return is_string(\$value) && \$value !== '' ? \$value : null;
            }
        }
        PHP;
    }

    private static function routesCode(string $className, string $module, string $resource): string
    {
        return <<<PHP
        <?php

        // 路由注册片段：复制到 app/Application/AppFactory.php 的 registerRoutes() 中，
        // 并在文件头补充 use Linc\Controller\\{$className}Controller;（权限码按模块调整）。

        \$app->group('/api/{$module}/{$resource}', function (RouteCollectorProxy \$group): void {
            \$group->get('', [{$className}Controller::class, 'index']);
            \$group->post('', [{$className}Controller::class, 'create'])
                ->add(new OperLog('{$className}管理', '新增'));
            \$group->delete('/{id}', [{$className}Controller::class, 'remove'])
                ->add(new OperLog('{$className}管理', '删除'));
        })->add(new RequirePermission('{$module}:{$resource}:list'));
        PHP;
    }

    private static function apiCode(string $module, string $resource): string
    {
        return <<<TS
        import { httpClient } from '@/api/http'

        export const {$resource}Api = {
          list: (query?: Record<string, unknown>) => httpClient.get('/{$module}/{$resource}', query as any),
          create: (body: Record<string, unknown>) => httpClient.post('/{$module}/{$resource}', body),
          remove: (id: number) => httpClient.delete(`/{$module}/{$resource}/\${id}`),
        }
        TS;
    }

    private static function viewCode(string $tableName, string $className, string $module, string $resource, array $columns): string
    {
        $columnLines = '';
        foreach ($columns as $column) {
            if ((int) ($column['list'] ?? 0) !== 1) {
                continue;
            }
            $comment = $column['comment'] ?? null;
            $title = $comment === null || trim((string) $comment) === '' ? (string) $column['col'] : (string) $comment;
            $columnLines .= "  { key: '" . $column['fieldName'] . "', title: '" . $title . "', minWidth: 120 },\n";
        }
        $columnsCode = $columnLines === '' ? "  { key: 'id', title: 'ID', width: 80 },\n" : $columnLines;
        return <<<VUE
        <script setup lang="ts">
        import { ListPage } from '@/components/business'
        import { httpClient } from '@/api/http'
        import { useListPage } from '@/composables/list'

        interface Row { id: number; status: number; createTime?: string; [key: string]: any }

        const list = useListPage<Row, { keyword: string }>({
          query: { keyword: '' },
          fetch: params => httpClient.get('/{$module}/{$resource}', params),
        })

        const columns = [
        {$columnsCode}]
        </script>

        <template>
          <ListPage
            title="{$className}管理"
            desc="TODO：补充页面说明。"
            table-title="{$className}列表"
            :list="list"
            :columns="columns"
            :min-width="900"
          />
        </template>
        VUE;
    }

    private static function menuSql(string $tableName, string $className, string $module, string $resource): string
    {
        return <<<SQL
        -- {$tableName} 菜单权限
        INSERT INTO sys_menu (id, parent_id, menu_name, menu_type, path, component, permission, sort, visible, status, create_by, update_by)
        SELECT COALESCE(MAX(id), 0) + 1, 0, '{$className}管理', 'menu', '/{$module}/{$resource}', '{$module}/{$resource}/index', '{$module}:{$resource}:list', 1, 1, 1, 'system', 'system'
        FROM sys_menu;
        SQL;
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @param list<string> $keys
     * @return list<array<string,mixed>>
     */
    private static function booleanRows(array $rows, array $keys): array
    {
        foreach ($rows as &$row) {
            foreach ($keys as $key) {
                if (isset($row[$key]) && (is_int($row[$key]) || is_numeric($row[$key]))) {
                    $row[$key] = (int) $row[$key] === 1;
                }
            }
        }
        unset($row);
        return $rows;
    }

    private static function moduleName(string $tableName): string
    {
        $split = strpos($tableName, '_');
        $module = $split > 0 ? substr($tableName, 0, $split) : 'system';
        return $module === 'sys' ? 'system' : $module;
    }

    private static function businessName(string $tableName): string
    {
        $split = strpos($tableName, '_');
        return $split > 0 ? substr($tableName, $split + 1) : $tableName;
    }

    /** 存储类型 → PHP 侧类型表述（列表 / 表单渲染与校验参考）。 */
    private static function fieldType(string $dataType): string
    {
        return match ($dataType) {
            'bigint', 'int', 'integer', 'smallint', 'tinyint' => 'int',
            'decimal', 'numeric', 'float', 'double' => 'float',
            default => 'string',
        };
    }

    private static function formType(string $dataType): string
    {
        return match ($dataType) {
            'text', 'longtext', 'mediumtext' => '文本域',
            'int', 'integer', 'smallint', 'tinyint', 'bigint', 'decimal', 'numeric', 'float', 'double' => '数字框',
            'datetime', 'timestamp', 'date', 'time' => '日期框',
            default => '文本框',
        };
    }

    private static function queryField(string $columnName): bool
    {
        $lower = strtolower($columnName);
        return str_contains($lower, 'name')
            || str_contains($lower, 'title')
            || str_contains($lower, 'code')
            || str_contains($lower, 'status')
            || str_contains($lower, 'type');
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return is_scalar($value) ? (string) $value : null;
    }
}
