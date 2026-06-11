# config.php Options & Caching

The exhaustive reference for `src/config.php` — every option XenForo documents
officially — plus the full cache configuration system. This complements the
development-oriented config covered in `docs/01-setup-environment.md` (which
focuses on `debug`/`development` mode for add-on work); here we document the
*complete* documented option surface and the cache providers/contexts.

> **Sources (official XenForo manual, verbatim):**
> - https://docs.xenforo.com/manual/config/config-php
> - https://docs.xenforo.com/manual/config/cache

> **Accuracy note for AI assistants:** Only the option keys below appear in the
> official XenForo manual. Do **not** invent additional `config.php` keys, cache
> providers, or context names. Variable names and values are **case-sensitive**
> and must be entered *exactly* as shown, or XenForo ignores the setting. Each
> option is shown with its documented default value.

---

## How config.php works

All the information XenForo needs to connect to your database lives in
`src/config.php`. Depending on how you installed, it may have been created by
copying `src/config.php.default`, or generated for you by the XenForo installer.

Normally the file contains just a handful of settings — enough to let XenForo
function — but a range of additional options can be added to change how XenForo
operates. Each setting is written as a PHP assignment:

```php
$config['variableName'] = 'default-value';
```

> **Warning:** These options control fundamental functionality. Incorrect
> configuration may render your site inoperable. If you run into problems, undo
> your changes to `config.php` and try again.

---

## Database connection

| Option key | Type | Purpose |
|---|---|---|
| `$config['db']['host']` | string | Name or IP address of the database server. Default `'localhost'`. |
| `$config['db']['port']` | int | Port of the database server. Default `3306`. |
| `$config['db']['socket']` | string/null | Socket of the database server. Default `null`. |
| `$config['db']['username']` | string | Username used to connect to the database server. Default `''`. |
| `$config['db']['password']` | string | Password used to connect to the database server. Default `''`. |
| `$config['db']['dbname']` | string | Name of the database that holds your forums. Default `''`. |

The host, port and socket are provided by your hosting provider. The username,
password and database name identify the database within which your forums are
installed. See `docs/01-setup-environment.md` for a minimal starter `config.php`.

### Database adapter

| Option key | Type | Purpose |
|---|---|---|
| `$config['db']['adapterClass']` | string | PHP class used to connect to the database. Default `'XF\Db\Mysqli\Adapter'`. |

The name of the PHP class used to connect to your database. If you use a MySQL
server, there is little reason to change this setting.

---

## Full unicode / emoji support

| Option key | Type | Purpose |
|---|---|---|
| `$config['fullUnicode']` | bool | Whether full unicode (Emoji) support is enabled. Default `false`. |

This tells XenForo whether you have performed the steps necessary to support
full unicode in your forum content. Full unicode is used to support *Emoji* in
text.

- If your installation **started at version 2** (rather than upgrading from
  XenForo 1), your database is already in full unicode format, and you may set
  this to `true`.
- If your installation was **upgraded from XenForo 1**, you must run the unicode
  conversion process before enabling it; leave this at `false` until you do.

> **Note:** Full unicode support requires at least MySQL 5.5.

---

## Site-wide feature disable

These options are **not** included as standard in `config.php` — XenForo uses the
default values. Setting any of them to `false` disables the corresponding
functionality entirely. The descriptions below describe what happens when set to
`false`.

> **Note:** If a system is disabled through `config.php`, it **cannot** be
> re-enabled through the Admin control panel — only an edit to the config file
> will restore the system's functionality.

