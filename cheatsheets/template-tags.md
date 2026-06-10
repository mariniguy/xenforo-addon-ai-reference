# XenForo Template Tag & Function Reference

> XenForo 2.3+ — public, admin, and email templates share the same syntax.

---

## Output syntax

| Syntax | Purpose | Escaping |
|--------|---------|---------|
| `{$variable}` | Output a variable | Auto-escaped (XSS safe) |
| `{$variable\|raw}` | Output trusted HTML | **No escaping** — only for sanitized values |
| `{$variable\|for_attr}` | Output in HTML attribute | Attribute-safe escaping |
| `{{ expression }}` | Evaluate: math, ternary, function call | Auto-escaped |
| `{{ expression \| raw }}` | Expression result as trusted HTML | No escaping |

---

## Control flow tags

```html
<!-- if / elseif / else -->
<xf:if is="$condition">
    ...
<xf:elseif is="$other" />
    ...
<xf:else />
    ...
</xf:if>

<!-- Supported operators in conditions -->
AND  OR  NOT  ==  !=  <>  >  >=  <  <=
$x instanceof \XF\Entity\User
$arr is empty
$arr is not empty
$var === null
$var !== null

<!-- foreach with empty state -->
<xf:foreach loop="$items" value="$item" key="$key" i="$i">
    <!-- $i is 1-based counter -->
<xf:else />
    <!-- rendered when loop is empty -->
</xf:foreach>

<!-- set a variable -->
<xf:set var="$myVar" value="{{ $a + $b }}" />
<xf:set var="$label" value="{{ $item.is_active ? 'Active' : 'Inactive' }}" />
```

---

## Page structure tags

```html
<!-- Sets <h1> and browser tab title -->
<xf:title>{{ phrase('page_title') }}</xf:title>

<!-- Override just the visible <h1> (title still used for tab) -->
<xf:h1>Different visible heading</xf:h1>

<!-- Meta description -->
<xf:description>{{ phrase('page_desc') }}</xf:description>

<!-- Breadcrumb trail -->
<xf:breadcrumb href="{{ link('forums') }}">Forums</xf:breadcrumb>
<xf:breadcrumb href="{{ link('forums', $forum) }}">{$forum.title}</xf:breadcrumb>

<!-- Sidebar content -->
<xf:sidebar>
    <div class="block">...</div>
</xf:sidebar>

<!-- Extra HTML in <head> -->
<xf:head>
    <meta name="robots" content="noindex" />
</xf:head>

<!-- Buttons/links in the page header bar -->
<xf:pageaction>
    <a href="{{ link('demo/add') }}" class="button button--primary">Add</a>
</xf:pageaction>

<!-- ACP only: action bar -->
<xf:actionbar>
    <a href="{{ link('demo/add') }}" class="button button--primary">Add</a>
</xf:actionbar>
```

---

## Asset tags

```html
<!-- Include CSS/LESS template -->
<xf:css src="demo_styles.less" />

<!-- Include JavaScript file (relative to js/ directory) -->
<xf:js src="demo/addon/main.js" />
<xf:js src="demo/addon/main.js" min="true" />   <!-- use .min.js in prod -->
<xf:js src="demo/addon/main.js" prod="true" />  <!-- only in production -->
<xf:js src="demo/addon/main.js" dev="true" />   <!-- only in dev mode -->
```

---

## Form tags

