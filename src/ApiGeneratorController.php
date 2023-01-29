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
     * @var string $path default: @app/models
     */
    public $path = '@v1';

    /**
     * @var string $namespace default app\models
     */
    public $namespace = 'v1';

    /**
     * @var string $template default: @app/views/template/model.php
     */
    public $template = '@app/views/templates/api.php';

    /**
     * @var string $moduleTemplatePath default: @vendor/atellitech/utils/templates/module
     */
    public $moduleTemplatePath = '@vendor/atellitech/utils-yii2/src/templates/module';

    /**
     * @var string $db database component id default: db
     */
    public $db = 'db';

    /**
     * declare options
     *
     * @param string $actionID
     * @return string[]
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'path', 'namespace', 'template'
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
     * generate an API module
     *
     * @param string $name
     * @return void
     */
    public function actionGenerateModule(string $name)
    {
        $nameAlias = sprintf('@%s', $name);
        Yii::setAlias($nameAlias, '@app/modules/' . $name);
        $data = [
            'name' => $name,
            'nameAlias' => $nameAlias
        ];
        $destPath = Yii::getAlias($nameAlias);
        $moduleTemplatePath = Yii::getAlias($this->moduleTemplatePath);
        $files = \yii\helpers\FileHelper::findFiles($moduleTemplatePath);
        foreach($files as $file) {
            $dest = str_replace($moduleTemplatePath, $destPath, $file);
            $dir = dirname($dest);
            if (!is_dir($dir))
                mkdir($dir, 0755, true);

            $contents = $this->view->renderFile($file, $data);
            file_put_contents($dest, $contents);
        }

        echo "\nFinished create module $name";
    }

    /**
     * generate a model by specific table name
     *
     * @param string $tableName
     * @param int $override {0: false, 1: true} default: 0
     * @return void
     */
    public function actionGenerate(string $tableName, int $override = 0)
    {

    }

    /**
     * gernerate all table name's models
     *
     * @param int {0: false, 1: true} default: 1
     */
    public function actionAll(int $override = 1)
    {

    }
}
