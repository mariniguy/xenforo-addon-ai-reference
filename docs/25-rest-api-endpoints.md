# REST API Endpoint Catalog

This is the **endpoint catalog** for the XenForo 2.3+ REST API — the exhaustive,
per-resource list of every built-in endpoint, its HTTP verb, path, and key
parameters/scope. It complements `docs/10-rest-api.md`, which covers the
*concepts* (authentication, key types, OAuth 2.0 flow, request/response/error
format, webhooks). Read that first for the mechanics; use this as the lookup
table when you need the exact verb and path for a specific operation.

> **Sources (verbatim from the official XenForo documentation):**
> - REST API overview / authentication / keys / scopes / errors:
>   <https://docs.xenforo.com/manual/reference/rest-api>
> - Full API reference (per-endpoint request/response schemas):
>   <https://docs.xenforo.com/rest-api>
>
> Every endpoint below is backed by an official per-endpoint schema file. Verbs
> and paths are taken directly from each endpoint's definition. Nothing here is
> invented — if an operation is not in the official reference, it is not listed.

---

## How to read this catalog

- **Base URL.** Every path below is relative to `<board_url>/api/`. For a board
  at `https://example.com/community/`, `GET /threads/` means
  `GET https://example.com/community/api/threads/`.
- **`{id}` placeholders.** A path segment like `{id}` is the numeric content ID
  (e.g. `GET /threads/{id}/` → `/threads/123/`).
- **Trailing slashes.** The official reference defines most endpoints with a
  trailing slash. XenForo accepts requests with or without it; they are shown
  as defined.
- **Verbs.** XenForo uses `GET` to read, `POST` to create **and** to update
  (there is no `PUT`/`PATCH` in this API — updates are `POST` to the item path),
  and `DELETE` to delete.
- **Key params / scope.** Lists the most relevant parameters and the OAuth scope
  family that governs the endpoint. Full per-field schemas live in the official
  reference linked above. Scope names follow `docs/10-rest-api.md`.

---

## Authentication, headers & scopes (quick recap)

Full detail is in `docs/10-rest-api.md`. The essentials needed to call anything
below:

| Header | Required | Purpose |
|--------|----------|---------|
| `XF-Api-Key` | Always | The API key. Every request must send it. |
| `XF-Api-User` | Optional | Context user ID. **Super user keys only.** If omitted, requests run as a guest. |

| Parameter | Where | Purpose |
|-----------|-------|---------|
| `api_bypass_permissions` | query or body | Set to `1` to bypass the context user's permissions. **Super user keys only.** |

- Request bodies use `application/x-www-form-urlencoded`, or
  `multipart/form-data` when uploading a file. All data is UTF-8.
- For non-GET requests, pass parameters in the **body**, not the query string.
- `XF-Api-User` and `api_bypass_permissions` are accepted on essentially every
  endpoint; to avoid repetition they are **not** repeated in each row below.
- Each endpoint is covered by one or more **scopes**. If the key has not been
  granted a required scope, the request fails regardless of user permissions.
  Scope families: `alert`, `attachment`, `conversation`, `feature`, `node`,
  `post`/`thread` (threads & posts share `thread:*`), `profile_post`, `search`,
  `user`, `media`, `resource`.

---

## Index & stats

Lightweight, read-only board metadata.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/index/` | API/board index information. | None required. |
| GET | `/stats/` | Public board statistics (counts, latest member, etc.). | None required. |
| GET | `/oembed/` | oEmbed 1.0 consumer endpoint — returns oEmbed data for a given content URL. | `url`, `maxwidth`, `maxheight`, `format`. |

---

## Authentication (auth)

Endpoints that exchange credentials/sessions for API access. See also the OAuth
2.0 section below.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/auth/` | Authenticate a user by login + password; validates the credentials. | `login`, `password`. |
| POST | `/auth/from-session` | Obtain API access from an existing (web) session. | Session identifier. |
| POST | `/auth/login-token` | Create a login token for a user (e.g. for auto-login flows). | `user_id` and related token options. |

