<?php

namespace AtelliTech\Yii2\Utils;

use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\helpers\FileHelper;

/**
 * Dumper a file that register all service and repository files with specific suffix. The default suffix is 'Repo' and 'Service'.
 *
 * @author Eric Huang <eric.huang@cyntelli.com>
 * @version 1.0.0
 */
class ContainerController extends Controller
{
    /**
     * @var string
     */
    public $destPath = '@app/config/container/definitions.php';

    /**
     * @var array<int, mixed>
     */
    public $sources = [['app\components', '@app/components']];

    /**
     * @var string
     */
    public $suffix = 'Repo|Service';

    /**
     * @var string[]
     */
    public $exceptClasses = [];

    /**
     * declare options
     *
     * @param string $actionID
     * @return string[]
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'destPath', 'suffix'
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
            'dest' => 'destPath'
        ]);
    }

    /**
     * dump definitions
     *
     * @return void
     */
    public function actionDefinitions()
    {
        $dis = [];
        foreach($this->sources as $source) {
            list($ns, $srcPath) = $source;
            $srcPath = Yii::getAlias($srcPath);
            $entries = FileHelper::findFiles($srcPath);
            $pattern = '/.+(' . $this->suffix . ')\.php$/';
            foreach($entries as $entry) {
                if (preg_match($pattern, $entry) == false)
                    continue;

                $filename = basename($entry);
                if (in_array($filename, $this->exceptClasses))
                    continue;

                $class = str_replace([$srcPath, '/'], [$ns, '\\'], $entry);
                $class = substr($class, 0, -4);
                $dis[] = $class;
            }
        }

        sort($dis);

        $content = '<?php' . PHP_EOL;
        $content .= 'return [' . PHP_EOL;
        foreach($dis as $di) {
            $content .= "        '$di' => '$di'," . PHP_EOL;
        }
        $content .= '    ];' . PHP_EOL;
        $dest = Yii::getAlias($this->destPath);
        file_put_contents($dest, $content);
        echo "\nDumped to $dest\n";
    }
}
