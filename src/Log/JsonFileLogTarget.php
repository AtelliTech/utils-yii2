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
            if ($text instanceof \Exception || $text instanceof \Throwable) {
                $errMsg = $text->getMessage();
                if (!isset($message[4]) || empty($message[4])) {
                    $message[4] = $text->getTrace();
                }
            } else {
                $errMsg = VarDumper::export($text);
            }
        } else {
            $errMsg = $text;
        }

        $traces = [];
        if (isset($message[4]) && !empty($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }

        $prefix = $this->getMessagePrefix($message);
        if (preg_match('/^\[(.*)\]\[(.*)\]\[(.*)\]$/', $prefix, $matches)) {
            $ip = $matches[1];
            $userID = $matches[2];
            $sessionID = $matches[3];
        } else {
            $ip = '-';
            $userID = '-';
            $sessionID = '-';
        }

        return Json::encode([
                'time' => $this->getTime($timestamp),
                'ip' => $ip,
                'userID' => $userID,
                'sessionID' => $sessionID,
                'level' => $level,
                'category' => $category,
                'text' => $errMsg,
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