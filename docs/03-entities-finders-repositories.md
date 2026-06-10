# Entities, Finders, and Repositories

## Overview

XenForo's data layer has three primary components:

- **Entity** — represents a single database row as an object; handles reading, writing, and validation
- **Finder** — builds and executes SELECT queries in an object-oriented way
- **Repository** — holds reusable Finder configurations and utility methods

---

## The Finder

The Finder builds queries programmatically. It always works with an entity type.

```php
// Get one user by ID
$user = \XF::finder('XF:User')->where('user_id', 1)->fetchOne();

// Get 10 users
$users = \XF::finder('XF:User')->limit(10)->fetch();

// Get a single field value
$username = \XF::finder('XF:User')->where('user_id', 1)->fetchOne()->username;

// Get an array of usernames from multiple rows
$usernames = \XF::finder('XF:User')->limit(10)->pluckFrom('username')->fetch();
```

### where()

```php
// Implied = operator
$finder->where('user_state', 'valid');

// Explicit operator
$finder->where('register_date', '>=', time() - 86400 * 7);

// Array syntax — mixed
$finder->where([
    'user_state' => 'valid',
    ['register_date', '>=', time() - 86400 * 7]
]);
```

Valid operators: `=`, `<>`, `!=`, `>`, `>=`, `<`, `<=`, `LIKE`, `BETWEEN`

### whereOr()

```php
// Two conditions joined by OR
$finder->whereOr(
    ['user_state', '<>', 'valid'],
    ['message_count', 0]
);

// More than two conditions
$finder->whereOr([
    ['user_state', '<>', 'valid'],
    ['message_count', 0],
    ['is_banned', 1]
]);
```

### with() — Joins

```php
// LEFT JOIN (must_exist = false, the default)
$finder->with('User');

// INNER JOIN (must_exist = true)
$finder->with('Forum', true);

// Multiple joins
$finder->with(['Forum', 'User'], true);

// Chain for mixed join types
$finder->with('Forum', true)->with('User');

// Join a specific item from a TO_MANY relation
$finder->with('ConnectedAccounts|facebook');
```

### order(), limit(), limitByPage()

```php
// Order by single column
$finder->order('message_count', 'DESC');

// Multiple columns
$finder->order('message_count', 'DESC')->order('register_date');

// Limit
$finder->limit(10);
$finder->limit(10, 100); // limit=10, offset=100

// Pass limit to fetch()
$finder->fetch(10);

// Paginate — page 3, 20 per page
$finder->limitByPage(3, 20);

// Over-fetch for permission filtering
$finder->limitByPage(3, 20, 1); // fetches up to 21
```

### fetch(), fetchOne(), total(), pluckFrom()

```php
// Returns ArrayCollection
$users = $finder->fetch();

// Returns single Entity or null
$user = $finder->fetchOne();

// Total count (ignores limit/offset)
$total = $finder->total();

// Array of a single column's values
$ids = $finder->pluckFrom('user_id')->fetch();

// Inspect the generated SQL
\XF::dumpSimple($finder->getQuery());
```

### Custom Finder Classes

Create `src/addons/Demo/Portal/Finder/FeaturedThread.php`:

```php
<?php

namespace Demo\Portal\Finder;

use XF\Mvc\Entity\Finder;

class FeaturedThread extends Finder
{
    public function applyFeaturedOrder(string $direction = 'DESC'): self
    {
        $options = \XF::options();

        if ($options->demoPortalDefaultSort === 'featured_date') {
            $this->setDefaultOrder('featured_date', $direction);
        } else {
            $this->setDefaultOrder('Thread.post_date', $direction);
        }

        return $this;
    }

    public function onlyVisible(): self
    {
        $this->where('Thread.discussion_state', 'visible');
        return $this;
    }
}
```

Usage:

```php
$finder = \XF::finder('Demo\Portal:FeaturedThread')
    ->applyFeaturedOrder('DESC')
    ->onlyVisible()
    ->with('Thread', true)
    ->limit(20);
```

