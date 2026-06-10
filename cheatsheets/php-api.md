# XenForo PHP API Quick Reference

> XenForo 2.3+ — all methods verified against official docs and source

---

## The `\XF` Facade

```php
// Application
\XF::app()                                    // \XF\App — application container
\XF::db()                                     // \XF\Db\AbstractAdapter — database
\XF::em()                                     // \XF\Mvc\Entity\Manager — entity manager
\XF::visitor()                                // \XF\Entity\User — current user (user_id=0 = guest)
\XF::options()                                // options object, ->optionId
\XF::option('optionId')                       // single option value
\XF::language()                               // current language entity
\XF::config('key')                            // config.php value
\XF::$time                                    // int — current request timestamp
\XF::$versionId                               // int — e.g. 2030070
\XF::extendClass('XF\\Entity\\Thread')        // string — resolved final class (respects extensions)

// Factories
\XF::finder('XF:User')                        // \XF\Mvc\Entity\Finder
\XF::repository('XF:User')                    // \XF\Mvc\Entity\Repository subclass
\XF::service('XF:Thread\Creator', $forum)     // \XF\Service\AbstractService subclass

// Phrases
\XF::phrase('phrase_name')                    // \XF\Phrase (auto-renders to string)
\XF::phrase('phrase_name', ['param' => $v])   // with parameters
\XF::phrase('phrase_name')->render()          // force to string

// Debugging
\XF::dump($var)                               // Symfony VarDumper — rich HTML
\XF::dumpSimple($var)                         // plain var_dump in <pre>

// Logging
\XF::logError('message')
\XF::logException($e, false, 'prefix: ')

// Misc
\XF::asVisitor($user, fn() => { ... })        // run closure as another user
```

---

## Database Adapter (`\XF::db()`)

```php
$db = \XF::db();

// Read — single value
$username = $db->fetchOne('SELECT username FROM xf_user WHERE user_id = ?', 1);

// Read — single row (associative array)
$user = $db->fetchRow('SELECT * FROM xf_user WHERE user_id = ?', 1);

// Read — all rows, numeric keys
$users = $db->fetchAll('SELECT * FROM xf_user LIMIT 10');

// Read — all rows, keyed by a column (params go in 3rd arg!)
$users = $db->fetchAllKeyed('SELECT * FROM xf_user LIMIT 10', 'user_id');
$users = $db->fetchAllKeyed('SELECT * FROM xf_user WHERE user_id > ?', 'user_id', [100]);

// Read — array of one column
$names = $db->fetchAllColumn('SELECT username FROM xf_user LIMIT 10');

// Read — [key => value] pairs from a 2-column query
$pairs = $db->fetchPairs('SELECT user_id, username FROM xf_user LIMIT 10');

// Write
$db->query('DELETE FROM xf_demo WHERE id = ?', $id);
$db->insert('xf_demo', ['user_id' => 1, 'title' => 'test', 'created_date' => \XF::$time]);
$db->insertBulk('xf_demo', [['user_id' => 1, 'title' => 'a'], ['user_id' => 2, 'title' => 'b']]);
$db->update('xf_demo', ['title' => 'new'], 'demo_id = ?', $id);
$db->delete('xf_demo', 'demo_id = ?', $id);
$insertId = $db->lastInsertId();
$affected  = $db->affectedRows();

// Transactions
$db->beginTransaction();
try { $db->commit(); } catch (\Exception $e) { $db->rollback(); throw $e; }

// Escaping (always prefer ? placeholders instead)
$safe = $db->quote($value);
$safe = $db->escapeLike($value);      // escapes % and _

// Schema manager
$sm = $db->getSchemaManager();
```

---

## Schema Manager (`$db->getSchemaManager()`)

```php
$sm = \XF::db()->getSchemaManager();

// Create table
$sm->createTable('xf_my_table', function(\XF\Db\Schema\Create $table) {
    $table->addColumn('id', 'int')->autoIncrement();           // becomes PK
    $table->addColumn('user_id', 'int')->setDefault(0);
    $table->addColumn('title', 'varchar', 150)->setDefault('');
    $table->addColumn('body', 'mediumtext')->nullable(true);
    $table->addColumn('options', 'mediumblob')->nullable(true); // for JSON_ARRAY columns
    $table->addColumn('count', 'int')->setDefault(0);
    $table->addColumn('is_active', 'tinyint', 1)->setDefault(1);
    $table->addColumn('created_date', 'int')->setDefault(0);
    $table->addKey('user_id');
    $table->addKey(['user_id', 'created_date'], 'idx_user_date');
    $table->addUniqueKey('title', 'unique_title');
});

// Alter table (only specify what changes — existing def retained)
$sm->alterTable('xf_my_table', function(\XF\Db\Schema\Alter $table) {
    $table->addColumn('new_col', 'varchar', 50)->setDefault('');
    $table->changeColumn('title')->length(255);
    $table->changeColumn('count')->unsigned(false);     // allow negatives
    $table->renameColumn('old_name', 'new_name');
    $table->dropColumns('col_to_remove');               // string or array
    $table->addKey('new_col');
    $table->dropIndexes('old_idx');
});

// Drop / rename
$sm->dropTable('xf_my_table');
$sm->renameTable('xf_old', 'xf_new');

// Checks
$sm->tableExists('xf_my_table');                        // bool
$sm->columnExists('xf_my_table', 'col');               // bool

// Column modifiers
$table->addColumn('x', 'int')
    ->setDefault(0)
    ->unsigned(false)           // allow negative (default: unsigned)
    ->nullable(true)            // allow NULL (default: NOT NULL)
    ->autoIncrement()           // AUTO_INCREMENT + primary key
    ->length(255)               // for varchar/int length
    ->after('other_col');       // column position
```

