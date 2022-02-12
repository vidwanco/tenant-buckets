<?php

namespace Vidwan\TenantBuckets;

use Aws\Credentials\Credentials;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Vidwan\TenantBuckets\Events\CreatedBucket;
use Vidwan\TenantBuckets\Events\CreatingBucket;

class Bucket
{
    /**
     * @access public
     * @var Current Tenant
     * @var AWS Credentials Object
     * @var AWS/Minio Endpoint
     * @var AWS/Minio Region
     * @var Use Path style endpoint (used for minio)
     * @access protected
     * @var string|null Name of the Created Bucket
     * @var Aws\Exception\AwsException|null Exception Error Bag
     */
    public $tenant;
    public $credentials;
    public $endpoint;
    public $region;
    public string $version = "2006-03-01";
    public bool $pathStyle = false;
    protected string|null $createdBucketName;
    protected AwsException|null $e;

    /**
     * Setup the Bucket Object
     *
     * @access public
     * @param Stancl\Tenancy\Contracts\TenantWithDatabase $tenant Current Teanant
     * @param Aws\Credentials\Credentials $credentials Aws Credentials Object
     * @param string $endpoint Aws/Minio Endpoint
     * @param bool $pathStyle Use Path Style Endpoint (set `true` for minio, default: false)
     * @return void
     */
    public function __construct(
        TenantWithDatabase $tenant,
        ?Credentials $credentials = null,
        ?string $region = null,
        ?string $endpoint = null,
        ?bool $pathStyle = null
    )
    {
        $this->tenant = $tenant;
        $this->credentials = $credentials ?? new Credentials(
            config('filesystems.disks.s3.key'),
            config('filesystems.disks.s3.secret')
        );
        $this->region = $region ?? config('filesystems.disks.s3.region');
        $this->endpoint = $endpoint ?? config('filesystems.disks.s3.endpoint');
        $pathStyle = $pathStyle ?? config('filesystems.disks.s3.use_path_style_endpoint');
        $this->pathStyle = $pathStyle ?? $this->pathStyle;
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
