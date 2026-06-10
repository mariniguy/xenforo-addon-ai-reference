# XenForo PHP API Quick Reference

> XenForo 2.3+ — all methods verified against official docs and source

## \XF Facade — Static Methods

```php
// App container
\XF::app()                              // XF\App instance
\XF::app()->request()                   // XF\Http\Request
\XF::app()->router('public')            // Public router
\XF::app()->router('admin')             // Admin router
\XF::app()->templater()                 // XF\Template\Templater
\XF::app()->mailer()                    // Mail system
\XF::app()->jobManager()               // Job queue manager
\XF::app()->cache()                     // Cache adapter
\XF::app()->registry()                  // Data registry (key-value)

// Data access
\XF::em()                               // Entity Manager (\XF\Mvc\Entity\Manager)
\XF::db()                               // DB Adapter (\XF\Db\AbstractAdapter)
\XF::registry()                         // Data registry
\XF::options()                          // All XF options (\XF\Entity\Option collection)

// Factories
\XF::finder('XF:Thread')                // Finder for entity type
\XF::repository('XF:Thread')           // Repository instance
\XF::service('XF:Thread\Creator', $f)  // Service instance with args

// Current context
\XF::visitor()                          // Current user Entity (guest if not logged in)
\XF::language()                         // Current XF\Language
\XF::$time                              // Current Unix timestamp (int, cached)
\XF::$versionId                         // XenForo version ID integer
\XF::$debugMode                         // bool

// Phrases
\XF::phrase('phrase_name')              // \XF\Phrase object (lazy render)
\XF::phraseDeferred('phrase_name')      // Phrase for async/deferred render
\XF::phrase('name', ['key' => $val])    // Phrase with parameters

// Debugging
\XF::dump($var)                         // Symfony VarDumper interactive HTML output
\XF::dumpSimple($var)                   // Plain-text var_dump wrapped in <pre>

// Deferred / async
\XF::runLater(function() { ... });      // Execute closure after response is sent

// Class proxy
\XF::extendClass('XF\Entity\Thread')    // Return extended class name (respects extensions)
```

---

## Entity Manager

```php
$em = \XF::em();
// Or in controllers/services: $this->em()

// Create unsaved entity
$item = $em->create('Demo:Item');

// Find by primary key (returns Entity or null)
$thread = $em->find('XF:Thread', 123);

// Find with eager-loaded relations
$thread = $em->find('XF:Thread', 123, ['User', 'Forum', 'Forum.Node']);

// Find multiple by IDs — returns ArrayCollection keyed by PK
$users = $em->findByIds('XF:User', [1, 2, 3]);
$users = $em->findByIds('XF:User', [1, 2, 3], ['ConnectedAccounts']);

// Get finder / repository
$finder = $em->getFinder('XF:Thread');
$repo   = $em->getRepository('XF:Thread');
```

---

## Finder Methods (Chained)

```php
$finder = \XF::finder('XF:Thread');

// WHERE conditions
->where('discussion_state', 'visible')              // = operator implied
->where('reply_count', '>', 10)
->where('post_date', '>=', time() - 86400 * 7)
->where([                                            // array shorthand
    'discussion_state' => 'visible',
    ['reply_count', '>', 5],
])
->whereOr(                                           // OR two conditions
    ['user_state', '<>', 'valid'],
    ['message_count', 0]
)
->whereOr([                                          // OR many conditions
    ['a', 1], ['b', 2], ['c', 3]
])

// JOIN (relations must be defined in entity structure)
->with('User')                                       // LEFT JOIN
->with('Forum', true)                                // INNER JOIN (must exist)
->with(['User', 'Forum'], true)                      // multiple at once
->with('Thread.Forum.Node')                          // dot-notation deep join
->with('ConnectedAccounts|facebook')                 // TO_MANY single-key join

// Ordering
->order('post_date', 'DESC')
->order([['score', 'DESC'], ['post_date', 'DESC']])  // multi-column
->setDefaultOrder('post_date', 'DESC')               // only applies if no other order set

// Limiting / pagination
->limit(10)
->limit(10, 100)                                     // limit=10, offset=100
->limitByPage($page, $perPage)                       // auto-calculates offset
->limitByPage($page, $perPage, 1)                    // over-fetch by 1

// Execution
->fetch()                                            // ArrayCollection
->fetch(10)                                          // with limit shorthand
->fetchOne()                                         // single Entity or null
->total()                                            // COUNT(*) ignoring limit/offset
->pluckFrom('user_id')->fetch()                      // array of one column's values

// Debug
->getQuery()                                         // returns SQL string (use with dumpSimple)
```

