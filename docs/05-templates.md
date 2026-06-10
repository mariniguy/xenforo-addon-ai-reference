# XenForo Template Reference

## Template Types

| Type | Purpose | Location |
|------|---------|----------|
| Public | Forum front-end pages | `_output/templates/public/` |
| Admin | Admin Control Panel | `_output/templates/admin/` |
| Email | System emails | `_output/templates/email/` |

All three types share the same syntax. CSS/LESS templates use the same syntax but are compiled as stylesheets.

---

## Core Syntax

| Syntax | Purpose |
|--------|---------|
| `{$variableName}` | Output a variable (auto-escaped) |
| `{$object.property}` | Access object property or array key |
| `{{ expression }}` | Evaluate expression: functions, filters, math, ternaries |
| `<xf:tagName />` | XenForo template tag |

```html
<h1>{$title}</h1>
<p>Posted by: {$thread.User.username}</p>
<p>{{ phrase('welcome_message') }}</p>
<p>{{ date($xf.time, 'M j, Y') }}</p>
<p>{{ $count > 10 ? 'many' : 'few' }}</p>
```

> Use `{$var}` for simple variable output. Use `{{ }}` for function calls, filters, and expressions.

---

## Escaping

All output is auto-escaped by default. Override when needed:

```html
<!-- Output pre-rendered HTML (trusted content only) -->
{$renderedBbCode|raw}

<!-- Attribute-safe escaping -->
<div title="{$description|for_attr}">...</div>

<!-- Force escaping explicitly -->
{{ $value | escape }}
```

> Never use `|raw` with user-supplied content — it creates XSS vulnerabilities.

---

## Global Variables (`$xf`)

Available in every template without being passed from the controller:

| Variable | Description |
|----------|-------------|
| `$xf.visitor` | Current user entity |
| `$xf.visitor.user_id` | Current user ID (0 = guest) |
| `$xf.visitor.username` | Current username |
| `$xf.visitor.is_admin` | Whether current user is admin |
| `$xf.visitor.is_moderator` | Whether current user is moderator |
| `$xf.options.optionName` | Any XenForo option value |
| `$xf.time` | Current Unix timestamp |
| `$xf.language` | Current language entity |
| `$xf.style` | Current style entity |
| `$xf.debug` | Whether debug mode is on |
| `$xf.versionId` | XenForo version ID |
| `$xf.app` | App instance |

---

## Control Structures

### If / ElseIf / Else

```html
<xf:if is="$xf.visitor.is_admin">
    <p>Admin only</p>
<xf:elseif is="$xf.visitor.is_moderator" />
    <p>Moderator only</p>
<xf:else />
    <p>Everyone else</p>
</xf:if>
```

Supported operators:

| Operator | Description |
|----------|-------------|
| `&&` / `AND` | Logical AND |
| `\|\|` / `OR` | Logical OR |
| `!` | Logical NOT |
| `==`, `!=`, `>`, `<`, `>=`, `<=` | Comparison |
| `===`, `!==` | Strict comparison |
| `? :` | Ternary |
| `?:` | Elvis (short ternary) |
| `??` | Null coalesce |
| `is empty` / `is not empty` | Emptiness check |
| `instanceof` | Type check |

### Foreach Loop

```html
<xf:foreach loop="$items" value="$item" key="$key" i="$i">
    <p>#{$i}: {$item.title}</p>
<xf:else />
    <p>No items found.</p>
</xf:foreach>
```

- `loop` — array or collection to iterate
- `value` — variable for each element
- `key` — variable for the array key (optional)
- `i` — 1-based iteration counter (optional)
- `if` — per-iteration condition (optional)

### Set Variable

```html
<!-- From expression -->
<xf:set var="$rowClass" value="{{ $i % 2 == 0 ? 'even' : 'odd' }}" />

<!-- From block content (content is escaped to string) -->
<xf:set var="$greeting">Hello, {$xf.visitor.username}!</xf:set>
```

### Trim Whitespace

```html
<xf:trim>
    <p>No surrounding whitespace in output</p>
</xf:trim>
```

### Comment

```html
<xf:comment>
    This will not appear in rendered HTML or page source.
</xf:comment>
```

---

## Page Structure Tags