---

## ArrayCollection

`fetch()` returns an `ArrayCollection`. Key methods:

```php
$collection = $finder->fetch();

// Count
$count = count($collection);
$count = $collection->count();

// Check empty
if ($collection->count() === 0) { ... }

// Iterate
foreach ($collection as $key => $entity) { ... }

// Get first/last
$first = $collection->first();
$last = $collection->last();

// Filter by callback
$filtered = $collection->filter(function($entity) {
    return $entity->is_enabled;
});

// Slice to page (for permission-based over-fetching)
$sliced = $collection->sliceToPage($page, $perPage);

// Merge two collections
$merged = $collection->merge($otherCollection);

// Group by field value
$grouped = $collection->groupBy('category_id');

// Pluck a named relation or field
$threads = $featuredCollection->pluckNamed('Thread');
$posts = $threads->pluckNamed('FirstPost', 'first_post_id');
```

---

## Entity System

### Defining an Entity

```php
<?php

namespace Demo\Portal\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class FeaturedThread extends Entity
{
    // Define a getter for a computed property
    public function getIsRecent(): bool
    {
        return ($this->featured_date > time() - 86400 * 7);
    }

    // Permission check methods on the entity
    public function canView(): bool
    {
        return $this->Thread && $this->Thread->canView();
    }

    // Lifecycle hooks
    protected function _preSave()
    {
        if ($this->isInsert() && !$this->featured_date) {
            $this->featured_date = time();
        }
    }

    protected function _postSave()
    {
        if ($this->isInsert()) {
            // Update the cache column on the thread
            $this->Thread->fastUpdate('demo_portal_featured', true);
        }
    }

    protected function _postDelete()
    {
        if ($this->Thread) {
            $this->Thread->fastUpdate('demo_portal_featured', false);
        }
    }

    // Required: define the structure
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_demo_portal_featured_thread';
        $structure->shortName = 'Demo\Portal:FeaturedThread';
        $structure->contentType = 'demo_featured_thread'; // for handler system
        $structure->primaryKey = 'thread_id';

        $structure->columns = [
            'thread_id'     => ['type' => self::UINT, 'required' => true],
            'featured_date' => ['type' => self::UINT, 'default' => \XF::$time],
            'feature_user_id' => ['type' => self::UINT, 'default' => 0],
        ];

        $structure->getters = [
            'is_recent' => true,
        ];

        $structure->relations = [
            'Thread' => [
                'entity'     => 'XF:Thread',
                'type'       => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary'    => true,
            ],
            'FeatureUser' => [
                'entity'     => 'XF:User',
                'type'       => self::TO_ONE,
                'conditions' => [['feature_user_id', '=', '$user_id']],
                'primary'    => false,
            ],
        ];

        $structure->behaviors = [];
        $structure->options = [];

        return $structure;
    }
}
```

---

## Entity Structure — All Properties

### table

```php
$structure->table = 'xf_demo_item';
```

Maps to the database table used for reads and writes.

### shortName

```php
$structure->shortName = 'Demo:Item';
```

The short class name used to reference this entity.

### contentType

```php
$structure->contentType = 'demo_item';
```

Used by the handler system (`xf_content_type_field`). Only needed if this entity participates in alerts, attachments, reactions, etc.

### primaryKey

```php
$structure->primaryKey = 'item_id';

// Composite primary key
$structure->primaryKey = ['item_id', 'user_id'];
```

### columns

