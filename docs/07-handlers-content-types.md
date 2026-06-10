# Handlers and Content Types

## The Handler Pattern

XenForo uses a "handler" pattern to allow different content types to plug into shared systems. Instead of one class knowing about every content type, each content type provides its own handler for each system it participates in.

For example:
- The attachment system doesn't know how a post or conversation message works
- The post content type provides `XF\Attachment\PostHandler` that tells the system how to check permissions, count attachments, and clean up

This is how `post`, `thread`, `profile_post`, and any custom content type from add-ons all participate in the same alert, reaction, attachment, and report systems without those systems needing to be modified.

---

## Content Type Fields Table

The `xf_content_type_field` table maps content types to handler classes:

| Column | Description |
|--------|-------------|
| `content_type` | Content type identifier (e.g., `post`, `thread`, `demo_item`) |
| `field_name` | The handler system (e.g., `attachment_handler_class`) |
| `field_value` | Fully qualified handler class name |

Example row:

| content_type | field_name | field_value |
|---|---|---|
| `post` | `attachment_handler_class` | `XF\Attachment\PostHandler` |
| `post` | `alert_handler_class` | `XF\Alert\PostHandler` |
| `post` | `reaction_handler_class` | `XF\Reaction\PostHandler` |

---

## All Handler Systems

| System | field_name | Base class |
|--------|-----------|------------|
| Activity log | `activity_log_handler_class` | `XF\ActivityLog\AbstractHandler` |
| Alerts | `alert_handler_class` | `XF\Alert\AbstractHandler` |
| Approval queue | `approval_queue_handler_class` | `XF\ApprovalQueue\AbstractHandler` |
| Attachments | `attachment_handler_class` | `XF\Attachment\AbstractHandler` |
| Bookmarks | `bookmark_handler_class` | `XF\Bookmark\AbstractHandler` |
| Change log | `change_log_handler_class` | `XF\ChangeLog\AbstractHandler` |
| Content voting | `content_vote_handler_class` | `XF\ContentVote\AbstractHandler` |
| Edit history | `edit_history_handler_class` | `XF\EditHistory\AbstractHandler` |
| Featured content | `featured_content_handler_class` | `XF\FeaturedContent\AbstractHandler` |
| Find new | `find_new_handler_class` | `XF\FindNew\AbstractHandler` |
| Inline moderation | `inline_mod_handler_class` | `XF\InlineMod\AbstractHandler` |
| Moderator log | `moderator_log_handler_class` | `XF\ModeratorLog\AbstractHandler` |
| News feed | `news_feed_handler_class` | `XF\NewsFeed\AbstractHandler` |
| Polls | `poll_handler_class` | `XF\Poll\AbstractHandler` |
| Reactions | `reaction_handler_class` | `XF\Reaction\AbstractHandler` |
| Reports | `report_handler_class` | `XF\Report\AbstractHandler` |
| Sitemap | `sitemap_handler_class` | `XF\Sitemap\AbstractHandler` |
| Stats | `stats_handler_class` | `XF\Stats\AbstractHandler` |
| Tags | `tag_handler_class` | `XF\Tag\AbstractHandler` |
| Trending content | `trending_content_handler_class` | `XF\TrendingContent\AbstractHandler` |
| Warnings | `warning_handler_class` | `XF\Warning\AbstractHandler` |
| Webhook events | `webhook_handler_class` | `XF\Webhook\Event\AbstractHandler` |

---

## Registering a Content Type Field

In Admin CP: Development > Execution manipulation > Content types (requires development mode).

Add:
- **Content type**: your content type identifier (e.g., `demo_item`)
- **Field name**: the handler system field (e.g., `attachment_handler_class`)
- **Field value**: your handler class (e.g., `Demo\Addon\Attachment\ItemHandler`)

The development output is written to `_output/content_type_fields/`:

```json
{
    "content_type": "demo_item",
    "field_name": "attachment_handler_class",
    "field_value": "Demo\\Addon\\Attachment\\ItemHandler"
}
```

Your entity must also declare `contentType`:

```php
$structure->contentType = 'demo_item';
```

### Discovering Handlers at Runtime

```php
// All attachment handler classes keyed by content type
$handlers = \XF::app()->getContentTypeField('attachment_handler_class');

// Single handler class for a specific content type
$handlerClass = \XF::app()->getContentTypeFieldValue('demo_item', 'attachment_handler_class');

// Instantiate with extension support
$handlerClass = \XF::extendClass($handlerClass);
$handler = new $handlerClass('demo_item');
```

---

## Writing an Attachment Handler

