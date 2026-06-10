# XenForo Entity Column Types

> Used in `$structure->columns` definitions. PHP constants on `\XF\Mvc\Entity\Entity`.

---

## All column type constants

| Constant | DB storage | PHP type | Notes |
|----------|-----------|----------|-------|
| `self::INT` | INT | int | Signed integer |
| `self::UINT` | INT UNSIGNED | int | Unsigned integer (most common for IDs, counts, timestamps) |
| `self::FLOAT` | FLOAT | float | Floating point |
| `self::BOOL` | TINYINT(1) | bool | `true`/`false` stored as `1`/`0` |
| `self::STR` | VARCHAR / TEXT | string | Generic string |
| `self::BINARY` | BINARY / BLOB | string | Binary data (no charset conversion) |
| `self::JSON` | MEDIUMBLOB | mixed | `json_encode` on write, `json_decode` on read (returns value or null) |
| `self::JSON_ARRAY` | MEDIUMBLOB | array | Like JSON but always returns array (empty array if null/invalid) |
| `self::SERIALIZED` | MEDIUMBLOB | mixed | `serialize()` on write, `unserialize()` on read |
| `self::SERIALIZED_ARRAY` | MEDIUMBLOB | array | Like SERIALIZED but always returns array |
| `self::LIST_COMMA` | TEXT | array | Comma-separated string ↔ PHP array |
| `self::LIST_LINES` | TEXT | array | Newline-separated string ↔ PHP array |

---

## Column definition keys

```php
$structure->columns = [
    'col_name' => [
        // Required
        'type'          => self::UINT,      // one of the constants above

        // Common
        'default'       => 0,               // REQUIRED for non-nullable columns
        'nullable'      => true,            // allow NULL (default: false = NOT NULL)
        'required'      => true,            // bool: reject empty value on save
        'required'      => 'phrase_name',   // string: phrase name for the error message
        'autoIncrement' => true,            // AUTO_INCREMENT (also sets as PK)

        // String-specific
        'maxLength'     => 255,             // VARCHAR/TEXT max length
        'match'         => 'email',         // format validation: 'email', 'url', 'username', 'color', etc.
        'match'         => '/^[a-z]+$/i',   // or a regex
        'censor'        => true,            // run content through the censor

        // Validation
        'allowedValues' => ['a', 'b', 'c'], // whitelist of valid values
        'unique'        => true,            // enforce uniqueness in DB
        'min'           => 0,               // minimum numeric value
        'max'           => 100,             // maximum numeric value
        'minLength'     => 3,               // minimum string length

        // Misc
        'changeLog'     => false,           // exclude from change logging (default: true)
    ],
];
```

---

## Examples for each type

```php
// UINT — IDs, counts, timestamps
'user_id'      => ['type' => self::UINT, 'default' => 0],
'thread_id'    => ['type' => self::UINT, 'required' => true],
'reply_count'  => ['type' => self::UINT, 'default' => 0],
'post_date'    => ['type' => self::UINT, 'default' => 0],
'id'           => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],

// INT — signed (rare, use UINT unless you need negatives)
'position'     => ['type' => self::INT, 'default' => 0],

// BOOL — flags
'is_active'    => ['type' => self::BOOL, 'default' => true],
'is_deleted'   => ['type' => self::BOOL, 'default' => false],
'has_avatar'   => ['type' => self::BOOL, 'default' => false],

// STR — text fields
'title'        => ['type' => self::STR, 'maxLength' => 150, 'required' => true],
'username'     => ['type' => self::STR, 'maxLength' => 50, 'required' => 'please_enter_valid_name'],
'email'        => ['type' => self::STR, 'maxLength' => 120, 'match' => 'email'],
'state'        => ['type' => self::STR, 'default' => 'visible',
                   'allowedValues' => ['visible', 'moderated', 'deleted']],
'url'          => ['type' => self::STR, 'maxLength' => 2000, 'match' => 'url', 'default' => ''],

// JSON_ARRAY — structured data, always an array
'extra_data'   => ['type' => self::JSON_ARRAY, 'default' => []],
'options'      => ['type' => self::JSON_ARRAY, 'default' => []],
'criteria'     => ['type' => self::JSON_ARRAY, 'default' => [],
                   'required' => 'please_select_criteria'],

// JSON — when value may not be an array
'metadata'     => ['type' => self::JSON, 'nullable' => true],

// LIST_COMMA — simple tag/ID lists
'node_ids'     => ['type' => self::LIST_COMMA, 'default' => []],
'tags'         => ['type' => self::LIST_COMMA, 'default' => []],

// SERIALIZED_ARRAY — legacy; prefer JSON_ARRAY for new code
'cache_data'   => ['type' => self::SERIALIZED_ARRAY, 'default' => []],
```

---

## Auto-inference rules

- `int` / `uint` columns are **UNSIGNED** by default → use `'unsigned' => false` (or `->unsigned(false)` in schema) to allow negatives
- All columns are **NOT NULL** by default → set `'nullable' => true` to allow NULL
- `autoIncrement` → automatically becomes the **primary key**
- `type => BOOL` → stored as `tinyint(1)` in MySQL; PHP booleans are transparently encoded/decoded
- `type => JSON_ARRAY` with `default => []` → returns `[]` even if DB stores NULL

---

## `verifyXxx` callbacks

When a value is set on a column, XF looks for a method named `verify<CamelCasedColumn>()`:

```php
// Column: 'style_id'
protected function verifyStyleId(&$value): bool
{
    if ($value && !\XF::em()->find('XF:Style', $value))
    {
        $this->error(\XF::phrase('invalid_style'), 'style_id');
        return false;
    }
    return true; // $value can be modified by reference
}

// Column: 'title'
protected function verifyTitle(&$value): bool
{
    $value = trim($value);
    if (mb_strlen($value) < 3)
    {
        $this->error('Title must be at least 3 characters.', 'title');
        return false;
    }
    return true;
}
```

---

## Getter overrides

```php
$structure->getters = [
    'computed_field'  => true,   // calls getComputedField()
    'real_db_column'  => true,   // overrides the stored column value
];

public function getComputedField(): string
{
    return $this->first_name . ' ' . $this->last_name;
}

// Bypass a getter with trailing underscore:
$rawValue = $entity->real_db_column_;   // skips getComputedField()
```
