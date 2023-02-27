<?php

namespace AtelliTech\Yii2\Utils;

use Exception;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\Inflector;

/**
 * code generator controlelr
 *
 * @author Eric Huang <eric.huang@cyntelli.com>
 * @version 1.0.0
 */
class ApiGeneratorController extends Controller
{
    /**
     * @var string $name default: v1
     */
    public $name = 'v1';

    /**
     * @var string $template default: @vendor/atellitech/utils-yii2/src/templates/api.php.tmpl
     */
    public $template = '@vendor/atellitech/utils-yii2/src/templates/api.php.tmpl';

    /**
     * @var string $moduleTemplatePath default: @vendor/atellitech/utils/templates/module
     */
    public $moduleTemplatePath = '@vendor/atellitech/utils-yii2/src/templates/module';

    /**
     * @var string $db database component id default: db
     */
    public $db = 'db';

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
            'name', 'template'
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
            'tmpl' => 'template'
        ]);
    }

    /**
     * generate an API module
     *
     * @return void
     */
    public function actionGenerateModule()
    {
        $nameAlias = sprintf('@%s', $this->name);
        Yii::setAlias($nameAlias, '@app/modules/' . $this->name);
        $data = [
            'name' => $this->name,
            'nameAlias' => $nameAlias
        ];
        $destPath = Yii::getAlias($nameAlias);
        $moduleTemplatePath = Yii::getAlias($this->moduleTemplatePath);
        $files = \yii\helpers\FileHelper::findFiles($moduleTemplatePath);
        foreach($files as $file) {
            $dest = str_replace([$moduleTemplatePath, '.tmpl'], [$destPath, ''], $file);
            $dir = dirname($dest);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $contents = $this->view->renderFile($file, $data);
            file_put_contents($dest, $contents);
        }

        echo "\nFinished create module $this->name";
    }

    /**
     * generate an API by specific table name
     *
     * @param string $tableName
     * @param int $override {0: false, 1: true} default: 0
     * @return void|int
     */
    public function actionGenerate(string $tableName, int $override = 0)
    {
        $template = Yii::getAlias($this->template);
        $nameAlias = '@' . $this->name;
        $controllersPath = Yii::getAlias($nameAlias . '/controllers');
        $db = $this->module->get($this->db);
        if (!is_dir($controllersPath))
            mkdir($controllersPath, 0755, true);

        // process class name and apiPath
        $className = Inflector::camelize($tableName);
        $apiPath = '/' . str_replace('_', '-', $tableName);
        $controllerName = $className . 'Controller';
        $dest = sprintf('%s/%s.php', $controllersPath, $controllerName);

        // check file exists
        if (file_exists($dest)) {
            if ($override == 0) {
                if (!$this->confirm("This API controller exists($dest), please type yes to override", true)) {
                    echo $this->ansiFormat("\nSkip generate API controller of $tableName when type no\n");
                    return 0;
                }
            }
        }

        // process annotations and requires
        $tableSchema = $db->getTableSchema($tableName);
        if (empty($tableSchema))
            throw new Exception("The table($tableName) not found", 404);

        $columns = $tableSchema->columns;
        $annotations = '';
        foreach($columns as $column) {
            $name = $column->name;
            $attrsStr = sprintf('property="%s", ref="#/components/schemas/%s/properties/%s"', $name, $className, $name);
            if (!empty($annotations))
                $annotations .= ",\n";

            $annotations .= sprintf(" *                  @OA\Property(%s)", $attrsStr);
        }

        // generate contents
        $data = [
            'name' => $this->name,
            'className' => $className,
            'apiPath' => $apiPath,
            'annotations' => $annotations
        ];
        $contents = $this->module->get('view')->renderFile($template, $data);
        file_put_contents($dest, $contents);
        echo "\nFinished API controller $className";
    }

    /**
     * gernerate all table name's models
     *
     * @param int $override {0: false, 1: true} default: 1
     * @return void
     */
    public function actionAll(int $override = 1)
    {
        $db = Yii::$app->get($this->db);
        $sql = 'show tables';
        $rows = $db->createCommand($sql)
                   ->queryColumn();

        foreach($rows as $tableName) {
            if (preg_match('/\./', $tableName) != false)
                continue;

            $this->run('generate', [$tableName, $override]);
        }
    }
}