```php
<?php

namespace Demo\Addon\Attachment;

use XF\Entity\Attachment;
use XF\Http\Request;

class ItemHandler extends \XF\Attachment\AbstractHandler
{
    // Return the entity or null if not found/not viewable
    public function getContext(?Attachment $attachment, array $extraContext = []): mixed
    {
        if (isset($extraContext['item_id'])) {
            $item = \XF::em()->find('Demo\Addon:Item', $extraContext['item_id']);
            if ($item && $item->canView()) {
                return $item;
            }
        }
        return null;
    }

    // Can the current user view attachments on this content?
    public function canView(Attachment $attachment, $context, &$error = null): bool
    {
        /** @var \Demo\Addon\Entity\Item $context */
        return $context->canView($error);
    }

    // Can the current user manage (delete) attachments on this content?
    public function canManageAttachments($context, &$error = null): bool
    {
        /** @var \Demo\Addon\Entity\Item $context */
        return $context->canEdit($error);
    }

    // Where to redirect after uploading
    public function getUploadUrl(array $extraContext = []): string
    {
        return \XF::app()->router()->buildLink('demo-items/attachments/upload', null, $extraContext);
    }

    // Called when attachment is deleted
    public function onAttachmentDelete(Attachment $attachment, $context): void
    {
        if ($context instanceof \Demo\Addon\Entity\Item) {
            $context->fastUpdate('attach_count', max(0, $context->attach_count - 1));
        }
    }

    // Get attachment constraints (size, count, extensions)
    public function getConstraints(array $extraContext = []): array
    {
        $options = \XF::options();
        return [
            'extensions' => '',  // empty = all allowed
            'size'       => $options->attachmentMaxFileSize * 1024,
            'count'      => $options->attachmentMaxPerMessage,
            'width'      => 0,
            'height'     => 0,
        ];
    }

    // Return the content type
    public function getContentType(): string
    {
        return 'demo_item';
    }
}
```

---

## Writing an Alert Handler

```php
<?php

namespace Demo\Addon\Alert;

class ItemHandler extends \XF\Alert\AbstractHandler
{
    // Return the content entity for a given ID
    public function getContent(int $contentId): mixed
    {
        return \XF::em()->find('Demo\Addon:Item', $contentId);
    }

    // Given multiple alert records, batch-load the content entities
    public function getContents(array $contentIds): array
    {
        return \XF::em()->findByIds('Demo\Addon:Item', $contentIds);
    }

    // Can the current user view this content?
    public function canViewContent($content, &$error = null): bool
    {
        return ($content instanceof \Demo\Addon\Entity\Item) && $content->canView($error);
    }

    // Return the alert template name
    public function getTemplateName(string $action): string
    {
        return 'demo_addon_alert_item_' . $action;
    }
}
```

Alert templates receive: `$alert`, `$alertAction`, `$content`

```html
<!-- Template: demo_addon_alert_item_reply -->
<xf:macro id="demo_addon_alert_item_reply" arg-alert="!" arg-content="!">
    <xf:username user="{$alert.User}" /> replied to your item
    <a href="{{ link('demo-items', $content) }}">{$content.title}</a>.
</xf:macro>
```

Sending alerts:

```php
/** @var \XF\Repository\Alert $alertRepo */
$alertRepo = \XF::repository('XF:Alert');

$alertRepo->alert(
    $item->User,           // recipient User entity
    \XF::visitor(),        // sender User entity
    'demo_item',           // content type
    $item->item_id,        // content ID
    'reply',               // action
    []                     // extra data
);
```

---

## Writing a Report Handler

```php
<?php

namespace Demo\Addon\Report;

use XF\Entity\Report;

class ItemHandler extends \XF\Report\AbstractHandler
{
    // Build the report entity from the submitted form
    public function setupReportEntityFromContent(Report $report, $content): void
    {
        /** @var \Demo\Addon\Entity\Item $content */
        $report->content_user_id = $content->user_id;
        $report->content_info = [
            'title'   => $content->title,
            'item_id' => $content->item_id,
        ];
    }

    // Get the content entity
    public function getContent(int $contentId): mixed
    {
        return \XF::em()->find('Demo\Addon:Item', $contentId);
    }

    // Can the current user report this content?
    public function canReport($content, &$error = null): bool
    {
        return \XF::visitor()->user_id > 0 && $content->canView();
    }

    // Can the current user view this report?
    public function canView(Report $report, &$error = null): bool
    {
        return \XF::visitor()->hasPermission('general', 'viewReports');
    }

    // Return the template for the report list
    public function getReportListTemplate(): string
    {
        return 'demo_addon_report_item';
    }
}
```

---

## Writing an Approval Queue Handler

