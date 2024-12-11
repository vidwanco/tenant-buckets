<?php

namespace Vidwan\TenantBuckets\Exceptions;

use Aws\Exception\AwsException;
use Exception;
use Throwable;

abstract class BaseException extends Exception
{
    protected array $data;

    protected AwsException $awsException;

    public function __construct(string $message = "", int $code = 0, Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getAwsException(): AwsException
    {
        return $this->awsException;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
