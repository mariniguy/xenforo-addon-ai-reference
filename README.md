# XenForo Add-on Development Reference for AI

> **The most comprehensive XenForo 2.3+ add-on development reference available.**  
> Built from the official XenForo developer documentation, real add-on source code, and hands-on examples. Designed so AI assistants can write correct, idiomatic XenForo add-ons without hallucinating APIs.

---

## What is this?

This repository is a structured knowledge base for building XenForo 2.x add-ons. It contains:

- **`xenforo.md`** — Single-file mega-reference (4,000+ lines) covering every concept with working PHP/XML/HTML code examples
- **`docs/`** — Topic-by-topic deep dives sourced directly from xenforo.com documentation
- **`examples/`** — Real, complete add-on code (including the official Demo/Portal tutorial addon)
- **`cheatsheets/`** — Quick-reference tables for the PHP API, template tags, entity column types
- **`addons/`** — Downloaded free add-ons from xenforo.com/community for pattern reference

---

## Quick navigation

| What you need | Where to look |
|---|---|
| Everything in one place | [`xenforo.md`](xenforo.md) |
| Setting up a dev environment | [`docs/01-setup-environment.md`](docs/01-setup-environment.md) |
| Add-on structure / addon.json / CLI | [`docs/02-addon-structure.md`](docs/02-addon-structure.md) |
| Entities, Finders, Repositories | [`docs/03-entities-finders-repositories.md`](docs/03-entities-finders-repositories.md) |
| Controllers and Routing | [`docs/04-controllers-routing.md`](docs/04-controllers-routing.md) |
| Templates (syntax, tags, forms) | [`docs/05-templates.md`](docs/05-templates.md) |
| Services, Jobs, Cron | [`docs/06-services-jobs-cron.md`](docs/06-services-jobs-cron.md) |
| Handlers and Content Types | [`docs/07-handlers-content-types.md`](docs/07-handlers-content-types.md) |
| Permissions, Options, Phrases | [`docs/08-permissions-options-phrases.md`](docs/08-permissions-options-phrases.md) |
| Criteria system | [`docs/09-criteria-system.md`](docs/09-criteria-system.md) |
| REST API and Webhooks | [`docs/10-rest-api.md`](docs/10-rest-api.md) |
| PHP API quick reference | [`cheatsheets/php-api.md`](cheatsheets/php-api.md) |
| Template tag reference | [`cheatsheets/template-tags.md`](cheatsheets/template-tags.md) |
| Entity column types | [`cheatsheets/entity-column-types.md`](cheatsheets/entity-column-types.md) |
| Complete working add-on (Demo/Portal) | [`examples/demo-portal/`](examples/demo-portal/) |
| Real add-on patterns | [`examples/real-addon-patterns.md`](examples/real-addon-patterns.md) |

---

## XenForo 2.3+ — Key concepts at a glance

### The \XF facade (use everywhere)
```php
\XF::app()                          // Application container
\XF::db()                           // Database adapter
\XF::em()                           // Entity manager
\XF::finder('XF:User')             // Build a finder query
\XF::repository('XF:User')         // Get a repository
\XF::service('XF:Thread\Creator')  // Instantiate a service
\XF::visitor()                      // Current user entity (user_id=0 = guest)
\XF::options()                      // Board options object
\XF::phrase('phrase_name')          // Get a phrase
\XF::$time                          // Current request timestamp
\XF::extendClass('XF\\Foo\\Bar')   // Resolve extended class (use before new)
```

### Minimal add-on structure
```
src/addons/Vendor/AddOn/
├── addon.json          ← REQUIRED: title, version_id, version_string, dev
├── Setup.php           ← install/upgrade/uninstall schema steps
├── Listener.php        ← all code event listeners
├── Entity/MyThing.php
├── Repository/MyThing.php
├── Pub/Controller/MyThing.php
├── Admin/Controller/MyThing.php
├── _data/              ← exported XML master data (ship this)
└── _output/            ← dev-mode JSON/HTML files (do NOT ship)
```

### Entity (database row wrapper)
```php
class MyThing extends \XF\Mvc\Entity\Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table      = 'xf_vendor_mything';
        $structure->shortName  = 'Vendor\AddOn:MyThing';
        $structure->primaryKey = 'thing_id';
        $structure->columns    = [
            'thing_id'     => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id'      => ['type' => self::UINT, 'default' => 0],
            'title'        => ['type' => self::STR, 'maxLength' => 150, 'required' => true],
            'created_date' => ['type' => self::UINT, 'default' => 0],
            'is_active'    => ['type' => self::BOOL, 'default' => true],
        ];
        $structure->relations  = [
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE,
                       'conditions' => 'user_id', 'primary' => true],
        ];
        return $structure;
    }
}
```

### Controller action
```php
public function actionIndex()
{
    $page    = $this->filterPage();
    $perPage = 20;
    $finder  = $this->finder('Vendor\AddOn:MyThing')->order('created_date', 'DESC');

    $viewParams = [
        'things' => $finder->limitByPage($page, $perPage)->fetch(),
        'total'  => $finder->total(),
        'page'   => $page,
        'perPage'=> $perPage,
    ];
    return $this->view('Vendor\AddOn:Index', 'vendor_addon_index', $viewParams);
}
```

### Template
```html
<xf:title>{{ phrase('vendor_addon_title') }}</xf:title>
<xf:foreach loop="$things" value="$thing">
    <div class="block-row">
        <h3>{$thing.title}</h3>
        <span class="u-muted"><xf:date time="{$thing.created_date}" /></span>
    </div>
<xf:else />
    <div class="blockMessage">{{ phrase('no_items_found') }}</div>
</xf:foreach>
<xf:pagenav page="{$page}" perpage="{$perPage}" total="{$total}" link="vendor-addon" />
```

---

## Add-on development workflow

```sh
# 1. Create the add-on
php cmd.php xf-addon:create

# 2. Enable development mode in src/config.php
# $config['development']['enabled'] = true;
# $config['development']['defaultAddOn'] = 'Vendor/AddOn';

# 3. Run install steps after writing Setup.php
php cmd.php xf-addon:install-step Vendor/AddOn 1

# 4. Create routes, listeners, class extensions in ACP (dev mode)

# 5. Export development output
php cmd.php xf-dev:export --addon Vendor/AddOn

# 6. Build for release
php cmd.php xf-addon:build-release Vendor/AddOn
# → _releases/Vendor-AddOn-1.0.0.zip
```

---

## Version targeting

This reference targets **XenForo 2.3.x** (latest stable line as of 2026).

- Minimum PHP: 7.2 (PHP 8.1+ strongly recommended)
- MySQL: 5.7+ / MariaDB 10.3+
- All code is **incompatible with XenForo 1.x**

---

## Sources

- [XenForo Official Developer Docs](https://xenforo.com/docs/dev/) (fetched live)
- [Official XenForo Documentation Source](https://github.com/xenforo-ltd/docs) (local copy in `_docs_src/`)
- Free add-ons from [xenforo.com/community/resources](https://xenforo.com/community/resources/)
- The [Let's build an add-on](https://xenforo.com/docs/dev/lets-build-an-add-on/) tutorial (Demo/Portal)

---

## Contributing

Found something wrong or missing? Open an issue or PR.  
All corrections welcome — accuracy is the whole point.