```php
$structure->columns = [
    'item_id'      => ['type' => self::UINT,   'autoIncrement' => true, 'nullable' => true, 'changeLog' => false],
    'title'        => ['type' => self::STR,    'maxLength' => 100, 'required' => 'please_enter_a_title'],
    'description'  => ['type' => self::STR,    'default' => ''],
    'item_date'    => ['type' => self::UINT,   'default' => \XF::$time],
    'user_id'      => ['type' => self::UINT,   'default' => 0],
    'is_enabled'   => ['type' => self::BOOL,   'default' => true],
    'view_count'   => ['type' => self::UINT,   'default' => 0],
    'extra_data'   => ['type' => self::JSON,   'default' => []],
    'tag_list'     => ['type' => self::JSON_ARRAY, 'default' => []],
    'options'      => ['type' => self::SERIALIZED, 'default' => []],
    'status'       => ['type' => self::STR,    'default' => 'visible', 'allowedValues' => ['visible', 'deleted', 'moderated']],
    'price'        => ['type' => self::FLOAT,  'default' => 0.0],
    'item_type'    => ['type' => self::STR,    'maxLength' => 50, 'default' => 'standard'],
    'list_cache'   => ['type' => self::LIST_COMMA, 'default' => []],
];
```

### Column Types Reference

| Constant | PHP Type | DB Type | Notes |
|----------|----------|---------|-------|
| `self::INT` | int | INT | Signed integer |
| `self::UINT` | int | INT UNSIGNED | Unsigned integer (most common) |
| `self::STR` | string | VARCHAR/TEXT | String |
| `self::BINARY` | string | BINARY/VARBINARY | Binary data |
| `self::BOOL` | bool | TINYINT(1) | Boolean — stored as 0/1 |
| `self::FLOAT` | float | FLOAT/DOUBLE | Float |
| `self::JSON` | array/object | TEXT/MEDIUMBLOB | Auto json_encode/decode |
| `self::JSON_ARRAY` | array | TEXT/MEDIUMBLOB | Ensures decoded value is array |
| `self::SERIALIZED` | mixed | TEXT/MEDIUMBLOB | PHP serialize/unserialize |
| `self::LIST_COMMA` | array | TEXT | Comma-separated list |
| `self::LIST_LINES` | array | TEXT | Newline-separated list |

### Column Definition Keys

| Key | Type | Description |
|-----|------|-------------|
| `type` | constant | Data type (required) |
| `default` | mixed | Default value (required for most columns) |
| `required` | bool/string | `true` or phrase name shown on validation fail |
| `maxLength` | int | Maximum string length |
| `allowedValues` | array | Enum-like whitelist of valid values |
| `unique` | bool | Enforce uniqueness (validation only, not DB constraint) |
| `autoIncrement` | bool | AUTO_INCREMENT primary key |
| `nullable` | bool | Allow NULL (stored as `null`) |
| `changeLog` | bool | Track changes in change log (default: true) |
| `forced` | bool | Always include in UPDATE even if not changed |
| `noSave` | bool | Column exists in entity but not written to DB |

### getters

```php
$structure->getters = [
    'is_super_admin' => true,           // calls getIsSuperAdmin()
    'last_activity'  => true,           // calls getLastActivity(), overrides column value
    'full_name'      => ['getter' => 'getFullName', 'cache' => true],
];
```

Getter methods are called automatically when accessing the named property. To bypass a getter and get the raw column value, append `_` to the property name:

```php
$user->last_activity_;  // raw DB value, bypasses getter
```

### relations

```php
$structure->relations = [
    // TO_ONE: load one related entity
    'User' => [
        'entity'     => 'XF:User',
        'type'       => self::TO_ONE,
        'conditions' => 'user_id',      // shorthand for [['user_id', '=', '$user_id']]
        'primary'    => false,          // true = INNER JOIN, false = LEFT JOIN
    ],

    // TO_ONE with custom conditions
    'Forum' => [
        'entity'     => 'XF:Forum',
        'type'       => self::TO_ONE,
        'conditions' => [['node_id', '=', '$forum_id']],
        'primary'    => true,
    ],

    // TO_MANY: load multiple related entities
    'ConnectedAccounts' => [
        'entity'     => 'XF:UserConnectedAccount',
        'type'       => self::TO_MANY,
        'conditions' => 'user_id',
        'key'        => 'provider',    // key the collection by this field
    ],
];
```

### behaviors

```php
$structure->behaviors = [
    'XF:Likeable'      => [],
    'XF:ChangeLoggable' => [],
    'XF:Taggable'      => ['stateField' => 'discussion_state'],
];
```

