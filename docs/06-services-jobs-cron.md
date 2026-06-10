# Services, Jobs, and Cron

## The Service Pattern

Services implement the "setup and go" pattern: you configure options on the service, then call a method to execute the operation. They live in the `Service/` directory of your add-on.

Services are the primary location for business logic — not controllers, not entities.

### Creating a Service

```php
<?php

namespace Demo\Portal\Service\FeaturedThread;

class Creator extends \XF\Service\AbstractService
{
    /** @var \XF\Entity\Thread */
    protected $thread;

    protected $featureUserId = 0;

    protected $notify = true;

    public function __construct(\XF\App $app, \XF\Entity\Thread $thread)
    {
        parent::__construct($app);
        $this->thread = $thread;
    }

    public function setFeatureUser(\XF\Entity\User $user): self
    {
        $this->featureUserId = $user->user_id;
        return $this;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;
        return $this;
    }

    /**
     * @return \Demo\Portal\Entity\FeaturedThread
     * @throws \XF\PrintableException
     */
    public function feature(): \Demo\Portal\Entity\FeaturedThread
    {
        $featuredThread = $this->setupFeaturedThread();

        $this->finalSetup();

        $featuredThread->save();

        $this->afterFeature($featuredThread);

        return $featuredThread;
    }

    protected function setupFeaturedThread(): \Demo\Portal\Entity\FeaturedThread
    {
        /** @var \Demo\Portal\Entity\FeaturedThread $featuredThread */
        $featuredThread = $this->thread->getRelationOrDefault('FeaturedThread');
        $featuredThread->featured_date = \XF::$time;
        $featuredThread->feature_user_id = $this->featureUserId;

        return $featuredThread;
    }

    protected function finalSetup(): void
    {
        // Last-chance modifications before save
    }

    protected function afterFeature(\Demo\Portal\Entity\FeaturedThread $featuredThread): void
    {
        $this->thread->fastUpdate('demo_portal_featured', true);

        if ($this->notify) {
            $this->sendNotifications($featuredThread);
        }
    }

    protected function sendNotifications(\Demo\Portal\Entity\FeaturedThread $featuredThread): void
    {
        // Send alerts/emails as needed
    }
}
```

### Using a Service

```php
// In a controller
/** @var \Demo\Portal\Service\FeaturedThread\Creator $creator */
$creator = $this->service('Demo\Portal:FeaturedThread\Creator', $thread);

$creator->setFeatureUser(\XF::visitor());
$creator->setNotify(true);

try {
    $featuredThread = $creator->feature();
} catch (\XF\PrintableException $e) {
    return $this->error($e->getMessage());
}

return $this->redirect($this->buildLink('portal'));
```

### ValidateAndSavableTrait

For services that validate and save an entity:

```php
<?php

namespace Demo\Portal\Service\Item;

class Editor extends \XF\Service\AbstractService
{
    use \XF\Service\ValidateAndSavableTrait;

    /** @var \Demo\Portal\Entity\Item */
    protected $item;

    public function __construct(\XF\App $app, \Demo\Portal\Entity\Item $item)
    {
        parent::__construct($app);
        $this->item = $item;
    }

    public function setTitle(string $title): self
    {
        $this->item->title = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->item->description = $description;
        return $this;
    }

    // Required by ValidateAndSavableTrait
    protected function _validate(): array
    {
        $this->item->preSave();
        return $this->item->getErrors();
    }

    // Required by ValidateAndSavableTrait
    protected function _save(): \Demo\Portal\Entity\Item
    {
        $this->item->save();
        return $this->item;
    }
}
```

Using a service with ValidateAndSavableTrait:

```php
/** @var \Demo\Portal\Service\Item\Editor $editor */
$editor = $this->service('Demo\Portal:Item\Editor', $item);
$editor->setTitle($this->filter('title', 'str'));
$editor->setDescription($this->filter('description', 'str'));

if (!$editor->validate($errors)) {
    return $this->error($errors);
}

$item = $editor->save();

return $this->redirect($this->buildLink('demo-portal/items', $item));
```

