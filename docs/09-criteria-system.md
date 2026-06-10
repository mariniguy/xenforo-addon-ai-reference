# Criteria System

## Overview

The Criteria system lets admins select conditions that are tested against users or page context. It is used by:
- Trophies
- User group promotions
- Forum notices
- Any add-on that needs conditional matching

Each criterion is a checkbox (optionally with inputs) stored as a **rule** + **data** pair.

---

## Criteria Types

| Type | Class | Matches against |
|------|-------|----------------|
| User | `XF\Criteria\User` | User entity properties |
| Page | `XF\Criteria\Page` | Current page/request context |
| Custom | extends `AbstractCriteria` | Any add-on-defined entity |

---

## Criterion Structure

Every criterion has:
- **Rule** — a `snake_case` string (e.g., `has_avatar`, `like_count`)
- **Data** — optional array of additional parameters (e.g., `['likes' => 5]`)

The rule converts to camelCase and is prefixed with `_match` to find the handler method:

```
rule: like_count   → method: _matchLikeCount(array $data, User $user)
rule: has_avatar   → method: _matchHasAvatar(array $data, User $user)
```

---

## How Matching Works

```php
// 1. Create a criteria object from saved data
/** @var \XF\Criteria\User $criteria */
$criteria = \XF::app()->criteria('XF:User', $entity->user_criteria);

// 2. Match a user
if ($criteria->isMatched(\XF::visitor())) {
    // All selected criteria match
}
```

`isMatched()` iterates every selected criterion:
1. Converts rule → method name
2. Calls the method if it exists, expects `bool`
3. If method missing → calls `isUnknownMatched()` → fires `criteria_user` event
4. Returns `true` only if **all** criteria pass

### Empty criteria behavior

```php
// Default: empty criteria = match (returns true)
$criteria->isMatched($user); // true if nothing selected

// Override: empty criteria = no match
$criteria->setMatchOnEmpty(false);
$criteria->isMatched($user); // false if nothing selected
```

---

## Built-in User Criteria Examples

From `XF\Criteria\User`:

```php
protected function _matchLikeCount(array $data, \XF\Entity\User $user): bool
{
    return $user->like_count >= $data['likes'];
}

protected function _matchHasAvatar(array $data, \XF\Entity\User $user): bool
{
    return $user->user_id && ($user->avatar_date || $user->gravatar);
}

protected function _matchMessageCount(array $data, \XF\Entity\User $user): bool
{
    return $user->message_count >= $data['messages'];
}

protected function _matchTrophyPoints(array $data, \XF\Entity\User $user): bool
{
    return $user->trophy_points >= $data['points'];
}

protected function _matchUserGroupMember(array $data, \XF\Entity\User $user): bool
{
    $groupIds = array_merge([$user->user_group_id], $user->secondary_group_ids);
    return in_array($data['user_group_id'], $groupIds);
}
```

---

## Using `helper_criteria` Template

### Prepare data in controller

```php
$savedCriteria = $entity->user_criteria; // JSON_ARRAY column

$criteria = $this->app()->criteria('XF:User', $savedCriteria);
$criteriaData = $criteria->getExtraTemplateData();

$viewParams = [
    'criteria'     => $criteria->getCriteriaForTemplate(),
    'criteriaData' => $criteriaData,
];
```

### Template with tabs

```html
<xf:form action="{{ link('my-page/save') }}" class="block">
    <div class="block-container">

        <h2 class="block-tabHeader tabs hScroller" data-xf-init="h-scroller tabs" role="tablist">
            <span class="hScroller-scroll">
                <a class="tabs-tab is-active" role="tab" tabindex="0"
                   aria-controls="ctrl_mainTab">{{ phrase('settings') }}</a>
                <xf:macro template="helper_criteria" name="user_tabs" />
                <xf:macro template="helper_criteria" name="page_tabs" />
            </span>
        </h2>

        <ul class="block-body tabPanes">
            <li class="is-active" role="tabpanel" id="ctrl_mainTab">
                <xf:textboxrow name="title" value="{$entity.title}" label="{{ phrase('title') }}" />
            </li>
            <xf:macro template="helper_criteria" name="user_panes"
                arg-criteria="{$criteria}" arg-data="{$criteriaData}" />
            <xf:macro template="helper_criteria" name="page_panes"
                arg-criteria="{$criteria}" arg-data="{$criteriaData}" />
        </ul>

        <xf:submitrow sticky="true" icon="save" />
    </div>
</xf:form>
```