---

## OAuth 2.0 (oauth2)

OAuth 2.0 Authorization Code flow token endpoints. The user-facing authorization
URL (`/oauth2/authorize`) lives on the board root, not under `/api/`; see
`docs/10-rest-api.md`.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/oauth2/token` | Token endpoint (GET form). | `grant_type`, `client_id`, `client_secret`, `code`/`refresh_token`, `redirect_uri`. |
| POST | `/oauth2/token` | Exchange an authorization code (or refresh token) for an access token. | `grant_type`, `client_id`, `client_secret`, `code`/`refresh_token`, `redirect_uri`. |
| POST | `/oauth2/revoke` | Revoke an access or refresh token. | `token`, client credentials. |
| POST | `/oauth2/introspect` | Introspect a token (validity/metadata). | `token`, client credentials. |

---

## Current user (me)

Operate on the authenticated context user. Governed by `user:*` scopes (and
`user:write` for mutations).

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/me/` | Get the current user's profile. | Scope: `user:read`. |
| POST | `/me/` | Update the current user's profile. | Profile fields. Scope: `user:write`. |
| POST | `/me/avatar` | Upload the current user's avatar. | `multipart/form-data` file `avatar`. Scope: `user:write`. |
| DELETE | `/me/avatar` | Remove the current user's avatar. | Scope: `user:write`. |
| POST | `/me/email` | Change the current user's email address. | `email`, current `password`. Scope: `user:write`. |
| POST | `/me/password` | Change the current user's password. | `new_password`, `existing_password`. Scope: `user:write`. |

---

## Users

User CRUD and lookup. Read uses `user:read`; create/update uses `user:write`;
delete uses `user:delete`. User creation/management typically requires a super
user key.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/users/` | List users (paginated). | `page`. Scope: `user:read`. |
| POST | `/users/` | Create a user. | `username`, `email`, `password` (+ optional profile fields). Scope: `user:write`. |
| GET | `/users/{id}/` | Get a user by ID. | Scope: `user:read`. |
| POST | `/users/{id}/` | Update a user. | Profile fields. Scope: `user:write`. |
| DELETE | `/users/{id}/` | Delete a user. | `rename_to`, content-handling options. Scope: `user:delete`. |
| POST | `/users/{id}/avatar` | Upload a user's avatar. | `multipart/form-data` file `avatar`. Scope: `user:write`. |
| DELETE | `/users/{id}/avatar` | Remove a user's avatar. | Scope: `user:write`. |
| GET | `/users/{id}/profile-posts` | List the profile posts on a user's profile. | `page`. Scope: `user:read` / `profile_post:read`. |
| GET | `/users/find-name` | Find users by a prefix of their username (autocomplete). | `username` (prefix). Scope: `user:read`. |
| GET | `/users/find-email` | Find a user by exact email. **Admin / permission-bypass only.** | `email`. Scope: `user:read`. |

> **Note:** the lookup paths are `find-name` and `find-email` (not
> `find-by-name` / `find-by-email`).

---

## Threads

Thread listing, CRUD, and thread actions. Reading uses `thread:read`;
create/update/soft-delete uses `thread:write`; hard-delete uses
`thread:delete_hard`. (Threads and posts share the `thread:*` scope family.)

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/threads/` | List threads (optionally filtered). | `page`, `prefix_id`, `starter_id`, `last_days`, `unread`, `thread_type`, `order` (`last_post_date`, `post_date`, …), `direction`. Scope: `thread:read`. |
| POST | `/threads/` | Create a thread (first post + optional thread-type data). | `node_id`, `title`, `message` (+ `prefix_id`, `discussion_type`, type-specific fields). Scope: `thread:write`. |
| GET | `/threads/{id}/` | Get a single thread. | `with_posts`, `page`, `order`. Scope: `thread:read`. |
| POST | `/threads/{id}/` | Update a thread (title, prefix, open/sticky, etc.). | Thread fields. Scope: `thread:write`. |
| DELETE | `/threads/{id}/` | Delete a thread (soft by default; hard if requested). | `hard_delete`, `reason`. Scope: `thread:write` (soft) / `thread:delete_hard` (hard). |
| GET | `/threads/{id}/posts` | Get a page of posts in a thread. | `page`, `order`. Scope: `thread:read`. |
| POST | `/threads/{id}/mark-read` | Mark the thread read up to a point. | `date`. Scope: `thread:read`. |
| POST | `/threads/{id}/move` | Move the thread to another forum. | `target_node_id` (+ optional `title`, `prefix_id`). Scope: `thread:write`. |
| POST | `/threads/{id}/change-type` | Change the thread's discussion type. | `discussion_type` (+ type-specific fields). Scope: `thread:write`. |
| POST | `/threads/{id}/vote` | Cast a vote on the thread (poll/question types). | Vote fields. Scope: `thread:write`. |
| POST | `/threads/{id}/feature` | Feature the thread. | Scope: `thread:write` (+ feature permission). |
| POST | `/threads/{id}/unfeature` | Remove the thread from featured. | Scope: `thread:write`. |

