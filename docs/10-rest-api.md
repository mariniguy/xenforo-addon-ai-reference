# REST API and Webhooks

> Source: [xenforo.com/docs/dev/](https://xenforo.com/docs/dev/) — API section

---

## Overview

XenForo includes a built-in REST API at `<board_url>/api/`. It uses JSON responses, standard HTTP methods, and supports both API key and OAuth 2.0 authentication.

Base URL example: `https://example.com/community/api/`

---

## Authentication

### API Key (simplest)

Create keys in **ACP → Setup → API keys**.

```http
GET /api/threads/123
XF-Api-Key: your_api_key_here
```

Three key types:
- **Guest key** — acts as a guest (no user context)
- **User key** — acts as a specific user
- **Super user key** — can act as any user, can bypass permissions

**Act as a specific user** with a super-user key:
```http
GET /api/me
XF-Api-Key: your_super_key
XF-Api-User: 42
```

**Bypass permissions** (super-user key only):
```http
POST /api/threads
XF-Api-Key: your_super_key
XF-Api-User: 1

api_bypass_permissions=1&node_id=5&title=Test&message=Hello
```

### OAuth 2.0

XenForo supports the authorization-code flow.

| Endpoint | URL |
|----------|-----|
| Authorize | `<board_url>/oauth2/authorize` |
| Token | `<board_url>/api/oauth2/token` |

**Get a token:**
```http
POST /api/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=AUTH_CODE_HERE
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&redirect_uri=https://your-app.com/callback
```

**Use the token:**
```http
GET /api/me
Authorization: Bearer ACCESS_TOKEN_HERE
```

**OAuth scopes:**

| Scope | Permission |
|-------|-----------|
| `read` | Read all accessible content |
| `write` | Write all accessible content |
| `thread:read` | Read threads |
| `thread:write` | Create/edit threads |
| `post:write` | Create/edit posts |
| `user:read` | Read user data |
| `user:write` | Modify user data |
| `conversation:read` | Read conversations |
| `conversation:write` | Send conversations |
| `attachment:write` | Upload attachments |
| `node:read` | Read node structure |
| `profile_post:write` | Write profile posts |
| `alert:read` | Read alerts |

---

## Request format

- Method: `GET`, `POST`, `DELETE` (XenForo uses POST for most writes, not PUT/PATCH)
- Body encoding: `application/x-www-form-urlencoded` or `multipart/form-data` (for uploads)
- Params can also go in the query string for GET requests
- All text must be UTF-8

---

## Response format

**Success (200):**
```json
{
    "thread": {
        "thread_id": 123,
        "title": "My Thread",
        "reply_count": 5,
        "view_count": 100,
        "post_date": 1700000000,
        "Forum": { "node_id": 2, "title": "General" },
        "User": { "user_id": 1, "username": "Admin" }
    }
}
```

**Error (4xx):**
```json
{
    "errors": [
        {
            "code": "api_key_not_found",
            "message": "API key provided in request was not found.",
            "params": []
        }
    ]
}
```

**Validation errors (400):**
```json
{
    "errors": [
        {
            "code": "validation_error",
            "message": "Please enter a valid title.",
            "params": { "field": "title" }
        }
    ]
}
```

---

## Built-in endpoints

### Users

```http
GET    /api/users/{id}                  # Get user by ID
POST   /api/users                       # Create user
POST   /api/users/{id}                  # Update user
DELETE /api/users/{id}                  # Delete user
GET    /api/me                          # Current authenticated user
POST   /api/me/avatar                   # Upload avatar
DELETE /api/me/avatar                   # Delete avatar
```

### Threads & Posts

```http
GET    /api/threads                     # List threads
POST   /api/threads                     # Create thread
GET    /api/threads/{id}                # Get thread
POST   /api/threads/{id}                # Update thread
DELETE /api/threads/{id}                # Delete thread

GET    /api/posts/{id}                  # Get post
POST   /api/posts                       # Create post (reply)
POST   /api/posts/{id}                  # Update post
DELETE /api/posts/{id}                  # Delete post

GET    /api/forums/{id}/threads         # List threads in a forum
GET    /api/search/forums/{id}/threads  # Search threads in forum
```

### Forums & Nodes

```http
GET    /api/nodes                       # List all nodes (flat)
GET    /api/nodes/flattened             # Flat list
GET    /api/nodes/{id}                  # Get node
POST   /api/nodes                       # Create node
DELETE /api/nodes/{id}                  # Delete node
GET    /api/forums/{id}                 # Get forum node
```

### Conversations

```http
GET    /api/conversations               # List conversations
POST   /api/conversations               # Start conversation
GET    /api/conversations/{id}          # Get conversation
DELETE /api/conversations/{id}          # Leave/delete conversation
GET    /api/conversations/{id}/messages # List messages
POST   /api/conversations/{id}/messages # Reply to conversation
GET    /api/conversation-messages/{id}  # Get a single message
```

### Attachments

```http
GET    /api/attachments                 # List attachments
POST   /api/attachments                 # Upload attachment
GET    /api/attachments/{id}            # Get attachment metadata
DELETE /api/attachments/{id}            # Delete attachment
GET    /api/attachments/{id}/data       # Download attachment binary
GET    /api/attachments/{id}/thumbnail  # Get thumbnail
```

### Alerts

```http
GET    /api/alerts                      # List alerts for current user
GET    /api/alerts/{id}                 # Get single alert
```

### Profile posts

```http
GET    /api/profile-posts/{id}          # Get profile post
POST   /api/profile-posts               # Create profile post
POST   /api/profile-posts/{id}          # Update profile post
DELETE /api/profile-posts/{id}          # Delete profile post
GET    /api/profile-posts/{id}/comments # List comments
POST   /api/profile-post-comments       # Create comment
GET    /api/profile-post-comments/{id}  # Get comment
POST   /api/profile-post-comments/{id}  # Update comment
DELETE /api/profile-post-comments/{id}  # Delete comment
```

---

## PHP examples (using cURL)

```php
// GET request with API key
function xfApiGet(string $boardUrl, string $apiKey, string $endpoint, array $params = []): array
{
    $url = rtrim($boardUrl, '/') . '/api/' . ltrim($endpoint, '/');
    if ($params)
    {
        $url .= '?' . http_build_query($params);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'XF-Api-Key: ' . $apiKey,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// POST request
function xfApiPost(string $boardUrl, string $apiKey, string $endpoint, array $data = []): array
{
    $url = rtrim($boardUrl, '/') . '/api/' . ltrim($endpoint, '/');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => [
            'XF-Api-Key: ' . $apiKey,
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Usage
$thread = xfApiGet('https://example.com/community', 'mykey', 'threads/123');
$new = xfApiPost('https://example.com/community', 'mykey', 'threads', [
    'node_id' => 5,
    'title'   => 'New thread via API',
    'message' => 'Thread body here.',
]);
```

---

## Writing custom API endpoints in your add-on

### 1. Create an API route

In ACP → Development → Routes → Add route: API:
- Route prefix: `demo-portal`
- Controller: `Demo\Portal:Portal` → resolves to `Demo\Portal\Api\Controller\Portal`

### 2. Create the API controller

```php
<?php

namespace Demo\Portal\Api\Controller;

use XF\Api\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Portal extends AbstractController
{
    // GET /api/demo-portal/
    public function actionGetIndex(): \XF\Api\Mvc\Reply\ApiResult
    {
        $this->assertApiScope('read');

        $page    = $this->filterPage();
        $perPage = $this->options()->demoPortalFeaturedPerPage;

        /** @var \Demo\Portal\Repository\FeaturedThread $repo */
        $repo   = $this->repository('Demo\Portal:FeaturedThread');
        $finder = $repo->findFeaturedThreadsForPortalView()->limitByPage($page, $perPage);

        $threads = $finder->fetch();
        $total   = $finder->total();

        return $this->apiResult([
            'featured_threads' => $threads->toApiResults(),
            'pagination'       => $this->getPaginationData($threads, $page, $perPage, $total),
        ]);
    }

    // GET /api/demo-portal/{thread_id}/
    public function actionGetView(ParameterBag $params): \XF\Api\Mvc\Reply\ApiResult
    {
        $this->assertApiScope('thread:read');

        $featured = $this->assertRecordExists(
            'Demo\Portal:FeaturedThread',
            $params->thread_id,
            ['Thread', 'Thread.User', 'Thread.Forum']
        );

        if (!$featured->Thread->canView($error))
        {
            return $this->noPermission($error);
        }

        return $this->apiResult(['featured_thread' => $featured->toApiResult()]);
    }

    // POST /api/demo-portal/
    public function actionPostIndex(): \XF\Api\Mvc\Reply\ApiResult
    {
        $this->assertApiScope('thread:write');
        $this->assertRequiredApiInput(['thread_id']);

        $threadId = $this->filter('thread_id', 'uint');
        $thread   = $this->assertRecordExists('XF:Thread', $threadId);

        if (!$thread->canView($error))
        {
            return $this->noPermission($error);
        }

        /** @var \Demo\Portal\Service\FeaturedThread\Creator $creator */
        $creator = $this->service('Demo\Portal:FeaturedThread\Creator', $thread);

        if (!$creator->validate($errors))
        {
            return $this->error($errors);
        }

        $featured = $creator->save();

        return $this->apiResult(
            ['featured_thread' => $featured->toApiResult()],
            \XF\Mvc\Reply\View::HTTP_CREATED
        );
    }

    // DELETE /api/demo-portal/{thread_id}/
    public function actionDeleteView(ParameterBag $params): \XF\Api\Mvc\Reply\ApiResult
    {
        $this->assertApiScope('thread:write');

        $featured = $this->assertRecordExists('Demo\Portal:FeaturedThread', $params->thread_id);
        $featured->delete();

        return $this->apiSuccess();
    }
}
```

### 3. Add `toApiResult()` to your entity

```php
public function toApiResult(array $verbosity = [], bool $forList = false): \XF\Api\Result\EntityResult
{
    $result = $this->toApiResultFromStructure($verbosity);

    $result->includeRelation('Thread', ['title', 'reply_count', 'post_date']);

    if (!$forList)
    {
        $result->includeRelation('Thread.FirstPost', ['message']);
    }

    return $result;
}
```

---

## Webhooks

Webhooks fire outgoing HTTP POST requests when content changes. Register a `webhook_handler_class` content type field to make your content trigger webhooks.

### Webhook handler

```php
<?php

namespace Demo\Portal\Webhook\Event;

use XF\Webhook\Event\AbstractHandler;

class FeaturedThread extends AbstractHandler
{
    public function getEventNames(): array
    {
        return ['featured_thread_create', 'featured_thread_delete'];
    }

    public function getEntityWith(): array
    {
        return ['Thread'];
    }

    public function getPayload(string $event, \XF\Mvc\Entity\Entity $entity): array
    {
        /** @var \Demo\Portal\Entity\FeaturedThread $entity */
        return [
            'thread_id'     => $entity->thread_id,
            'thread_title'  => $entity->Thread ? $entity->Thread->title : null,
            'featured_date' => $entity->featured_date,
        ];
    }
}
```

Register:
- Content type: `demo_featured_thread`
- Field name: `webhook_handler_class`
- Field value: `Demo\Portal\Webhook\Event\FeaturedThread`

Fire the webhook from your service:

```php
// In _save() after saving
\XF::app()->fire('demo_featured_thread_create', [$featuredThread]);
```

---

## Common API patterns

### Pagination in API responses

```php
$this->getPaginationData($collection, $page, $perPage, $total)
// Returns: ['current_page' => 1, 'per_page' => 20, 'total' => 150, 'last_page' => 8]
```

### Asserting scopes

```php
$this->assertApiScope('read');           // require 'read' OAuth scope
$this->assertApiScope('thread:write');   // require 'thread:write' scope
$this->assertSuperUserKey();             // require super user API key
$this->assertRequiredApiInput(['field']); // require specific POST fields
```

### API error codes (common)

| Code | Meaning |
|------|---------|
| `api_key_not_found` | Invalid API key |
| `api_key_no_user_context` | Key type doesn't support user context |
| `api_scope_missing` | Missing required OAuth scope |
| `not_found` | Resource not found |
| `no_permission` | Action not allowed |
| `validation_error` | Input validation failed |
| `rate_limit_exceeded` | Too many requests |
