# gsandbox

Sandbox for Amazon Glacier written in PHP. Useful to mock Amazon Glacier API in unit tests.

This is still work in progress.

## Coverage

### Vault operations

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Create vault | `PUT /-/vaults/vault-name` |
| ✓ | List vaults | `GET /-/vaults` |
| ✓ | Delete vault | `DELETE /-/vaults/vault-name` |
| ✓ | Describe vault | `GET /-/vaults/vault-name` |

### Job operations

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Initiate job | `POST /-/vaults/vault-name/jobs` |
| ✓ | List jobs | `GET /-/vaults/vault-name/jobs` |
| ✓ | Describe job | `GET /-/vaults/vault-name/job/job-id` |
| ✓ | Get job output | `GET /-/vaults/vault-name/jobs/job-id/output` |

### Archive operations

|  | Action | HTTP request |
| --- | --- | --- |
| - | Upload Archive | `POST /-/vaults/vault-name/archives` |
| ✓ | Delete Archive | `DELETE /-/vaults/vault-name/archives/archive-id` |

### Multipart upload operations

|  | Action | HTTP request |
| --- | --- | --- |
| ✓ | Initiate multipart upload | `POST /-/vaults/vault-name/multipart-uploads` |
| ✓ | List multipart uploads | `GET /-/vaults/vault-name/multipart-uploads` |
| ✓ | Upload multipart part | `PUT /-/vaults/vault-name/multipart-uploads/id` |
| ✓ | List multipart upload parts | `GET /-/vaults/vault-name/multipart-uploads/id` |
| ✓ | Finalize multipart upload | `POST /-/vaults/vault-name/multipart-uploads/id` |
| ✓ | Abort multipart upload | `DELETE /-/vaults/vault-name/multipart-uploads/id` |

### Limitations

 * Request signature checking is not implemented.
 * Error responses are not properly implemented yet.

### Notes

 * Call `GET /sandbox/reset/ACCESSKEY` to delete all data for the specified account.

## Requirements

 * Web server with PHP 5.6+

## Installation

 * Checkout repository to `/var/www/gsandbox` for example.
 * Call `composer install` to install dependencies.
 * `cp config.sample.php config.php`.
 * Edit `config.php` and change `storePath` to the path where data should be stored (e.g. `/var/gsandboxstore/`).
 * Make `storePath` readable and writeable for web server.
 * Each directory in `storePath` represents a fake Amazon AWS Access Key you will be using to connect to the test server. Create that directory and make it readable and writeable for the web server.
 * Configure virtual host for web server. Example for apache:

        <VirtualHost *:80>
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

## Credits

Sebastian Volland - http://github.com/sebcode