---

## Posts

Individual posts (replies). There is **no** `POST /threads/{id}/posts`; replies
are created via `POST /posts/` with a `thread_id`. Same `thread:*` scope family.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/posts/` | Add a new reply to a thread. | `thread_id`, `message` (+ `attachment_key`). Scope: `thread:write`. |
| GET | `/posts/{id}/` | Get a single post. | Scope: `thread:read`. |
| POST | `/posts/{id}/` | Update a post. | `message`, `silent`, `author_alert`. Scope: `thread:write`. |
| DELETE | `/posts/{id}/` | Delete a post (soft by default; hard if requested). | `hard_delete`, `reason`. Scope: `thread:write` / `thread:delete_hard`. |
| POST | `/posts/{id}/react` | React to a post (toggle a reaction). | `reaction_id`. Scope: `thread:write`. |
| POST | `/posts/{id}/vote` | Vote on a post (question-thread answers). | Vote fields. Scope: `thread:write`. |
| POST | `/posts/{id}/mark-solution` | Mark/unmark the post as the solution (question threads). | Scope: `thread:write`. |

---

## Forums & nodes

Nodes are the generic tree (categories, forums, pages, links, …). Forum-specific
read helpers live under `/forums/`. Reading uses `node:read`; create/update uses
`node:write`; delete uses `node:delete`. Node creation/management generally
requires a super user key.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/nodes/` | List nodes as a tree. | Scope: `node:read`. |
| GET | `/nodes/flattened` | List all nodes flattened in display order (with depth). | Scope: `node:read`. |
| POST | `/nodes/` | Create a node. | `node_type_id`, `title`, `parent_node_id` (+ type data). Scope: `node:write`. |
| GET | `/nodes/{id}/` | Get a single node. | Scope: `node:read`. |
| POST | `/nodes/{id}/` | Update a node. | Node fields (+ type data). Scope: `node:write`. |
| DELETE | `/nodes/{id}/` | Delete a node. | `delete_children`. Scope: `node:delete`. |
| GET | `/forums/{id}/` | Get a forum node's details. | Scope: `node:read`. |
| GET | `/forums/{id}/threads` | List a page of threads in the forum. | `page`, `prefix_id`, `starter_id`, `last_days`, `unread`, `thread_type`, `order`, `direction`. Scope: `node:read` / `thread:read`. |
| POST | `/forums/{id}/mark-read` | Mark the whole forum read. | `date`. Scope: `node:read`. |

---

## Conversations (direct messages)

