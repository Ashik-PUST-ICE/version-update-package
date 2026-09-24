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

## Purchase codes

The installer requires a purchase code by default. Set your private owner code
in the seller application `.env`:

```env
ASHIK_REQUIRE_PURCHASE_CODE=true
ASHIK_MASTER_PURCHASE_CODE=ASHIK-OWNER-CHANGE-ME
```

For each customer, run this after the package migration has run:

```bash
php artisan ashik:purchase-code "Customer name" "customer@example.com"
```

Give the printed code only to that customer. The code is saved in the
`ashik_purchase_codes` table and becomes used for the customer domain after
installation. The installation itself is recorded in `ashik_installations`.

For a fresh customer project where codes are issued from your seller system,
you may also pass a comma-separated code list through `ASHIK_PURCHASE_CODES`.
Do not commit the master code or customer codes to a public repository.

The updater route is `/erp/super-admin/version-update` by default.