```html
<!-- Form wrapper -->
<xf:form action="{{ link('demo/save') }}" ajax="true" class="block">
<div class="block-container"><div class="block-body">

    <!-- Text input -->
    <xf:textboxrow name="title" value="{$title}"
        label="{{ phrase('title') }}"
        explain="{{ phrase('title_explain') }}"
        required="true"
        maxlength="150"
        placeholder="Enter title..." />

    <!-- Standalone text input (no label/row wrapper) -->
    <xf:textbox name="search" value="{$query}" />

    <!-- Textarea -->
    <xf:textarearow name="body" value="{$body}"
        label="{{ phrase('body') }}"
        rows="5" />

    <!-- Number input -->
    <xf:numberboxrow name="count" value="{$count}"
        label="{{ phrase('count') }}"
        min="0" max="9999" step="1" />

    <!-- Select dropdown -->
    <xf:selectrow name="type" value="{$type}" label="{{ phrase('type') }}">
        <xf:option value="">-- Select --</xf:option>
        <xf:option value="a">Option A</xf:option>
        <xf:option value="b">Option B</xf:option>
    </xf:selectrow>

    <!-- Select from entity collection -->
    <xf:selectrow name="category_id" value="{$categoryId}" label="{{ phrase('category') }}">
        <xf:options source="{$categories}" valueKey="category_id" labelKey="title" />
    </xf:selectrow>

    <!-- Checkbox row (multiple checkboxes) -->
    <xf:checkboxrow label="{{ phrase('options') }}">
        <xf:option name="is_active" value="1" selected="{$isActive}">
            {{ phrase('active') }}
            <xf:hint>{{ phrase('active_explain') }}</xf:hint>
        </xf:option>
        <xf:option name="is_featured" value="1" selected="{$isFeatured}">
            {{ phrase('featured') }}
        </xf:option>
    </xf:checkboxrow>

    <!-- Radio buttons -->
    <xf:radiorrow name="sort" value="{$sort}" label="{{ phrase('sort_by') }}">
        <xf:option value="date">{{ phrase('date') }}</xf:option>
        <xf:option value="title">{{ phrase('title') }}</xf:option>
    </xf:radiorrow>

    <!-- On/off toggle -->
    <xf:checkboxrow label="{{ phrase('enabled') }}">
        <xf:option name="enabled" value="1" selected="{$enabled}" />
    </xf:checkboxrow>

    <!-- Datetime picker -->
    <xf:datetimerow name="event_date" value="{$eventDate}" label="{{ phrase('date') }}" />

    <!-- Token input (user autocomplete) -->
    <xf:tokeninputrow name="user_ids" label="{{ phrase('users') }}"
        value="{$userIds}" ac="username" />

    <!-- Color picker -->
    <xf:colorinputrow name="color" value="{$color}" label="{{ phrase('color') }}" />

    <!-- Hidden values -->
    <xf:hiddenval name="addon_id" value="Demo/Portal" />
    <xf:hiddenval name="_xfSet[featured]" value="1" />

    <!-- Custom row wrapper -->
    <xf:formrow label="{{ phrase('custom') }}">
        <p>Any custom HTML here</p>
    </xf:formrow>

    <!-- Submit button -->
    <xf:submitrow submit="{{ phrase('save') }}" sticky="true" icon="save" />
    <xf:submitrow submit="{{ phrase('save') }}" cancel="{{ link('demo') }}" />

</div></div>
</xf:form>
```

---

## User display tags

```html
<!-- Avatar (sizes: xxs / xs / s / m / l) -->
<xf:avatar user="{$user}" size="s" />
<xf:avatar user="{$user}" size="m" href="{{ link('members', $user) }}" />
<xf:avatar user="{$user}" size="l" defaultname="{$post.username}" href="" />

<!-- Username with styling/link -->
<xf:username user="{$user}" />
<xf:username user="{$user}" href="{{ link('members', $user) }}" />

<!-- Date display -->
<xf:date time="{$timestamp}" />          <!-- relative: "3 hours ago" -->
<xf:datetime time="{$timestamp}" />      <!-- absolute + relative tooltip -->
```

---

## Navigation tags

```html
<!-- Page navigation -->
<xf:pagenav page="{$page}" perpage="{$perPage}" total="{$total}"
    link="demo-portal" wrapperclass="block" />

<!-- Widget position -->
<xf:widgetpos id="demo_portal_view_sidebar" position="sidebar" />
```

---

## Template reuse

```html
<!-- Macro definition (arg! = required, arg-name="default" = optional with default) -->
<xf:macro id="item_card" arg-item="!" arg-showDate="true">
    <div class="block-row">
        <h3>{$item.title}</h3>
        <xf:if is="$showDate">
            <span class="u-muted"><xf:date time="{$item.created_date}" /></span>
        </xf:if>
    </div>
</xf:macro>

<!-- Call macro from same template -->
<xf:macro id="item_card" arg-item="{$item}" />

<!-- Call macro from another template -->
<xf:macro id="other_template::item_card" arg-item="{$item}" arg-showDate="false" />

<!-- Include another template (shares current scope) -->
<xf:include template="demo_shared_header" />

<!-- Include with variable remapping -->
<xf:include template="demo_shared_header">
    <xf:map from="$items" to="$headerItems" />
    <xf:set var="$showCount" value="true" />
</xf:include>

<!-- Template inheritance -->
<xf:extends template="demo_base_layout" />
<xf:extension id="content">
    <p>Replaces parent content block</p>
</xf:extension>
<xf:extension id="content">
    <xf:extensionparent />          <!-- include parent content first -->
    <p>Then append this</p>
</xf:extension>

<!-- Wrap (content declares its layout) -->
<xf:wrap template="demo_account_wrapper">
    <xf:set var="$activeTab" value="settings" />
</xf:wrap>
<!-- Wrapper receives: {$innerContent|raw} -->
```

