# Controllers and Routing

## Routing Overview

XenForo maps URLs to controllers through routes defined in Admin CP > Development > Routes. There are two route types: **Public** (front-end) and **Admin** (Admin CP).

A URL like `index.php?members/your-name.1` is parsed as:
- Route prefix: `members`
- Action: (none — defaults to `actionIndex`)
- Route parameters: `your-name.1`

---

## Route Configuration

### Fields

| Field | Description |
|-------|-------------|
| Route prefix | The path segment after `index.php?` (e.g., `portal`, `members`) |
| Route format | Parameter extraction pattern (e.g., `:int<thread_id,title>/:page`) |
| Sub-name | Makes a child route (e.g., sub-name `following` on prefix `members`) |
| Controller | Short class name of the controller (e.g., `Demo\Portal:Portal`) |
| Section context | Navigation tab ID to highlight when on this route |
| Add-on | Which add-on owns this route |

### Route Format Syntax

```
:int<thread_id,title>/:page
```

| Pattern | Meaning |
|---------|---------|
| `:int<column_id>` | Integer parameter, maps to `column_id` in ParameterBag |
| `:int<id,title>` | Integer with slug prefix — `title-here.123` in URL |
| `:page` | Page number shortcut — becomes `page-2` in URL |
| `:string<key>` | String parameter |

### Sub-names

Sub-names split routes further. The route `members/following` with sub-name `following` on prefix `members` will match `index.php?members/anyone.1/following` and route to a different controller/action than the base `members/` route.

---

## Route Parameters — ParameterBag

When a route is matched, URL parameters are wrapped in a `ParameterBag` object passed to every controller action:

```php
public function actionView(\XF\Mvc\ParameterBag $params)
{
    $threadId = $params->thread_id;
    $page = $params->page ?: 1;
}
```

---

## Building Links

### In PHP

```php
// Simple link
$url = $this->buildLink('portal');

// Link with entity data
$url = $this->buildLink('threads', $thread);

// Link with page
$url = $this->buildLink('forums', $forum, ['page' => 2]);

// Canonical link (absolute URL with domain)
$url = $this->buildLink('canonical:threads', $thread);

// Outside a controller
$url = \XF::app()->router('public')->buildLink('threads', $thread);
```

### In Templates

```html
{{ link('threads', $thread) }}
{{ link('forums', $forum, {'page': 2}) }}
{{ link('posts', $post, {}, 'post-' . $post.post_id) }}
```

---

## Controller Structure

### Public Controller

```php
<?php

namespace Demo\Portal\Pub\Controller;

use XF\Mvc\ParameterBag;

class Portal extends \XF\Pub\Controller\AbstractController
{
    public function actionIndex()
    {
        $page = $this->filterPage();
        $perPage = $this->options()->demoPortalFeaturedPerPage ?? 10;

        /** @var \Demo\Portal\Repository\FeaturedThread $repo */
        $repo = $this->repository('Demo\Portal:FeaturedThread');

        $finder = $repo->findFeaturedThreadsForPortalView()
            ->limitByPage($page, $perPage);

        $viewParams = [
            'featuredThreads' => $finder->fetch(),
            'total'   => $finder->total(),
            'page'    => $page,
            'perPage' => $perPage,
        ];

        return $this->view('Demo\Portal:Index', 'demo_portal_view', $viewParams);
    }

    public function actionView(ParameterBag $params)
    {
        $item = $this->assertRecordExists('Demo\Portal:Item', $params->item_id);

        if (!$item->canView($error)) {
            return $this->noPermission($error);
        }

        $viewParams = ['item' => $item];

        return $this->view('Demo\Portal:Item\View', 'demo_portal_item_view', $viewParams);
    }
}
```

### Admin Controller