```php
<?php

namespace Demo\Addon\ApprovalQueue;

use XF\Entity\ApprovalQueue;

class ItemHandler extends \XF\ApprovalQueue\AbstractHandler
{
    // Get the content entity to display in approval queue
    public function getContent(int $contentId): mixed
    {
        return \XF::em()->find('Demo\Addon:Item', $contentId);
    }

    // Can current user action items in approval queue?
    public function canActionItems(&$error = null): bool
    {
        return \XF::visitor()->hasPermission('demo', 'approveItems');
    }

    // Approve the content
    public function actionApprove($content, ApprovalQueue $approval): void
    {
        /** @var \Demo\Addon\Entity\Item $content */
        $content->item_state = 'visible';
        $content->save();
    }

    // Delete the content
    public function actionDelete($content, ApprovalQueue $approval): void
    {
        /** @var \Demo\Addon\Entity\Item $content */
        $content->delete();
    }

    // Return the template for the queue item
    public function getApprovalQueueTemplate(): string
    {
        return 'demo_addon_approval_item';
    }
}
```

---

## Writing a News Feed Handler

```php
<?php

namespace Demo\Addon\NewsFeed;

class ItemHandler extends \XF\NewsFeed\AbstractHandler
{
    // Return the content entity for a given ID
    public function getContent(int $contentId): mixed
    {
        return \XF::em()->find('Demo\Addon:Item', $contentId);
    }

    // Can the current user view this content in the news feed?
    public function canViewContent($content, &$error = null): bool
    {
        return $content && $content->canView($error);
    }

    // Return news feed template name
    public function getTemplateName(string $action): string
    {
        return 'demo_addon_news_feed_item_' . $action;
    }
}
```

Publishing to news feed:

```php
/** @var \XF\Repository\NewsFeed $newsFeedRepo */
$newsFeedRepo = \XF::repository('XF:NewsFeed');

$newsFeedRepo->publish(
    'demo_item',    // content type
    $item->item_id, // content ID
    'insert',       // action (insert, edit, etc.)
    $item->user_id, // user ID
    []              // extra data
);
```

---

## Writing a Search Handler

```php
<?php

namespace Demo\Addon\Search\Data;

use XF\Search\Data\AbstractData;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;
use XF\Search\Query\Query;

class Item extends AbstractData
{
    // Return the entity for a content ID
    public function getEntityWith(): array
    {
        return ['User'];
    }

    // Build the search index record
    public function getIndexData($entity): ?IndexRecord
    {
        /** @var \Demo\Addon\Entity\Item $entity */
        if ($entity->item_state !== 'visible') {
            return null;
        }

        return IndexRecord::create('demo_item', $entity->item_id, [
            'title'        => $entity->title,
            'message'      => $entity->description,
            'date'         => $entity->created_date,
            'user_id'      => $entity->user_id,
            'discussion_id' => $entity->item_id,
        ]);
    }

    // Define metadata structure for filtering
    public function setupMetadataStructure(MetadataStructure $structure): void
    {
        $structure->addField('item_category', MetadataStructure::INT);
    }

    // Can the current user view this content in results?
    public function canViewContent($entity, &$error = null): bool
    {
        return $entity->canView($error);
    }

    // Return the template for search result display
    public function getTemplateData($entity, array $options = []): array
    {
        return [
            'item' => $entity,
        ];
    }
}
```

---

## Writing a Sitemap Handler

```php
<?php

namespace Demo\Addon\Sitemap;

class Item extends \XF\Sitemap\AbstractHandler
{
    // Get entities to include in the sitemap (batch approach)
    public function getRecords(int $start, int $limit): iterable
    {
        return \XF::finder('Demo\Addon:Item')
            ->where('item_id', '>', $start)
            ->where('item_state', 'visible')
            ->order('item_id')
            ->limit($limit)
            ->fetch();
    }

    // Build a sitemap entry for a single entity
    public function getEntryFromRecord($record): ?array
    {
        /** @var \Demo\Addon\Entity\Item $record */
        $router = \XF::app()->router('public');

        return [
            'url'      => $router->buildLink('canonical:demo-items', $record),
            'lastmod'  => $record->created_date,
            'priority' => 0.5,
        ];
    }

    // How many records to process per batch
    public function getRecordBatchSize(): int
    {
        return 500;
    }
}
```

---

## Writing a Reaction Handler

```php
<?php

namespace Demo\Addon\Reaction;

class ItemHandler extends \XF\Reaction\AbstractHandler
{
    // Return the content entity
    public function getContent(int $contentId): mixed
    {
        return \XF::em()->find('Demo\Addon:Item', $contentId);
    }

    // Can the current user react to this content?
    public function canReact($content, &$error = null): bool
    {
        /** @var \Demo\Addon\Entity\Item $content */
        return \XF::visitor()->user_id && $content->canView();
    }

    // Called when reaction count changes — update cached value
    public function onReactionCountChange($content, int $change): void
    {
        /** @var \Demo\Addon\Entity\Item $content */
        $content->fastUpdate('reaction_score', $content->reaction_score + $change);
    }

    // Return the reaction template name
    public function getTemplateName(): string
    {
        return 'demo_addon_reaction_item';
    }
}
```
