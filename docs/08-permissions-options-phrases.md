# Permissions, Options, and Phrases

## Permissions

### Permission Groups and Permissions

Permissions are defined under Admin CP > Development > Permission definitions.

**Permission group** fields:
| Field | Description |
|-------|-------------|
| Permission group ID | Unique string identifier (e.g., `demo`, `forum`) |
| Title | Human-readable name |
| Display order | Sort order |
| Add-on | Owning add-on |

**Permission** fields:
| Field | Description |
|-------|-------------|
| Permission group | The group this belongs to |
| Permission ID | Unique string within the group (e.g., `viewItems`, `demoPortalFeature`) |
| Title | Human-readable name |
| Interface group | Where it appears in the permissions UI (e.g., `Forum moderator permissions`) |
| Permission type | `flag` (yes/no) or `integer` (value) |
| Default value | For integer permissions |
| Display order | Sort order |

### Permission Types

**Flag permissions** (`type = flag`):
- Value is `yes`, `no`, or `allow` (inherited yes)
- Checked with `hasPermission()`

**Integer permissions** (`type = integer`):
- Value is a number
- Retrieved with `hasPermission()` which returns the integer

### Checking Permissions in PHP

```php
// Check a global permission
$canPost = \XF::visitor()->hasPermission('demo', 'postItems');

// Check a node/forum permission
$canFeature = \XF::visitor()->hasNodePermission($thread->node_id, 'demoPortalFeature');

// Integer permission
$maxItems = \XF::visitor()->hasPermission('demo', 'maxItems');
if ($maxItems > 0 && $currentCount >= $maxItems) {
    // Over limit
}

// Permission via entity method (recommended pattern)
public function canFeatureUnfeature(&$error = null): bool
{
    return \XF::visitor()->hasNodePermission($this->node_id, 'demoPortalFeature');
}

public function canView(&$error = null): bool
{
    $visitor = \XF::visitor();

    if (!$visitor->hasPermission('demo', 'viewItems')) {
        $error = \XF::phraseDeferred('do_not_have_permission');
        return false;
    }

    return true;
}
```

### Checking Permissions in Templates

```html
<xf:if is="$xf.visitor.hasPermission('demo', 'postItems')">
    <a href="{{ link('demo-items/create') }}">Add item</a>
</xf:if>

<xf:if is="$thread.canFeatureUnfeature()">
    <xf:option name="featured" value="1" selected="{$thread.demo_portal_featured}">
        {{ phrase('demo_portal_featured') }}
    </xf:option>
</xf:if>
```

### Setting Default Permissions in Setup

```php
public function installStep1()
{
    // Grant 'Registered' user group the viewItems permission
    $this->applyGlobalPermissionForGroup(
        'demo',           // permission group
        'viewItems',      // permission ID
        2                 // user group ID (2 = Registered)
    );

    // Set a node permission default
    $this->applyNodePermissionForGroup(
        $nodeId,
        'demo',
        'demoPortalFeature',
        'moderator_group'
    );
}
```

---

## Options

### Creating Option Groups

Admin CP > Setup > Options > Add option group

| Field | Description |
|-------|-------------|
| Group ID | Unique identifier (e.g., `demoPortal`) |
| Title | Shown in Admin CP |
| Description | Optional description |
| Display order | Sort position |
| Add-on | Owning add-on |

### Creating Options

Admin CP > Setup > Options > [Your Group] > Add option

| Field | Description |
|-------|-------------|
| Option ID | Unique identifier (e.g., `demoPortalFeaturedPerPage`) |
| Title | Human-readable name |
| Description | Help text shown in Admin CP |
| Edit format | How the option is displayed (see below) |
| Data type | PHP data type of the stored value |
| Default value | Initial value |
| Format parameters | For spin box, radio, select, multi-choice formats |
| Display order | Sort position |
| Add-on | Owning add-on |

### Edit Formats

| Format | Description |
|--------|-------------|
| `text_box` | Single-line text input |
| `text_area` | Multi-line text area |
| `spin_box` | Numeric spinner |
| `radio` | Radio buttons |
| `select` | Dropdown select |
| `checkbox` | Single checkbox |
| `multi_checkbox` | Multiple checkboxes |
| `template` | Custom admin template |
| `callback` | PHP callback for custom rendering |

### Format Parameters Syntax

For radio and select formats, format parameters define the choices:

```
value1={{ phrase('option_one') }}
value2={{ phrase('option_two') }}
value3=Literal string label
```

Example for a sort order option:

```
featured_date={{ phrase('demo_portal_featured_date') }}
post_date={{ phrase('demo_portal_post_date') }}
```

For spin box with constraints:

```
min=1
max=100
step=1
```

For multi-checkbox:

```
option_a=Option A label
option_b=Option B label
option_c=Option C label
```

### Data Types

| Type | PHP type | Example |
|------|---------|---------|
| `string` | string | `'hello'` |
| `integer` | int | `10` |
| `unsigned_integer` | int | `10` |
| `positive_integer` | int | `10` |
| `numeric` | float | `1.5` |
| `boolean` | bool | `true` / `false` |
| `array` | array | `['a', 'b']` |

### Reading Options in PHP

```php
// Via options() in a controller
$perPage = $this->options()->demoPortalFeaturedPerPage;

// Statically anywhere
$sortOrder = \XF::options()->demoPortalDefaultSort;

// Full options object
$options = \XF::options();
$isEnabled = $options->demoEnabled;
$perPage = $options->demoPortalFeaturedPerPage;

// With fallback
$value = \XF::options()->demoMyOption ?? 'default';
```

