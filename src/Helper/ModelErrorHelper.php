<?php

namespace AtelliTech\Yii2\Helper;

use yii\base\Model;

/**
 * This helper is used handle model error
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
class ModelErrorHelper
{
    /**
     * convert first error to message
     *
     * @param Model $model
     * @return string
     */
    public static function firstToMessage(Model $model): string
    {
        $errors = $model->getFirstErrors();
        $message = '';
        foreach($errors as $name=>$error) {
            $message = sprintf('The attribute(%s) has error(%s)', $name, $error);
        }

        return $message;
    }

    /**
     * convert all errors to message
     *
     * @param Model $model
     * @return array<int, string>
     */
    public static function allToMessage(Model $model): array
    {
        $errors = $model->getErrors();
        $messages = [];
        foreach($errors as $name=>$error) {
            $messages[] = sprintf('The attribute(%s) has error(%s)', $name, $error);
        }

        return $messages;
    }
}