```php
<?php

namespace Demo\Portal\Admin\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\FormAction;

class Item extends \XF\Admin\Controller\AbstractController
{
    public function actionIndex()
    {
        $items = $this->finder('Demo\Portal:Item')
            ->order('created_date', 'DESC')
            ->fetch();

        return $this->view('Demo\Portal:Item\List', 'demo_portal_item_list', [
            'items' => $items,
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $item = $this->assertRecordExists('Demo\Portal:Item', $params->item_id);

        return $this->itemAddEdit($item);
    }

    public function actionAdd()
    {
        /** @var \Demo\Portal\Entity\Item $item */
        $item = $this->em()->create('Demo\Portal:Item');

        return $this->itemAddEdit($item);
    }

    protected function itemAddEdit(\Demo\Portal\Entity\Item $item)
    {
        $viewParams = ['item' => $item];

        return $this->view('Demo\Portal:Item\Edit', 'demo_portal_item_edit', $viewParams);
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        if ($params->item_id) {
            $item = $this->assertRecordExists('Demo\Portal:Item', $params->item_id);
        } else {
            $item = $this->em()->create('Demo\Portal:Item');
        }

        $this->itemSaveProcess($item)->run();

        return $this->redirect($this->buildLink('demo-portal/items'));
    }

    protected function itemSaveProcess(\Demo\Portal\Entity\Item $item): FormAction
    {
        $form = $this->formAction();

        $input = $this->filter([
            'title'       => 'str',
            'description' => 'str',
            'is_enabled'  => 'bool',
        ]);

        $form->basicEntitySave($item, $input);

        return $form;
    }

    public function actionDelete(ParameterBag $params)
    {
        $item = $this->assertRecordExists('Demo\Portal:Item', $params->item_id);

        if ($this->isPost()) {
            $item->delete();
            return $this->redirect($this->buildLink('demo-portal/items'));
        }

        return $this->view('Demo\Portal:Item\Delete', 'demo_portal_item_delete', [
            'item' => $item,
        ]);
    }
}
```

---

## Reply Types

### View Reply

```php
public function actionExample()
{
    $viewParams = [
        'hello' => 'Hello',
        'world' => 'world!',
    ];
    return $this->view('Demo:Example', 'demo_example', $viewParams);
}
```

Arguments:
1. View class short name (may not actually exist — serves as extension point)
2. Template name
3. Array of template parameters

### Redirect Reply

```php
// Temporary redirect (303)
return $this->redirect($this->buildLink('demo/example'));

// With flash message (shown on AJAX requests)
return $this->redirect($this->buildLink('demo/example'), 'Changes saved!');

// Permanent redirect (301)
return $this->redirect($this->buildLink('demo/example'), '', 'permanent');

// Shorthand permanent
return $this->redirectPermanently($this->buildLink('demo/example'));
```

### Error Reply

```php
return $this->error('The thing you are looking for could not be found.', 404);
```

### Message Reply

```php
return $this->message('Operation completed successfully.');
```

### Exception Reply

Used to interrupt controller flow from anywhere (including helper methods):

```php
throw $this->exception($this->error('An unexpected error occurred.'));
throw $this->exception($this->noPermission());
throw $this->exception($this->redirect($this->buildLink('index')));
```

### Reroute Reply

Routes to a different action without a redirect:

```php
public function actionOldUrl()
{
    return $this->rerouteController(__CLASS__, 'newUrl');
}

// Pass parameters to the target action
return $this->rerouteController(__CLASS__, 'view', $params);
```

---

## Input Filtering

### filter() Method

```php
// Single value
$title = $this->filter('title', 'str');
$count = $this->filter('count', 'uint');
$enabled = $this->filter('is_enabled', 'bool');
$page = $this->filterPage(); // shorthand for page filter

// Multiple values at once
$input = $this->filter([
    'title'       => 'str',
    'description' => 'str',
    'count'       => 'uint',
    'is_enabled'  => 'bool',
    'tags'        => 'array',
    'criteria'    => 'array',
]);
```

### Filter Types