---

## Entity Instance Methods

```php
$entity = \XF::em()->find('XF:Thread', 1);

// Property access
$entity->thread_id
$entity->title
$entity->User->username          // lazy-loads relation if not with()'d
$entity->last_activity_          // trailing _ bypasses getter

// Setting values
$entity->title = 'New title';
$entity->set('title', 'New title');
$entity->bulkSet(['title' => 'T', 'is_sticky' => true]);

// State checks
$entity->isInsert()                                  // true = new, not yet saved
$entity->isUpdate()                                  // true = existing record
$entity->isChanged('title')                          // changed since last save
$entity->getExistingValue('username')                // value before current change
$entity->isStateChanged('discussion_state', 'visible') // returns 'enter'|'leave'|false

// Persistence
$entity->save()                                      // INSERT or UPDATE
$entity->delete()                                    // DELETE
$entity->fastUpdate('view_count', $n)                // single-column UPDATE without loading

// Relations
$entity->getRelationOrDefault('FeaturedThread')       // get related or create default
$entity->getRelationOrDefault('FeaturedThread', false) // false = no auto-default values
$entity->addCascadedSave($related)                   // save $related when $entity saves

// Errors
$entity->error('msg', 'field')                       // add error
$entity->getErrors()                                 // ['field' => 'msg', ...]
$entity->preSave()                                   // run validation without saving
$entity->hasErrors()                                 // bool

// Options
$entity->setOption('admin_edit', true)
$entity->getOption('admin_edit')
```

---

## ArrayCollection Methods

```php
$c = $finder->fetch();

count($c)                        // or $c->count()
$c->first()                      // first element
$c->last()                       // last element
$c->keys()                       // array of collection keys

// Filtering
$c->filter(fn($e) => $e->is_enabled)
$c->filter(function(\XF\Entity\Thread $t) {
    return $t->canView();
})

// Slicing
$c->slice(0, 10)
$c->sliceToPage($page, $perPage)  // correct offset + rekey

// Named relation plucking
$threads = $featuredCollection->pluckNamed('Thread')
$posts   = $threads->pluckNamed('FirstPost', 'first_post_id')  // keyed by field

// Conversion / grouping
$c->toArray()
$c->groupBy('category_id')
$c->merge($otherCollection)

// Iteration
foreach ($c as $key => $entity) { ... }
```

---

## DB Adapter Methods