```html
<!-- Sets <h1> and browser tab title -->
<xf:title>{{ phrase('my_page') }}</xf:title>

<!-- Override visible <h1> separately from tab title -->
<xf:h1>{{ phrase('welcome_back', {'name': $xf.visitor.username}) }}</xf:h1>

<!-- Breadcrumb -->
<xf:breadcrumb href="{{ link('demo') }}">{{ phrase('demo') }}</xf:breadcrumb>

<!-- From data source (array with href/value keys) -->
<xf:breadcrumb source="$forum.getBreadcrumbs(false)" />

<!-- Meta description -->
<xf:description>{{ $forum.description }}</xf:description>

<!-- Inject into <head> -->
<xf:head option="og_image">
    <meta property="og:image" content="{$imageUrl}" />
</xf:head>

<!-- Page action buttons (top right area) -->
<xf:pageaction>
    <xf:button href="{{ link('threads/create', $forum) }}" icon="add">
        {{ phrase('post_new_thread') }}
    </xf:button>
</xf:pageaction>

<!-- Sidebar content -->
<xf:sidebar>
    <div class="block">
        <div class="block-container">
            <h3 class="block-header">{{ phrase('info') }}</h3>
            <div class="block-body block-row">Sidebar content here.</div>
        </div>
    </div>
</xf:sidebar>

<!-- Page navigation -->
<xf:pagenav page="{$page}" perpage="{$perPage}" total="{$total}" link="portal" wrapperclass="block" />

<!-- Widget position -->
<xf:widgetpos id="demo_portal_sidebar" position="sidebar" />

<!-- Arbitrary page parameter -->
<xf:page option="allowBookmarkControlMenu" value="1" />
```

---

## Phrases

```html
<!-- Static phrase name (compile-time) -->
{{ phrase('my_phrase_name') }}

<!-- With parameters -->
{{ phrase('welcome_user', {'name': $xf.visitor.username, 'count': $count}) }}

<!-- Dynamic phrase name (runtime) -->
{{ phrase_dynamic($phraseName) }}
{{ phrase_dynamic($phraseName, {'param': $value}) }}
```

Phrase names must be string literals when using `phrase()`. Use `phrase_dynamic()` when the name is in a variable.

---

## Macros

### Defining

```html
<xf:macro id="item_card" arg-item="!" arg-showDate="true" arg-class="">
    <div class="block-row {$class}">
        <h3>{$item.title}</h3>
        <xf:if is="$showDate">
            <span class="u-muted">{{ date($item.created_date, 'M j, Y') }}</span>
        </xf:if>
    </div>
</xf:macro>
```

- `arg-name="!"` — required argument
- `arg-name="default"` — optional argument with default value

### Calling

```html
<!-- Same template -->
<xf:macro id="item_card" arg-item="{$item}" arg-showDate="false" />

<!-- Different template -->
<xf:macro id="demo_macros::item_card" arg-item="{$item}" />
```

### Macro Extends

```html
<xf:macro id="custom_card" extends="shared_macros::base_card" arg-item="!">
    <xf:extension id="footer">
        <xf:extensionparent />
        <span class="badge">{$item.category}</span>
    </xf:extension>
</xf:macro>
```

---

## Template Includes

Includes share the parent's variable scope:

```html
<!-- Basic include -->
<xf:include template="demo_shared_header" />

<!-- With variable remapping -->
<xf:include template="demo_shared_header">
    <xf:map from="$items" to="$headerItems" />
    <xf:set var="$showCount" value="true" />
</xf:include>
```

---

## Template Inheritance (Extends)

### Parent template

```html
<!-- demo_base_layout -->
<div class="block">
    <div class="block-header">
        <xf:extension id="header">
            <h2>{{ phrase('default_header') }}</h2>
        </xf:extension>
    </div>
    <div class="block-body">
        <xf:extension id="content">
            <p>{{ phrase('default_content') }}</p>
        </xf:extension>
    </div>
</div>
```

### Child template

```html
<!-- demo_custom_page -->
<xf:extends template="demo_base_layout" />

<xf:title>{{ phrase('custom_page') }}</xf:title>

<!-- Override content but keep header -->
<xf:extension id="content">
    <p>This replaces the parent's content block.</p>
</xf:extension>
```

### Append to parent content

```html
<xf:extension id="content">
    <xf:extensionparent />
    <p>This appears after the parent's content.</p>
</xf:extension>
```

### Extension value shorthand

```html
<!-- In parent: define a value-only extension point -->
<a href="{{ $extensionValue }}">
    <xf:extensionvalue id="action" />
</a>

<!-- In child: pass a value -->
<xf:extension id="action" value="{{ link('demo/edit', $item) }}" />
```

---

## Template Wrapping

The content template declares which layout wraps it:

```html
<!-- demo_page (content template) -->
<xf:wrap template="demo_wrapper">
    <xf:set var="$activeTab" value="settings" />
    <xf:map from="$title" to="$pageTitle" />
</xf:wrap>

<h3>Page content here.</h3>
```

```html
<!-- demo_wrapper (wrapper template) -->
<div class="page-layout">
    <h1>{$pageTitle}</h1>
    <div class="page-content">
        {$innerContent|raw}
    </div>
</div>
```

The wrapper must output `{$innerContent|raw}` — the `|raw` filter is required because the content is pre-escaped HTML.

---

## CSS and JavaScript Inclusion

