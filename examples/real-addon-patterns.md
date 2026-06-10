# Real XenForo Addon Patterns

Patterns extracted directly from 10 free XenForo addons downloaded from xenforo.com. All PHP code is attributed to its original addon and author.

---

## 1. Listener — Class Extension Registration

Source: **Smiley Manager** by Milano (`Milano_SmileyManager_Listener`)
and **XenPorta** by 8wayRun.Com (`EWRporta_Listener_Controller`)

The listener is the main hook point. Static methods are wired to code events in the addon XML.

```php
// Smiley Manager: Milano/SmileyManager/Listener.php
class Milano_SmileyManager_Listener
{
    // Extend a public controller
    public static function loadAccountController($class, array &$extend)
    {
        $extend[] = 'Milano_SmileyManager_ControllerPublic_Account';
    }

    // Extend an admin controller
    public static function loadSmilieControllerAdmin($class, array &$extend)
    {
        $extend[] = 'Milano_SmileyManager_ControllerAdmin_Smilie';
    }

    // Extend a DataWriter
    public static function loadUserDataWriter($class, array &$extend)
    {
        $extend[] = 'Milano_SmileyManager_DataWriter_User';
    }

    // Register a custom template helper callback
    public static function initDependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        XenForo_Template_Helper_Core::$helperCallbacks += array(
            'parsesmilies' => array('Milano_SmileyManager_Helper_Smilie', 'parseSmilies'),
        );
    }

    // Inject a JS file into the editor template
    public static function templateEditorCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
    {
        if (self::_assertQuickloadSmileyEnabled()) {
            $template->addRequiredExternal('js', 'js/Milano/SmileyManager/editor.js');
        }
    }

    // Modify editor JSON options to activate a plugin
    public static function editorSetup(XenForo_View $view, $formCtrlName, &$message, array &$editorOptions, &$showWysiwyg)
    {
        if ($showWysiwyg && self::_assertQuickloadSmileyEnabled()) {
            $editorOptions['json']['editorOptions']['plugins'][] = 'SmileyManager';
        }
    }

    // Permission + option check helper
    protected static function _assertQuickloadSmileyEnabled()
    {
        if (XenForo_Application::get('options')->SmileyManager_quickloadSmiley) {
            $visitor = XenForo_Visitor::getInstance();
            if (!empty($visitor['quickload_smiley'])) {
                return true;
            }
        }
        return false;
    }
}
```

**XenPorta switch-based listener** (cleaner for extending multiple classes):

```php
// XenPorta: EWRporta/Listener/Controller.php
class EWRporta_Listener_Controller
{
    public static function controller($class, array &$extend)
    {
        switch ($class) {
            case 'XenForo_ControllerPublic_Forum':
                $extend[] = 'EWRporta_ControllerPublic_Forum';
                break;
            case 'XenForo_ControllerPublic_Thread':
                $extend[] = 'EWRporta_ControllerPublic_Thread';
                break;
        }
    }
}
```

**Key insight:** XF1 uses a single `load_class` event per class type. The switch pattern is more efficient than registering separate listeners for each class.

---

## 2. Route\Interface Implementation

Source: **XenPorta** by 8wayRun.Com (`EWRporta_Route_Portal`)

Every custom URL prefix needs a class implementing `XenForo_Route_Interface`.

```php
// XenPorta: EWRporta/Route/Portal.php
class EWRporta_Route_Portal implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        // resolveActionWithStringParam: maps /portal/{layout_id} to action + param
        $action = $router->resolveActionWithStringParam($routePath, $request, 'layout_id');
        // resolveActionAsPageNumber: maps /portal/page-2 to page param
        $action = $router->resolveActionAsPageNumber($action, $request);
        return $router->getRouteMatch('EWRporta_ControllerPublic_Portal', $action, 'portal');
    }

    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        $action = XenForo_Link::getPageNumberAsAction($action, $extraParams);
        return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'layout_id');
    }
}
```