Behaviors hook into the entity lifecycle and add reusable functionality without requiring direct entity edits.

### options

```php
$structure->options = [
    'admin_edit'          => false,
    'skip_email_confirm'  => false,
    'custom_title_disallowed' => [],
];
```

Options are flags that modify entity behavior under certain conditions. Set them via `$entity->setOption('admin_edit', true)` before saving.

---

## Entity Lifecycle

### _preSave()

Called before the save begins. Use for validation and pre-calculation:

```php
protected function _preSave()
{
    if ($this->isChanged('user_group_id') || $this->isChanged('secondary_group_ids')) {
        $groupRepo = $this->getUserGroupRepo();
        $this->display_style_group_id = $groupRepo->getDisplayGroupIdForUser($this);
    }

    if ($this->isInsert() && !$this->slug) {
        $this->slug = \XF\Util\Str::slugify($this->title);
    }
}
```

### _postSave()

Called after the save, before transaction commit. Use for side effects:

```php
protected function _postSave()
{
    if ($this->isUpdate() && $this->isChanged('username') && $this->getExistingValue('username') !== null) {
        $this->app()->jobManager()->enqueue('XF:UserRenameCleanUp', [
            'originalUserId'   => $this->user_id,
            'originalUserName' => $this->getExistingValue('username'),
            'newUserName'      => $this->username,
        ]);
    }

    if ($this->isInsert()) {
        $this->rebuildCountCache();
    }
}
```

### _preDelete() / _postDelete()

```php
protected function _preDelete()
{
    if ($this->is_locked) {
        $this->error(\XF::phrase('cannot_delete_locked_item'));
    }
}

protected function _postDelete()
{
    // Clean up associated records
    $db = $this->db();
    $db->delete('xf_demo_item_tag', 'item_id = ?', $this->item_id);
}
```

### State Methods

```php
$entity->isInsert();                          // true if new record
$entity->isUpdate();                          // true if existing record
$entity->isChanged('title');                  // true if field changed since last save
$entity->getExistingValue('username');        // value before current change
$entity->isStateChanged('discussion_state', 'visible'); // 'enter', 'leave', or false
```

### Mutation Methods

```php
// Save entity (INSERT or UPDATE)
$entity->save();

// Delete entity
$entity->delete();

// Fast update a single column without loading entity
$entity->fastUpdate('view_count', $entity->view_count + 1);

// Get related entity or create default if not found
$featuredThread = $thread->getRelationOrDefault('FeaturedThread');
$featuredThread->save();

// Add a related entity that should save when this entity saves
$entity->addCascadedSave($relatedEntity);
```

---

## Verify Callbacks

When a column value is set, XenForo checks for a `verifyColumnName()` method:

```php
public function verifyStyleId(int &$value): bool
{
    if ($value && !\XF::app()->data('XF:Style')->styleExists($value)) {
        $value = 0; // Reset to default if invalid
    }
    return true;
}

public function verifyTitle(string &$value): bool
{
    $value = trim($value);
    if (strlen($value) < 3) {
        $this->error(\XF::phrase('title_must_be_at_least_3_characters'), 'title');
        return false;
    }
    return true;
}
```

---

## Repositories

Repositories correspond to an entity and hold reusable Finder configurations:

```php
<?php

namespace Demo\Portal\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class FeaturedThread extends Repository
{
    /**
     * Returns a configured finder for the portal view.
     * Returns the Finder (not results) to allow callers to further modify it.
     */
    public function findFeaturedThreadsForPortalView(): Finder
    {
        $visitor = \XF::visitor();

        return $this->finder('Demo\Portal:FeaturedThread')
            ->setDefaultOrder('featured_date', 'DESC')
            ->with('Thread', true)
            ->with('Thread.User')
            ->with('Thread.Forum', true)
            ->with('Thread.Forum.Node.Permissions|' . $visitor->permission_combination_id)
            ->with('Thread.FirstPost', true)
            ->with('Thread.FirstPost.User')
            ->where('Thread.discussion_type', '<>', 'redirect')
            ->where('Thread.discussion_state', 'visible');
    }

    public function markAllAsSeen(int $userId): void
    {
        $this->db()->update(
            'xf_demo_portal_featured_thread',
            ['last_seen_date' => \XF::$time],
            'feature_user_id = ?',
            $userId
        );
    }

    public function rebuildFeaturedCount(): int
    {
        $count = $this->finder('Demo\Portal:FeaturedThread')
            ->where('Thread.discussion_state', 'visible')
            ->total();

        \XF::registry()->set('demoPortalFeaturedCount', $count);

        return $count;
    }
}
```

