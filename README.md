# Provision S3 Buckets for each tenant.
![Vidwan/Tenant-Buckets](https://banners.beyondco.de/Tenant%20Buckets.png?theme=dark&packageManager=composer+require&packageName=vidwan%2Ftenant-buckets&pattern=circuitBoard&style=style_1&description=Provision+S3+Buckets+for+tenants.&md=1&showWatermark=0&fontSize=100px&images=collection)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/vidwanco/tenant-buckets.svg?style=flat-square)](https://packagist.org/packages/vidwanco/tenant-buckets)
[![Tests](https://github.com/vidwanco/tenant-buckets/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/vidwan-co/tenant-buckets/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/vidwan-co/tenant-buckets.svg?style=flat-square)](https://packagist.org/packages/vidwan-co/tenant-buckets)

Automatically Provision AWS S3 Buckets for each tenant. It's an Extention for [TenancyForLaravel](https://tenancyforlaravel.com/). Refer to [TenancyForLaravel](https://tenancyforlaravel.com/) for more details.

## Concept

The concept is simple. It is to automatically provison a new AWS S3 bucket for tenant on registration and update the same on the central database's tenant table & data coloumn under `tenant_bucket`.
Then using a bootstrapper updating the bucket in config `filesystems.disks.s3.bucket` during runtime when in Tenant's context and then reverting it back on central context.

## 🚧 Work In Progress 🚧

This is still a **work in progress** and may not be usable. Please use at your own risks.
> Collaborations are always helpful 😃

### Roadmap

- [x] Automatic Bucket Creation
- [x] Selecting the created bucket during Tenancy Bootstrapping.

> **Note:** I have still not tested this package under ***production*** environment or with a real AWS S3 Bucket. I have only tested it under ***development*** environment using [MinIO](https://min.io/). I will update this after testing it on AWS S3 Bucket with an additional section on AWS IAM Policy Setup for creating the buckets using `aws-sdk-php`. Untill then, if you have tested, a PR is welcome.

## Installation

You can install the package via composer:

```bash
composer require vidwan/tenant-buckets
```

## Usage

### 1. Filesystem Config Setup

Ensure your S3 configuration has all the Key/Value pairs, as below:

**File:** `config/filesystems.php` 
```php
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],

```
> Using Minio for development? Make sure to update your `.env` with `AWS_USE_PATH_STYLE_ENDPOINT=true`

### 2. Tenancy Config

There are two parts in tenancy config to take care of.
#### Part **a**.

Add the `TenantBucketBootstrapper::class` to the tenancy config file under `bootstrappers`.

```php
Vidwan\TenantBuckets\Bootstrappers\TenantBucketBootstrapper::class
```

**File:** `config/tenancy.php`
```php
    'bootstrappers' => [
        // Tenancy Bootstrappers
		Vidwan\TenantBuckets\Bootstrappers\TenantBucketBootstrapper::class,
    ],
```

#### Part **b**.

Make sure the `s3` is commented in `tenancy.filesystem.disks` config.

**File:** `config/tenancy.php`
```php
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3', // Make sure this stays commented
        ],
    ],
```

Cheers! 🥳
## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Shashwat Mishra](https://github.com/secrethash)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
