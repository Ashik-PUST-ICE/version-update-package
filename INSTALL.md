# Install Ashik Version Updater

## GitHub repository

Upload this folder as a separate repository named `ashik-version-updater`.
The repository root must contain `composer.json`, `src`, `config`, `routes`,
and `resources`.

## Install command

Until the package is published on Packagist, install it from GitHub:

```bash
composer config repositories.ashik-version-updater vcs https://github.com/YOUR_USERNAME/ashik-version-updater
composer require ashik/version-updater:dev-main
```

Then publish the configuration:

```bash
php artisan vendor:publish --tag=ashik-version-updater-config
php artisan optimize:clear
```

The default page is:

```text
/erp/super-admin/version-update
```

The own installer page is:

```text
/ashik-install
```

It checks the environment, runs database migrations, creates the storage link,
and writes both `ashik-installed` and the compatible `installed` marker in the storage directory.

The package protects its own version-update routes with an install check. To
protect every host-application route, also add the package middleware to the
host application's main web route group.

If the host application does not use the `super-admin` middleware alias, set a
different middleware list in `config/version-updater.php`.
