<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @OA\Schema(
 *   schema="<?= $className ?>",
 *   title="<?= $className ?> Model",
 *   description="This model is used to access <?= $tableName ?> data",
 *   required={"<?= implode('", "', $requires) ?>"},
<?php foreach($annotations as $idx=>$annotation): ?>
 *   @OA\Property(<?= $annotation ?>)<?= ($idx != $lastAnnotationNum) ? ',' : '' ?>

<?php endforeach ?>
 * )
 *
 * @version 1.0.0
 */
class <?= $className ?> extends ActiveRecord
{
    /**
     * Return table name of <?= $tableName ?>.
     *
     * @return string
     */
    public static function tableName()
    {
        return '<?= $tableName ?>';
    }

    /**
     * Use timestamp to store time of login, update and create
     */
    public function behaviors()
    {
        return [
<?php if (isset($attributes['created_by']) && isset($attributes['updated_by'])): ?>
            [
                'class' => BlameableBehavior::className(),
                'defaultValue' => 0,
            ],
<?php endif ?>
<?php if (isset($attributes['created_at']) && isset($attributes['updated_at'])): ?>
            \yii\behaviors\TimestampBehavior::className()
<?php endif; ?>
        ];
    }

    /**
     * rules
     */
    public function rules()
    {
        return [
<?= trim($rules, ',') . "\n" ?>
        ];
    }

    /**
     * return extra fields
     */
    public function extraFields()
    {
<?= $extraFields . "\n" ?>
    }
}
