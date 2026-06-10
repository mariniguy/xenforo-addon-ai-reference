<?php

namespace Demo\Portal;

use XF\Pub\App;

class Listener
{
    /**
     * app_pub_start event: register the portal nav tab as active when
     * the current controller belongs to this addon.
     */
    public static function appPubStart(App $app): void
    {
        // Nothing needed here for basic operation; used as a hook point for
        // registering custom services, extending the visitor, etc.
    }
}
