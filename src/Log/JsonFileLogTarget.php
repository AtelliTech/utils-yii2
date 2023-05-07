<?php

namespace AtelliTech\Yii2\Utils\Log;

use Exception;
use Throwable;
use Yii;
use yii\web\Request;
use yii\helpers\VarDumper;
use yii\log\FileTarget;
use yii\log\Logger;

/**
 * This log target extends yii\log\FileTarget and modify its formatMessage method.
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
class JsonFileLogTarget extends FileTarget
{
    /**
     * Formats a log message for display as a string.
     * @param array<int, mixed> $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof Exception || $text instanceof Throwable) {
                $text = (string) $text;
            } else {
                $text = VarDumper::export($text);
            }
        }

        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }

        $stacks = [];
        if (strpos($text, 'Stack trace:')!=false) {
            $splits = explode('Stack trace:', $text);
            $text = $splits[0] ?? $text;
            $text = trim($text, "\n");
            $stacks = isset($splits[1]) ? (explode("\n", trim($splits[1], "\n"))) : [];
        }

        $userID = '-';
        $ip = '-';
        $sessionID = '-';
        if (Yii::$app !== null) {
            $request = Yii::$app->getRequest();
            $ip = $request instanceof Request ? $request->getUserIP() : '-';

            /* @var $user \yii\web\User */
            $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
            if ($user && ($identity = $user->getIdentity(false))) {
                $userID = $identity->getId();
            } else {
                $userID = '-';
            }

            /* @var $session \yii\web\Session */
            $session = Yii::$app->has('session', true) ? Yii::$app->get('session') : null;
            $sessionID = $session && $session->getIsActive() ? $session->getId() : '-';
        }

        return json_encode([
                'time' => $this->getTime($timestamp),
                'ip' => $ip,
                'userID' => $userID,
                'sessionID' => $sessionID,
                'level' => $level,
                'category' => $category,
                'text' => $text,
                'traces' => $traces,
                'stacks' => $stacks
            ]);
    }
}