---

## Entity Manager (`\XF::em()`)

```php
$em = \XF::em();

// Find by primary key
$user   = $em->find('XF:User', 123);                          // entity or null
$user   = $em->find('XF:User', 123, ['Admin', 'Profile']);    // with eager relations

// Find or exception (throws if not found)
$user   = $em->findOneOrException('XF:User', 123);

// Find with conditions
$user   = $em->findOne('XF:User', ['username' => 'John']);

// Create new (unsaved) entity
$thing  = $em->create('Demo\AddOn:Thing');

// Finder shorthand
$finder = $em->getFinder('XF:Thread');
```

---

## Finder

```php
$finder = \XF::finder('XF:User');

// Conditions
->where('user_id', 1)                           // = (implied)
->where('user_id', '>', 100)                    // with operator
->where('user_state', 'valid')
->where('register_date', '>=', \XF::$time - 86400 * 7)
->where([                                       // array form (AND)
    'user_state' => 'valid',
    ['message_count', '>', 0],
])
->whereOr(                                      // OR conditions
    ['user_state', '<>', 'valid'],
    ['message_count', 0]
)
->whereOr([                                     // OR array
    ['user_state', '<>', 'valid'],
    ['message_count', 0],
    ['is_banned', 1],
])
->whereIn('user_id', [1, 2, 3])
->whereNotIn('user_id', [4, 5])

// Joins (requires entity relations)
->with('Forum', true)                           // true = INNER JOIN
->with('User')                                  // LEFT JOIN
->with(['Forum', 'User'], true)
->with('Thread.Forum.Node.Permissions|' . $visitor->permission_combination_id)
->with('ConnectedAccounts|facebook')            // single keyed TO_MANY member

// Ordering
->order('message_count', 'DESC')
->order('register_date')                        // default ASC
->setDefaultOrder('created_date', 'DESC')       // overridable default

// Paging
->limit(10)
->limit(10, 100)                                // limit, offset
->limitByPage(3, 20)                            // page, perPage
->limitByPage(3, 20, 1)                         // +1 overfetch

// Terminal
->fetch()                                       // ArrayCollection
->fetch(10)                                     // fetch with limit
->fetchOne()                                    // single entity or null
->total()                                       // COUNT(*)
->pluckFrom('username')->fetch()                // array of one column
->pluckFrom('username', 'user_id')->fetch()     // keyed array
->getQuery()                                    // inspect SQL string

// Debug
\XF::dumpSimple($finder->getQuery());
```

---

## ArrayCollection methods

```php
$collection = $finder->fetch();

$collection->count()                            // number of items
$collection->first()                            // first entity or null
$collection->last()                             // last entity or null
$collection->toArray()                          // plain PHP array
$collection->keys()                             // array of keys
$collection->pluckNamed('relation')             // collection of related entities
$collection->pluckNamed('Thread', 'thread_id')  // keyed collection of related entities
$collection->groupBy('user_id')                 // group by field value
$collection->filter(fn($e) => $e->is_active)   // filter by callback
$collection->slice(0, 10)                       // slice
$collection->sliceToPage($page, $perPage)       // slice to a page
$collection->merge($other)                      // merge two collections
$collection->isEmpty()                          // bool
```

---

## Entity instance methods