**Key insight:** `resolveActionWithStringParam` handles `/portal/my-layout` → `layout_id=my-layout`. `resolveActionAsPageNumber` handles `/portal/page-3` → `page=3`. Both are needed for a full-featured route.

---

## 3. DataWriter — Full CRUD with Lifecycle Hooks

Source: **FAQ Manager** by Iversia (`Iversia_FAQ_DataWriter_Question`)

The DataWriter is the XF1 equivalent of an ORM model. It handles validation, save, and delete lifecycle.

```php
// FAQ Manager: Iversia/FAQ/DataWriter/Question.php
class Iversia_FAQ_DataWriter_Question extends XenForo_DataWriter
{
    const DATA_ATTACHMENT_HASH = 'attachmentHash';

    protected function _getFields()
    {
        return array(
            'xf_faq_question' => array(
                'faq_id'        => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'category_id'   => array('type' => self::TYPE_UINT, 'required' => true),
                'moderation'    => array('type' => self::TYPE_UINT, 'required' => true),
                'sticky'        => array('type' => self::TYPE_UINT, 'default' => 0),
                'user_id'       => array('type' => self::TYPE_UINT, 'required' => true),
                'attach_count'  => array('type' => self::TYPE_UINT, 'default' => 0),
                'likes'         => array('type' => self::TYPE_UINT, 'default' => 0),
                'like_users'    => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
                'question'      => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 150),
                'answer'        => array('type' => self::TYPE_STRING),
                'submit_date'   => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'faq_id')) {
            return false;
        }
        return array('xf_faq_question' => $this->getModelFromCache('Iversia_FAQ_Model_Question')->getById($id));
    }

    protected function _getUpdateCondition($tableName)
    {
        return 'faq_id = ' . $this->_db->quote($this->getExisting('faq_id'));
    }

    // After save: index for search + associate attachments
    protected function _postSave()
    {
        $this->_indexForSearch();

        $attachmentHash = $this->getExtraData(self::DATA_ATTACHMENT_HASH);
        if ($attachmentHash) {
            $this->_associateAttachments($attachmentHash);
        }
    }

    // After delete: remove from search + delete attachments
    protected function _postDelete()
    {
        $this->_deleteFromSearchIndex();
        $this->_deleteAttachments();
    }

    // Associate a temp attachment hash with this record after save
    protected function _associateAttachments($attachmentHash)
    {
        $rows = $this->_db->update('xf_attachment', array(
            'content_type' => 'xf_faq_question',
            'content_id'   => $this->get('faq_id'),
            'temp_hash'    => '',
            'unassociated' => 0
        ), 'temp_hash = ' . $this->_db->quote($attachmentHash));

        if ($rows) {
            $newCount = $this->get('attach_count') + $rows;
            // setAfterPreSave allows setting a field after the initial pre-save phase
            $this->set('attach_count', $newCount, '', array('setAfterPreSave' => true));
            $this->_db->update('xf_faq_question',
                array('attach_count' => $newCount),
                'faq_id = ' . $this->get('faq_id')
            );
        }
    }

    // Only re-index when relevant fields changed
    protected function _indexForSearch()
    {
        if ($this->isChanged('answer') || $this->isChanged('question')) {
            $this->_insertOrUpdateSearchIndex();
        }
    }
}
```

**XenAtendo Events DataWriter** — shows `allowedValues` enum validation + `_preSave` visitor injection:

