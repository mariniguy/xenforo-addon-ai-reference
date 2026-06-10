# The Criteria System

> Source: [xenforo.com/docs/dev/criteria/](https://xenforo.com/docs/dev/criteria/)

---

## Overview

The criteria system lets admins configure conditions that are checked against users or pages. It powers trophies, user-group promotions, and notices — and you can use it in your own add-ons.

A **criterion** = a `rule` (snake_case string) + optional `data` array.

At match time, the rule is converted to a camelCase method name and called on the criteria class:
`like_count` → `_matchLikeCount()`

---

## Built-in criteria types

| Type | Class | Used by |
|------|-------|---------|
| User criteria | `XF\Criteria\User` | Trophies, user-group promotions, notices |
| Page criteria | `XF\Criteria\Page` | Notices |

---

## How matching works

```php
// 1. Create a criteria object from saved data
/** @var \XF\Criteria\User $criteria */
$criteria = \XF::app()->criteria('XF:User', $entity->user_criteria);

// 2. Optionally: empty selection = no match (important for destructive ops!)
$criteria->setMatchOnEmpty(false);

// 3. Test against a user
if ($criteria->isMatched(\XF::visitor()))
{
    // visitor matches ALL selected criteria
}
```

`isMatched()` internally:
1. For each criterion, converts `rule` → `_match<Rule>()` method name
2. Calls that method — returns `true` = matches, `false` = fails
3. Unknown rules fire the `criteria_user` / `criteria_page` code event
4. If no criteria selected, returns `$matchOnEmpty` (default `true`)

---

## Storing criteria in an entity

```php
// Entity column
'user_criteria' => ['type' => self::JSON_ARRAY, 'default' => []],
'page_criteria' => ['type' => self::JSON_ARRAY, 'default' => []],

// In controller — save form input directly
$userCriteriaInput = $this->filter('user_criteria', 'array');
$form->basicEntitySave($entity, [
    'user_criteria' => $userCriteriaInput,
    'page_criteria' => $this->filter('page_criteria', 'array'),
]);
```

---

## Template UI — `helper_criteria`

Use the built-in `helper_criteria` admin template to render criteria checkboxes:

```php
// Controller — prepare data
$savedCriteria = $entity->user_criteria;
$criteria      = \XF::app()->criteria('XF:User', $savedCriteria);
$criteriaData  = $criteria->getExtraTemplateData();

$viewParams = [
    'criteria'     => $criteria->getCriteriaForTemplate(),
    'criteriaData' => $criteriaData,
];
```

```html
<!-- Template — without tabs -->
<xf:macro template="helper_criteria" name="user_panes"
    arg-container="0"
    arg-criteria="{$criteria}"
    arg-data="{$criteriaData}" />

<!-- Template — with tabs (full tab/pane structure) -->
<h2 class="block-tabHeader tabs hScroller" data-xf-init="h-scroller tabs" role="tablist">
    <span class="hScroller-scroll">
        <a class="tabs-tab is-active" role="tab" tabindex="0" aria-controls="main">Settings</a>
        <xf:macro template="helper_criteria" name="user_tabs" />
        <xf:macro template="helper_criteria" name="page_tabs" />
    </span>
</h2>

<ul class="block-body tabPanes">
    <li class="is-active" role="tabpanel" id="main">
        <!-- your other fields -->
    </li>
    <xf:macro template="helper_criteria" name="user_panes"
        arg-criteria="{$criteria}"
        arg-data="{$criteriaData}" />
    <xf:macro template="helper_criteria" name="page_panes"
        arg-criteria="{{ [] }}"
        arg-data="{{ [] }}" />
</ul>
```

---

## Custom criterion on an existing type (User)

### Step 1 — Add the input to `helper_criteria` via template modification

Target template: `helper_criteria` (Admin tab).  
Find the appropriate `<!--[XF:user:content_bottom]-->` comment and insert before it:

```html
<xf:option name="user_criteria[likes_on_single][rule]" value="likes_on_single"
    selected="{$criteria.likes_on_single}"
    label="Has at least X likes on a single post:">
    <xf:numberbox name="user_criteria[likes_on_single][data][likes]"
        value="{$criteria.likes_on_single.likes}"
        size="5" min="0" step="1" />
</xf:option>
$0
```

### Step 2 — Handle the unknown rule via a code event listener

Register: event `criteria_user`, callback `Demo\Portal\Listener::criteriaUser`

```php
public static function criteriaUser(
    string $rule,
    array $data,
    \XF\Entity\User $user,
    bool &$returnValue
): void {
    switch ($rule)
    {
        case 'likes_on_single':
            $likes = \XF::db()->fetchOne(
                'SELECT `reaction_score` FROM `xf_post`
                 WHERE `user_id` = ?
                 ORDER BY `reaction_score` DESC
                 LIMIT 1',
                [$user->user_id]
            );
            $returnValue = is_numeric($likes) && $likes >= $data['likes'];
            break;
    }
}
```

---

## Writing a custom criteria type

For criteria that test something other than a user (e.g., a Post), create a new criteria class:

```php
<?php

namespace Demo\Portal\Criteria;

use XF\Criteria\AbstractCriteria;

class Post extends AbstractCriteria
{
    // Match: post has at least X reactions
    protected function _matchReactionScore(array $data, \XF\Entity\Post $post): bool
    {
        return $post->reaction_score >= $data['score'];
    }

    // Match: post author has a specific username
    protected function _matchUsername(array $data, \XF\Entity\Post $post): bool
    {
        return $post->username === $data['name'];
    }

    // Match: post was edited at least X times
    protected function _matchEditedCount(array $data, \XF\Entity\Post $post): bool
    {
        return $post->edit_count >= $data['count'];
    }

    /**
     * Custom isMatched for non-User entities.
     * Mirrors AbstractCriteria::isMatched() but accepts a Post.
     */
    public function isMatchedPost(\XF\Entity\Post $post): bool
    {
        if (!$this->criteria)
        {
            return $this->matchOnEmpty;
        }

        foreach ($this->criteria as $criterion)
        {
            $rule = $criterion['rule'];
            $data = isset($criterion['data']) ? $criterion['data'] : [];

            $method = '_match' . \XF\Util\Php::camelCase($rule);
            if (method_exists($this, $method))
            {
                if (!$this->$method($data, $post))
                {
                    return false;
                }
            }
            else
            {
                if (!$this->isUnknownMatchedPost($rule, $data, $post))
                {
                    return false;
                }
            }
        }

        return true;
    }

    protected function isUnknownMatchedPost(string $rule, array $data, \XF\Entity\Post $post): bool
    {
        return false;
    }
}
```

**Using your custom criteria type:**

```php
// Create from form input
/** @var \Demo\Portal\Criteria\Post $postCriteria */
$postCriteria = \XF::app()->criteria('Demo\Portal:Post', $criteriaInput);
$postCriteria->setMatchOnEmpty(false);

// Test against a post
$posts = \XF::finder('XF:Post')->limit(100)->fetch();
foreach ($posts as $post)
{
    if ($postCriteria->isMatchedPost($post))
    {
        // post matches all selected criteria
    }
}
```

---

## Adding your type to `helper_criteria`

Create a template modification targeting `helper_criteria` (Admin tab).  
Search type: Regular expression. Find: `/$/`

Replace with your tab and pane macros:

```html
<xf:macro name="demo_portal_post_tabs" arg-container="" arg-active="">
    <xf:set var="$tabs">
        <a class="tabs-tab{{ $active == 'demo_portal_post' ? ' is-active' : '' }}"
            role="tab" tabindex="0"
            aria-controls="{{ unique_id('criteriaPostDemo') }}">Post criteria</a>
    </xf:set>
    <xf:if is="$container">
        <div class="tabs" role="tablist">{$tabs|raw}</div>
    <xf:else />
        {$tabs|raw}
    </xf:if>
</xf:macro>

<xf:macro name="demo_portal_post_panes" arg-container="" arg-active="" arg-criteria="!" arg-data="!">
    <xf:set var="$panes">
        <li class="{{ $active == 'demo_portal_post' ? 'is-active' : '' }}"
            role="tabpanel"
            id="{{ unique_id('criteriaPostDemo') }}">

            <xf:checkboxrow label="Post conditions">

                <xf:option label="Has at least X reactions"
                    name="post_criteria[reaction_score][rule]"
                    value="reaction_score"
                    selected="{$criteria.reaction_score}">
                    <xf:numberbox name="post_criteria[reaction_score][data][score]"
                        value="{$criteria.reaction_score.score}"
                        size="5" min="0" step="1" />
                </xf:option>

                <xf:option label="Was edited at least X times"
                    name="post_criteria[edited_count][rule]"
                    value="edited_count"
                    selected="{$criteria.edited_count}">
                    <xf:numberbox name="post_criteria[edited_count][data][count]"
                        value="{$criteria.edited_count.count}"
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

## `getExtraTemplateData()` — inject extra data into criteria templates

Override in your criteria class to provide data (e.g., lists of options) to the template:

```php
public function getExtraTemplateData(): array
{
    $templateData = parent::getExtraTemplateData();

    /** @var \XF\Repository\Forum $forumRepo */
    $forumRepo = \XF::repository('XF:Forum');
    $templateData['forums'] = $forumRepo->getForumOptionsData(false);

    return $templateData;
}
```

Or use the `criteria_template_data` event to add data to an **existing** criteria type without
extending it:

```php
public static function criteriaTemplateData(array &$templateData): void
{
    $templateData['myCustomData'] = \XF::repository('Demo\Portal:Item')->getItemOptions();
}
```

---

## `setMatchOnEmpty`

Controls what happens when the admin selects **no criteria**:

```php
$criteria->setMatchOnEmpty(true);   // no selection = everything matches (default)
$criteria->setMatchOnEmpty(false);  // no selection = nothing matches

// IMPORTANT: Always set to false for destructive operations like bulk-delete
$postCriteria->setMatchOnEmpty(false);
```

---

## Important warning: large datasets

Never do this:
```php
// BAD — loads every post into memory
$posts = \XF::finder('XF:Post')->fetch();
foreach ($posts as $post) { /* match */ }
```

Use a Job for large datasets:
```php
// GOOD — process in batches via Job system
\XF::app()->jobManager()->enqueue('Demo\Portal:MatchPostCriteria', [
    'start'    => 0,
    'criteria' => $postCriteriaInput,
]);
```
