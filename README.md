# gsandbox

[![Build Status](https://travis-ci.org/sebcode/gsandbox.svg?branch=master)](https://travis-ci.org/sebcode/gsandbox)

Sandbox for Amazon Glacier written in PHP. Useful to mock Amazon Glacier API in unit tests.

## Coverage

### [Vault Operations](http://docs.aws.amazon.com/amazonglacier/latest/dev/vault-operations.html)

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Create vault | `PUT /-/vaults/vault-name` |
| ✓ | List vaults | `GET /-/vaults` |
| ✓ | Delete vault | `DELETE /-/vaults/vault-name` |
| ✓ | Describe vault | `GET /-/vaults/vault-name` |
| ✓ | Add/remove tags | `POST /-/vaults/vault-name/tags` |
| ✓ | List tags | `GET /-/vaults/vault-name/tags` |

### [Job Operations](http://docs.aws.amazon.com/amazonglacier/latest/dev/job-operations.html)

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Initiate job | `POST /-/vaults/vault-name/jobs` |
| ✓ | List jobs | `GET /-/vaults/vault-name/jobs` |
| ✓ | Describe job | `GET /-/vaults/vault-name/job/job-id` |
| ✓ | Get job output | `GET /-/vaults/vault-name/jobs/job-id/output` |

### [Archive Operations](http://docs.aws.amazon.com/amazonglacier/latest/dev/archive-operations.html)

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Upload Archive | `POST /-/vaults/vault-name/archives` |
| ✓ | Delete Archive | `DELETE /-/vaults/vault-name/archives/archive-id` |

### [Multipart Upload Operations](http://docs.aws.amazon.com/amazonglacier/latest/dev/multipart-archive-operations.html)

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Initiate multipart upload | `POST /-/vaults/vault-name/multipart-uploads` |
| ✓ | List multipart uploads | `GET /-/vaults/vault-name/multipart-uploads` |
| ✓ | Upload multipart part | `PUT /-/vaults/vault-name/multipart-uploads/id` |
| ✓ | List multipart upload parts | `GET /-/vaults/vault-name/multipart-uploads/id` |
| ✓ | Finalize multipart upload | `POST /-/vaults/vault-name/multipart-uploads/id` |
| ✓ | Abort multipart upload | `DELETE /-/vaults/vault-name/multipart-uploads/id` |

### [Data Retrieval Policy Operations](http://docs.aws.amazon.com/amazonglacier/latest/dev/data-retrieval-policy-operations.html)

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Get Data Retrieval Policy | `GET /-/policies/data-retrieval` |
| ✓ | Set Data Retrieval Policy | `PUT /-/policies/data-retrieval` |

### Limitations

 * Request signature checking is not implemented.
 * Error responses are not properly implemented yet.

### Notes

 * Call `GET /sandbox/reset/ACCESSKEY` to delete all data for the specified account.

## Requirements

 * Web server with PHP 7
 * [PHP Composer](https://getcomposer.org/)
 * [PHP Codeception](http://codeception.com/) for Tests

## Installation

 * Checkout repository to `/var/www/gsandbox` for example.
 * Call `composer install` to install dependencies.
 * `cp config.sample.php config.php`.
 * Edit `config.php` and change `storePath` to the path where data should be
   stored (e.g. `/var/gsandboxstore/`).
 * Make `storePath` readable and writeable for web server.
 * Each directory in `storePath` represents a fake Amazon AWS Access Key you
   will be using to connect to the test server. Create that directory and make
   it readable and writeable for the web server.
 * Add `127.0.0.1 gsandbox.localhost` to `/etc/hosts`

### Using PHP's Builtin Webserver

 * Run `php -S 127.0.0.1:8080 htdocs/index.php`

### Using Apache

 * Example apache virtual host:

        <VirtualHost *:8080>
          ServerName gsandbox.localhost
          DocumentRoot /var/www/gsandbox/htdocs

          <Directory /var/www/gsandbox/htdocs>
            RewriteEngine On
            RewriteBase /
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule . index.php [L]
            SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

            DirectoryIndex index.php
            AllowOverride All
            Order allow,deny
            Allow from all
          </Directory>
        </VirtualHost>

## Run Tests

 * Make sure that `$storePath/UNITTEST/vaults` exists.
 * Make sure that gsandbox is accessible via `http://gsandbox.localhost:8080/`.
 * Run `vendor/bin/codecept run`.

## Credits

Sebastian Volland - http://github.com/sebcode