```php
// State
$entity->isInsert()                             // new (never saved)
$entity->isUpdate()                             // existing row
$entity->exists()                               // has a PK value in DB
$entity->isChanged('column')                    // bool — field changed since save
$entity->isStateChanged('column', 'value')      // 'enter'|'leave'|false
$entity->getExistingValue('column')             // value before current change
$entity->getErrors()                            // validation errors array

// Read
$entity->column_name                            // calls getter if defined
$entity->column_name_                           // bypass getter, raw DB value
$entity->RelationName                           // get related entity (lazy-loads)
$entity->get('column')                          // same as ->column
$entity->toArray()                              // all columns as array
$entity->toApiResult()                          // for REST API responses

// Write
$entity->column_name = $value;                  // calls verifyXxx if defined
$entity->set('column', $value);
$entity->bulkSet(['col1' => $v1, 'col2' => $v2]);

// Persist
$entity->save();                                // full lifecycle
$entity->save(false);                           // skip preSave (e.g., called again)
$entity->delete();
$entity->fastUpdate('column', $value);          // immediate single-column UPDATE, no lifecycle

// Relations
$entity->getRelationOrDefault('RelName')         // get or create unsaved relation entity
$entity->getRelationOrDefault('RelName', false)  // false = don't cache
$entity->hydrateRelation('RelName', $entity)     // manually set a relation
$entity->addCascadedSave($otherEntity)           // save another entity in same transaction
$entity->setError('Error message')
```

---

## Controller helpers

```php
// In a controller action method:
$this->view('Addon:ViewClass', 'template_name', $viewParams)
$this->redirect($this->buildLink('route'))
$this->redirect($url, 'Flash message', 'permanent')
$this->redirectPermanently($url)
$this->error('Error message', 404)
$this->message('Success message')
$this->noPermission()
$this->notFound()
throw $this->exception($this->error('Fatal'))
$this->rerouteController('XF:Forum', 'index', ['node_id' => 2])

// Input
$this->filter('key', 'str')
$this->filter(['a' => 'uint', 'b' => 'str', 'c' => 'bool'])
$this->filterPage()                     // current page, min 1
$this->request()                        // \XF\Http\Request

// Guards
$this->assertPostOnly()
$this->assertValidCsrfToken()
$this->assertRegistrationRequired()
$this->assertRecordExists('XF:Thread', $id, ['Forum'])   // throws notFound if null
$this->assertCanonicalUrl($url)                          // redirect if URL is wrong
$this->assertValidPage($page, $perPage, $total, 'route') // 404 if page > max

// Factories (same as \XF:: but scoped to request app)
$this->repository('XF:Thread')
$this->finder('XF:Thread')
$this->em()
$this->options()
$this->app()
$this->service('XF:Thread\Creator', $forum)
$this->buildLink('threads', $thread, ['page' => 2])

// FormAction
$form = $this->formAction();
$form->basicEntitySave($entity, $this->filter([...]));
$form->setup(function() use ($entity) { /* setup phase */ });
$form->validate(function(\XF\Mvc\FormAction $form) { /* validate phase */ });
$form->apply(function() use ($entity) { /* apply phase */ });
$form->complete(function() { /* complete phase */ });
$form->run();
```

---

## App container services

```php
$app = \XF::app();

$app->jobManager()->enqueue('Addon:JobClass', ['key' => 'val'])
$app->jobManager()->enqueueUnique('unique_key', 'Addon:JobClass', [])
$app->jobManager()->runUnique(...)

$app->mailer()->newMail()
    ->setTo($email, $username)
    ->setTemplate('template_name', ['var' => $val])
    ->send()

$app->http()->reader()->getUntrusted($url, [], $error)

$app->registry()->set('key', $data)
$app->registry()->get('key')
$app->registry()->delete('key')

$app->simpleCache()->setValue('Addon\Name', 'key', $value)
$app->simpleCache()->getValue('Addon\Name', 'key')

$app->criteria('XF:User', $savedCriteria)
$app->criteria('Addon:CustomType', $data)

$app->router('public')->buildLink('canonical:route', $entity)
$app->router('admin')->buildLink('admin-route')

$app->request()->getIp()
$app->request()->isSecure()
$app->request()->getServer('HTTP_HOST')

$app->logException($e, false, 'prefix: ')

$app->getContentTypeField('attachment_handler_class')          // all handlers
$app->getContentTypeFieldValue('post', 'attachment_handler_class')  // one handler
```

---

## Filter types (for `$this->filter()`)

| Type | PHP type returned |
|------|------------------|
| `str` / `string` | string |
| `int` | int (allows negative) |
| `uint` | int (min 0) |
| `posint` | int (min 1) |
| `float` | float |
| `bool` | bool |
| `array` | array (shallow filter) |
| `array-bool` | array of bools |
| `json-array` | decoded JSON array |
| `datetime` | int Unix timestamp |
| `str[]` | array of strings |
| `uint[]` | array of unsigned ints |
| `int[]` | array of ints |

---

## Permission checks

```php
// Global permission (group + permission ID)
\XF::visitor()->hasPermission('forum', 'postThread')
\XF::visitor()->hasPermission('general', 'manageAddOns')

// Node/content permission
\XF::visitor()->hasNodePermission($nodeId, 'postThread')
\XF::visitor()->hasContentPermission($contentType, $contentId, $permId)

// Admin check
\XF::visitor()->is_admin
\XF::visitor()->isSuperAdmin()

// User state
\XF::visitor()->user_id                 // 0 = guest
\XF::visitor()->is_banned
```