---

## Template functions

```html
<!-- Phrases -->
{{ phrase('phrase_name') }}
{{ phrase('phrase_name', {'param': $value}) }}
{{ phrase_dynamic($runtimePhraseName) }}

<!-- Links -->
{{ link('threads', $thread) }}
{{ link('threads', $thread, {'page': 2}) }}
{{ link('canonical:portal') }}
{{ link('full:threads', $thread) }}   <!-- absolute URL -->

<!-- Dates -->
{{ date($timestamp) }}                        <!-- date only: "Jun 10, 2026" -->
{{ date($timestamp, 'M j, Y') }}             <!-- custom PHP date format -->
{{ date_time($timestamp) }}                  <!-- date + time -->
{{ time($timestamp) }}                       <!-- time only -->

<!-- Numbers -->
{{ number($value) }}
{{ number($value, 2) }}                       <!-- 2 decimal places -->
{{ $value | number(2) }}                      <!-- filter form -->

<!-- BBCode -->
{{ bb_code($text, 'post') }}
{{ bb_code($text, 'post', $user) }}
{{ bb_code($text, 'post', $user, {'attachments': $attachments, 'viewAttachments': true}) }}
{{ bb_code_clean($text) }}                    <!-- strip to plain text -->

<!-- Avatars / users (function form) -->
{{ avatar($user, 's') }}
{{ avatar($user, 'm', $url) }}

<!-- Style properties -->
{{ property('propertyName') }}
{{ prop('propertyName') }}

<!-- Misc -->
{{ unique_id('prefix') }}                     <!-- unique HTML ID for this render -->
{{ csrf_token() }}                            <!-- CSRF token value -->
{{ $xf.visitor.csrf_token_page }}            <!-- same via global -->
{{ dump($var) }}                              <!-- debug dump -->
```

---

## All filters

| Filter | Effect |
|--------|-------|
| `\|raw` | Bypass HTML escaping (trusted HTML only) |
| `\|for_attr` | Attribute-safe escaping |
| `\|escape` | Force HTML escape |
| `\|number` | Format as number (locale-aware) |
| `\|number(2)` | Format with 2 decimal places |
| `\|date` | Format timestamp as date string |
| `\|nl2br` | Convert newlines to `<br>` |
| `\|json` | JSON-encode the value |
| `\|count` | Count array/collection |
| `\|first` | First element |
| `\|last` | Last element |
| `\|keys` | Array keys |
| `\|lower` | Lowercase string |
| `\|upper` | Uppercase string |
| `\|trim` | Trim whitespace |
| `\|wrap` | Wrap in HTML element |

---

## Global `$xf` variables

| Variable | Type | Description |
|----------|------|-------------|
| `$xf.visitor` | `\XF\Entity\User` | Current user (user_id=0 = guest) |
| `$xf.visitor.user_id` | int | Current user ID |
| `$xf.visitor.username` | string | Current username |
| `$xf.visitor.is_admin` | bool | Is admin |
| `$xf.visitor.is_moderator` | bool | Is moderator |
| `$xf.visitor.avatar_date` | int | Avatar timestamp (0 = no avatar) |
| `$xf.options` | object | Board options (`$xf.options.boardTitle`) |
| `$xf.options.{optionId}` | mixed | Specific option value |
| `$xf.time` | int | Current request timestamp |
| `$xf.language` | `\XF\Entity\Language` | Current language |
| `$xf.language.language_id` | int | Current language ID |
| `$xf.style` | `\XF\Entity\Style` | Current style |
| `$xf.debug` | bool | Debug mode enabled |
| `$xf.versionId` | int | XenForo version ID |
| `$xf.app` | `\XF\App` | Application container |
| `$xf.router` | router | Public router |

---

## Template callbacks

```html
<!-- Method prefix must be: get, render, is, has, can, prepare, build -->
<xf:callback class="Demo\Portal\Template\Helper"
    method="renderFeaturedCount"
    params="['sidebar', true]">
</xf:callback>
```

```php
// PHP class
class Helper
{
    public static function renderFeaturedCount(
        \XF\Template\Templater $templater,
        &$escape,
        string $position,
        bool $showTotal
    ): string {
        $escape = false; // we return trusted HTML
        $count = \XF::finder('Demo\Portal:FeaturedThread')->total();
        return '<span class="featuredCount">' . $count . '</span>';
    }
}
```