```php
// XenAtendo: EWRatendo/DataWriter/Events.php
class EWRatendo_DataWriter_Events extends XenForo_DataWriter
{
    protected $_existingDataErrorPhrase = 'requested_page_not_found';

    protected function _getFields()
    {
        return array(
            'EWRatendo_events' => array(
                'event_id'           => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'event_state'        => array('type' => self::TYPE_STRING, 'default' => 'visible',
                    'allowedValues' => array('visible', 'moderated', 'deleted')
                ),
                'event_recur_units'  => array('type' => self::TYPE_STRING, 'default' => 'none',
                    'allowedValues' => array('none', 'days', 'weeks', 'months')
                ),
                'promote_icon'       => array('type' => self::TYPE_STRING, 'default' => 'default',
                    'allowedValues' => array('default', 'avatar', 'attach', 'image', 'disabled')
                ),
                // ... other fields
            )
        );
    }

    // Auto-populate user_id from visitor on insert
    protected function _preSave()
    {
        if (!$this->_existingData && !$this->get('user_id')) {
            $visitor = XenForo_Visitor::getInstance();
            $this->set('user_id', $visitor['user_id']);
            $this->set('username', ($visitor['user_id'] ? $visitor['username'] : $_SERVER['REMOTE_ADDR']));
        }
    }

    // Sync moderation queue when event_state changes
    protected function _postSave()
    {
        $this->_updateModerationQueue();
    }

    protected function _postDelete()
    {
        $this->getModelFromCache('XenForo_Model_ModerationQueue')
             ->deleteFromModerationQueue('event', $this->get('event_id'));
    }

    protected function _updateModerationQueue()
    {
        if (!$this->isChanged('event_state')) {
            return;
        }

        if ($this->get('event_state') == 'moderated') {
            $this->getModelFromCache('XenForo_Model_ModerationQueue')
                 ->insertIntoModerationQueue('event', $this->get('event_id'), XenForo_Application::$time);
        } elseif ($this->getExisting('event_state') == 'moderated') {
            $this->getModelFromCache('XenForo_Model_ModerationQueue')
                 ->deleteFromModerationQueue('event', $this->get('event_id'));
        }
    }
}
```

**Key DataWriter lifecycle methods:**
| Method | When called | Common use |
|--------|-------------|------------|
| `_getFields()` | Always | Define schema, types, validation |
| `_preSave()` | Before INSERT/UPDATE | Auto-populate fields, extra validation |
| `_postSave()` | After INSERT/UPDATE | Search index, caches, alerts |
| `_preDelete()` | Before DELETE | Permission checks |
| `_postDelete()` | After DELETE | Search index cleanup, cascade |
| `_getExistingData()` | On setExistingData() | Fetch current row |
| `_getUpdateCondition()` | On UPDATE | WHERE clause |

---

## 4. Model — Query Layer

Source: **FAQ Manager** by Iversia (`Iversia_FAQ_Model_Category`)

Models extend `XenForo_Model` and contain all database queries.

```php
// FAQ Manager: Iversia/FAQ/Model/Category.php
class Iversia_FAQ_Model_Category extends XenForo_Model
{
    // fetchAllKeyed returns an array keyed by the given column
    public function getAll($limit = 0)
    {
        if ($limit != 0) {
            return $this->fetchAllKeyed(
                "SELECT * FROM xf_faq_category ORDER BY display_order ASC, title ASC LIMIT ?",
                'category_id',
                $limit
            );
        }
        return $this->fetchAllKeyed(
            "SELECT * FROM xf_faq_category ORDER BY display_order ASC, title ASC",
            'category_id'
        );
    }

    // Single row fetch
    public function getById($category_id)
    {
        return $this->_getDb()->fetchRow(
            'SELECT * FROM xf_faq_category WHERE category_id = ?',
            $category_id
        );
    }

    // Permission check — keep permission logic in the model, not the controller
    public function canManageCategories()
    {
        $visitor = XenForo_Visitor::getInstance();
        return $visitor->hasPermission('FAQ_Manager_Permissions', 'manageFAQCategories');
    }
}
```

**Key model helpers:**
- `$this->fetchAllKeyed($sql, $keyColumn, $params)` — returns assoc array keyed by column
- `$this->_getDb()->fetchRow($sql, $params)` — single row
- `$this->_getDb()->fetchAll($sql, $params)` — all rows as indexed array
- `$this->_getDb()->fetchOne($sql, $params)` — scalar value
- `$this->getModelFromCache('ClassName')` — shared model instance (avoids re-instantiation)