---

## Extending Core Services

```php
// src/addons/Demo/Portal/XF/Service/Thread/Creator.php
<?php

namespace Demo\Portal\XF\Service\Thread;

class Creator extends XFCP_Creator
{
    protected $featureThread = null;

    public function setFeatureThread(bool $feature): self
    {
        $this->featureThread = $feature;
        return $this;
    }

    protected function _save(): \XF\Entity\Thread
    {
        $thread = parent::_save();

        if ($this->featureThread !== null && $thread->discussion_state === 'visible') {
            /** @var \Demo\Portal\Entity\FeaturedThread $featuredThread */
            $featuredThread = $thread->getRelationOrDefault('FeaturedThread', false);

            if ($this->featureThread) {
                if (!$featuredThread->exists()) {
                    $featuredThread->save();
                    $thread->fastUpdate('demo_portal_featured', true);
                }
            } else {
                if ($featuredThread->exists()) {
                    $featuredThread->delete();
                    $thread->fastUpdate('demo_portal_featured', false);
                }
            }
        }

        return $thread;
    }
}
```

---

## Job System

Jobs handle long-running or deferred tasks. They run in the background via the deferred runner (triggered on subsequent page loads or via cron).

### Creating a Job

```php
<?php

namespace Demo\Portal\Job;

use XF\Job\AbstractJob;

class RebuildFeaturedCache extends AbstractJob
{
    protected $defaultData = [
        'start'     => 0,
        'batch'     => 50,
    ];

    /**
     * Run the job. Return a JobResult to indicate status.
     */
    public function run(int $maxRunTime): \XF\Job\JobResult
    {
        $startTime = microtime(true);

        $db = $this->app->db();
        $em = $this->app->em();

        // Fetch a batch of items starting from where we left off
        $threads = $this->app->finder('XF:Thread')
            ->where('thread_id', '>', $this->data['start'])
            ->order('thread_id', 'ASC')
            ->limit($this->data['batch'])
            ->fetch();

        if (!$threads->count()) {
            // No more items — job complete
            return $this->complete();
        }

        foreach ($threads as $thread) {
            if (microtime(true) - $startTime >= $maxRunTime) {
                // Running out of time — save progress and resume
                break;
            }

            // Do work on each thread
            $hasFeaturedRecord = (bool) $db->fetchOne(
                'SELECT 1 FROM xf_demo_portal_featured_thread WHERE thread_id = ?',
                $thread->thread_id
            );

            $thread->fastUpdate('demo_portal_featured', $hasFeaturedRecord);

            $this->data['start'] = $thread->thread_id;
        }

        // Resume in next run
        return $this->resume();
    }

    public function getStatusMessage(): string
    {
        return sprintf('Rebuilding featured cache (offset: %d)', $this->data['start']);
    }

    public function canCancel(): bool
    {
        return true;
    }

    public function canTriggerByChoice(): bool
    {
        return true;
    }
}
```

### Enqueuing a Job

```php
// Enqueue to run in the background (runs on next page load)
$this->app()->jobManager()->enqueue('Demo\Portal:RebuildFeaturedCache');

// Enqueue with data
$this->app()->jobManager()->enqueue('Demo\Portal:RebuildFeaturedCache', [
    'start' => 0,
    'batch' => 100,
]);

// Also available statically
\XF::app()->jobManager()->enqueue('Demo\Portal:RebuildFeaturedCache', [], true);
// Third arg: unique (prevents duplicate jobs of same type)

// From an entity's _postSave
$this->app()->jobManager()->enqueue('XF:UserRenameCleanUp', [
    'originalUserId'   => $this->user_id,
    'originalUserName' => $this->getExistingValue('username'),
    'newUserName'      => $this->username,
]);
```

### Job Result Methods