### Reading Options in Templates

```html
{{ $xf.options.demoPortalFeaturedPerPage }}

<xf:if is="$xf.options.demoEnabled">
    <!-- Feature is enabled -->
</xf:if>

<xf:if is="$xf.options.demoPortalDefaultSort == 'featured_date'">
    <xf:date time="{$featuredThread.featured_date}" />
<xf:else />
    <xf:date time="{$thread.post_date}" />
</xf:if>
```

### Option in the Admin CP form

When options use `template` edit format, create an admin template named after the option ID:

```html
<!-- Admin template: option_template_demoPortalCustom -->
<xf:textboxrow name="options[demoPortalCustom]"
    value="{$preparedOptions.demoPortalCustom}"
    label="{{ phrase('demo_portal_custom_label') }}" />
```

---

## Phrases

Phrases are translatable strings used throughout XenForo.

### Creating Phrases

Admin CP > Appearance > Phrases > Add phrase

| Field | Description |
|-------|-------------|
| Phrase title | Unique identifier in snake_case (e.g., `demo_portal_featured`) |
| Text | The phrase text |
| Global | Whether available in email templates |
| Add-on | Owning add-on |

### Phrase Naming Conventions

| Context | Convention | Example |
|---------|-----------|---------|
| General | `addon_description` | `demo_portal_featured_threads` |
| Error messages | `addon_error_description` | `demo_item_not_found` |
| Permission titles | `perm_group_permId_description` | `demo_view_items` |
| Option group titles | `option_group_groupId_title` | No convention, just descriptive |
| Option titles | `option_optionId_description` | `demo_portal_items_per_page` |
| Admin navigation | `nav_item_description` | `demo_portal_admin_nav` |

### Using Phrases in PHP

```php
// Get phrase object (lazy-evaluated)
$phrase = \XF::phrase('demo_portal_featured');

// Get phrase with parameters
$phrase = \XF::phrase('demo_portal_items_count', ['count' => $total]);

// Get raw string immediately
$text = \XF::phrase('demo_portal_featured')->render();

// Deferred phrase (for use in places where rendering happens later)
$phrase = \XF::phraseDeferred('do_not_have_permission');

// In error messages
$this->error(\XF::phrase('demo_item_not_found'));

// In entity verify callback
$this->error(\XF::phrase('please_enter_valid_title'), 'title');
```

### Using Phrases in Templates

```html
<!-- Static phrase -->
{{ phrase('demo_portal_featured') }}

<!-- With parameters — phrase text: "Welcome, {name}!" -->
{{ phrase('demo_portal_welcome', {'name': $xf.visitor.username}) }}

<!-- Dynamic phrase name -->
{{ phrase_dynamic($phraseName) }}
{{ phrase_dynamic($phraseName, {'param': $value}) }}

<!-- In a tag attribute -->
<xf:textboxrow label="{{ phrase('title') }}" />

<!-- Inline with concatenation -->
<p>{{ phrase('replies:') }} {$thread.reply_count|number}</p>
```

### Phrase Parameters

In your phrase text, use `{parameter_name}` placeholders:

```
Phrase: welcome_user_message
Text:   Welcome back, {username}! You have {count} new notifications.
```

```html
{{ phrase('welcome_user_message', {
    'username': $xf.visitor.username,
    'count': $notificationCount
}) }}
```

### Phrase Variants (Pluralization)

XenForo supports plural phrase variants. Create phrases with the same name but appended with `_{count}` or use the built-in pluralization:

```html
{{ phrase('x_replies', {'count': $thread.reply_count})|number }}
```

The system will select from:
- `x_replies` — default
- `x_replies_0` — when count is 0
- `x_replies_1` — when count is 1

### Accessing Phrases in Cron/Job Context

Outside of a request context, phrases need explicit language:

```php
$language = \XF::app()->language(\XF::visitor()->language_id);
$phrase = $language->phrase('demo_portal_featured')->render();
```

---

## Admin Navigation

Create navigation entries in Admin CP > Development > Admin navigation.

| Field | Description |
|-------|-------------|
| Navigation ID | Unique identifier (e.g., `demoPortal`) |
| Parent | Parent navigation item ID |
| Title | Human-readable name |
| Link | Route to navigate to |
| Icon | Font Awesome icon |
| Display order | Sort position |

Use these IDs as the `section context` in your admin routes so the correct navigation item is highlighted.

---

## Putting It All Together: Example Add-on Permission Flow

```php
// 1. Entity method checks permission
public function canEdit(&$error = null): bool
{
    $visitor = \XF::visitor();

    if (!$visitor->user_id) {
        return false;
    }

    // Owners can always edit their own items
    if ($this->user_id === $visitor->user_id) {
        return $visitor->hasPermission('demo', 'editOwnItems');
    }

    // Others need the editAnyItem permission
    return $visitor->hasPermission('demo', 'editAnyItem');
}

// 2. Controller uses the entity method
public function actionEdit(\XF\Mvc\ParameterBag $params)
{
    $item = $this->assertRecordExists('Demo\Addon:Item', $params->item_id);

    if (!$item->canEdit($error)) {
        return $this->noPermission($error);
    }

    return $this->view('Demo\Addon:Item\Edit', 'demo_addon_item_edit', ['item' => $item]);
}

// 3. Template checks permission for UI
```
```html
<xf:if is="$item.canEdit()">
    <a href="{{ link('demo-items/edit', $item) }}">{{ phrase('edit') }}</a>
</xf:if>
```