---

## 5. Controller — Full CRUD Pattern

Source: **FAQ Manager** by Iversia (`Iversia_FAQ_ControllerPublic_FAQ`)

```php
class Iversia_FAQ_ControllerPublic_FAQ extends XenForo_ControllerPublic_Abstract
{
    // READ: index listing with pagination and options-driven layout
    public function actionIndex()
    {
        $faq_id  = $this->_input->filterSingle('faq_id', XenForo_Input::UINT);
        $page    = $this->_input->filterSingle('page', XenForo_Input::UINT);

        // Reroute to permalink if specific ID given
        if ($faq_id) {
            return $this->responseReroute(__CLASS__, 'permalink');
        }

        $questions = $this->_getQuestionModel()->getAll(array(
            'perPage'   => XenForo_Application::get('options')->faqPerPage,
            'page'      => $page,
            'order'     => XenForo_Application::get('options')->faqSortOrder,
            'direction' => XenForo_Application::get('options')->faqSortOrderDir,
        ));

        $viewParams = array(
            'faq'        => $questions,
            'page'       => $page,
            'canManage'  => $this->_getQuestionModel()->canManageFAQ(),
            'canAsk'     => $this->_getQuestionModel()->canAskQuestions(),
        );

        // canonicalizeRequestUrl ensures the canonical URL is always correct
        $this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('faq', '', array('page' => $page)));

        return $this->getWrapper('faq', 'index',
            $this->responseView('Iversia_FAQ_ViewPublic_Index', 'iversia_faq_index', $viewParams)
        );
    }

    // CREATE: display form
    public function actionCreate()
    {
        $this->_assertCanManageFAQ(); // throws noPermission if fails
        return $this->responseView('Iversia_FAQ_ViewPublic_Create', 'iversia_faq_create', array(
            'categories' => $this->_getCategoryModel()->getAll(),
        ));
    }

    // CREATE/UPDATE: save handler (handles both new and edit)
    public function actionSave()
    {
        $this->_assertCanManageFAQ();

        $faq_id  = $this->_input->filterSingle('faq_id', XenForo_Input::UINT);
        $visitor = XenForo_Visitor::getInstance();

        $input = array(
            'question'         => $this->_input->filterSingle('question', XenForo_Input::STRING),
            'category_id'      => $this->_input->filterSingle('category_id', XenForo_Input::UINT),
            // getHelper('Editor')->getMessageText handles BB code editor input
            'answer'           => $this->getHelper('Editor')->getMessageText('message', $this->_input),
            'attachment_hash'  => $this->_input->filterSingle('attachment_hash', XenForo_Input::STRING),
        );
        $input['answer'] = XenForo_Helper_String::autoLinkBbCode($input['answer']);

        $dw = XenForo_DataWriter::create('Iversia_FAQ_DataWriter_Question');

        if ($faq_id) {
            $dw->setExistingData($faq_id); // UPDATE mode
        }

        $dw->bulkSet(array(
            'user_id'     => $visitor['user_id'],
            'category_id' => $input['category_id'],
            'question'    => $input['question'],
            'answer'      => $input['answer'],
            'moderation'  => 0,
        ));

        // Pass extra (non-column) data to the DataWriter for post-save processing
        $dw->setExtraData(Iversia_FAQ_DataWriter_Question::DATA_ATTACHMENT_HASH, $input['attachment_hash']);
        $dw->save();

        $question = $dw->getMergedData(); // get the saved row with autoincrement ID

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('faq', $question),
            new XenForo_Phrase('iversia_faq_question_added')
        );
    }

    // DELETE
    public function actionDelete()
    {
        $this->_assertCanManageFAQ();
        $faq_id = $this->_input->filterSingle('faq_id', XenForo_Input::UINT);

        $dw = XenForo_DataWriter::create('Iversia_FAQ_DataWriter_Question');
        $dw->setExistingData($faq_id);
        $dw->delete();

        return $this->responseRedirect(
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('faq'),
            new XenForo_Phrase('iversia_faq_question_deleted')
        );
    }

    // USER ACTIVITY: shown in the online users list
    public static function getSessionActivityDetailsForList(array $activities)
    {
        foreach ($activities as $key => $activity) {
            $faqLink     = XenForo_Link::buildPublicLink('full:faq');
            $faqLinkText = new XenForo_Phrase('iversia_faq');
            $faqAction   = new XenForo_Phrase('viewing_page');

            if (!empty($activity['params']['faq_id'])) {
                $faq_id    = (int) $activity['params']['faq_id'];
                $questions = XenForo_Model::create('XenForo_Model_DataRegistry')->get('faqCache');
                if (isset($questions[$faq_id])) {
                    $faqLinkText = new XenForo_Phrase('iversia_faq') . ' #' . $faq_id . ': ' . $questions[$faq_id];
                    $faqLink     = XenForo_Link::buildPublicLink('full:faq', array('faq_id' => $faq_id));
                }
            }

            $output[$key] = array($faqAction, $faqLinkText, $faqLink, false);
        }
        return $output;
    }

    // Permission assertion helpers — keep assertions DRY
    protected function _assertCanManageFAQ()
    {
        if (!$this->_getQuestionModel()->canManageFAQ()) {
            throw $this->getNoPermissionResponseException();
        }
    }

    // Model factory helpers
    protected function _getQuestionModel()
    {
        return $this->getModelFromCache('Iversia_FAQ_Model_Question');
    }

    protected function _getCategoryModel()
    {
        return $this->getModelFromCache('Iversia_FAQ_Model_Category');
    }
}
```

