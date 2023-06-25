<?php

namespace AtelliTech\Yii2\Utils;

use Exception;
use Yii;
use yii\console\Controller;
use yii\db\ColumnSchema;
use yii\helpers\Console;
use yii\helpers\Inflector;

/**
 * code generator controlelr
 *
 * @author Eric Huang <eric.huang@cyntelli.com>
 * @version 1.0.0
 */
class ModelGeneratorController extends Controller
{
    /**
     * @var string $path default: @app/models
     */
    public $path = '@app/models';

    /**
     * @var string $namespace default app\models
     */
    public $namespace = 'app\models';

    /**
     * @var string $template default: @app/views/template/model.php
     */
    public $template = '@vendor/atellitech/utils-yii2/src/templates/model.php.tmpl';

    /**
     * @var string $db database component id default: db
     */
    public $db = 'db';

    /**
     * @var string $oldDb original database component id
     */
    public $oldDb;

    /**
     * @var string $defaultAction
     */
    public $defaultAction = 'generate';

    /**
     * declare options
     *
     * @param string $actionID
     * @return string[]
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'path', 'namespace', 'template', 'db', 'oldDb'
        ]);
    }

     /**
     * {@inheritdoc}
     *
     * @return array<string, string>
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'p' => 'path',
            'ns' => 'namespace',
            'tmpl' => 'template'
        ]);
    }

    /**
     * generate a model by specific table name
     *
     * @param string $tableName
     * @param int $override {0: false, 1: true} default: 0
     * @return void|int
     */
    public function actionGenerate(string $tableName, int $override = 0)
    {
        $className = Inflector::camelize($tableName);
        $classPath = sprintf('%s/%s.php', Yii::getAlias($this->path), $className);
        if (file_exists($classPath)) {
            if ($override == 0) {
                if (!$this->confirm("This model file exists($classPath), please type yes to override", true)) {
                    echo $this->ansiFormat("\nSkip generate model of $tableName when type no\n");
                    return 0;
                }
            }
        }

        // check director
        $dirname = dirname($classPath);
        if (!is_dir($dirname))
            mkdir($dirname, 0755, true);

        $db = $this->module->get($this->db);
        $schema = $db->getTableSchema($tableName);
        $attributes = [];
        $ruleTypes = [
            'trim' => []
        ];
        $otherRules = [];
        $extraFields = [];
        $columns = $schema->columns;
        foreach($columns as $name=>$col) {
            if ($col->type === 'bigint')
                $type = 'integer';
            else
                $type = $col->phpType;

            if (!isset($ruleTypes[$type]))
                $ruleTypes[$type] = [];

            $ruleTypes[$type][] = $name;
            $ruleTypes['trim'][] = $name;

            // extract extra field of relation column
            if (preg_match('/\_id$/', $name) != false)
                $extraFields[] = str_replace('_id', '', $name);

            // extract enum column
            if (preg_match('/^enum/i', $col->dbType) != false) {
                if (!isset($otherRules['in']))
                    $otherRules['in'] = [];

                $otherRules['in'][$name] = ['range'=>$col->enumValues];
            }

            // set attributes
            $attributes[$name] = 1;
        }

        $rules = $this->composeRuleTypes($ruleTypes);
        $rules = array_merge($rules, $this->composeOtherRules($otherRules));

        // process annotations and requires
        list($schemas, $requires) = $this->createSchemaByColumns($columns);
        $annotations = [];
        foreach($schemas as $schema) {
            $attrsStr = '';
            foreach($schema as $key=>$value) {
                if ($value === null)
                    continue;

                if (!empty($attrsStr))
                    $attrsStr .= ', ';

                $attrsStr .= $key . '=' . $value;
            }

            $annotations[] = $attrsStr;
        }

        $data = [
            'className' => $className,
            'namespace' => $this->namespace,
            'requires' => $requires,
            'annotations' => $annotations,
            'lastAnnotationNum' => count($annotations) - 1,
            'tableName' => $tableName,
            'rules' => implode("\n", $rules),
            'extraFields' => $this->composeExtraFields($extraFields),
            'attributes' => $attributes
        ];

        $contents = $this->view->renderFile($this->template, $data);
        file_put_contents($classPath, $contents);
        echo $this->ansiFormat("Create model $className(TableName: $tableName), success\n", Console::FG_GREEN);
    }

    /**
     * gernerate all table name's models
     *
     * @param int $override {0: false, 1: true} default: 1
     * @return void
     */
    public function actionAll(int $override = 1)
    {
        $db = $this->module->get($this->db);
        $sql = 'show tables';
        $rows = $db->createCommand($sql)
                   ->queryColumn();

        foreach($rows as $tableName) {
            if (preg_match('/\./', $tableName) != false)
                continue;

            $this->run('generate', [$tableName, $override]);
        }
    }