```html
<!-- Include a LESS/CSS template -->
<xf:css src="demo_styles.less" />

<!-- Inline CSS -->
<xf:css>
    .demo-element { color: red; }
</xf:css>

<!-- Include a JS file (relative to /js directory) -->
<xf:js src="demo/addon/main.js" />

<!-- Inline JS -->
<xf:js>
    console.log('Loaded');
</xf:js>

<!-- Conditional for prod/dev -->
<xf:js prod="demo/addon/main.min.js" dev="demo/addon/main.js" />
```

---

## Form Tags

### Form Container

```html
<xf:form action="{{ link('demo/save') }}" ajax="true" class="block">
    <div class="block-container">
        <div class="block-body">
            <!-- form fields here -->
        </div>
    </div>
</xf:form>
```

`ajax="true"` enables AJAX submission with inline error display and flash messages.

### Text Input Row

```html
<xf:textboxrow name="title"
    value="{$item.title}"
    label="{{ phrase('title') }}"
    explain="{{ phrase('enter_a_title') }}"
    required="required"
    maxlength="{{ max_length('Demo:Item', 'title') }}" />
```

### Textarea Row

```html
<xf:textarearow name="description"
    value="{$item.description}"
    label="{{ phrase('description') }}"
    autosize="true"
    rows="5" />
```

### Number Box Row

```html
<xf:numberboxrow name="price"
    value="{$item.price}"
    label="{{ phrase('price') }}"
    min="0" max="9999" step="0.01" />
```

### Select Row

```html
<xf:selectrow name="category_id"
    value="{$item.category_id}"
    label="{{ phrase('category') }}">
    <xf:option value="0">{{ phrase('none') }}</xf:option>
    <xf:options source="{$categories}" />
</xf:selectrow>
```

### Checkbox Row

```html
<xf:checkboxrow label="{{ phrase('options') }}">
    <xf:option name="is_enabled" value="1" selected="{$item.is_enabled}"
        label="{{ phrase('enabled') }}"
        hint="{{ phrase('enabled_hint') }}" />
    <xf:option name="is_featured" value="1" selected="{$item.is_featured}">
        {{ phrase('featured') }}
    </xf:option>
</xf:checkboxrow>
```

### Radio Row

```html
<xf:radiorow name="visibility" value="{$item.visibility}" label="{{ phrase('visibility') }}">
    <xf:option value="public">{{ phrase('public') }}</xf:option>
    <xf:option value="private">{{ phrase('private') }}</xf:option>
    <xf:option value="members">{{ phrase('members_only') }}</xf:option>
</xf:radiorow>
```

### Password Row

```html
<xf:passwordboxrow name="password"
    label="{{ phrase('password') }}"
    checkstrength="true"
    hideshow="true" />
```

### Date/Time Row

```html
<xf:dateinputrow name="start_date" value="{$event.start_date}" label="{{ phrase('start_date') }}" />
<xf:timeinputrow name="start_time" value="{$event.start_time}" label="{{ phrase('start_time') }}" />
<xf:datetimeinputrow name="scheduled_at" value="{$item.scheduled_at}" label="{{ phrase('scheduled_at') }}" />
```

### Editor Row (Rich Text)

```html
<xf:editorrow name="message"
    value="{$post.message}"
    label="{{ phrase('message') }}"
    attachments="{$attachments}"
    previewable="true" />
```

### Upload Row

```html
<xf:uploadrow name="avatar"
    label="{{ phrase('upload_avatar') }}"
    accept=".jpg,.png,.gif" />
```

### Token Input Row

```html
<xf:tokeninputrow name="tags"
    value="{$item.tags}"
    label="{{ phrase('tags') }}"
    href="{{ link('tags/autocomplete') }}"
    max-tokens="10" />
```

### Submit Row

```html
<xf:submitrow submit="{{ phrase('save') }}" icon="save" sticky="true" />
```

### Hidden Value

```html
<xf:hiddenval name="redirect" value="{{ link('forums') }}" />
```

### Common Row Attributes

All `*row` tags accept:
- `label` — label text
- `hint` — hint next to label
- `explain` — explanatory text below the control
- `error` — error message
- `rowclass` — CSS classes for the row
- `rowid` — HTML `id` for the row

---

## User Tags

```html
<!-- Avatar -->
<xf:avatar user="{$post.User}" size="m" />
<!-- Sizes: o (384px), h (384px), l (192px), m (96px), s (48px) -->

<!-- Username with rich styling -->
<xf:username user="{$thread.User}" rich="true" />

<!-- User title -->
<xf:usertitle user="{$xf.visitor}" />

<!-- User activity -->
<xf:useractivity user="{$user}" />
```

---

## UI Component Tags

