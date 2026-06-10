# REST API and Webhooks

## Overview

The XenForo REST API is accessible at `<board_url>/api/`. All endpoints are prefixed by this URL.

Example: if XenForo is at `https://example.com/community/`, the API base is `https://example.com/community/api/`.

---

## Authentication

### API Keys (Server-to-Server)

API keys are created in Admin CP > API keys.

**Key types:**
| Type | Description |
|------|-------------|
| Guest key | Unauthenticated requests — acts as a guest user |
| User key | Requests act as a specific user |
| Super user key | Can act as any user; can bypass permissions |

**Request header:**
```http
XF-Api-Key: your_api_key_here
```

**Acting as a specific user (super user key only):**
```http
XF-Api-Key: your_super_key
XF-Api-User: 42
```

**Bypass permissions (super user key only):**
```
?api_bypass_permissions=1
```
or as a POST body parameter.

### OAuth 2.0

OAuth 2.0 uses the Authorization Code flow. Endpoints:

| Endpoint | URL |
|---------|-----|
| Authorization | `<board_url>/oauth2/authorize` |
| Token | `<board_url>/api/oauth2/token` |
| Refresh | `<board_url>/api/oauth2/token` |
| Revoke | `<board_url>/api/oauth2/revoke` |
| Introspect | `<board_url>/api/oauth2/introspect` |

**Authorization URL example:**
```
https://example.com/oauth2/authorize
  ?response_type=code
  &client_id=YOUR_CLIENT_ID
  &redirect_uri=https://yourapp.com/callback
  &scope=thread:read user:read
  &state=random_state_string
```

**Token exchange:**
```http
POST /api/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=AUTH_CODE
&redirect_uri=https://yourapp.com/callback
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

**Refresh token:**
```http
POST /api/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token=REFRESH_TOKEN
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

**Using the access token:**
```http
Authorization: Bearer ACCESS_TOKEN
```

---

## OAuth 2.0 Scopes

| Scope | Description |
|-------|-------------|
| `alert:read` | View and mark alerts |
| `alert:write` | Create custom alerts (super-user keys only) |
| `attachment:delete` | Delete attachments |
| `attachment:read` | View attachment data |
| `attachment:write` | Upload new attachments |
| `conversation:read` | View direct messages |
| `conversation:write` | Create/update direct messages |
| `feature:read` | Read featured content |
| `node:delete` | Delete nodes |
| `node:read` | Read nodes |
| `node:write` | Create/update nodes |
| `profile_post:delete_hard` | Hard-delete profile posts |
| `profile_post:read` | View profile posts |
| `profile_post:write` | Create/update/soft-delete profile posts |
| `search:read` | View search results |
| `search:write` | Create searches |
| `thread:delete_hard` | Hard-delete threads and posts |
| `thread:read` | Read threads and posts |
| `thread:write` | Create/update/soft-delete threads and posts |
| `user:delete` | Delete users |
| `user:read` | Read user profiles |
| `user:write` | Update user profiles |
| `media:read` | View media, albums, comments |
| `media:write` | Create/update/soft-delete media |
| `media:delete_hard` | Hard-delete media |
| `resource:read` | View resources |
| `resource:write` | Create/update/soft-delete resources |
| `resource:delete_hard` | Hard-delete resources |

---

## Request Format

- Use `application/x-www-form-urlencoded` for POST/PUT requests
- Use `multipart/form-data` when uploading files
- All data must be UTF-8
- For non-GET requests, pass parameters in the request body (not query string)

---

## Response Format

All responses are JSON (except binary file downloads).

**Success (HTTP 200):**
```json
{
    "thread": {
        "thread_id": 1,
        "title": "My Thread",
        "user_id": 5,
        ...
    }
}
```

**List response:**
```json
{
    "threads": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 20,
        "shown": 20,
        "total": 195
    }
}
```

---

## Error Format

HTTP 4xx status. Response body:

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

