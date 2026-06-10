<?php

namespace Demo\Portal\XF\Service\Thread;

/**
 * Class extension of XF\Service\Thread\Editor.
 * When a thread's discussion_state changes to 'deleted' or 'moderated',
 * remove it from the portal automatically.
 */
class Editor extends XFCP_Editor
{
    protected function afterUpdate(): void
    {
        parent::afterUpdate();

        $thread = $this->thread;

        // If the thread is no longer visible, remove it from the portal.
        if ($thread->isChanged('discussion_state') && $thread->discussion_state !== 'visible') {
            /** @var \Demo\Portal\Repository\FeaturedThread $repo */
            $repo = \XF::repository('Demo\Portal:FeaturedThread');
            $repo->unfeatureThread($thread);
        }
    }
}