Private conversations and their messages. Reading uses `conversation:read`;
create/update uses `conversation:write`.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/conversations/` | List the context user's conversations. | `page`, `starter_id`, `receiver_id`, `unread`. Scope: `conversation:read`. |
| POST | `/conversations/` | Start a new conversation. | `recipient_ids`, `title`, `message` (+ `conversation_open`, `open_invite`). Scope: `conversation:write`. |
| GET | `/conversations/{id}/` | Get a single conversation (master record). | Scope: `conversation:read`. |
| POST | `/conversations/{id}/` | Update conversation properties. | Conversation fields. Scope: `conversation:write`. |
| DELETE | `/conversations/{id}/` | Leave/delete the conversation for the context user. | `ignore`, recipient-state options. Scope: `conversation:write`. |
| GET | `/conversations/{id}/messages` | List messages in a conversation. | `page`. Scope: `conversation:read`. |
| POST | `/conversations/{id}/invite` | Invite additional users. | `recipient_ids`. Scope: `conversation:write`. |
| POST | `/conversations/{id}/labels` | Apply/remove the context user's labels. | Label fields. Scope: `conversation:write`. |
| POST | `/conversations/{id}/mark-read` | Mark the conversation read. | `date`. Scope: `conversation:read`. |
| POST | `/conversations/{id}/mark-unread` | Mark the conversation unread. | Scope: `conversation:read`. |
| POST | `/conversations/{id}/star` | Star/unstar the conversation. | Scope: `conversation:write`. |

### Conversation messages

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/conversation-messages/` | Reply to a conversation. | `conversation_id`, `message` (+ `attachment_key`). Scope: `conversation:write`. |
| GET | `/conversation-messages/{id}/` | Get a single conversation message. | Scope: `conversation:read`. |
| POST | `/conversation-messages/{id}/` | Edit a conversation message. | `message`. Scope: `conversation:write`. |
| POST | `/conversation-messages/{id}/react` | React to a conversation message. | `reaction_id`. Scope: `conversation:write`. |

---

## Profile posts & comments

Profile posts on user profiles plus their comments. Reading uses
`profile_post:read`; create/update/soft-delete uses `profile_post:write`;
hard-delete uses `profile_post:delete_hard`.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/profile-posts/` | Create a profile post on a user's profile. | `user_id` (target profile), `message`. Scope: `profile_post:write`. |
| GET | `/profile-posts/{id}/` | Get a single profile post. | Scope: `profile_post:read`. |
| POST | `/profile-posts/{id}/` | Update a profile post. | `message`. Scope: `profile_post:write`. |
| DELETE | `/profile-posts/{id}/` | Delete a profile post. | `hard_delete`, `reason`. Scope: `profile_post:write` / `profile_post:delete_hard`. |
| POST | `/profile-posts/{id}/react` | React to a profile post. | `reaction_id`. Scope: `profile_post:write`. |
| GET | `/profile-posts/{id}/comments` | List comments on a profile post. | `page` / `before`. Scope: `profile_post:read`. |
| POST | `/profile-post-comments/` | Comment on a profile post. | `profile_post_id`, `message`. Scope: `profile_post:write`. |
| GET | `/profile-post-comments/{id}/` | Get a single profile-post comment. | Scope: `profile_post:read`. |
| POST | `/profile-post-comments/{id}/` | Update a profile-post comment. | `message`. Scope: `profile_post:write`. |
| DELETE | `/profile-post-comments/{id}/` | Delete a profile-post comment. | `hard_delete`. Scope: `profile_post:write` / `profile_post:delete_hard`. |
| POST | `/profile-post-comments/{id}/react` | React to a profile-post comment. | `reaction_id`. Scope: `profile_post:write`. |

---

## Alerts

The context user's alerts. Reading/marking uses `alert:read`; creating custom
alerts uses `alert:write` (super user keys only).

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/alerts/` | List the context user's alerts. | `page`, `cutoff`, `unviewed`/`unread`. Scope: `alert:read`. |
| POST | `/alerts/` | Create a custom user alert. | `to_user_id`, `alert` text/body fields. Scope: `alert:write`. |
| GET | `/alerts/{id}/` | Get a single alert. | Scope: `alert:read`. |
| POST | `/alerts/{id}/mark` | Mark one alert read/unread (viewed/unviewed). | `read` (or viewed state). Scope: `alert:read`. |
| POST | `/alerts/mark-all` | Mark all alerts read. | `read`. Scope: `alert:read`. |