**Input filter types:**
| Constant | PHP type | Notes |
|----------|----------|-------|
| `XenForo_Input::UINT` | int (unsigned) | Most IDs and counts |
| `XenForo_Input::STRING` | string | Auto-trimmed |
| `XenForo_Input::BOOL` | bool | Checkbox inputs |
| `XenForo_Input::ARRAY_SIMPLE` | array | Simple arrays |
| `XenForo_Input::JSON_ARRAY` | array | JSON-decoded |

---

## 6. AlertHandler

Source: **XenCarta** by 8wayRun.Com (`EWRcarta_AlertHandler_Wiki`)
and **XFR User Albums** by XF-Russia (`XfRu_UserAlbums_AlertHandler_Album`)

```php
// XenCarta: minimal AlertHandler
class EWRcarta_AlertHandler_Wiki extends XenForo_AlertHandler_Abstract
{
    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        // Return array keyed by content_id so XenForo can match alerts to content
        return $model->getModelFromCache('EWRcarta_Model_Pages')->getPagesByIDs($contentIds);
    }
}

// XFR User Albums: AlertHandler with custom template naming
class XfRu_UserAlbums_AlertHandler_Album extends XenForo_AlertHandler_Abstract
{
    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        $albumsModel = $model->getModelFromCache('XfRu_UserAlbums_Model_Albums');
        return $albumsModel->getAlbumsByIds($contentIds);
    }

    // Default template: "{content_type}_{action}_alert"
    // e.g. for content_type=xfr_useralbum, action=like → "xfr_useralbum_like_alert"
    protected function _getDefaultTemplateTitle($contentType, $action)
    {
        return $contentType . '_' . $action . '_alert';
    }
}
```

**Registration:** AlertHandlers must be registered in the addon XML as a `content_type` with `alert_handler_class`. The alert template must also exist.

---

## 7. AttachmentHandler

Source: **XenCarta** by 8wayRun.Com (`EWRcarta_AttachmentHandler_Wiki`)

