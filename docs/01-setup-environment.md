# XenForo Development Environment Setup

## System Requirements

XenForo requires the following minimum specifications:

- **PHP**: 7.2 minimum (8.4 recommended)
- **MySQL**: 5.7+ (MariaDB and Percona are also compatible)
- **Required PHP extensions**: MySQLi, GD (with JPEG support), PCRE, cURL, SPL, SimpleXML, DOM, JSON, iconv, ctype

Download the official requirements test script to verify your server: https://xenforo.com/purchase/requirements-zip

---

## Installing XenForo Locally

### Option 1: Laragon (Windows — Recommended)

[Laragon](https://laragon.org) is the recommended local stack for Windows. It includes Apache, PHP, and MySQL pre-configured.

1. Download and install [Laragon Lite](https://laragon.org/download/)
2. Right-click the gear icon > Tools > Path > **Add Laragon to Path**
3. Open a new command prompt and verify: `php -v`

#### Install Xdebug on Windows

1. Run `php -i > Desktop\info.txt` to collect your PHP info
2. Visit https://xdebug.org/wizard, paste the file contents, and follow the installation instructions
3. Edit `php.ini` (Laragon gear icon > PHP > php.ini) and append:

```ini
[xdebug]
xdebug.remote_enable = 1
xdebug.remote_autostart = 1
zend_extension = C:\laragon\bin\php\php-X.X.XX-...\ext\php_xdebug-X.X.X-X.X-vcXX-x86_64.dll
```

### Option 2: Linux/macOS Manual Stack

Install Apache (or nginx), MySQL/MariaDB, and PHP manually:

```bash
# Ubuntu/Debian
sudo apt install apache2 mysql-server php php-mysqli php-gd php-curl php-xml php-mbstring php-json

# macOS with Homebrew
brew install php mysql httpd
```

### Option 3: Pre-built Stacks

- **LAMP** (Linux): https://bitnami.com/stack/lamp
- **MAMP** (macOS): https://bitnami.com/stack/mamp
- **WAMP** (Windows): https://bitnami.com/stack/wamp

All include Apache, MySQL/MariaDB, PHP, and phpMyAdmin.

---

## Uploading XenForo Files

1. Download XenForo from the [Customer Area](https://xenforo.com/customers)
2. Extract the ZIP file — you will see an `upload/` directory
3. Copy all contents of `upload/` to your web root (`public_html`, `htdocs`, or `www`)

---

## Creating `src/config.php`

Create `src/config.php` with your database credentials:

```php
<?php

$config['db']['host'] = 'localhost';
$config['db']['port'] = '3306';
$config['db']['username'] = 'root';
$config['db']['password'] = 'mypassword';
$config['db']['dbname'] = 'xenforo';
```

> The config file must be in `src/`, not `library/` (which is legacy XF1).

---

## Installing via CLI

```bash
php cmd.php xf:install
```

You will be prompted for:
- Administrator username and password
- Board title

For a clean reinstall (drops all `xf_` tables):

```bash
php cmd.php xf:install --clear
```

---

## File Permissions

XenForo writes to `data/` and `internal_data/` at runtime. These must be writable by the web server user.

Options to handle permissions:

1. **Run CLI as the web server user** — switch to `www-data` or `apache` before running CLI commands
2. **ACLs** — apply filesystem ACLs so both the CLI user and web server user have write access
3. **Force chmod in config.php**:

```php
$config['chmodWritableValue'] = 0666;
```

For add-on development, the `_output` directory also needs to be writable:

```bash
chmod 0777 src/addons/YourAddon/_output
```

---

## Debug Mode

Debug mode enables the development tools in Admin CP and shows query/timing info at the bottom of every page.

```php
// src/config.php
$config['debug'] = true;
```

In debug mode, hovering over the timing bar shows the current controller, action, and template name. Clicking it shows all executed queries and stack traces.

---

## Development Mode

Development mode auto-writes development output files to `_output/` directories and enables filesystem template editing.

```php
// src/config.php
$config['development']['enabled'] = true;
$config['development']['defaultAddOn'] = 'YourVendor/YourAddon';
```

- `defaultAddOn` is optional but auto-populates the add-on selector when creating new data in the Admin CP
- Development mode automatically enables debug mode

**Development mode also enables:**
- Template editing from filesystem
- Auto-export of data to `_output/` on every change
- Admin CP Development menu (Routes, Class Extensions, Code Event Listeners, etc.)

---

## Additional config.php Options

### Disable email (recommended for dev with real user data)

```php
$config['enableMail'] = false;
```

Or use a mail-catching service like [MailTrap.io](https://mailtrap.io).

### Separate cookie prefix (multiple XF installs on same domain)

```php
$config['cookie']['prefix'] = 'xf_dev_';
```

Without this, two XF installs on the same domain will share session cookies and log you out unexpectedly.

### Fix session issues with dynamic IP / VPN

```php
$c->extend('session', function(\XF\Session\Session $session) {
    $session->setConfig([
        'ipv4CidrMatch' => 0,
        'ipv6CidrMatch' => 0
    ]);
    return $session;
});
```

> Never use this in production.

### Full example config.php for local development

```php
<?php

$config['db']['host'] = 'localhost';
$config['db']['port'] = '3306';
$config['db']['username'] = 'root';
$config['db']['password'] = 'secret';
$config['db']['dbname'] = 'xenforo_dev';

$config['debug'] = true;

$config['development']['enabled'] = true;
$config['development']['defaultAddOn'] = 'Demo/Portal';

$config['enableMail'] = false;

$config['cookie']['prefix'] = 'xf_dev_';

$config['chmodWritableValue'] = 0666;
```

---

## PhpStorm IDE Setup

XenForo development is best with [PhpStorm](https://www.jetbrains.com/phpstorm/).

### Type Hinting with XFCP

When you extend classes using `XFCP_ClassName`, PhpStorm won't know what `$this` refers to. XenForo automatically generates `extension_hint.php` in your add-on's `_output/` directory to fix this. Add your `_output` directory as a source root or include it as a library in PhpStorm.

### Xdebug Integration

1. Install Xdebug (see above)
2. In PhpStorm: Run > Edit Configurations > PHP Remote Debug
3. Set IDE key to `PHPSTORM`
4. Install the [Xdebug Helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc) Chrome extension
5. Enable debugging in the browser extension, set a breakpoint, and refresh the page

### Autocompletion

PhpStorm will read all files in `src/` and `src/addons/` automatically. The Composer-generated autoloader at `src/vendor/autoload.php` helps PhpStorm understand third-party classes.

---

## Add-on Management CLI Commands

```bash
# Install an add-on
php cmd.php xf:addon-install [addon_id]

# Upgrade an add-on
php cmd.php xf:addon-upgrade [addon_id]

# Rebuild (re-import data) for an add-on
php cmd.php xf:addon-rebuild [addon_id]

# Uninstall an add-on
php cmd.php xf:addon-uninstall [addon_id]

# File integrity check
php cmd.php xf:file-check [addon_id]
```

---

## Development Commands

These require development mode to be enabled:

```bash
# Import _output files into the database
php cmd.php xf-dev:import --addon [addon_id]

# Export database data to _output files
php cmd.php xf-dev:export --addon [addon_id]
```

---

## Debugging Utilities

```php
// Fancy Symfony VarDumper output (interactive, collapsible)
\XF::dump($var);

// Simple plain-text dump (same as var_dump but wrapped in <pre>)
\XF::dumpSimple($var);

// View the SQL a finder will produce without executing it
\XF::dumpSimple($finder->getQuery());
```

---

## Linux Notes

- PHP-CLI and PHP-FPM/Apache may be different users — ensure both can write to `data/`, `internal_data/`, and `_output/` directories
- Recommended: run CLI commands as `www-data` or set up ACLs
- Install PHP 8.x from `ondrej/php` PPA on Ubuntu for latest versions

## macOS Notes

- Homebrew is the easiest way to manage PHP and MySQL versions
- Use `valet` (Laravel Valet) or MAMP Pro for a managed stack
- XenForo works with the built-in Apache but requires enabling PHP and enabling mod_rewrite