| Field | Description |
|-------|-------------|
| `code` | Machine-readable error code |
| `message` | Human-readable message (may be translated — don't use for logic) |
| `params` | Key-value pairs providing additional context |

Common error codes:

| Code | Meaning |
|------|---------|
| `api_key_not_found` | Invalid API key |
| `not_found` | Resource not found |
| `no_permission` | Insufficient permissions |
| `csrf_token_invalid` | CSRF validation failed |
| `validation_error` | Input validation failed |

---

## Built-in Endpoints

### Authentication

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth` | Authenticate with username/password |
| POST | `/api/auth/from-session` | Create API token from existing session |
| POST | `/api/auth/login-token` | Create a login token |
| GET/POST | `/api/oauth2/token` | OAuth2 token endpoint |
| POST | `/api/oauth2/revoke` | Revoke OAuth2 token |
| POST | `/api/oauth2/introspect` | Introspect token |

### Current User (Me)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/me` | Get current user profile |
| POST | `/api/me` | Update current user profile |
| POST | `/api/me/avatar` | Upload current user avatar |
| DELETE | `/api/me/avatar` | Delete current user avatar |
| POST | `/api/me/email` | Update email |
| POST | `/api/me/password` | Update password |

### Users

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/users` | List users |
| POST | `/api/users` | Create user |
| GET | `/api/users/{id}` | Get user by ID |
| POST | `/api/users/{id}` | Update user |
| DELETE | `/api/users/{id}` | Delete user |
| POST | `/api/users/{id}/avatar` | Upload user avatar |
| DELETE | `/api/users/{id}/avatar` | Delete user avatar |
| GET | `/api/users/{id}/profile-posts` | List user's profile posts |
| GET | `/api/users/find-by-name` | Find user by username |
| GET | `/api/users/find-by-email` | Find user by email |

### Threads

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/threads` | List threads |
| POST | `/api/threads` | Create thread |
| GET | `/api/threads/{id}` | Get thread |
| POST | `/api/threads/{id}` | Update thread |
| DELETE | `/api/threads/{id}` | Delete thread |
| GET | `/api/threads/{id}/posts` | Get posts in thread |
| POST | `/api/threads/{id}/mark-read` | Mark thread as read |
| POST | `/api/threads/{id}/move` | Move thread |
| POST | `/api/threads/{id}/change-type` | Change thread type |
| POST | `/api/threads/{id}/vote` | Vote on thread (question type) |
| POST | `/api/threads/{id}/feature` | Feature thread |
| POST | `/api/threads/{id}/unfeature` | Unfeature thread |

### Posts

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/posts/{id}` | Get post |
| POST | `/api/posts` | Create post (reply) |
| POST | `/api/posts/{id}` | Update post |
| DELETE | `/api/posts/{id}` | Delete post |
| POST | `/api/posts/{id}/react` | React to post |
| POST | `/api/posts/{id}/vote` | Vote on post (question type) |
| POST | `/api/posts/{id}/mark-solution` | Mark post as solution |

### Forums (Nodes)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/nodes` | List nodes |
| GET | `/api/nodes/flattened` | Flat list of all nodes |
| GET | `/api/nodes/{id}` | Get node |
| POST | `/api/nodes` | Create node |
| POST | `/api/nodes/{id}` | Update node |
| DELETE | `/api/nodes/{id}` | Delete node |
| GET | `/api/forums/{id}` | Get forum details |
| GET | `/api/forums/{id}/threads` | List threads in forum |
| POST | `/api/forums/{id}/mark-read` | Mark forum as read |

### Conversations

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/conversations` | List conversations |
| POST | `/api/conversations` | Create conversation |
| GET | `/api/conversations/{id}` | Get conversation |
| POST | `/api/conversations/{id}` | Update conversation |
| DELETE | `/api/conversations/{id}` | Delete conversation |
| GET | `/api/conversations/{id}/messages` | List messages |
| POST | `/api/conversation-messages` | Reply to conversation |
| POST | `/api/conversation-messages/{id}` | Update message |
| DELETE | `/api/conversation-messages/{id}` | Delete message |
| POST | `/api/conversations/{id}/invite` | Invite users |
| POST | `/api/conversations/{id}/star` | Star/unstar |
| POST | `/api/conversations/{id}/mark-read` | Mark as read |
| POST | `/api/conversations/{id}/labels` | Apply labels |

### Alerts

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/alerts` | List alerts |
| POST | `/api/alerts` | Create alert (super user) |
| GET | `/api/alerts/{id}` | Get alert |
| POST | `/api/alerts/{id}/mark` | Mark alert read/unread |
| POST | `/api/alerts/mark-all` | Mark all alerts read |

### Attachments

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/attachments` | List attachments |
| POST | `/api/attachments` | Upload attachment |
| GET | `/api/attachments/{id}` | Get attachment info |
| DELETE | `/api/attachments/{id}` | Delete attachment |
| GET | `/api/attachments/{id}/data` | Download attachment |
| GET | `/api/attachments/{id}/thumbnail` | Get thumbnail |
| POST | `/api/attachments/new-key` | Get a new attachment key |

### Search

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/search` | Create search |
| GET | `/api/search/{id}` | Get search results |
| POST | `/api/search/{id}/older` | Get older results |
| POST | `/api/search/member` | Search user content |
| GET | `/api/search/forums/{id}` | Search within forum |

### Profile Posts

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/profile-posts/{id}` | Get profile post |
| POST | `/api/profile-posts` | Create profile post |
| POST | `/api/profile-posts/{id}` | Update profile post |
| DELETE | `/api/profile-posts/{id}` | Delete profile post |
| POST | `/api/profile-posts/{id}/react` | React |
| GET | `/api/profile-posts/{id}/comments` | List comments |
| POST | `/api/profile-post-comments` | Create comment |

### Stats

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/stats` | Board statistics |
| GET | `/api/index` | Board index info |

---

## Usage Examples

### cURL — Get thread

```bash
curl -X GET "https://example.com/api/threads/1" \
  -H "XF-Api-Key: YOUR_API_KEY"
```

### cURL — Create post

```bash
curl -X POST "https://example.com/api/posts" \
  -H "XF-Api-Key: YOUR_API_KEY" \
  -H "XF-Api-User: 1" \
  -d "thread_id=123&message=Hello world!"
```

### PHP — Get user

```php
$ch = curl_init('https://example.com/api/users/1');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'XF-Api-Key: YOUR_API_KEY',
    ],
]);
$response = json_decode(curl_exec($ch), true);
$user = $response['user'];
echo $user['username'];
```

### PHP — Create thread

```php
$ch = curl_init('https://example.com/api/threads');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'node_id'    => 2,
        'title'      => 'My New Thread',
        'message'    => 'First post content here.',
    ]),
    CURLOPT_HTTPHEADER => [
        'XF-Api-Key: YOUR_API_KEY',
        'XF-Api-User: 1',
    ],
]);
$response = json_decode(curl_exec($ch), true);
```

---

## Writing Custom API Controllers

Custom API endpoints use the same controller pattern as public controllers but extend `XF\Api\Controller\AbstractController`.

```php
<?php

namespace Demo\Addon\Api\Controller;

use XF\Mvc\ParameterBag;

class Item extends \XF\Api\Controller\AbstractController
{
    /**
     * GET /api/demo-items
     */
    public function actionGetMultiple()
    {
        $this->assertRequiredApiScope('thread:read'); // reuse existing scope

        $page = $this->filterPage();
        $perPage = 20;

        $finder = $this->finder('Demo\Addon:Item')
            ->where('item_state', 'visible')
            ->order('created_date', 'DESC')
            ->limitByPage($page, $perPage);

        $items = $finder->fetch();
        $total = $finder->total();

        $itemsOut = $items->toApiArray();

        return $this->apiResult([
            'items'      => $itemsOut,
            'pagination' => $this->getPaginationData($itemsOut, $page, $perPage, $total),
        ]);
    }

    /**
     * GET /api/demo-items/:item_id
     */
    public function actionGetSingle(ParameterBag $params)
    {
        $item = $this->assertViewableItem($params->item_id);

        return $this->apiResult([
            'item' => $item->toApiArray(),
        ]);
    }

    /**
     * POST /api/demo-items
     */
    public function actionPost()
    {
        $this->assertRequiredApiScope('thread:write');
        $this->assertApiScope('thread:write');

        $input = $this->filter([
            'title'       => 'str',
            'description' => 'str',
        ]);

        /** @var \Demo\Addon\Entity\Item $item */
        $item = $this->em()->create('Demo\Addon:Item');
        $item->bulkSet($input);
        $item->user_id = \XF::visitor()->user_id;
        $item->save();

        return $this->apiResult([
            'item' => $item->toApiArray(),
        ]);
    }

    protected function assertViewableItem(int $id): \Demo\Addon\Entity\Item
    {
        /** @var \Demo\Addon\Entity\Item $item */
        $item = $this->assertRecordExists('Demo\Addon:Item', $id);

        if (!$item->canView($error)) {
            throw $this->exception($this->noPermission($error));
        }

        return $item;
    }
}
```

Register the route in Admin CP > Development > Routes (API type):
- Route prefix: `demo-items`
- Controller: `Demo\Addon:Item`

### Entity toApiArray()

Add to your entity to control which fields are exposed:

```php
public function toApiArray(array $extraWith = [], bool $verbosity = true, array $options = []): array
{
    $result = $this->toArrayHelper([
        'item_id',
        'title',
        'description',
        'created_date',
        'user_id',
        'is_enabled',
    ]);

    if ($this->User) {
        $result['User'] = $this->User->toApiArray();
    }

    return $result;
}
```

---

## Webhook Events

XenForo can send webhook payloads to external URLs when events occur.

### Configuration

Admin CP > API > Webhooks > Add webhook:
- Target URL
- Secret (used to sign payloads)
- Events to subscribe to

### Payload Format

```http
POST https://your-app.com/webhook
Content-Type: application/json
XF-Signature: sha256=HMAC_SHA256_OF_BODY

{
    "event": "thread.created",
    "timestamp": 1672531200,
    "data": {
        "thread": { ... }
    }
}
```

### Verifying Signature

```php
$secret = 'your_webhook_secret';
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_XF_SIGNATURE'];

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$data = json_decode($payload, true);
// process $data['event'] and $data['data']
```

### Available Webhook Events

| Event | Triggered when |
|-------|---------------|
| `thread.created` | New thread posted |
| `thread.updated` | Thread edited |
| `thread.deleted` | Thread deleted |
| `post.created` | New reply posted |
| `post.updated` | Post edited |
| `post.deleted` | Post deleted |
| `user.created` | New user registered |
| `user.updated` | User profile updated |
| `user.deleted` | User deleted |
| `conversation.created` | New conversation started |
| `profile_post.created` | New profile post |

### Writing a Custom Webhook Handler

```php
<?php

namespace Demo\Addon\Webhook\Event;

class ItemCreated extends \XF\Webhook\Event\AbstractHandler
{
    public function getEventName(): string
    {
        return 'demo_item.created';
    }

    public function getPayload($content): array
    {
        /** @var \Demo\Addon\Entity\Item $content */
        return [
            'item_id'      => $content->item_id,
            'title'        => $content->title,
            'user_id'      => $content->user_id,
            'created_date' => $content->created_date,
        ];
    }
}
```

Register via content type field:
- Content type: `demo_item`
- Field name: `webhook_handler_class`
- Field value: `Demo\Addon\Webhook\Event\ItemCreated`

Trigger the webhook from your service:

```php
\XF::app()->fire('demo_item.created', [$item, $item->toApiArray()]);
```