| Option key | Default | Disabling effect |
|---|---|---|
| `$config['enableMail']` | `true` | Completely disables all email-sending features. No email is sent at all, ever. |
| `$config['enableMailQueue']` | `true` | Disables the email queue. New email is sent as soon as generated rather than batched; queued email is not sent. |
| `$config['enableListeners']` | `true` | Disables all code event listeners — largely turns off all add-on functionality. Useful to recover access when an add-on breaks your control panel. |
| `$config['enableTemplateModificationCallbacks']` | `true` | Disables template modifications that operate via a PHP callback. Like `enableListeners`, helps regain access after a broken callback. |
| `$config['enableGzip']` | `true` | Disables gzip compression of final HTML/CSS output; content is sent as uncompressed plain text. |
| `$config['enableContentLength']` | `true` | Stops XenForo sending a `Content-Length` HTTP header. Disable when content is modified in transit and the header cannot be updated correctly. |
| `$config['enableTfa']` | `true` | Disables two-factor authentication (2FA) for all users. |
| `$config['enableLivePayments']` | `true` | No payments are processed; payment providers are not contacted and no transactions are attempted. Useful for test sites using a live database copy. |
| `$config['enableClickjackingProtection']` | `true` | Stops XenForo sending the `X-Frame-Options: SAMEORIGIN` header. Disable only if you understand the implications (it also blocks valid iframe embedding). |
| `$config['enableReverseTabnabbingProtection']` | `true` | Disables reverse-tabnabbing phishing protection on external links. Disable only if you understand the implications (may interfere with affiliate-link services). |
| `$config['enableApi']` (2.1+) | `true` | Disables the REST API, normally accessible via `<url>/api/`. |
| `$config['enableAddOnArchiveInstaller']` (2.1+) | `false` | Controls the control-panel add-on install/upgrade (zip upload) system. **Disabled by default for security reasons.** |
| `$config['enableOneClickUpgrade']` (2.1+) | `true` | Disables the one-click XenForo upgrade system in the control panel. |

> **Note on `enableAddOnArchiveInstaller`:** Unlike the others, this one defaults
> to `false`. To allow admins to upload a zip add-on and have it installed or
> upgraded automatically, you must explicitly set it to `true`.

---

## Cookie settings

Configure how cookies are set on visitors' browsers. **Incorrect or invalid
values may leave you and your visitors unable to log in, including to the admin
control panel.** The primary reason to change these is to accommodate multiple
XenForo installations on the same domain. With a single installation, there is
no need to change them.

| Option key | Type | Purpose |
|---|---|---|
| `$config['cookie']['prefix']` | string | Prefix for all XenForo cookie names. Default `'xf_'`. |
| `$config['cookie']['path']` | string | Path within which XenForo cookies are available. Default `'/'`. |
| `$config['cookie']['domain']` | string | Domain on which cookies can be read. Default `''`. |

### `prefix`

XenForo cookies are normally prefixed with `xf_` to distinguish them from other
systems' cookies. The prefix should use letters, numbers and underscores
**only**, and is case-sensitive. Changing it resets the **Remember me** setting
for all logged-in visitors, who must log in again on their next visit.

### `path`

With the default `/`, cookies are available across your whole website. Change it
only if XenForo cookies must be restricted to a sub-directory.

- `'/'` — cookies available to all areas of your website.
- `'/forum/'` — cookies readable only by pages within the `forum` directory
  (`http://example.com/forum`).
- `'/path/to/other/folder/'` — cookies readable only within that directory;
  pages in folders *above* it (e.g. `/path`, `/path/to`) cannot read them.

> **Warning:** If you specify a cookie path that does not allow cookies to be set
> within the XenForo root directory, XenForo will be unable to read its own
> cookies, and critical operations like logging in will fail.

### `domain`

Specifies a domain on which cookies can be read. It is unusual to need anything
other than the default. Setting a value that prevents XenForo from reading its
own cookies breaks important functionality (such as staying logged in).

- `''` — cookies readable **only** on the domain on which they were set.
- `'.example.com'` — cookies readable on *example.com* and any subdomain.
- `'subdomain.example.com'` — cookies readable only on *subdomain.example.com*.

> **Multiple installs on one domain:** give each install a distinct prefix, e.g.
> `$config['cookie']['prefix'] = 'xf2_';`

---

## Data and script locations

Change where XenForo stores the data and scripts it keeps in files (avatars,
attachments, JavaScript, etc.).

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['externalDataPath']` | path | `'data'` | Filesystem path to the `data` directory (avatars, attachment thumbnails) served directly to browsers. Must be within the web root. |
| `$config['externalDataUrl']` | url | `'data'` | URL of the `data` directory as visible from the web server. |
| `$config['internalDataPath']` | path | `'internal_data'` | Filesystem path to `internal_data` (files not served directly, e.g. attachments). |
| `$config['codeCachePath']` | path | `'%s/code_cache'` | Location of the `code_cache` directory (cached PHP files). Normally sits in `internal_data`. |
| `$config['tempDataPath']` | path | `'%s/temp'` | Path to the directory for temporary files (e.g. uploads being processed). Normally sits in `internal_data`. |
| `$config['javaScriptUrl']` | url | `'js'` | Location of the `js` folder on the public web server. Must be within the web root. |

### Path variables (`*Path`)

A name ending in **Path** refers to an internal filesystem path on the server,
relative to the XenForo install directory. Relative paths start at the install
directory and can be set outside the web root. If XenForo lives at
`/users/yourname/htdocs/xenforo`:

- `data` -> `/users/yourname/htdocs/xenforo/data`
- `../another-folder` -> `/users/yourname/htdocs/another-folder`

An absolute path from the server root (e.g. `/users/yourname/htdocs/xenforo/data`)
is also accepted.

### URL variables (`*Url`)

A name ending in **Url** refers to the path relative to your XenForo directory as
visible from the web root. If XenForo resides at `http://example.com/xenforo`:

- `data` -> `http://example.com/xenforo/data`
- `../another-folder` -> `http://example.com/another-folder`
- `/a-root-folder` -> `http://example.com/a-root-folder` (leading `/` = web root)

A full URL including the domain is also valid, e.g. `http://example.com/xenforo/data`
or protocol-relative `//example.com/xenforo/data`.

> **Warning:** Directories specified as paths **must** be writeable by the web
> server (chmod 777) or XenForo cannot store data there. If any of these paths
> and URLs are set incorrectly, important XenForo functionality will be
> **broken**. Change them *only* if you know exactly what you're doing.

---

## HTTP client settings

Control the behavior of the internal XenForo HTTP client used to fetch resources
across the internet (such as images and web pages for the Image and link proxy).

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['http']['sslVerify']` | bool/null | `null` | Force verification of SSL certificates for HTTPS resource requests. |
| `$config['http']['proxy']` | string/null | `null` | Proxy server address through which the HTTP client performs requests. |

Setting `sslVerify` to `true` can be beneficial in some circumstances, but SSL
certificate verification can fail in a number of ways, resulting in an inability
to fetch the requested resource. If in doubt, leave this setting alone. To route
requests through a proxy, enter the proxy server's address in `proxy`.

---

## Other variables

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['globalSalt']` | string | `'<unique value>'` | Secret value used to *salt* caches, cookies and other data to prevent theft/faking. |
| `$config['checkVersion']` | bool | `true` | Verify the version in the PHP scripts matches the database; blocks regular visitors during a pending upgrade. |
| `$config['passwordIterations']` | int | `10` | Strength of the bcrypt password storage. Each increment roughly doubles hashing time. |
| `$config['maxImageResizePixelCount']` | int | `20000000` | Max image size (total pixels, width × height) XenForo will attempt to resize. Larger images are not resized and may be rejected. |
| `$config['adminLogLength']` | int | `60` | Number of days to keep the admin activity log before pruning. |
| `$config['chmodWritableValue']` | int | `0` | If non-zero, files (and directories) created by XenForo are chmodded to this value. |
| `$config['proxyUrlFormat']` | string | `'proxy.php?{type}={url}&hash={hash}'` | Format for links using the Image and link proxy. |
| `$config['jobMaxRunTime']` | int | `8` | Seconds a processing job may run before being suspended for another go-around. |
| `$config['fsAdapters']` | array | `[]` | List of available filesystem adapters (advanced power-user feature). |

> **Security:** Never reveal the `globalSalt` value to anyone — doing so
> compromises the security of your installation. XenForo normally generates its
> own secure global salt; only change it to define your own.

> **`proxyUrlFormat`:** The format must include the tokens `{type}`, `{url}` and
> `{hash}`, and must target `proxy.php` unless you have an alternative script or
> system to handle proxy requests.

> **`chmodWritableValue`:** When set, directories are chmodded to this value and
> are additionally always user-, group-, and world-executable. In most
> situations, XenForo determines the correct chmod value automatically.

---

## For developers and designers

These three blocks (`debug`, `designer`, `development`) overlap with
`docs/01-setup-environment.md`, which covers their day-to-day use during add-on
development. They are listed here for completeness of the option surface.

### Debug mode

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['debug']` | bool | `false` | Run XenForo in debug mode (required for Designer and Developer modes). |

> **Warning:** Never, **never** enable debug mode on a live production site
> exposed to the Internet. Execution and page generation run significantly more
> slowly, and important information such as internal SQL query state can be
> revealed to visiting users. Only enable it on a private, protected
> installation.

### Designer mode

Allows templates to be edited directly in the filesystem rather than the
template editor.

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['designer']['enabled']` | bool | `false` | Switches designer mode on/off. |
| `$config['designer']['basePath']` | path | `'src' . \DIRECTORY_SEPARATOR . 'styles'` | Location where XenForo expects the designer's template files. |

