<?= "<?php\n"; ?>

namespace app\modules\<?= $name ?>;

/**
 * This is an API module of <?= $name ?>.
 */
class Module extends \yii\base\Module
{
    /**
     * {@inherit}
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
    }
}