### Template without tabs

```html
<xf:macro template="helper_criteria" name="user_panes"
    arg-container="0"
    arg-criteria="{$criteria}"
    arg-data="{$criteriaData}" />
```

Pass `{{ [] }}` to `arg-criteria` when no saved data exists.

---

## Adding a Custom Criterion to Existing User Criteria

### Step 1: Template modification on `helper_criteria`

Find the appropriate insertion comment (e.g., `<!--[XF:user:content_bottom]-->`) and add your criterion option:

```html
<xf:option name="user_criteria[likes_on_single][rule]"
    value="likes_on_single"
    selected="{$criteria.likes_on_single}"
    label="Likes on single message:">
    <xf:numberbox name="user_criteria[likes_on_single][data][likes]"
        value="{$criteria.likes_on_single.likes}"
        size="5" min="0" step="1" />
</xf:option>
$0
```

### Step 2: Code event listener for `criteria_user`

```php
<?php

namespace Demo\Addon;

class Listener
{
    public static function criteriaUser(
        string $rule,
        array $data,
        \XF\Entity\User $user,
        bool &$returnValue
    ): void {
        switch ($rule) {
            case 'likes_on_single':
                $db = \XF::db();
                $maxLikes = $db->fetchOne(
                    'SELECT `likes` FROM `xf_post`
                     WHERE `user_id` = ?
                     ORDER BY `likes` DESC LIMIT 1',
                    [$user->user_id]
                );

                $returnValue = is_numeric($maxLikes)
                    && (int) $maxLikes >= (int) ($data['likes'] ?? 0);
                break;
        }
    }
}
```

Register via Admin CP > Development > Code event listeners:
- Event: `criteria_user`
- Class: `Demo\Addon\Listener`
- Method: `criteriaUser`

---

## Writing a Custom Criteria Type

For criteria about a completely different entity (e.g., Post):

### Criteria class

```php
<?php

namespace PostsRemover\Criteria;

use XF\Criteria\AbstractCriteria;

class Post extends AbstractCriteria
{
    protected function _matchLikeCount(array $data, \XF\Entity\Post $post): bool
    {
        return $post->likes && $post->likes >= (int) ($data['likes'] ?? 0);
    }

    protected function _matchUsername(array $data, \XF\Entity\Post $post): bool
    {
        return $post->username === ($data['name'] ?? '');
    }

    protected function _matchEditedCount(array $data, \XF\Entity\Post $post): bool
    {
        return $post->edit_count && $post->edit_count >= (int) ($data['count'] ?? 0);
    }

    /**
     * Custom matching for Post entity (AbstractCriteria::isMatched() is for User).
     */
    public function isMatchedPost(\XF\Entity\Post $post): bool
    {
        if (!$this->criteria) {
            return $this->matchOnEmpty;
        }

        foreach ($this->criteria as $criterion) {
            $rule = $criterion['rule'];
            $data = $criterion['data'] ?? [];

            $method = '_match' . \XF\Util\Php::camelCase($rule);
            if (method_exists($this, $method)) {
                if (!$this->$method($data, $post)) {
                    return false;
                }
            } else {
                if (!$this->isUnknownMatchedPost($rule, $data, $post)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function isUnknownMatchedPost(
        string $rule,
        array $data,
        \XF\Entity\Post $post
    ): bool {
        return false;
    }
}
```

### Template