### Calling Repositories

```php
// In a controller
/** @var \Demo\Portal\Repository\FeaturedThread $repo */
$repo = $this->repository('Demo\Portal:FeaturedThread');

// In PHP outside a controller
/** @var \Demo\Portal\Repository\FeaturedThread $repo */
$repo = \XF::repository('Demo\Portal:FeaturedThread');
```

---

## Direct Database Access

For cases where the Finder isn't suitable:

```php
$db = \XF::db();

// Fetch a single row as associative array
$user = $db->fetchRow('SELECT * FROM xf_user WHERE user_id = ?', 1);

// Fetch a single value
$username = $db->fetchOne('SELECT username FROM xf_user WHERE user_id = ?', 1);

// Fetch all rows (numerically indexed)
$users = $db->fetchAll('SELECT * FROM xf_user LIMIT 10');

// Fetch all rows (keyed by a column)
$users = $db->fetchAllKeyed('SELECT * FROM xf_user LIMIT 10', 'user_id');
// NOTE: third argument is the query params when using fetchAllKeyed
$users = $db->fetchAllKeyed('SELECT * FROM xf_user WHERE user_state = ?', 'user_id', 'valid');

// Fetch an array of a single column
$usernames = $db->fetchAllColumn('SELECT username FROM xf_user LIMIT 10');

// Execute without return value
$db->query('DELETE FROM xf_demo WHERE demo_id = ?', 42);

// Quote/escape values manually
$quoted = $db->quote($userInput);
```

---

## Schema Manager

Used in Setup.php for create/alter/drop operations:

```php
$sm = $this->schemaManager();
// or: $sm = \XF::db()->getSchemaManager();

// Create table
$sm->createTable('xf_demo_item', function(\XF\Db\Schema\Create $table) {
    $table->addColumn('item_id', 'int')->autoIncrement();
    $table->addColumn('user_id', 'int')->setDefault(0);
    $table->addColumn('title', 'varchar', 150);
    $table->addColumn('slug', 'varchar', 150);
    $table->addColumn('description', 'mediumtext');
    $table->addColumn('created_date', 'int')->setDefault(0);
    $table->addColumn('is_enabled', 'tinyint')->setDefault(1);
    $table->addUniqueKey('slug');
    $table->addKey(['user_id', 'created_date']);
});

// Alter table
$sm->alterTable('xf_demo_item', function(\XF\Db\Schema\Alter $table) {
    // Add column
    $table->addColumn('view_count', 'int')->setDefault(0)->after('title');

    // Change column
    $table->changeColumn('title')->length(200);

    // Drop column
    $table->dropColumns('old_column');
    $table->dropColumns(['old1', 'old2']);

    // Add key
    $table->addKey('created_date');
    $table->addUniqueKey('slug', 'slug_unique');

    // Drop key
    $table->dropIndexes('old_index');
});

// Drop table
$sm->dropTable('xf_demo_item');

// Check if table exists
if ($sm->tableExists('xf_demo_item')) { ... }

// Check if column exists
if ($sm->columnExists('xf_demo_item', 'view_count')) { ... }
```

Notes:
- All integer columns default to `UNSIGNED NOT NULL`
- Use `->unsigned(false)` to allow negative integers
- Use `->nullable(true)` to allow NULL
- Auto-increment columns are automatically set as PRIMARY KEY
