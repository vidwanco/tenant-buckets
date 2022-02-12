<?php

namespace Vidwan\TenantBuckets\Bootstrappers;

use Illuminate\Contracts\Foundation\Application;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class TenantBucketBootstrapper implements TenancyBootstrapper
{
    /**
     * Application Interface
     * @var Application
     */
    protected $app;

    /**
     * Orignal Bucket Name
     * @var string
     */
    protected string|null $orignalBucket;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->orignalBucket = $this->app['config']['filesystems.disks.s3.bucket'];
    }

    public function bootstrap(Tenant $tenant)
    {
        // Select Bucket Name
        $bucket = 'tenant'.$tenant->getTenantKey();
        $bucket = $tenant->tenant_bucket ?? $bucket;
        $this->app['config']['filesystems.disks.s3.bucket'] = $bucket;
    }

    public function revert()
    {
        //
        $this->app['config']['filesystems.disks.s3.bucket'] = $this->orignalBucket;
    }
}
