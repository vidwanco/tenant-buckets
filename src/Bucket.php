<?php

namespace Vidwan\TenantBuckets;

use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Stancl\Tenancy\Contracts\Tenant;
use Vidwan\TenantBuckets\Events\CreatedBucket;
use Vidwan\TenantBuckets\Events\CreatingBucket;
use Vidwan\TenantBuckets\Events\DeletedBucket;
use Vidwan\TenantBuckets\Events\DeletingBucket;
use Vidwan\TenantBuckets\Exceptions\CreateBucketException;
use Vidwan\TenantBuckets\Exceptions\DeleteBucketException;

class Bucket
{
    /**
     * @var \AWS\Credentials\Credentials Credentials Object
    */
    protected $credentials;

    /**
     * @var string AWS/Minio Endpoint
    */
    protected $endpoint;

    /**
     * @var string AWS/Minio Region
    */
    protected $region;

    protected string $version = "2006-03-01";

    /**
     * @var bool Use Path style endpoint (used for minio)
    */
    protected bool $pathStyle = false;

    /**
     * @var string|null Name of the Created Bucket
    */
    protected string|null $bucketName;

    public function __construct(
        protected Tenant $tenant
    ) {
        $this->setupCredentials();
    }

    private function setupCredentials()
    {
        $this->credentials = new Credentials(
            config('filesystems.disks.s3.key'),
            config('filesystems.disks.s3.secret')
        );
        $this->region = config('filesystems.disks.s3.region');
        $this->endpoint = config('filesystems.disks.s3.endpoint');
        $this->pathStyle = config('filesystems.disks.s3.use_path_style_endpoint', false);
    }

    /**
     * Create Tenant Specific Bucket
     *
     * @return self $this
     */
    public function createTenantBucket(): self
    {
        $bucketName = config('tenancy.filesystem.suffix_base').$this->tenant->getTenantKey();

        return $this->createBucket($bucketName, $this->credentials);
    }

    /**
     * Delete Tenant Specific Bucket
     *
     * @return self $this
     */
    public function deleteTenantBucket(): self
    {
        $bucketName = $this->tenant->tenant_bucket;

        return $bucketName ? $this->deleteBucket($bucketName, $this->credentials) : $this;
    }

    /**
     * Create a New Bucket
     *
     * @param string $name Name of the S3 Bucket
     * @param \Aws\Credentials\Credentials $credentials AWS Credentials Object
     * @return self $this
     */
    public function createBucket(string $name, Credentials $credentials): self
    {
        event(new CreatingBucket($this->tenant));

        $params = [
            "credentials" => $credentials,
            "endpoint" => $this->endpoint,
            "region" => $this->region,
            "version" => $this->version,
            "use_path_style_endpoint" => $this->pathStyle,
        ];
        $this->bucketName = $this->formatBucketName($name);

        $client = new S3Client($params);

        try {
            $client->createBucket([
                'Bucket' => $this->bucketName,
            ]);

            // Update Tenant
            $this->tenant->tenant_bucket = $this->bucketName;
            $this->tenant->save();
        } catch (AwsException $e) {
            // We catch to set the error bag
            throw new CreateBucketException($this->tenant, $this->bucketName, $e);
        }

        event(new CreatedBucket($this->tenant));

        return $this;
    }

    /**
     * Create a New Bucket
     *
     * @param string $name Name of the S3 Bucket
     * @param \Aws\Credentials\Credentials $credentials AWS Credentials Object
     * @return self $this
     */
    public function deleteBucket(string $name, Credentials $credentials): self
    {
        event(new DeletingBucket(tenant: $this->tenant));

        $params = [
            "credentials" => $credentials,
            "endpoint" => $this->endpoint,
            "region" => $this->region,
            "version" => $this->version,
            "use_path_style_endpoint" => $this->pathStyle,
        ];

        $client = new S3Client($params);

        try {
            $client->deleteBucket([
                'Bucket' => $name,
            ]);
        } catch (AwsException $e) {
            // We catch to set the error bag
            throw new DeleteBucketException($this->tenant, $name, $e);
        }

        event(new DeletedBucket($this->tenant));

        return $this;
    }

    /**
     * Get Created Bucket Name
     *
     * @return string
     */
    public function getBucketName(): string|null
    {
        return $this->bucketName;
    }

    /**
     * Format Bucket Name according to AWS S3 Bucket Naming Rules
     * @see https://docs.aws.amazon.com/AmazonS3/latest/userguide/bucketnamingrules.html
     * @param string $name
     */
    protected function formatBucketName(string $name): string
    {
        return str(preg_replace('/[^a-zA-Z0-9]/', '', $name))->lower()->toString();
    }
}
