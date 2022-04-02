<?php

namespace Vidwan\TenantBuckets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Vidwan\TenantBuckets\Bucket;

class CreateTenantBucket implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Current Tenant
     * @access protected
     */
    protected $tenant;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(TenantWithDatabase $tenant)
    {
        //
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bucket = new Bucket($this->tenant);
        $create = $bucket->createTenantBucket();

        // $this->tenant->tenant_bucket = $create->getBucketName();
        // $this->tenant->save();
    }

    /**
    * Get the tags that should be assigned to the job.
    *
    * @return  array
    */
    public function tags()
    {
        return [
            'tenant:' . $this->tenant->id,
        ];
    }
}
