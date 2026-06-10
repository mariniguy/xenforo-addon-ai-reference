<?php

namespace Demo\Portal\XF\Service\Thread;

/**
 * Class extension of XF\Service\Thread\Creator.
 * After a thread is successfully created, auto-features it if the parent
 * forum has demo_portal_auto_feature = 1.
 */
class Creator extends XFCP_Creator
{
    /**
     * Called by the parent service after the thread and first post are saved.
     * We hook in here to auto-feature threads from eligible forums.
     */
    protected function afterInsert(): void
    {
        parent::afterInsert();

        $thread = $this->thread;
        $forum  = $thread->Forum;

        if ($forum && $forum->demo_portal_auto_feature) {
            /** @var \Demo\Portal\Repository\FeaturedThread $repo */
            $repo = \XF::repository('Demo\Portal:FeaturedThread');
            $repo->featureThread($thread);
        }
    }
}