---

## Attachments

Upload/download of attachments. An attachment must first be associated with a
**key** (created via `new-key`) before files are uploaded. Reading uses
`attachment:read`; uploading uses `attachment:write`; deleting uses
`attachment:delete`.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/attachments/new-key` | Create an attachment key bound to a content type/context. | `type` (e.g. `post`, `conversation_message`), `context[...]`. Scope: `attachment:write`. |
| POST | `/attachments/` | Upload a file against an existing key. **`multipart/form-data`.** | `key`, file field `attachment`. Scope: `attachment:write`. |
| GET | `/attachments/` | List attachments for a given key. | `key`. Scope: `attachment:read`. |
| GET | `/attachments/{id}/` | Get attachment metadata. | Scope: `attachment:read`. |
| GET | `/attachments/{id}/data` | Download the attachment's binary data. | Returns the file (binary, not JSON). Scope: `attachment:read`. |
| GET | `/attachments/{id}/thumbnail` | Get the attachment thumbnail. | Binary. Scope: `attachment:read`. |
| GET | `/attachments/{id}/retina-thumbnail` | Get the 2x (retina) thumbnail. | Binary. Scope: `attachment:read`. |
| DELETE | `/attachments/{id}/` | Delete an attachment. | Scope: `attachment:delete`. |

---

## Search

Create searches and page through results. Reading results uses `search:read`;
creating a search uses `search:write`.

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/search/` | Create a new search and return the first page of results. | `keywords`, `c[...]` constraints (content type, node, user, dates), `order`. Scope: `search:write`. |
| GET | `/search/{id}/` | Retrieve the results of a previously created search. | `page`. Scope: `search:read`. |
| POST | `/search/{id}/older` | Fetch the next (older) batch of results for a search. | `last_date`/cursor. Scope: `search:read`. |
| POST | `/search/member` | Search content authored by a specific member. | `user_id`, `keywords` (+ constraints). Scope: `search:write`. |

### Search forums (saved/forum search views)

A distinct `search-forums` resource group (separate from `/forums/`).

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/search-forums/{id}/` | Get information about the specified search-forum. | Scope: `search:read` / `node:read`. |
| GET | `/search-forums/{id}/threads` | Get a page of threads for the search-forum. | `page`, `order`, `direction`. Scope: `search:read` / `thread:read`. |

---

## Featured content (featured)

Read featured content. Governed by `feature:read`. (Featuring/unfeaturing of
specific content is done on that content — see thread/resource/media `feature`
actions.)

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/featured/` | List featured content items. | `page`. Scope: `feature:read`. |

---

> **Add-on resources below (`media`, `resources`).** The following groups belong
> to the bundled **XenForo Media Gallery** and **Resource Manager** add-ons. The
> endpoints exist in the official API reference and are documented here for
> completeness; they are only available when the corresponding add-on is
> installed and active. Media uses `media:*` scopes; resources use `resource:*`
> scopes.

