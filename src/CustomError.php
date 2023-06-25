<?php

namespace AtelliTech\Yii2\Utils;

/**
 * It's a custom error class.
 *
 * @author Eric Huang <eric.huang@atelli.ai>
 */
class CustomError
{
	/**
	 * construct
	 *
	 * @param string $message
	 * @param int $code
	 * @param array<string, mixed> $details
	 * @return void
	 */
	public function __construct(
		private string $message,
		private int $code,
		private array $details = []
	) {}

	/**
	 * Get the value of message
	 *
	 * @return string
	 */
	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * Get the value of code
	 *
	 * @return int
	 */
	public function getCode(): int
	{
		return $this->code;
	}

	/**
	 * Get the value of details
	 *
	 * @return array<string, mixed>
	 */
	public function getDetails(): array
	{
		return $this->details;
	}

	/**
	 * Set the value of message
	 *
	 * @param string $message
	 * @return self
	 */
	public function setMessage(string $message): self
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * Set the value of code
	 *
	 * @param int $code
	 * @return self
	 */
	public function setCode(int $code): self
	{
		$this->code = $code;

		return $this;
	}

	/**
	 * Set the value of details
	 *
	 * @param array<string, mixed> $details
	 * @return self
	 */
	public function setDetails(array $details): self
	{
		$this->details = $details;

		return $this;
	}

	/**
	 * Convert to array
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'message' => $this->message,
			'code' => $this->code,
			'details' => $this->details
		];
	}

	/**
	 * Convert to json
	 *
	 * @return string
	 */
	public function toJson(): string
	{
		return json_encode($this->toArray());
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString(): string
	{
		return $this->toJson();
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function toString(): string
	{
		return sprintf('%s #%d', $this->message, $this->code);
	}
}