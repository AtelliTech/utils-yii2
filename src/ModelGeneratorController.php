<?php

namespace AtelliTech\Yii2\Utils;

use Exception;
use Yii;
use yii\console\Controller;
use yii\db\ColumnSchema;
use yii\helpers\Console;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

/**
 * ModelGeneratorController is a Yii2 CLI tool
 * used to automatically generate models and migration code from table schema,
 * including support for Swagger annotations.
 *
 * Usage:
 * ```bash
 * yii model-generator/generate <table-name> [--override=1]
 * yii model-generator/all [--override=1]
 * yii model-generator/migration <table-name>
 * ```
 *
 * @property string $path Output directory for models (default: @app/models)
 * @property string $namespace Namespace for the models (default: app\models)
 * @property string $template Path to the template file
 * @property string $db Database component ID to use
 * @property string $oldDb Backup database component ID
 * @property string $defaultAction Default CLI action (generate)
 *
 * @author Eric Huang <eric.huang@cyntelli.com>
 * @version 2.0.0
 */
class ModelGeneratorController extends Controller
{
    public string $path = '@app/models';
    public string $namespace = 'app\\models';
    public string $template = '@vendor/atellitech/utils-yii2/src/templates/model.php.tmpl';
    public string $db = 'db';
    public string $oldDb;

    /**
     * @var string
     */
    public $defaultAction = 'generate';

    /**
     * Defines supported CLI options.
     * @param string $actionID
     * @return string[]
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'path', 'namespace', 'template', 'db', 'oldDb',
        ]);
    }

    /**
     * Defines aliases for CLI options.
     * @return array<string, string>
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'p' => 'path',
            'ns' => 'namespace',
            'tmpl' => 'template',
        ]);
    }

    /**
     * Generate a model from the specified table.
     * @param string $tableName Table name
     * @param int $override Whether to override existing file (0 = no, 1 = yes)
     * @return int
     */
    public function actionGenerate(string $tableName, int $override = 0): int
    {
        $className = Inflector::singularize(Inflector::camelize($tableName));
        $classPath = Yii::getAlias($this->path) . DIRECTORY_SEPARATOR . "$className.php";

        if (file_exists($classPath) && !$override) {
            if (!$this->confirm("Model exists at {$classPath}. Override?", true)) {
                $this->stdout("Skipped model generation for {$tableName}\n", Console::FG_YELLOW);
                return 0;
            }
        }

        FileHelper::createDirectory(dirname($classPath));
        $schema = $this->getDb()->getTableSchema($tableName);

        if (!$schema) {
            $this->stderr("Schema for table '{$tableName}' not found.\n", Console::FG_RED);
            return 1;
        }

        $ruleTypes = ['trim' => []];
        $otherRules = [];
        $extraFields = [];
        $attributes = [];

        foreach ($schema->columns as $name => $col) {
            $type = $this->processColumn($col, $name, $ruleTypes, $otherRules, $extraFields);
            $attributes[$name] = 1;
        }

        $rules = array_merge(
            $this->composeRuleTypes($ruleTypes),
            $this->composeOtherRules($otherRules)
        );

        [$schemas, $requires] = $this->createSchemaByColumns($schema->columns);
        $annotations = array_map(function ($schema) {
            return implode(', ', array_filter(array_map(
                fn($k, $v) => $v !== null ? "$k=$v" : null,
                array_keys($schema),
                $schema
            )));
        }, $schemas);

        $data = [
            'className' => $className,
            'namespace' => $this->namespace,
            'requires' => $requires,
            'annotations' => $annotations,
            'lastAnnotationNum' => count($annotations) - 1,
            'tableName' => $tableName,
            'rules' => implode("\n", $rules),
            'extraFields' => $this->composeExtraFields($extraFields),
            'attributes' => $attributes,
        ];

        $contents = $this->view->renderFile($this->template, $data);
        file_put_contents($classPath, $contents);

        $this->stdout("Created model {$className} for table {$tableName}.\n", Console::FG_GREEN);
        return 0;
    }

    /**
     * Generate models for all tables in the database.
     * @param int $override Whether to override existing files (0 = no, 1 = yes)
     */
    public function actionAll(int $override = 1): void
    {
        foreach ($this->getDb()->createCommand('SHOW TABLES')->queryColumn() as $tableName) {
            if (!str_contains($tableName, '.')) {
                $this->run('generate', [$tableName, $override]);
            }
        }
    }

    /**
     * Generate a migration snippet for the given table.
     * @param string $tableName Table name
     * @throws Exception If the table is not found
     */
    public function actionMigration(string $tableName): void
    {
        $schema = $this->getDb()->getTableSchema($tableName);
        if (!$schema) {
            throw new Exception("Table '{$tableName}' not found.", 404);
        }

        $syntax = "\$this->createTable(\$this->table, [";

        foreach ($schema->columns as $col) {
            $syntax .= "\n    \"{$col->name}\" => " . $this->buildColumnMigration($col) . ',';
        }

        $syntax .= "\n]);";
        echo "\n{$syntax}\n\n";
    }

    /**
     * Get the DB connection component.
     * @return \yii\db\Connection
     */
    protected function getDb()
    {
        return Yii::$app->get($this->db);
    }

