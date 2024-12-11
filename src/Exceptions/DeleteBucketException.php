<?php

namespace Vidwan\TenantBuckets\Exceptions;

use Aws\Exception\AwsException;
use Stancl\Tenancy\Contracts\Tenant;

class DeleteBucketException extends BaseException
{

    public function __construct(
        protected Tenant $tenant,
        protected string $bucketName,
        protected AwsException $awsException
    )
    {
        $message = "[tenant-buckets] Error: (Tenant ID: {$tenant->id}) {$awsException->getAwsErrorMessage()}";
        parent::__construct($message,$awsException->getCode(), $awsException);

        $this->setData();
    }

    private function setData(): static
    {
        $this->data = [
            'tenant' => $this->tenant->id,
            'bucket' => $this->bucketName,
            'error_code' => $this->awsException->getAwsErrorCode(),
            'error_type' => $this->awsException->getAwsErrorType(),
            'error_message' => $this->awsException->getAwsErrorMessage(),
            'response' => $this->awsException->getResponse(),
        ];
        return $this;
    }

}
