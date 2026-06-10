<?php

namespace Demo\Portal\XF\Admin\Controller;

/**
 * Class extension of XF\Admin\Controller\Forum.
 * The demo_portal_auto_feature field is exposed automatically in the forum
 * edit form via the _data/options.xml template modification; no PHP override
 * is strictly required. This file is included for completeness and to show
 * the pattern for when you DO need to intercept admin forum saves.
 */
class Forum extends XFCP_Forum
{
    // If you need to act on save (e.g., clear a cache), override
    // actionSave() here, call parent::actionSave(), then do your work.
}