```php
// Indicate job is complete
return $this->complete();

// Indicate job should resume (saves $this->data state)
return $this->resume();
```

### Deferred Job Pattern

For very quick one-off tasks that should run after the current request:

```php
\XF::runLater(function() use ($threadId) {
    $thread = \XF::em()->find('XF:Thread', $threadId);
    if ($thread) {
        $thread->rebuildCounters();
    }
});
```

---

## Cron Entries

Cron entries are recurring scheduled tasks defined in the Admin CP (Development > Cron entries) or via XML export.

### Cron entry fields

| Field | Description |
|-------|-------------|
| Cron entry ID | Unique identifier (e.g., `demoPortalCleanup`) |
| Title | Human-readable name |
| Cron class | Fully qualified PHP class (e.g., `Demo\Portal\Cron\Cleanup`) |
| Cron method | Static method name (e.g., `run`) |
| Run rules | Minutes, hours, days, months (crontab-style) |
| Active | Whether the cron is enabled |

### Writing a Cron Class

```php
<?php

namespace Demo\Portal\Cron;

class Cleanup
{
    public static function run(): void
    {
        $cutoff = \XF::$time - (86400 * 30); // 30 days ago

        $db = \XF::db();
        $db->query(
            'DELETE FROM xf_demo_portal_featured_thread WHERE featured_date < ?',
            $cutoff
        );

        // Update cache
        \XF::repository('Demo\Portal:FeaturedThread')->rebuildFeaturedCount();
    }

    public static function rebuildTrophies(): void
    {
        /** @var \XF\Repository\Trophy $trophyRepo */
        $trophyRepo = \XF::repository('XF:Trophy');
        $trophyRepo->updateTrophiesForAllUsers();
    }
}
```

### Triggering from CLI

```bash
php cmd.php xf:run-cron demoPortalCleanup
```

---

## Job Manager API

```php
$jobManager = \XF::app()->jobManager();

// Enqueue a job
$jobManager->enqueue('Demo\Portal:RebuildCache');

// Enqueue uniquely (one instance at a time)
$jobManager->enqueue('Demo\Portal:RebuildCache', [], true);

// Enqueue with priority (lower = runs first)
$jobManager->enqueueUnique('demo_rebuild', 'Demo\Portal:RebuildCache', []);

// Cancel a job
$jobManager->cancelJob($jobId);

// Get running jobs
$jobs = $jobManager->getRunningJobs();

// Check if a job type is queued
$isQueued = $jobManager->getUniqueJob('demo_rebuild');
```

---

## Quick Example: Service + Job Together

The typical flow for a bulk operation:

```php
// Controller action triggers the service
public function actionBulkFeature()
{
    $this->assertPostOnly();

    $threadIds = $this->filter('thread_ids', 'array-uint');

    if (!$threadIds) {
        return $this->error('No threads selected.');
    }

    // Enqueue a job to process the batch
    \XF::app()->jobManager()->enqueue('Demo\Portal:BulkFeature', [
        'thread_ids' => $threadIds,
        'user_id'    => \XF::visitor()->user_id,
    ]);

    return $this->message('Threads are being featured in the background.');
}
```

```php
// Job processes the batch
class BulkFeature extends AbstractJob
{
    public function run(int $maxRunTime): \XF\Job\JobResult
    {
        if (empty($this->data['thread_ids'])) {
            return $this->complete();
        }

        $threadId = array_shift($this->data['thread_ids']);

        $thread = $this->app->em()->find('XF:Thread', $threadId);
        if ($thread) {
            /** @var \Demo\Portal\Service\FeaturedThread\Creator $creator */
            $creator = $this->app->service('Demo\Portal:FeaturedThread\Creator', $thread);
            $creator->setNotify(false);
            $creator->feature();
        }

        if (empty($this->data['thread_ids'])) {
            return $this->complete();
        }

        return $this->resume();
    }
}
```
