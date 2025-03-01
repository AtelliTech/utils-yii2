<?php

namespace AtelliTech\Yii2\Utils;

/**
 * This trait is used to access custom error.
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
trait CustomErrorTrait
{
    /**
     * @var CustomError
     */
    private $customError;

    /**
     * get custom error.
     *
     * @return CustomError
     */
    public function getCustomError(): CustomError
    {
        return $this->customError;
    }

    /**
     * set custom error.
     *
     * @param string $message
     * @param int $code
     * @param array<int, array<string, mixed>> $details
     * @return void
     */
    protected function setCustomError(string $message, int $code, array $details = []): void
    {
        $this->customError = new CustomError($message, $code, $details);
    }
}