```php
class EWRcarta_AttachmentHandler_Wiki extends XenForo_AttachmentHandler_Abstract
{
    // The route prefix for attachment URLs (/wiki/attachment/...)
    protected $_contentRoute = 'wiki';

    // The phrase key for "attached to a {wiki page}"
    protected $_contentTypePhraseKey = 'wiki';

    // The column in the content row that holds the content's primary key
    protected $_contentIdKey = 'page_id';

    protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
    {
        // Return true/false or delegate to a permission check
        return true;
    }

    public function _canViewAttachment(array $attachment, array $viewingUser)
    {
        return true;
    }

    public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
    {
        // Called after an attachment is deleted. Update attach_count here if needed.
        return true;
    }

    public function getAttachmentCountLimit()
    {
        // Return false for unlimited, or an int for a per-content limit
        return true;
    }
}
```

---

## 8. CronEntry

Source: **XenCarta** by 8wayRun.Com (`EWRcarta_CronEntry_CleanUp`)

```php
class EWRcarta_CronEntry_CleanUp
{
    // Method name is referenced in the addon cron XML
    public static function runDailyCleanUp()
    {
        $db = XenForo_Application::getDb();

        // XenForo option: readMarkingDataLifetime (days)
        $readMarkingCutOff = XenForo_Application::$time
            - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);

        $db->delete('EWRcarta_read', 'page_read_date < ' . $readMarkingCutOff);
    }
}
```

---

## 9. Install / Uninstall with Versioned Steps

Source: **XenPorta** by 8wayRun.Com (`EWRporta_Install`)

XenPorta uses a version-step pattern: `installCode()` loops from the previous version ID + 1 to the new version ID and calls `_install_{N}()` methods. This allows incremental upgrades without running all install steps every time.

```php
class EWRporta_Install
{
    public static function installCode($existingAddOn, $addOnData)
    {
        $endVersion = $addOnData['version_id'];
        $strVersion = $existingAddOn ? ($existingAddOn['version_id'] + 1) : 50;

        $install = self::getInstance();

        for ($i = $strVersion; $i <= $endVersion; $i++) {
            $method = '_install_' . $i;
            if (method_exists($install, $method)) {
                $install->$method();
            }
        }
    }

    protected function _install_50()
    {
        $this->_getDb()->query("
            CREATE TABLE IF NOT EXISTS `EWRporta_blocks` (
                `block_id`    varchar(25) NOT NULL,
                `title`       varchar(75) NOT NULL,
                `cache`       varchar(255) NOT NULL DEFAULT '+10 minutes',
                `display`     enum('show','hide') NOT NULL,
                `active`      tinyint(3) unsigned NOT NULL DEFAULT '1',
                PRIMARY KEY (`block_id`)
            ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
        ");
    }

    protected function _install_52()
    {
        // New tables added in version 52
        $this->_getDb()->query("CREATE TABLE IF NOT EXISTS `EWRporta_categories` ( ... )");
    }

    protected function _install_58()
    {
        // Safe ALTER: only add column if it doesn't exist yet
        $this->addColumnIfNotExist('EWRporta_categories', 'style_id', 'int(10) unsigned NOT NULL');
    }

    // Utility: idempotent ALTER TABLE ADD COLUMN
    public function addColumnIfNotExist($table, $field, $attr)
    {
        if ($this->_getDb()->fetchRow('SHOW columns FROM `' . $table . '` WHERE Field = ?', $field)) {
            return false;
        }
        return $this->_getDb()->query("ALTER TABLE `{$table}` ADD `{$field}` {$attr}");
    }

    // Smiley Manager pattern: table patch (existing table, add column)
    // In Milano_Common_Install:
    protected static function _getTablePatches()
    {
        return array(
            'xf_user_option' => array(
                'quickload_smiley' => "TINYINT(3) UNSIGNED DEFAULT '0'"
            )
        );
    }
}
```

---

## 10. Moderation Queue Integration

Source: **FAQ Manager** by Iversia and **XenAtendo** by 8wayRun.Com