```php
$db = \XF::db();

// --- READS ---
$db->fetchRow('SELECT * FROM xf_user WHERE user_id = ?', 1)
   // returns ['user_id'=>1, 'username'=>'...', ...]  or false

$db->fetchOne('SELECT username FROM xf_user WHERE user_id = ?', 1)
   // returns scalar value or false

$db->fetchAll('SELECT * FROM xf_user LIMIT 10')
   // returns [['user_id'=>1,...], ['user_id'=>2,...], ...]

$db->fetchAllKeyed('SELECT * FROM xf_user LIMIT 10', 'user_id')
   // returns [1 => ['user_id'=>1,...], 2 => [...], ...]
   // NOTE: params go as 3rd arg when using fetchAllKeyed:
$db->fetchAllKeyed('SELECT * FROM xf_user WHERE user_state = ?', 'user_id', 'valid')

$db->fetchAllColumn('SELECT username FROM xf_user LIMIT 10')
   // returns ['Admin', 'Bob', ...]

// --- WRITES ---
$db->query('DELETE FROM xf_demo WHERE demo_id = ?', 42)

$db->insert('xf_demo', [
    'title'        => 'Test',
    'created_date' => time(),
])

$db->insertBulk('xf_demo', [
    ['title' => 'A', 'created_date' => time()],
    ['title' => 'B', 'created_date' => time()],
])

$db->update('xf_demo', ['title' => 'New'], 'demo_id = ?', 1)

$db->delete('xf_demo', 'demo_id = ?', 1)

$db->emptyTable('xf_demo')            // TRUNCATE

// --- UTILITY ---
$db->quote($value)                     // escape + quote a value
$db->lastInsertId()                    // last AUTO_INCREMENT value
$db->affectedRows()                    // rows affected by last write

// --- TRANSACTIONS ---
$db->beginTransaction()
$db->commit()
$db->rollback()
```

---

## Schema Manager

```php
$sm = \XF::db()->getSchemaManager();
// In Setup.php: $sm = $this->schemaManager();

// Create table
$sm->createTable('xf_demo_item', function(\XF\Db\Schema\Create $table) {
    $table->addColumn('item_id', 'int')->autoIncrement();
    $table->addColumn('user_id', 'int')->setDefault(0);
    $table->addColumn('title', 'varchar', 150);
    $table->addColumn('slug', 'varchar', 150)->setDefault('');
    $table->addColumn('description', 'mediumtext')->nullable(true);
    $table->addColumn('created_date', 'int')->setDefault(0);
    $table->addColumn('is_enabled', 'tinyint')->setDefault(1);
    $table->addColumn('extra_data', 'mediumblob')->nullable(true);
    $table->addColumn('price', 'decimal', '10,2')->setDefault('0.00');
    // Keys
    $table->addUniqueKey('slug', 'slug_unique');
    $table->addKey(['user_id', 'created_date'], 'user_date');
});

// Alter table
$sm->alterTable('xf_demo_item', function(\XF\Db\Schema\Alter $table) {
    $table->addColumn('view_count', 'int')->setDefault(0)->after('title');
    $table->changeColumn('title')->length(200);      // change only length, rest preserved
    $table->changeColumn('is_enabled')->setDefault(0)->unsigned(false);
    $table->dropColumns('old_column');
    $table->dropColumns(['old1', 'old2']);
    $table->addKey('view_count');
    $table->addUniqueKey('slug');
    $table->dropIndexes('old_index_name');
    $table->renameColumn('old_name', 'new_name');
});

// Drop table
$sm->dropTable('xf_demo_item');

// Existence checks
$sm->tableExists('xf_demo_item')          // bool
$sm->columnExists('xf_demo_item', 'slug') // bool

// Column chainable modifiers
$table->addColumn('field', 'int')
    ->setDefault(0)           // DEFAULT value
    ->nullable(true)          // allow NULL
    ->unsigned(false)         // allow negative ints (unsigned true by default)
    ->autoIncrement()         // AUTO_INCREMENT
    ->after('other_column')   // column position
    ->length(255)             // for varchar/char
    ->precision(10, 2)        // for decimal
```

---

## App Container Services Reference

```php
$app = \XF::app();

// Core services
$app->db()                            // \XF\Db\AbstractAdapter
$app->em()                            // \XF\Mvc\Entity\Manager
$app->templater()                     // \XF\Template\Templater
$app->mailer()                        // \XF\Mail\Mailer
$app->session()                       // \XF\Session\Session
$app->permissions()                   // \XF\Permission\Builder
$app->cache()                         // \XF\Cache\AbstractCache
$app->registry()                      // \XF\DataRegistry
$app->jobManager()                    // \XF\Job\Manager

// Factories
$app->finder('XF:Thread')
$app->repository('XF:Thread')
$app->service('XF:Thread\Creator', $forum)
$app->criteria('XF:User', $criteriaData)

// Routing
$app->router('public')->buildLink('threads', $thread)
$app->router('public')->buildLink('canonical:threads', $thread)
$app->router('admin')->buildLink('users')

// Content type handler
$app->getContentTypeField('attachment_handler_class')
    // ['post' => 'XF\Attachment\PostHandler', ...]
$app->getContentTypeFieldValue('post', 'attachment_handler_class')
    // 'XF\Attachment\PostHandler'
```

