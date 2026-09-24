# Ashik Version Updater

Reusable local ZIP version updater for Laravel applications.

## Install in another Laravel project

This folder is a standalone Git package. Upload the contents of this folder as
its own repository, for example `ashik/version-updater`.

### Upload this package to GitHub

From the Laravel project root:

```bash
git subtree split --prefix=packages/ashik/version-updater -b ashik-version-updater
git push https://github.com/YOUR_USERNAME/ashik-version-updater.git ashik-version-updater:main
```

Or create a new repository, copy the contents of this folder into it, and run
`git init`, `git add .`, `git commit`, and `git push` there.

### Install directly from GitHub

Replace the URL with your repository URL:

```bash
composer config repositories.ashik-version-updater vcs https://github.com/YOUR_USERNAME/ashik-version-updater
composer require ashik/version-updater:dev-main
php artisan vendor:publish --tag=ashik-version-updater-config
php artisan optimize:clear
```

After the package is published, the shorter command works:

```bash
composer require ashik/version-updater
```

```bash
php artisan vendor:publish --tag=ashik-version-updater-config
composer dump-autoload
```

Register the `super-admin` middleware alias in the host application, or change
`version-updater.middleware` in `config/version-updater.php` to match the host
application's authentication middleware.

Set the target release in the host `.env`:

```env
ASHIK_VERSION_UPDATER_NAME="Ashik Version Update"
ASHIK_BUILD_VERSION=2
ASHIK_CURRENT_VERSION=1.1.0
```

The updater route is `/erp/super-admin/version-update` by default.