    /**
     * generator table migration
     *
     * @param string $tableName
     * @return void
     */
    public function actionMigration(string $tableName)
    {
        $db = Yii::$app->get($this->db);
        $tableSchema = $db->getTableSchema($tableName);
        if (empty($tableSchema))
            throw new Exception("The table($tableName) not found", 404);

        $syntax = '$this->createTable($this->table, [';
        $columns = $tableSchema->columns;
        foreach($columns as $column) {
            $name = $column->name;

            // check type
            if (str_starts_with($column->dbType, 'enum')) {
                $chains = ['ENUM' . strstr($column->dbType, '(')];

                // check default
                if (!empty($column->defaultValue))
                    $chains[] = sprintf('DEFAULT "%s"', $column->defaultValue);

                // check null
                if ($column->allowNull)
                    $chains[] = 'NULL';
                else
                    $chains[] = 'NOT NULL';

                // check comment
                if (!empty($column->comment))
                    $chains[] = 'COMMENT \'' . str_replace('"', "\"", $column->comment) . '\'';

                $syntax .= sprintf("\n    \"%s\" => \"%s\",", $name, implode(' ', $chains));
            } else {
                $chains = ['$this'];

                // check is primary
                if ($column->isPrimaryKey) {
                    $chains[] = 'bigPrimaryKey(20)';
                } else {
                    $type = $column->type;
                    $size = $column->size;
                    if (preg_match('/\_(at|by)$/', $name, $matches) != false) {
                        $type = 'integer';
                        $size = 10;
                        if ($matches[1] == 'by') {
                            $type = 'bigInteger';
                            $size = 20;
                        }
                    }

                    $chains[] = sprintf('%s(%d)', $type, $size);
                }

                // check unsigned
                if ($column->isPrimaryKey || preg_match('/\_(at|by)$/', $name) != false)
                    $chains[] = 'unsigned()';

                // check null
                if ($column->allowNull && preg_match('/\_(at|by)$/', $name) == false)
                    $chains[] = 'null()';
                else
                    $chains[] = 'notNull()';

                // check default
                if (!empty($column->defaultValue))
                    $chains[] = sprintf('defaultValue("%s")', $column->defaultValue);

                // check comment
                if (!empty($column->comment) || preg_match('/\_(at|by)$/', $name, $matched) != false) {
                    $comment = $column->comment;

                    if (isset($matched[1])) {
                        if ($matched[1] == 'at')
                            $comment = 'unixtime';
                        else
                            $comment = 'ref: users.id';
                    }

                    $chains[] = 'comment("' . str_replace('"', "\"", $comment) . '")';
                }

                $syntax .= sprintf("\n    \"%s\" => %s,", $name, implode('->', $chains));
            }
        }

        $syntax .= "\n]);";
        echo "\n$syntax\n\n";
    }

    /**
     * compose rule types
     *
     * @param array<string, string[]> $ruleTypes
     * @return string[]
     */
    protected function composeRuleTypes(array $ruleTypes): array
    {
        $rules = [];
        foreach($ruleTypes as $type=>$columns) {
            $rules[] = sprintf("            [['%s'], '%s'],", implode("', '", $columns), $type);
        }

        return $rules;
    }

    /**
     * compose other rules
     *
     * @param array<string, mixed> $otherRules
     * @return string[]
     */
    protected function composeOtherRules(array $otherRules): array
    {
        $rules = [];
        foreach($otherRules as $type=>$items) {
            foreach($items as $name=>$item) {
                if ($type == 'in')
                    $rules[] = sprintf("            [['%s'], '%s', 'range'=>['%s']],", $name, $type, implode("', '", $item['range']));
            }
        }

        return $rules;
    }

    /**
     * compose extra fields
     *
     * @param string[] $extraFields
     * @return string
     */
    protected function composeExtraFields(array $extraFields): string
    {
        if (empty($extraFields))
            return "        return [];";
        else
            return sprintf("        return ['%s'];", implode("', '", $extraFields));
    }

    /**
     * extract columns into properties of OAS
     *
     * @param ColumnSchema[] $columns
     * @return array<int, array<array<string, mixed>>|string[]>
     */
    protected function createSchemaByColumns(array $columns): array
    {
        // prepare swagger annotation
        $schemas = [];
        $requires = [];
        foreach($columns as $name=>$col) {
            $comment = str_replace('"', '""', $col->comment);
            if (empty($comment))
                $comment = $name;

            $type = $col->phpType;
            if ($type == 'resource')
                $type = 'object';
            elseif ($col->type == 'bigint')
                $type = 'integer';

            if ($col->autoIncrement)
                $comment .= ' #autoIncrement';

            if ($col->isPrimaryKey)
                $comment .= ' #pk';

            $default = $col->defaultValue === null ? null : sprintf('"%s"', $col->defaultValue);
            $maxLength = $col->size;
            $enum = null;
            if (!empty($col->enumValues))
                $enum = '{"' . implode('", "', $col->enumValues) . '"}';

            $attrs = [
                'property' => sprintf('"%s"', $name),
                'type' => sprintf('"%s"', $type),
                'description' => sprintf('"%s"', $comment),
                'maxLength' => ($maxLength == 0) ? null : $maxLength,
                'default' => $default,
                'enum' => $enum
            ];

            if (!$col->allowNull)
                $requires[] = $name;

            $schemas[] = $attrs;
        }

        return [$schemas, $requires];
    }
}
