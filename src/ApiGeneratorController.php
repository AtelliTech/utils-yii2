<?php

namespace AtelliTech\Yii2\Utils;

use Exception;
use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;

/**
 * ApiGeneratorController is a Yii2 CLI tool
 * used to generate API controllers and modules based on database table schema.
 *
 * Usage:
 * ```bash
 * yii api-generator/generate <table-name> [--override=1]
 * yii api-generator/generate-module [--name=v1]
 * yii api-generator/all [--override=1]
 * ```
 *
 * @property string $name API module name (default: v1)
 * @property string $template Template file path
 * @property string $moduleTemplatePath Template path for API module skeleton
 * @property string $db Database component ID
 * @property string $defaultAction Default CLI action (generate)
 *
 * @version 1.1.0
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
class ApiGeneratorController extends Controller
{
    public string $name = 'v1';
    public string $template = '@vendor/atellitech/utils-yii2/src/templates/api.php.tmpl';
    public string $moduleTemplatePath = '@vendor/atellitech/utils-yii2/src/templates/module';
    public string $db = 'db';
    public $defaultAction = 'generate';

    /**
     * @param mixed $actionID
     * @return string[]
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['name', 'template']);
    }

    /**
     * @return array<string, string>
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), ['tmpl' => 'template']);
    }

    /**
     * Generate a new API module folder structure with templates.
     */
    public function actionGenerateModule(): void
    {
        $nameAlias = "@{$this->name}";
        Yii::setAlias($nameAlias, "@app/modules/{$this->name}");

        $data = [
            'name' => $this->name,
            'nameAlias' => $nameAlias,
        ];

        $destPath = Yii::getAlias($nameAlias);
        $templatePath = Yii::getAlias($this->moduleTemplatePath);
        $files = FileHelper::findFiles($templatePath);

        foreach ($files as $file) {
            $dest = str_replace([$templatePath, '.tmpl'], [$destPath, ''], $file);
            FileHelper::createDirectory(dirname($dest));
            $content = $this->view->renderFile($file, $data);
            file_put_contents($dest, $content);
        }

        $this->stdout("\nAPI module '{$this->name}' created successfully.\n", \yii\helpers\Console::FG_GREEN);
    }

    /**
     * Generate an API controller based on a table schema.
     *
     * @param string $tableName Table name
     * @param int $override Whether to override if the controller exists (default: 0)
     * @throws Exception If table is not found
     * @return int
     */
    public function actionGenerate(string $tableName, int $override = 0): int
    {
        $template = Yii::getAlias($this->template);
        $nameAlias = "@{$this->name}";
        $controllersPath = Yii::getAlias("{$nameAlias}/controllers");
        FileHelper::createDirectory($controllersPath);

        $className = Inflector::camelize($tableName);
        $controllerName = "{$className}Controller";
        $dest = "{$controllersPath}/{$controllerName}.php";

        if (file_exists($dest) && !$override) {
            if (!$this->confirm("Controller '{$controllerName}' exists. Overwrite?", true)) {
                $this->stdout("\nSkipped generating API controller for '{$tableName}'.\n", \yii\helpers\Console::FG_YELLOW);

                return 0;
            }
        }

        $db = $this->module->get($this->db);
        $tableSchema = $db->getTableSchema($tableName);
        if (!$tableSchema) {
            throw new Exception("Table '{$tableName}' not found.", 404);
        }

        $annotations = $this->generateAnnotations($className, $tableSchema->columns);
        $modelClassName = Inflector::singularize($className);
        $searchModelClassName = "{$modelClassName}Search";
        $searchServiceClassName = "{$modelClassName}SearchService";

        $data = [
            'name' => $this->name,
            'className' => $className,
            'apiPath' => '/'.str_replace('_', '-', $tableName),
            'annotations' => $annotations,
            'modelClassName' => $modelClassName,
            'searchModelClassName' => $searchModelClassName,
            'searchServiceClassName' => $searchServiceClassName,
        ];

        $contents = $this->module->get('view')->renderFile($template, $data);
        file_put_contents($dest, $contents);

        $this->stdout("\nAPI controller '{$className}' generated successfully.\n", \yii\helpers\Console::FG_GREEN);

        return 0;
    }

    /**
     * Generate API controllers for all tables.
     *
     * @param int $override Whether to override existing controllers (default: 1)
     */
    public function actionAll(int $override = 1): void
    {
        $db = Yii::$app->get($this->db);
        foreach ($db->createCommand('SHOW TABLES')->queryColumn() as $tableName) {
            if (!str_contains($tableName, '.')) {
                $this->run('generate', [$tableName, $override]);
            }
        }
    }

    /**
     * Generate Swagger-style property annotations for the API controller.
     *
     * @param string $className The model class name
     * @param array<string, mixed> $columns Column definitions
     * @return string Multiline string of @OA\Property annotations
     */
    private function generateAnnotations(string $className, array $columns): string
    {
        $lines = [];
        foreach ($columns as $column) {
            if (false != preg_match('/^(id|created_at|updated_at|created_by|updated_by)$/', $column->name)) {
                continue; // Skip common fields
            }

            $lines[] = sprintf(
                ' *                  @OA\Property(property="%s", ref="#/components/schemas/%s/properties/%s")',
                $column->name,
                $className,
                $column->name
            );
        }

        return implode(",\n", $lines);
    }
}
