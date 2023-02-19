<?php

namespace AtelliTech\Yii2\Utils;

/**
 * This trait is used to represent an error
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
trait ErrorTrait
{
    /**
     * @var array<string, mixed> $errorValues
     */
    private $errorValues = ['code'=>0, 'message'=>'', 'details'=>[]];

    /**
     * set error
     *
     * @param int $code
     * @param string $message
     * @param array $details
     */
    protected function setError(int $code, string $message, array $details = [])
    {
        $this->errorValues['code'] = $code;
        $this->errorValues['message'] = $message;
        $this->errorValues['details'] = $details;
    }

    /**
     * set error code
     *
     * @param int $code
     * @return void
     */
    public function setErrorCode(int $code): void
    {
        $this->errorValues['code'] = $code;
    }

    /**
     * set error message
     *
     * @param int $message
     * @return void
     */
    public function setErrorMessage(int $message): void
    {
        $this->errorValues['message'] = $message;
    }

    /**
     * set error details
     *
     * @param array<int, mixed> $details
     * @return void
     */
    public function setErrorDetails(array $details): void
    {
        $this->errorValues['details'] = $details;
    }

    /**
     * get error code
     *
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorValues['code'];
    }

    /**
     * get error message
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorValues['message'];
    }

    /**
     * get error details
     *
     * @return array<int, mixed>
     */
    public function getErrorDetails(): array
    {
        return $this->errorValues['details'];
    }

    /**
     * get error values
     *
     * @return array<string, mixed>
     */
    public function getErrorValues(): array
    {
        return $this->errorValues;
    }
}