## Media Gallery — media

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/media/` | List media items. | `page`. Scope: `media:read`. |
| POST | `/media/` | Create (upload) a media item. | `attachment_key`/file, `title`, `category_id`/`album_id`, `description`. Scope: `media:write`. |
| GET | `/media/{id}/` | Get a single media item. | Scope: `media:read`. |
| POST | `/media/{id}/` | Update a media item. | Media fields. Scope: `media:write`. |
| DELETE | `/media/{id}/` | Delete a media item. | `hard_delete`. Scope: `media:write` / `media:delete_hard`. |
| GET | `/media/{id}/data` | Download the media item's binary data. | Binary. Scope: `media:read`. |
| GET | `/media/{id}/comments` | List comments on a media item. | `page`/`before`. Scope: `media:read`. |
| POST | `/media/{id}/react` | React to a media item. | `reaction_id`. Scope: `media:write`. |
| POST | `/media/{id}/feature` | Feature a media item. | Scope: `media:write`. |
| POST | `/media/{id}/unfeature` | Unfeature a media item. | Scope: `media:write`. |

### Media albums

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/media-albums/` | List media albums. | `page`. Scope: `media:read`. |
| POST | `/media-albums/` | Create a media album. | `title`, `description`, privacy fields. Scope: `media:write`. |
| GET | `/media-albums/{id}/` | Get a single album. | Scope: `media:read`. |
| POST | `/media-albums/{id}/` | Update an album. | Album fields. Scope: `media:write`. |
| DELETE | `/media-albums/{id}/` | Delete an album. | `hard_delete`. Scope: `media:write` / `media:delete_hard`. |
| GET | `/media-albums/{id}/media` | List media in an album. | `page`. Scope: `media:read`. |
| GET | `/media-albums/{id}/comments` | List comments on an album. | `page`/`before`. Scope: `media:read`. |
| POST | `/media-albums/{id}/react` | React to an album. | `reaction_id`. Scope: `media:write`. |

### Media categories

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/media-categories/` | List media categories (tree). | Scope: `media:read`. |
| GET | `/media-categories/flattened` | List media categories flattened in display order. | Scope: `media:read`. |
| POST | `/media-categories/` | Create a media category. | `title`, `parent_category_id`. Scope: `media:write` (admin/super user). |
| GET | `/media-categories/{id}/` | Get a single media category. | Scope: `media:read`. |
| POST | `/media-categories/{id}/` | Update a media category. | Category fields. Scope: `media:write`. |
| DELETE | `/media-categories/{id}/` | Delete a media category. | Scope: `media:write`. |
| GET | `/media-categories/{id}/content` | List the media/content in a category. | `page`. Scope: `media:read`. |

### Media comments

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/media-comments/` | List media comments. | `page`/`before`. Scope: `media:read`. |
| POST | `/media-comments/` | Comment on a media item or album. | `content_type` (`media`/`album`), `content_id`, `message`. Scope: `media:write`. |
| GET | `/media-comments/{id}/` | Get a single media comment. | Scope: `media:read`. |
| POST | `/media-comments/{id}/` | Update a media comment. | `message`. Scope: `media:write`. |
| DELETE | `/media-comments/{id}/` | Delete a media comment. | `hard_delete`. Scope: `media:write` / `media:delete_hard`. |
| POST | `/media-comments/{id}/react` | React to a media comment. | `reaction_id`. Scope: `media:write`. |

---

## Resource Manager — resources

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/resources/` | List resources. | `page`, category/prefix filters, `order`. Scope: `resource:read`. |
| POST | `/resources/` | Create a resource. | `resource_category_id`, `title`, `tag_line`, `version_string`, description/file fields. Scope: `resource:write`. |
| GET | `/resources/{id}/` | Get a single resource. | Scope: `resource:read`. |
| POST | `/resources/{id}/` | Update a resource. | Resource fields. Scope: `resource:write`. |
| DELETE | `/resources/{id}/` | Delete a resource. | `hard_delete`. Scope: `resource:write` / `resource:delete_hard`. |
| GET | `/resources/{id}/reviews` | List a resource's reviews. | `page`. Scope: `resource:read`. |
| GET | `/resources/{id}/updates` | List a resource's update posts. | `page`. Scope: `resource:read`. |
| GET | `/resources/{id}/versions` | List a resource's versions. | `page`. Scope: `resource:read`. |
| POST | `/resources/{id}/feature` | Feature a resource. | Scope: `resource:write`. |
| POST | `/resources/{id}/unfeature` | Unfeature a resource. | Scope: `resource:write`. |

### Resource categories

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/resource-categories/` | List resource categories (tree). | Scope: `resource:read`. |
| GET | `/resource-categories/flattened` | List resource categories flattened in display order. | Scope: `resource:read`. |
| POST | `/resource-categories/` | Create a resource category. | `title`, `parent_category_id`. Scope: `resource:write` (admin/super user). |
| GET | `/resource-categories/{id}/` | Get a single resource category. | Scope: `resource:read`. |
| POST | `/resource-categories/{id}/` | Update a resource category. | Category fields. Scope: `resource:write`. |
| DELETE | `/resource-categories/{id}/` | Delete a resource category. | Scope: `resource:write`. |
| GET | `/resource-categories/{id}/resources` | List resources in a category. | `page`, `order`. Scope: `resource:read`. |

