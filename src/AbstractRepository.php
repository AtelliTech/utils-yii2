<?php

namespace AtelliTech\Yii2\Utils;

use Exception;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\db\Connection;

/**
 * This is an abstract repository class for accessing ActiveRecord, so every repository class should extend this class
 *
 * Note: the repository class must extend this class and should be set property modelClass
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
abstract class AbstractRepository
{
    use CustomErrorTrait;

    /**
     * @var string $modelClass
     */
    protected string $modelClass;

    /**
     * create
     *
     * @param array<string, mixed> $data
     * @param string $scenario default: null
     * @return ActiveRecord|bool
     */
    public function create(array $data, ?string $scenario = null): ActiveRecord|bool
    {
        try {
            $model = new $this->modelClass;
            if ($scenario !== null)
                $model->setScenario($scenario);

            $model->loadDefaultValues();
            $model->load($data, '');
            if (!$model->validate()) {
                $errors = $model->getErrorSummary(true);
                $this->setCustomError("Invalid data on create {$this->modelClass}. Err: " . implode(' ', $errors), 400, [['input'=>$data, 'class'=>$this->modelClass], ['errors'=>$errors]]);
                return false;
            }

            if (!$model->save(false)) {
                $errors = $model->getErrorSummary(true);
                $this->setCustomError("Create {$this->modelClass}, failed. Err: " . implode(' ', $errors), 500, [['input'=>$data, 'class'=>$this->modelClass], ['errors'=>$errors]]);
                return false;
            }

            return $model;
        } catch (Exception $e) {
            $this->setCustomError($e->getMessage(), $e->getCode(), [['input'=>$data]]);
            return false;
        }
    }

    /**
     * update
     *
     * @param array<string, mixed>|int|ActiveRecord $pk
     * @param array<string, mixed> $data
     * @param string $scenario default: null
     * @return ActiveRecord|bool
     */
    public function update(array|int|ActiveRecord $pk, array $data, ?string $scenario = null): ActiveRecord|bool
    {
        try {
            $model = null;
            if ($pk instanceof ActiveRecord) {
                if (($className=$pk::class) !== $this->modelClass) {
                    $this->setCustomError("Invalid model class($className)", 400, [['input'=>['pk'=>$pk->getPrimaryKey(), 'data'=>$data, 'scenario'=>$scenario]]]);
                    return false;
                }

                $model = &$pk;
            } else if (is_int($pk) || is_array($pk)) {
                $model = $this->findOne($pk);
            }

            if (empty($model)) {
                $this->setCustomError("{$this->modelClass} not found", 404, [['input'=>['pk'=>$pk]]]);
                return false;
            }

            if ($scenario !== null)
                $model->setScenario($scenario);

            $model->load($data, '');
            if (!$model->validate()) {
                $errors = $model->getErrorSummary(true);
                $this->setCustomError("Invalid data on update {$this->modelClass}. Err: " . implode(' ', $errors), 400, [['input'=>['pk'=>$model->getPrimaryKey(), 'data'=>$data, 'scenario'=>$scenario]], ['errors'=>$errors]]);
                return false;
            }

            if (!$model->save(false)) {
                $errors = $model->getErrorSummary(true);
                $this->setCustomError("Create {$this->modelClass}, failed. Err: " . implode(' ', $errors), 500, [['input'=>['pk'=>$model->getPrimaryKey(), 'data'=>$data, 'scenario'=>$scenario]], ['errors'=>$errors]]);
                return false;
            }

            return $model;
        } catch (Exception $e) {
            $this->setCustomError($e->getMessage(), $e->getCode(), [['input'=>['pk'=>$pk, 'data'=>$data, 'scenario'=>$scenario]]]);
            return false;
        }
    }

    /**
     * delete
     *
     * @param array<string, mixed>|int|ActiveRecord $pk
     * @return bool
     */
    public function delete(array|int|ActiveRecord $pk): bool
    {
        try {
            $model = null;
            if ($pk instanceof ActiveRecord) {
                if (($className=$pk::class) !== $this->modelClass) {
                    $this->setCustomError("Invalid model class($className)", 400, [['input'=>['pk'=>$pk->getPrimaryKey()]]]);
                    return false;
                }

                $model = &$pk;
            } else if (is_int($pk) || is_array($pk)) {
                $model = $this->findOne($pk);
            }

            if (empty($model)) {
                $this->setCustomError("{$this->modelClass} not found", 404, [['input'=>['pk'=>$pk]]]);
                return false;
            }

            if (!$model->delete()) {
                $errors = $model->getErrorSummary(true);
                $this->setCustomError("Delete {$this->modelClass}, failed. Err: " . implode(' ', $errors), 500, [['input'=>['pk'=>$model->getPrimaryKey()]], ['errors'=>$errors]]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->setCustomError($e->getMessage(), $e->getCode(), [['input'=>['pk'=>$pk]]]);
            return false;
        }
    }

    /**
     * delete all
     *
     * @param array<string, mixed> $condition
     * @return bool
     */
    public function deleteAll(array $condition): bool
    {
        try {
            if ($this->modelClass::deleteAll($condition) === false) {
                $this->setCustomError("Delete more {$this->modelClass}, failed", 500, [['input'=>['condition'=>$condition]]]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->setCustomError($e->getMessage(), $e->getCode(), [['input'=>['condition'=>$condition]]]);
            return false;
        }
    }

    /**
     * find
     *
     * @return ActiveQuery
     */
    public function find(): ActiveQuery
    {
        return $this->modelClass::find();
    }

    /**
     * findOne
     *
     * @param array<string, mixed>|int $condition
     * @return ActiveRecord|null
     */
    public function findOne(array|int $condition): ?ActiveRecord
    {
        return $this->modelClass::findOne($condition);
    }

    /**
     * get db
     *
     * @return Connection
     */
    public function getDb(): Connection
    {
        return $this->modelClass::getDb();
    }
}
