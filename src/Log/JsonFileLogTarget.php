<?php

namespace AtelliTech\Yii2\Utils\Log;

use Exception;
use Throwable;
use Yii;
use yii\web\Request;
use yii\helpers\ArrayHelper as Arr;
use yii\helpers\VarDumper;
use yii\log\FileTarget;
use yii\log\Logger;
use yii\helpers\Json;
use yii\base\InvalidConfigException;
use yii\log\LogRuntimeException;
use yii\helpers\FileHelper;


/**
 * This log target extends yii\log\FileTarget and modify its formatMessage method.
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
class JsonFileLogTarget extends FileTarget
{
    /**
     * @var string[]
     */
    protected $defaultMaskVars = ['_SERVER.LS_COLORS', '_SERVER.SHELL', '_SERVER.PWD', '_SERVER.LOGNAME', '_SERVER.XDG_SESSION_TYPE', '_SERVER.MOTD_SHOWN', '_SERVER.HOME', '_SERVER.LANG', '_SERVER.LS_COLORS', '_SERVER.SSH_CONNECTION', '_SERVER.LESSCLOSE', '_SERVER.XDG_SESSION_CLASS', '_SERVER.TERM', '_SERVER.LESSOPEN', '_SERVER.USER', '_SERVER.DISPLAY', '_SERVER.SHLVL', '_SERVER.XDG_SESSION_ID', '_SERVER.XDG_RUNTIME_DIR', '_SERVER.SSH_CLIENT', '_SERVER.XDG_DATA_DIRS', '_SERVER.PATH', '_SERVER.DBUS_SESSION_BUS_ADDRESS', '_SERVER.SSH_TTY', '_SERVER.OLDPWD', '_SERVER._', '_SERVER.PHP_SELF', '_SERVER.SCRIPT_NAME', '_SERVER.SCRIPT_FILENAME', '_SERVER.PATH_TRANSLATED', '_SERVER.DOCUMENT_ROOT'];


    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        if (empty($this->maskVars))
            $this->maskVars = $this->defaultMaskVars;
    }

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

        return Json::encode([
                'time' => $this->getTime($timestamp),
                'ip' => $ip,
                'userID' => $userID,
                'sessionID' => $sessionID,
                'level' => $level,
                'category' => $category,
                'text' => $text,
                'traces' => $traces
            ]);
    }

    /**
     * Writes log messages to a file.
     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
     * @throws InvalidConfigException if unable to open the log file for writing
     * @throws LogRuntimeException if unable to write complete log to file
     * @return void
     */
    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";

        if (trim($text) === '') {
            $text = "\n"; // No messages to export, so we exit the function early
        }

        if (strpos($this->logFile, '://') === false || strncmp($this->logFile, 'file://', 7) === 0) {
            $logPath = dirname($this->logFile);
            FileHelper::createDirectory($logPath, $this->dirMode, true);
        }

        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
        }
        $writeResult = @fwrite($fp, $text);
        if ($writeResult === false) {
            $error = error_get_last();
            throw new LogRuntimeException("Unable to export log through file ({$this->logFile})!: {$error['message']}");
        }
        $textSize = strlen($text);
        if ($writeResult < $textSize) {
            throw new LogRuntimeException("Unable to export whole log through file ({$this->logFile})! Wrote $writeResult out of $textSize bytes.");
        }
        @fflush($fp);
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }
}