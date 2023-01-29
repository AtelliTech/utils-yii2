<?= "<?php\n"; ?>

namespace <?= $name ?>\components;

use yii\web\HttpException;
use yii\web\Response;

/**
 * This is soft delete action extens rest action that using changing particular column status.
 */
class SoftDeleteAction extends \yii\rest\Action
{
    /**
     * @var string $statusValue
     */
    public $statusValue;

    /**
     * @var string $statusColumn
     */
    public $statusColumn;

    /**
     * Deletes a model.
     * @param mixed $id id of the model to be deleted.
     * @return Response
     * @throws HttpException on failure.
     */
    public function run($id): Response
    {
        $modelName = $this->modelClass;
        $statusColumn = $this->statusColumn;
        $statusValue = $this->statusValue;
        $model = $this->findModel($id);
        if ($model == null)
            throw new HttpException(404, "This $modelName($id) not found");

        $model->$statusColumn = $statusValue;
        if (!$model->save())
            throw new HttpException(500, "Delete $modelName by id($id), failed");

        return $this->controller->module->response->setStatusCode(204, '');
    }
}