### Development mode

For add-on developers.

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['development']['enabled']` | bool | `false` | Switches developer mode on/off. |
| `$config['development']['defaultAddOn']` | string | `''` | Add-on ID auto-selected for newly-created material in the Admin CP. |
| `$config['development']['skipAddOns']` | array/null | `null` | Array of add-on IDs to skip when running development tools (e.g. importing/exporting master data), such as `['addOn1', 'addOn2']`. |
| `$config['development']['throwJobErrors']` | bool/null | `null` | If `true`, errors normally suppressed during development jobs (e.g. the build script) are thrown and displayed, interrupting the job. |
| `$config['development']['fullJs']` | bool | `false` | If `true`, use the full (unminified) JavaScript files instead of the minified, rolled-up libraries. |

> **`fullJs`:** Full JavaScript files are easier to step through when debugging,
> but generate more HTTP requests and consume more bandwidth, slowing the
> experience for visitors. Not recommended on live production sites. See
> `docs/01-setup-environment.md` for a typical development block.

---

## Cache settings

For large XenForo sites, a cache mechanism can speed up page generation and save
database queries. All cache settings fall within the `$config['cache']` section.

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['cache']['enabled']` | bool | `false` | Master switch for caching. Must be `true` for any cache (including contexts) to operate. |
| `$config['cache']['provider']` | string | — | The cache provider name (see below). |
| `$config['cache']['config']` | array | — | Provider-specific configuration array. |
| `$config['cache']['sessions']` | bool | — | If `true`, caches XenForo user sessions. |

To disable a configured cache at any time:

```php
$config['cache']['enabled'] = false;
```