```html
<!-- Button -->
<xf:button href="{{ link('threads/create', $forum) }}" icon="add">
    {{ phrase('post_new_thread') }}
</xf:button>

<!-- Date (dynamic relative time) -->
<xf:date time="{$post.post_date}" />

<!-- Reaction button -->
<xf:react content="{$post}" link="posts/react" />

<!-- Reactions summary -->
<xf:reactions content="{$post}" link="posts/reactions" />

<!-- Font Awesome icon -->
<xf:fa icon="fa-star" />
<xf:fa icon="fa-circle-notch fa-spin" />

<!-- Advertisement slot -->
<xf:ad position="forum_list_above" />

<!-- Widget by key -->
<xf:widget key="forum_stats" />
```

---

## Template Callbacks

```html
<xf:callback class="Demo\Template\Helper" method="renderStats" params="['sidebar']"></xf:callback>
```

Allowed method prefixes: `are`, `can`, `does`, `exists`, `has`, `is`, `validate`, `verify`, `count`, `data`, `display`, `fetch`, `filter`, `find`, `get`, `pluck`, `print`, `render`, `return`, `show`, `view`, `total`

```php
// The callback class
namespace Demo\Template;

class Helper
{
    public static function renderStats(string $context): string
    {
        $repo = \XF::repository('Demo:Stats');
        $stats = $repo->getLatestStats();
        return \XF::app()->templater()->renderTemplate('demo_stats_widget', [
            'stats' => $stats,
            'context' => $context,
        ]);
    }
}
```

---

## Template Functions Reference

### URL and Linking

```html
{{ link('threads', $thread) }}
{{ link('forums', $forum, {'page': 2}) }}
{{ link('posts', $post, {}, 'post-' . $post.post_id) }}
{{ link_type('admin', 'users/edit', $user) }}
{{ base_url('/styles/demo.css', true) }}
```

### Content Rendering

```html
{{ bb_code($post.message, 'post', $post.User) }}
{{ bb_code_snippet($post.message, 'post', $post.User, 200) }}
{{ snippet($text, 150) }}
{{ structured_text($plainText) }}
{{ highlight($result.title, $searchTerm) }}
{{ prefix('thread', $thread.prefix_id) }}
{{ smilie($text) }}
```

### Date and Time

```html
{{ date($post.post_date) }}
{{ date($post.post_date, 'M j, Y') }}
{{ date_time($post.post_date) }}
{{ date_dynamic($post.post_date) }}
{{ duration(5, 'days') }}
```

### Numbers

```html
{{ number($count) }}
{{ number($price, 2) }}
{{ number_short($forum.message_count) }}
{{ file_size($attachment.file_size) }}
```

### Utilities

```html
{{ unique_id('inputField') }}
{{ max_length('XF:Thread', 'title') }}
{{ is_addon_active('Demo/Addon') }}
{{ is_addon_active('Demo/Addon', 1000070, '>=') }}
{{ csrf_input() }}
{{ csrf_token() }}
{{ redirect_input(link('forums')) }}
{{ page_nav({'page': $page, 'perPage': $perPage, 'total': $total, 'link': 'portal'}) }}
```

---

## Filters Reference

```html
<!-- Output safety -->
{$html|raw}
{{ $value | escape }}
{{ $value | for_attr }}

<!-- Numbers -->
{{ $price | number(2) }}
{{ $price | currency('USD') }}
{{ $bytes | file_size }}
{{ $num | zerofill(5) }}

<!-- Strings -->
{{ $text | to_lower }}
{{ $text | to_upper }}
{{ $text | substr(0, 50) }}
{{ $text | replace('foo', 'bar') }}
{{ $text | strip_tags }}
{{ $text | nl2br }}
{{ $text | censor }}

<!-- Arrays -->
{{ $names | join(', ') }}
{{ $items | pluck('title') | join(', ') }}
{{ $items | count }}
{{ $items | first }}
{{ $items | last }}

<!-- Encoding -->
{{ $data | json }}
{{ $data | json(true) }}
{{ $str | urlencode }}
{{ $url | url('host') }}

<!-- Other -->
{{ $value | default('fallback') }}
{{ $count | parens }}
```

---

## Template Modifications

Template modifications let you alter existing templates from your add-on without editing them directly.

Admin CP > Appearance > Template Modifications

Settings:
- **Template**: name of template to modify
- **Modification key**: unique identifier (`vendor_addon_template_purpose`)
- **Type**: Simple or Regular expression
- **Find**: string or regex to find
- **Replace**: replacement content (`$0` reinserts the matched text)
- **Priority**: lower = runs earlier

```
Find:    <xf:option name="allow_posting"

Replace: <xf:option name="demo_auto_feature" 
            selected="$forum.demo_auto_feature"
            label="Auto-feature threads" />
         $0
```

Regex example:

```
Find:    /$/ (matches end of template)
Replace: <xf:macro name="my_footer_addition" />
```