| Type | Description |
|------|-------------|
| `str` | String, trimmed |
| `string` | Alias for `str` |
| `int` | Integer (signed) |
| `uint` | Unsigned integer (0+) |
| `posint` | Positive integer (1+) |
| `float` | Float |
| `bool` | Boolean |
| `array` | Array |
| `array-bool` | Array of booleans |
| `array-uint` | Array of unsigned integers |
| `array-str` | Array of strings |
| `json-array` | JSON-decoded array |
| `email` | Email address |
| `url` | URL |
| `ip` | IP address |
| `datetime` | Date/time string → timestamp |
| `date` | Date string → timestamp |
| `file` | Uploaded file |

---

## Assertions

```php
// Assert record exists, throw 404 error if not found
$thread = $this->assertRecordExists('XF:Thread', $params->thread_id);

// Assert exists with custom error message
$item = $this->assertRecordExists('Demo:Item', $params->item_id, null, 'item_not_found');

// Assert exists with custom finder callback
$thread = $this->assertRecordExists('XF:Thread', $params->thread_id, function($finder) {
    $finder->with('Forum', true)->with('User');
});

// Assert POST request
$this->assertPostOnly();

// Assert valid CSRF token
$this->assertValidCsrfToken($this->filter('_xfToken', 'str'));

// Check if this is a POST request
if ($this->isPost()) { ... }

// No permission error
return $this->noPermission();
return $this->noPermission(\XF::phrase('custom_error_phrase'));
```

---

## FormAction Phases

`FormAction` runs in phase order: `setup` → `validate` → `apply` → `complete`.

```php
protected function itemSaveProcess(\Demo\Portal\Entity\Item $item): \XF\Mvc\FormAction
{
    $form = $this->formAction();

    // setup: runs first — get inputs, set values
    $form->setup(function() use ($item) {
        $item->title = $this->filter('title', 'str');
        $item->description = $this->filter('description', 'str');
    });

    // validate: check validity, return false to stop
    $form->validate(function(\XF\Mvc\FormAction $form) use ($item) {
        if (!$item->title) {
            $form->logError('Title cannot be empty.', 'title');
        }
    });

    // apply: save entities
    $form->apply(function() use ($item) {
        $item->save();
    });

    // complete: post-save actions
    $form->complete(function() use ($item) {
        // Send notification email, etc.
    });

    // Or use basicEntitySave() to handle apply phase automatically
    // $form->basicEntitySave($item, $inputArray);

    return $form;
}
```

---

## Pre-dispatch Hooks

Override `preDispatch()` or `preDispatchType()` to run logic before all actions:

```php
protected function preDispatch($action, ParameterBag $params)
{
    parent::preDispatch($action, $params);

    // Load shared data used by multiple actions
    if ($this->request->getParam('forum_id')) {
        $this->forum = $this->assertRecordExists('XF:Forum', $this->request->getParam('forum_id'));
    }
}
```

---

## Modifying Controller Actions (Proper Extension)

When extending an existing controller action, always call the parent and check the reply type:

```php
// src/addons/Demo/XF/Pub/Controller/Member.php
public function actionIndex()
{
    $reply = parent::actionIndex();

    if ($reply instanceof \XF\Mvc\Reply\View) {
        // Add or modify view params
        $reply->setParam('demoExtra', 'extra value');

        // Replace a param
        $reply->setParam('title', 'Custom title');

        // Read existing params
        $existingItems = $reply->getParam('items');
    }

    return $reply;
}
```

---

## Controller Helper Methods

```php
// Get current visitor
$visitor = \XF::visitor();

// Get option value
$value = $this->options()->optionName;

// Get app instance
$app = $this->app();

// Get entity manager
$em = $this->em();

// Create entity
$item = $this->em()->create('Demo:Item');

// Get finder
$finder = $this->finder('Demo:Item');

// Get repository
$repo = $this->repository('Demo:Item');

// Get service
$creator = $this->service('XF:Thread\Creator', $forum);

// Get current page
$page = $this->filterPage();

// Build link
$url = $this->buildLink('demo/portal');

// Get request object
$request = $this->request();

// Get DB adapter
$db = $this->db();
```