    /**
     * Process a column to extract type and rule details.
     * @param ColumnSchema $col Column schema object
     * @param string $name Column name
     * @param array $ruleTypes Reference to collected rule types
     * @param array $otherRules Reference to other rules (default, enum)
     * @param array $extraFields Reference to extra fields (e.g., *_id)
     * @return string Type name
     */
    private function processColumn(ColumnSchema $col, string $name, array &$ruleTypes, array &$otherRules, array &$extraFields): string
    {
        $type = $col->type === 'bigint' ? 'integer' : $col->phpType;
        $ruleTypes[$type][] = $name;

        if ($type === 'string') {
            $ruleTypes['trim'][] = $name;
        }

        if (str_ends_with($name, '_id')) {
            $extraFields[] = substr($name, 0, -3);
        }

        if (str_starts_with($col->dbType, 'enum')) {
            $otherRules['in'][$name] = ['range' => $col->enumValues];
        }

        if ($col->defaultValue !== null) {
            $otherRules['defaults'][$name] = ['value' => $col->defaultValue];
        }

        return $type;
    }

    /**
     * Compose validation rules by type.
     * @param array<string, string[]> $ruleTypes
     * @return string[]
     */
    protected function composeRuleTypes(array $ruleTypes): array
    {
        $rules = [];
        foreach ($ruleTypes as $type => $columns) {
            $rules[] = sprintf("            [['%s'], '%s'],", implode("', '", $columns), $type);
        }
        return $rules;
    }

    /**
     * Compose enum and default value rules.
     * @param array<string, mixed> $otherRules
     * @return string[]
     */
    protected function composeOtherRules(array $otherRules): array
    {
        $rules = [];
        foreach ($otherRules as $type => $items) {
            foreach ($items as $name => $item) {
                if ($type === 'in') {
                    $rules[] = sprintf("            [['%s'], 'in', 'range' => ['%s']],", $name, implode("', '", $item['range']));
                } elseif ($type === 'defaults') {
                    $rules[] = sprintf("            [['%s'], 'default', 'value' => '%s'],", $name, $item['value']);
                }
            }
        }
        return $rules;
    }

    /**
     * Compose extraFields definition for model.
     * @param string[] $extraFields
     * @return string
     */
    protected function composeExtraFields(array $extraFields): string
    {
        return empty($extraFields)
            ? '        return [];'
            : sprintf("        return ['%s'];", implode("', '", $extraFields));
    }

    /**
     * Extract Swagger annotation schema from columns.
     * @param ColumnSchema[] $columns
     * @return array{0: array<array<string, mixed>>, 1: string[]}
     */
    protected function createSchemaByColumns(array $columns): array
    {
        $schemas = [];
        $requires = [];

        foreach ($columns as $name => $col) {
            $comment = $col->comment ?: $name;
            $type = $col->phpType === 'resource' ? 'object' : ($col->type === 'bigint' ? 'string' : $col->phpType);
            $default = $col->defaultValue !== null ? ($type === 'string' ? "\"{$col->defaultValue}\"" : $col->defaultValue) : null;

            $attrs = [
                'property' => "\"$name\"",
                'type' => "\"$type\"",
                'description' => "\"$comment\"",
                'maxLength' => $col->size ?: null,
                'default' => $default,
                'enum' => $col->enumValues ? '{"' . implode('", "', $col->enumValues) . '"}' : null,
            ];

            if (!$col->allowNull) {
                $requires[] = $name;
            }

            $schemas[] = $attrs;
        }

        return [$schemas, $requires];
    }

    /**
     * Build the migration definition for a column.
     * @param ColumnSchema $column Column schema
     * @return string Migration code snippet
     */
    private function buildColumnMigration(ColumnSchema $column): string
    {
        if (str_starts_with($column->dbType, 'enum')) {
            $chain = ['"ENUM' . strstr($column->dbType, '(') . '"'];
            if ($column->defaultValue !== null) {
                $chain[] = sprintf('DEFAULT "%s"', $column->defaultValue);
            }
            $chain[] = $column->allowNull ? 'NULL' : 'NOT NULL';
            if ($column->comment) {
                $chain[] = sprintf("COMMENT '%s'", str_replace('"', '"', $column->comment));
            }
            return implode(' ', $chain);
        }

        $chain = ['$this'];
        if ($column->isPrimaryKey) {
            $chain[] = 'bigPrimaryKey(20)';
        } else {
            $type = $column->type;
            $size = $column->size;
            if (preg_match('/\\_(at|by)$/', $column->name, $matches)) {
                $type = $matches[1] === 'by' ? 'bigInteger' : 'integer';
                $size = $matches[1] === 'by' ? 20 : 10;
            }
            $chain[] = sprintf('%s(%d)', $type, $size);
        }
        if ($column->isPrimaryKey || preg_match('/\\_(at|by)$/', $column->name)) {
            $chain[] = 'unsigned()';
        }
        $chain[] = ($column->allowNull && !preg_match('/\\_(at|by)$/', $column->name)) ? 'null()' : 'notNull()';
        if ($column->defaultValue !== null) {
            $chain[] = sprintf('defaultValue("%s")', $column->defaultValue);
        }
        if ($column->comment || preg_match('/\\_(at|by)$/', $column->name, $match)) {
            $comment = $match[1] === 'at' ? 'unixtime' : ($match[1] === 'by' ? 'ref: users.id' : $column->comment);
            $chain[] = sprintf('comment("%s")', str_replace('"', '"', $comment));
        }

        return implode('->', $chain);
    }
}