### Resource versions

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/resource-versions/` | Create a new version of a resource. | `resource_id`, `version_string`, message/file fields. Scope: `resource:write`. |
| GET | `/resource-versions/{id}/` | Get a single resource version. | Scope: `resource:read`. |
| DELETE | `/resource-versions/{id}/` | Delete a resource version. | `hard_delete`. Scope: `resource:write` / `resource:delete_hard`. |
| GET | `/resource-versions/{id}/download` | Download a resource version's file. | Binary. Scope: `resource:read` (+ download permission). |

### Resource updates

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| POST | `/resource-updates/` | Post a resource update. | `resource_id`, `title`, `message`. Scope: `resource:write`. |
| GET | `/resource-updates/{id}/` | Get a single resource update. | Scope: `resource:read`. |
| POST | `/resource-updates/{id}/` | Update a resource update post. | `title`, `message`. Scope: `resource:write`. |
| DELETE | `/resource-updates/{id}/` | Delete a resource update. | `hard_delete`. Scope: `resource:write` / `resource:delete_hard`. |

### Resource reviews

| Method | Endpoint | Purpose | Key params / scope |
|--------|----------|---------|--------------------|
| GET | `/resource-reviews/` | List resource reviews. | `page`. Scope: `resource:read`. |
| POST | `/resource-reviews/` | Create a review for a resource. | `resource_id`, `rating`, `message`. Scope: `resource:write`. |
| GET | `/resource-reviews/{id}/` | Get a single resource review. | Scope: `resource:read`. |
| DELETE | `/resource-reviews/{id}/` | Delete a resource review. | `hard_delete`. Scope: `resource:write` / `resource:delete_hard`. |
| POST | `/resource-reviews/{id}/author-reply` | Add/update the author's reply to a review. | `message`. Scope: `resource:write`. |
| DELETE | `/resource-reviews/{id}/author-reply` | Remove the author's reply to a review. | Scope: `resource:write`. |

---

## Notes on verbs, deletes, and pagination

- **No PUT/PATCH.** Updates are `POST` to the item path (e.g. `POST /threads/{id}/`).
- **Soft vs hard delete.** Many `DELETE` endpoints soft-delete by default and
  accept `hard_delete=1` to permanently remove (requires the matching
  `*:delete_hard` scope and permission). User/node/attachment deletes have their
  own options (e.g. `rename_to`, `delete_children`).
- **Pagination.** List endpoints return a `pagination` object
  (`current_page`, `last_page`, `per_page`, `shown`, `total`) and accept `page`.
- **Binary endpoints.** `/attachments/{id}/data`, `/attachments/{id}/thumbnail`,
  `/attachments/{id}/retina-thumbnail`, `/media/{id}/data`, and
  `/resource-versions/{id}/download` return raw file data, not JSON.
- **Custom add-on endpoints.** Add-ons can register their own API routes; see
  `docs/10-rest-api.md` ("Writing Custom API Controllers") for the controller
  pattern (`XF\Api\Controller\AbstractController`, `assertApiScope()`,
  `toApiArray()`).

**See also:** `docs/10-rest-api.md` (REST concepts, OAuth flow, webhooks),
`docs/04-controllers-routing.md` (routing & controllers),
`docs/08-permissions-options-phrases.md` (permissions that back API scopes).