### Page-level cache (master switch)

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['pageCache']['enabled']` | bool | `false` | Enables caching of entire pages for guests. Disabled by default until cache resources are allocated. |

When a cache is configured, XenForo can cache whole pages for guest users via the
`pageCache`. This is powerful but can consume large quantities of cache
resources, so it is disabled by default. See **Guest page caching** below.

---

## Cache providers

XenForo ships with several cache providers. Many require software to be installed
on your server — check with your host as to which are available. The officially
listed providers are:

- APC
- File system cache
- Memcached
- Redis
- WinCache
- XCache
- ... and more

All configuration is done within `src/config.php`. Every provider needs
`$config['cache']['enabled'] = true;` plus a `provider` value; those needing more
also set a `config` array.

### Providers with no additional configuration

APC, WinCache and XCache require only the `enabled` and `provider` lines (no
`config` array). Use the exact provider string:

| Provider | `$config['cache']['provider']` value |
|---|---|
| APC | `'ApcCache'` |
| WinCache | `'WinCache'` |
| XCache | `'XCache'` |

```php
$config['cache']['enabled'] = true;
$config['cache']['provider'] = 'ApcCache'; // or 'WinCache' / 'XCache'
```

### File system cache

```php
$config['cache']['enabled'] = true;
$config['cache']['provider'] = 'Filesystem';
$config['cache']['config'] = [
    'directory' => '/path/to/your/cache/directory'
];
```

> **Note:** Ensure that the directory exists, is writable by the web server user,
> and is **not** publicly accessible.

### Memcached

```php
$config['cache']['enabled'] = true;
$config['cache']['provider'] = 'Memcached';
$config['cache']['config'] = [
    'server' => '127.0.0.1'
];
```

It is also possible to configure an array of servers, if required.

### Redis

```php
$config['cache']['enabled'] = true;
$config['cache']['provider'] = 'Redis';
$config['cache']['config'] = [
    'host' => '127.0.0.1',
    'password' => 'password'
];
```

Redis has a number of additional configuration options. The following list shows
the default values of all supported configuration items:

```php
'host' => '',
'port' => 6379,
'timeout' => 0.0,
'password' => '',
'database' => 0,
'persistent' => false,
'persistent_id' => ''
```

> WinCache and XCache are listed under **Providers with no additional
> configuration** above.

---

## Session caching

In addition to data caches, XenForo can cache user sessions:

```php
$config['cache']['sessions'] = true;
```

> **Note:** Your cache must have enough space to hold the sessions, or users may
> not be able to log in properly. Writing sessions to the cache is **not**
> recommended if you are using APC as your cache provider.

---

## Cache contexts (2.1+)

Starting in XenForo 2.1, a different cache configuration may be specified for
different scenarios (contexts). This allows, for example, a global cache plus a
separate cache for sessions or guest page caching.

To specify a cache for a specific context:

```php
$config['cache']['context']['CONTEXT_NAME']['provider'] = 'CacheProvider';
$config['cache']['context']['CONTEXT_NAME']['config'] = [];
```

Replace `CONTEXT_NAME` with a specific context (below), and `CacheProvider` /
the config value with the cache type being used (Memcached, Redis, etc.) and its
necessary configuration.

To use specific contexts, caching must be globally enabled:

```php
$config['cache']['enabled'] = true;
```

The following cache contexts are used by default in XenForo 2.1:

| Context | Used for |
|---|---|
| `css` | CSS cache |
| `page` | Guest page cache |
| `registry` | Registry cache |
| `sessions` | Session cache |

When specifying a custom provider for a context, you may also override the cache
namespace if needed:

```php
$config['cache']['context']['CONTEXT_NAME']['namespace'] = 'value';
```

If not specified, the global cache namespace is used.

---

## Guest page caching (2.1+)

XenForo 2.1 can cache guest page views for a period of time, reducing the
overhead caused by guests browsing the site and potentially lowering overall
server load.

Guest page caching can cache a large amount of data. It therefore **requires**
that you set up a specific `page` cache context — if this is not done, page
caching will not be enabled. XenForo recommends using a separate cache "instance"
for the global and page caches so the page cache does not force data such as
sessions out of the global cache.

A basic page cache setup:

```php
$config['cache']['enabled'] = true;
$config['pageCache']['enabled'] = true;
$config['cache']['context']['page']['provider'] = 'CacheProvider';
$config['cache']['context']['page']['config'] = [];
```

Modify the `CacheProvider` value and its configuration to refer to a specific
cache type (see **Cache contexts** above).

When a page is served from the cache, an `X-XF-Cache-Status: HIT` header is
present in the response.

### Advanced guest page caching configuration

| Option key | Type | Default | Purpose |
|---|---|---|---|
| `$config['pageCache']['lifetime']` | int (seconds) | `300` | How long a page is cached for. |
| `$config['pageCache']['recordSessionActivity']` | bool | `true` | If `true`, a session activity record is updated when a page is served from cache (more accurate online-user count at the cost of extra work per cached page). |
| `$config['pageCache']['routeMatches']` | array | `[]` | Route prefixes where the cache is active. E.g. `['threads/', 'forums/']` caches only thread/forum pages. A leading `#` means the value is a regular expression to test the route against. |
| `$config['pageCache']['onSetup']` | closure/null | `null` | A closure for custom page-cache setup. Receives the `\XF\PageCache` object; if it returns `false`, the page cache is disabled for this request. |

> **`routeMatches`:** e.g. `['threads/', 'forums/']` caches only thread- and
> forum-related pages. A leading `#` on a value makes it a regular expression
> tested against the route.

---

## Complete example

A production-oriented `config.php` combining database, a Redis global cache, a
separate Redis page-cache context, and guest page caching:

```php
<?php

// --- Database ---
$config['db']['host']     = 'localhost';
$config['db']['port']     = 3306;
$config['db']['username'] = 'xenforo';
$config['db']['password'] = 'secret';
$config['db']['dbname']   = 'xenforo';

// --- Unicode (fresh XF2 install) ---
$config['fullUnicode'] = true;

// --- Global cache (Redis) ---
$config['cache']['enabled']  = true;
$config['cache']['provider'] = 'Redis';
$config['cache']['config'] = [
    'host' => '127.0.0.1',
    'port' => 6379,
];

// --- Separate page-cache context + guest page caching ---
$config['pageCache']['enabled'] = true;
$config['cache']['context']['page']['provider'] = 'Redis';
$config['cache']['context']['page']['config'] = [
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 1,
];
$config['pageCache']['lifetime'] = 300;
```

> **Reminder:** Every key above appears in the official XenForo manual. If a
> setting you need is not listed in this document, it is not documented by
> XenForo — confirm it against the source before relying on it, and never
> fabricate config keys.

---

**See also:** `docs/01-setup-environment.md` (debug/development setup for add-on
work), `docs/11-schema-migrations.md` (Setup.php schema changes).