---

## Job Manager API

```php
$jm = \XF::app()->jobManager();

// Enqueue (runs on next page load)
$jm->enqueue('Demo:RebuildCache')
$jm->enqueue('Demo:RebuildCache', ['start' => 0, 'batch' => 100])
$jm->enqueue('Demo:RebuildCache', [], true)          // unique = only one at a time

// Unique with custom key
$jm->enqueueUnique('demo_rebuild_key', 'Demo:RebuildCache', [])

// Check if unique job is queued
$job = $jm->getUniqueJob('demo_rebuild_key')   // returns job row or false

// Cancel
$jm->cancelJob($jobId)
```

---

## Common Repository Quick Reference

```php
// Attachments — batch-hydrate from single query
/** @var \XF\Repository\Attachment $r */
$r = \XF::repository('XF:Attachment');
$r->addAttachmentsToContent($posts, 'post');   // $posts = ArrayCollection keyed by post_id

// Alerts — send an alert
/** @var \XF\Repository\Alert $r */
$r = \XF::repository('XF:Alert');
$r->alert($recipientUser, $senderUser, 'post', $postId, 'quote', []);
$r->alert($recipientUser, null, 'demo_item', $itemId, 'created', []);

// News feed — publish an entry
/** @var \XF\Repository\NewsFeed $r */
$r = \XF::repository('XF:NewsFeed');
$r->publish('thread', $thread->thread_id, 'insert', $thread->user_id, []);

// User group — rebuild permissions
/** @var \XF\Repository\UserGroup $r */
$r = \XF::repository('XF:UserGroup');
$r->rebuildUserGroupPermissions();

// Node — get tree
/** @var \XF\Repository\Node $r */
$r = \XF::repository('XF:Node');
$nodeTree = $r->createNodeTree($r->getFullNodeList());

// Trophy — award to user
/** @var \XF\Repository\Trophy $r */
$r = \XF::repository('XF:Trophy');
$r->updateTrophiesForUser(\XF::visitor());
```

---

## Controller Helper Methods

```php
// Available inside any controller that extends AbstractController

$this->em()                           // Entity Manager
$this->db()                           // DB Adapter
$this->app()                          // App
$this->request()                      // HTTP Request
$this->options()                      // XF Options

$this->finder('Demo:Item')            // Finder
$this->repository('Demo:Item')        // Repository
$this->service('Demo:Item\Creator', $arg) // Service

$this->filter('title', 'str')         // Filter single input
$this->filter(['title' => 'str', 'count' => 'uint'])  // Filter multiple
$this->filterPage()                   // Get current page number

$this->buildLink('demo/items')
$this->buildLink('demo/items', $item)
$this->buildLink('demo/items', $item, ['page' => 2])

$this->assertRecordExists('Demo:Item', $params->item_id)
$this->assertRecordExists('Demo:Item', $id, function($f) { $f->with('User'); })
$this->assertPostOnly()
$this->assertValidCsrfToken($token)
$this->isPost()

// Reply helpers
$this->view('Demo:View', 'template_name', $viewParams)
$this->redirect($url)
$this->redirect($url, 'Flash message')
$this->redirectPermanently($url)
$this->error('Error message', 404)
$this->message('Success message')
$this->noPermission()
$this->noPermission(\XF::phrase('custom_error'))
throw $this->exception($this->error('msg'))
$this->rerouteController(__CLASS__, 'action')
$this->formAction()                   // FormAction instance
```
