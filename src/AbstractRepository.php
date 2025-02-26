<?php

namespace AtelliTech\Yii2\Utils;

use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * This is an abstract repository class for accessing ActiveRecord, so every repository class should extend this class.
 *
 * Note: the repository class must extend this class and should be set property modelClass
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
abstract class AbstractRepository
{
    /**
     * @var string
     */
    protected string $modelClass;

    /**
     * create or update.
     *
     * @param ActiveRecord|array<string, mixed>|int $pk primary key
     * @param array<string, mixed> $data
     * @param null|string $scenario default: null
     * @return ActiveRecord
     */
    public function createOrUpdate(ActiveRecord|array|int $pk, array $data, ?string $scenario = null): ActiveRecord
    {
        $model = null;
        if ($pk instanceof ActiveRecord) {
            if (($className = $pk::class) !== $this->modelClass) {
                throw new Exception('Invalid primary key of model class('.$this->modelClass.')', 400);
            }

            $model = &$pk;
        } elseif (is_int($pk) || is_array($pk)) {
            $model = $this->findOne($pk);
        }

        if (empty($model)) {
            return $this->create($data, $scenario);
        }

        return $this->update($model, $data, $scenario);
    }

    /**
     * create.
     *
     * @param array<string, mixed> $data
     * @param null|string $scenario default: null
     * @return ActiveRecord
     */
    public function create(array $data, ?string $scenario = null): ActiveRecord
    {
        $model = new $this->modelClass();
        if (null !== $scenario) {
            $model->setScenario($scenario);
        }

        $model->loadDefaultValues();
        $model->load($data, '');
        if (!$model->validate()) {
            $errors = $model->getErrorSummary(true);

            throw new Exception(implode(' ', $errors), 400);
        }

        if (!$model->save(false)) {
            $errors = $model->getErrorSummary(true);

            throw new Exception(implode(' ', $errors), 500);
        }

        return $model;
    }

    /**
     * update.
     *
     * @param ActiveRecord|array<string, mixed>|int $pk
     * @param array<string, mixed> $data
     * @param null|string $scenario default: null
     * @return ActiveRecord
     */
    public function update(ActiveRecord|array|int $pk, array $data, ?string $scenario = null): ActiveRecord
    {
        $model = null;
        if ($pk instanceof ActiveRecord) {
            if (($className = $pk::class) !== $this->modelClass) {
                throw new Exception('Invalid primary key of model class('.$this->modelClass.')', 400);
            }

            $model = &$pk;
        } elseif (is_int($pk) || is_array($pk)) {
            $model = $this->findOne($pk);

            if (empty($model)) {
                if (is_array($pk)) {
                    $pk = json_encode($pk);
                }

                $message = sprintf('%s not found. pk: %s', $this->modelClass, $pk);

                throw new Exception($message, 404);
            }
        }

        if (null !== $scenario) {
            $model->setScenario($scenario);
        }

        $model->load($data, '');
        if (!$model->validate()) {
            $errors = $model->getErrorSummary(true);

            throw new Exception(implode(' ', $errors), 400);
        }

        if (!$model->save(false)) {
            $errors = $model->getErrorSummary(true);

            throw new Exception(implode(' ', $errors), 500);
        }

        return $model;
    }

    /**
     * delete.
     *
     * @param ActiveRecord|array<string, mixed>|int $pk
     * @return bool
     */
    public function delete(ActiveRecord|array|int $pk): bool
    {
        $model = null;
        if ($pk instanceof ActiveRecord) {
            if (($className = $pk::class) !== $this->modelClass) {
                throw new Exception('Invalid primary key of model class('.$this->modelClass.')', 400);
            }

            $model = &$pk;
        } elseif (is_int($pk) || is_array($pk)) {
            $model = $this->findOne($pk);

            if (empty($model)) {
                if (is_array($pk)) {
                    $pk = json_encode($pk);
                }

                $message = sprintf('%s not found. pk: %s', $this->modelClass, $pk);

                throw new Exception($message, 404);
            }
        }

        if (!$model->delete()) {
            $errors = $model->getErrorSummary(true);

            throw new Exception(implode(' ', $errors), 500);
        }

        return true;
    }

    /**
     * delete all.
     *
     * @param array<string, mixed> $condition
     * @return bool
     */
    public function deleteAll(array $condition): bool
    {
        if (false === $this->modelClass::deleteAll($condition)) {
            $message = sprintf('Delete multiple %s(%s), failed', $this->modelClass, json_encode($condition));

            throw new Exception($message, 500);
        }

        return true;
    }

    /**
     * find.
     *
     * @return ActiveQuery
     */
    public function find(): ActiveQuery
    {
        return $this->modelClass::find();
    }

    /**
     * findOne.
     *
     * @param array<string, mixed>|int $condition
     * @return null|ActiveRecord
     */
    public function findOne(array|int $condition): ?ActiveRecord
    {
        return $this->modelClass::findOne($condition);
    }

    /**
     * get database connection of model class.
     *
     * @return Connection
     */
    public function getDb(): Connection
    {
        return $this->modelClass::getDb();
    }
}