```php
// Insert into moderation queue (e.g. when user submits content awaiting review)
$this->getModelFromCache('XenForo_Model_ModerationQueue')->insertIntoModerationQueue(
    'xf_faq_question',  // content_type
    $question['faq_id'], // content_id
    XenForo_Application::$time
);

// Remove from moderation queue (e.g. when content state changes to visible or deleted)
$this->getModelFromCache('XenForo_Model_ModerationQueue')->deleteFromModerationQueue(
    'event',
    $this->get('event_id')
);
```

**In a DataWriter** — only act when the relevant field actually changed:

```php
protected function _updateModerationQueue()
{
    if (!$this->isChanged('event_state')) {
        return; // Don't touch queue if state didn't change
    }

    if ($this->get('event_state') == 'moderated') {
        $this->getModelFromCache('XenForo_Model_ModerationQueue')
             ->insertIntoModerationQueue('event', $this->get('event_id'), XenForo_Application::$time);
    } elseif ($this->getExisting('event_state') == 'moderated') {
        $this->getModelFromCache('XenForo_Model_ModerationQueue')
             ->deleteFromModerationQueue('event', $this->get('event_id'));
    }
}
```

---

## 11. Search Index Integration

Source: **FAQ Manager** by Iversia

```php
// In DataWriter._postSave() — only re-index when content changes
protected function _indexForSearch()
{
    if ($this->isChanged('answer') || $this->isChanged('question')) {
        $dataHandler = $this->getModelFromCache('XenForo_Model_Search')
                           ->getSearchDataHandler('xf_faq_question');
        $indexer = new XenForo_Search_Indexer();
        $dataHandler->insertIntoIndex($indexer, $this->getMergedData());
    }
}

// In DataWriter._postDelete()
protected function _deleteFromSearchIndex()
{
    $dataHandler = $this->getModelFromCache('XenForo_Model_Search')
                       ->getSearchDataHandler('xf_faq_question');
    $indexer = new XenForo_Search_Indexer();
    $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
}
```

You also need to implement `XenForo_Search_DataHandler_Abstract` and register the content type in your addon XML.

---

## 12. Likes Integration

Source: **FAQ Manager** by Iversia

```php
// In controller actionLike():
$likeModel    = $this->getModelFromCache('XenForo_Model_Like');
$existingLike = $likeModel->getContentLikeByLikeUser('xf_faq_question', $faq_id, $visitor['user_id']);

if ($this->_request->isPost()) {
    if ($existingLike) {
        $latestUsers = $likeModel->unlikeContent($existingLike);
    } else {
        $latestUsers = $likeModel->likeContent('xf_faq_question', $faq_id, $question['user_id']);
    }

    $liked = !$existingLike;

    // If this is an AJAX request (_noRedirect()), return a view for inline update
    if ($this->_noRedirect() && $latestUsers !== false) {
        $question['like_users'] = $latestUsers;
        $question['likes']     += ($liked ? 1 : -1);
        $question['like_date']  = ($liked ? XenForo_Application::$time : 0);
        return $this->responseView('...ViewPublic_LikeConfirmed', '', array(
            'question' => $question,
            'liked'    => $liked,
        ));
    }
}
```

---

## 13. DataRegistry (Simple Cache)

Source: **FAQ Manager**, **Smiley Manager**, **XenPorta**

`XenForo_Model_DataRegistry` stores serialized data in `xf_data_registry`. Use it for data that is expensive to compute but changes infrequently.

```php
// READ
$data = XenForo_Model::create('XenForo_Model_DataRegistry')->get('myAddonCacheKey');

// WRITE
XenForo_Model::create('XenForo_Model_DataRegistry')->set('myAddonCacheKey', $data);

// DELETE (force rebuild on next request)
XenForo_Model::create('XenForo_Model_DataRegistry')->delete('myAddonCacheKey');

// Simple cache (stored in xf_option, faster but size-limited)
$data = XenForo_Application::getSimpleCacheData('myKey');
XenForo_Application::setSimpleCacheData('myKey', $value);  // pass false to delete
```

---

## 14. XF2 Patterns (Demo Portal addon in this repo)

The `examples/demo-portal/` directory is a complete XF2 addon. Key differences from XF1:

### Entity (replaces DataWriter + Model row)
```php
// Entity defines its own structure, columns, and relations
class FeaturedThread extends \XF\Mvc\Entity\Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table      = 'xf_demo_portal_featured_thread';
        $structure->primaryKey = 'thread_id';
        $structure->columns    = [
            'thread_id'        => ['type' => self::UINT, 'required' => true],
            'featured_date'    => ['type' => self::UINT, 'default' => \XF::$time],
            'featured_user_id' => ['type' => self::UINT, 'default' => 0],
        ];
        $structure->relations = [
            'Thread' => [
                'entity'     => 'XF:Thread',
                'type'       => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary'    => true,
            ],
        ];
        $structure->defaultWith = ['Thread', 'Thread.User'];
        return $structure;
    }
}
```

### Repository (replaces Model)
```php
class FeaturedThread extends \XF\Mvc\Entity\Repository
{
    public function findFeaturedThreadsForPortal(): \Demo\Portal\Finder\FeaturedThread
    {
        return $this->finder('Demo\Portal:FeaturedThread')
            ->with(['Thread', 'Thread.User', 'Thread.Forum'])
            ->where('Thread.discussion_state', 'visible')
            ->setDefaultOrder('featured_date', 'DESC');
    }
}
```

### Finder (replaces Model query builder methods)
```php
class FeaturedThread extends \XF\Mvc\Entity\Finder
{
    public function fromAutoFeatureForums(): self
    {
        $this->with('Thread.Forum');
        $this->where('Thread.Forum.demo_portal_auto_feature', 1);
        return $this;
    }
}
```

### Class Extension (replaces XF1 listener + extend array)
```php
// XF2: just extend the class and use XFCP_ pseudo-parent
namespace Demo\Portal\XF\Service\Thread;

class Creator extends XFCP_Creator
{
    protected function afterInsert(): void
    {
        parent::afterInsert();

        if ($this->thread->Forum->demo_portal_auto_feature) {
            \XF::repository('Demo\Portal:FeaturedThread')->featureThread($this->thread);
        }
    }
}
```

### Setup (replaces Install.php callback)
```php
class Setup extends \XF\AddOn\AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->schemaManager()->createTable('xf_demo_portal_featured_thread', function (Create $table) {
            $table->addColumn('thread_id', 'int')->unsigned();
            $table->addColumn('featured_date', 'int')->unsigned();
            $table->addPrimaryKey('thread_id');
        });
    }

    public function installStep2(): void
    {
        $this->schemaManager()->alterTable('xf_forum', function (Alter $table) {
            $table->addColumn('demo_portal_auto_feature', 'tinyint')->unsigned()->setDefault(0);
        });
    }

    public function uninstallStep1(): void
    {
        $this->schemaManager()->dropTable('xf_demo_portal_featured_thread');
    }
}
```

---

## Summary: XF1 vs XF2 Component Map

| XF1 | XF2 | Notes |
|-----|-----|-------|
| `XenForo_DataWriter` | `XF\Mvc\Entity\Entity` | Entity handles both schema and save lifecycle |
| `XenForo_Model` | `XF\Mvc\Entity\Repository` + `Finder` | Repository for business logic, Finder for queries |
| `Listener.php` + XML event | `class_extensions.xml` + `XFCP_` | Class extensions replace most listener patterns |
| `XenForo_Route_Interface` | Route entry in `routes.xml` | No PHP class needed for simple routes |
| `Install::installCode()` | `Setup::installStep1()` etc | Trait-based step runner replaces version loop |
| `XenForo_Application::get('options')->key` | `\XF::options()->key` | Same concept, new API |
| `XenForo_Visitor::getInstance()` | `\XF::visitor()` | Same concept, new API |
| `$this->getModelFromCache('Class')` | `$this->repository('Addon:Name')` | Repository replaces model cache pattern |
| `XenForo_Model_DataRegistry` | `\XF::app()->registry()` | Same concept, new API |
