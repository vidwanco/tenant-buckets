<?php

namespace Vidwan\TenantBuckets;

use Aws\Credentials\Credentials;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Vidwan\TenantBuckets\Events\CreatedBucket;
use Vidwan\TenantBuckets\Events\CreatingBucket;
use Vidwan\TenantBuckets\Events\DeletedBucket;
use Vidwan\TenantBuckets\Events\DeletingBucket;

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
    protected string|null $createdBucketName;

    /**
     * @var \Aws\Exception\AwsException|null Exception Error Bag
    */
    protected AwsException|null $e;

    public function __construct(
        protected TenantWithDatabase $tenant
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
     * @access public
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
     * @access public
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
     * @param Aws\Credentials\Credentials $credentials AWS Credentials Object
     * @access public
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

        $client = new S3Client($params);

        try {
            $exec = $client->createBucket([
                'Bucket' => $name,
            ]);
            $this->createdBucketName = $name;

            // Update Tenant
            $this->tenant->tenant_bucket = $name;
            $this->tenant->save();
        } catch (AwsException $e) {
            $this->e = $e;
            Log::error($this->getErrorMessage());
        }

        event(new CreatedBucket($this->tenant));

        return $this;
    }

    /**
     * Create a New Bucket
     *
     * @param string $name Name of the S3 Bucket
     * @param Aws\Credentials\Credentials $credentials AWS Credentials Object
     * @access public
     * @return self $this
     */
    public function deleteBucket(string $name, Credentials $credentials): self
    {
        event(new DeletingBucket($this->tenant));

        $params = [
            "credentials" => $credentials,
            "endpoint" => $this->endpoint,
            "region" => $this->region,
            "version" => $this->version,
            "use_path_style_endpoint" => $this->pathStyle,
        ];

        $client = new S3Client($params);

        try {
            $exec = $client->deleteBucket([
                'Bucket' => $name,
            ]);
        } catch (AwsException $e) {
            $this->e = $e;
            if (config('tenant-buckets.errors.throw', true))
                throw $e;
            Log::error($this->getErrorMessage());
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
        return $this->createdBucketName;
    }

    /**
     * Get Error Messsge
     *
     * @return string|null
     */
    public function getErrorMessage(): string|null
    {
        return ($this->e) ?
        "Error: " . $this->e->getAwsErrorMessage() :
        null;
    }

    /**
     * Get Error Bag
     *
     * @return AwsException|null
     */
    public function getErrorBag(): AwsException|null
    {
        return $this->e ? $this->e : null;
    }
}