```html
<xf:checkboxrow label="Post criteria">

    <xf:option label="Post has at least X likes"
        name="post_criteria[like_count][rule]" value="like_count">
        <xf:numberbox name="post_criteria[like_count][data][likes]"
            size="5" min="0" step="1" />
    </xf:option>

    <xf:option label="Post author username"
        name="post_criteria[username][rule]" value="username">
        <xf:textbox name="post_criteria[username][data][name]" />
    </xf:option>

    <xf:option label="Post edited at least X times"
        name="post_criteria[edited_count][rule]" value="edited_count">
        <xf:numberbox name="post_criteria[edited_count][data][count]"
            size="5" min="0" step="1" />
    </xf:option>

</xf:checkboxrow>
```

### Controller

```php
public function actionRemove()
{
    $this->assertPostOnly();

    $postCriteriaInput = $this->filter('post_criteria', 'array');

    /** @var \PostsRemover\Criteria\Post $postCriteria */
    $postCriteria = $this->app()->criteria('PostsRemover:Post', $postCriteriaInput);
    $postCriteria->setMatchOnEmpty(false); // don't delete everything if nothing selected

    $posts = $this->finder('XF:Post')->fetch();
    $deleted = 0;

    foreach ($posts as $post) {
        if ($postCriteria->isMatchedPost($post)) {
            $post->delete();
            $deleted++;
        }
    }

    return $this->message("Done! $deleted posts removed.");
}
```

---

## getExtraTemplateData()

Provides extra data to criteria templates (e.g., lists of categories):

### Override in your criteria class

```php
public function getExtraTemplateData(): array
{
    $templateData = parent::getExtraTemplateData();

    $templateData['demoCategories'] = \XF::finder('Demo:Category')
        ->order('display_order')
        ->fetch();

    return $templateData;
}
```

### Via event listener (for existing types)

Listen to `criteria_template_data`:

```php
public static function criteriaTemplateData(array &$templateData): void
{
    $templateData['demoCategories'] = \XF::finder('Demo:Category')->fetch();
}
```

---

## Adding a Custom Type to helper_criteria

Create a template modification on `helper_criteria` (regex find `/$/ ` to append at end):

```html
<xf:macro name="post_tabs" arg-container="" arg-active="">
    <xf:set var="$tabs">
        <a class="tabs-tab{{ $active == 'post' ? ' is-active' : '' }}"
           role="tab" tabindex="0"
           aria-controls="{{ unique_id('criteriaPost') }}">Post criteria</a>
    </xf:set>
    <xf:if is="$container">
        <div class="tabs" role="tablist">{$tabs|raw}</div>
    <xf:else />
        {$tabs|raw}
    </xf:if>
</xf:macro>

<xf:macro name="post_panes" arg-container="" arg-active="" arg-criteria="!" arg-data="!">
    <xf:set var="$panes">
        <li class="{{ $active == 'post' ? ' is-active' : '' }}"
            role="tabpanel"
            id="{{ unique_id('criteriaPost') }}">

            <xf:checkboxrow label="Post conditions">
                <xf:option name="post_criteria[like_count][rule]"
                    value="like_count"
                    selected="{$criteria.like_count}"
                    label="Has at least X likes">
                    <xf:numberbox name="post_criteria[like_count][data][likes]"
                        value="{$criteria.like_count.likes}"
                        size="5" min="0" step="1" />
                </xf:option>
            </xf:checkboxrow>

        </li>
    </xf:set>
    <xf:if is="$container">
        <ul class="tabPanes">{$panes|raw}</ul>
    <xf:else />
        {$panes|raw}
    </xf:if>
</xf:macro>
```

---

## Storing and Loading Criteria

Criteria data is stored in `JSON_ARRAY` entity columns:

```php
// Entity column definition
'user_criteria' => ['type' => self::JSON_ARRAY, 'default' => []],
'page_criteria' => ['type' => self::JSON_ARRAY, 'default' => []],
```

```php
// Controller: save from form
$form->basicEntitySave($entity, [
    'user_criteria' => $this->filter('user_criteria', 'array'),
    'page_criteria' => $this->filter('page_criteria', 'array'),
]);

// Controller: prepare for view
$criteria = $this->app()->criteria('XF:User', $entity->user_criteria);
$viewParams = [
    'criteria'     => $criteria->getCriteriaForTemplate(),
    'criteriaData' => $criteria->getExtraTemplateData(),
];
```
