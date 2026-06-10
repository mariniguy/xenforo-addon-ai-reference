<?php

namespace Demo\Portal\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

/**
 * Class extension of XF\Pub\Controller\Thread.
 * Adds feature/unfeature actions accessible from the thread view.
 */
class Thread extends XFCP_Thread
{
    /**
     * POST /threads/{thread_id}/feature
     * Feature this thread on the portal.
     */
    public function actionDemoPortalFeature(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();

        /** @var \XF\Entity\Thread $thread */
        $thread = $this->assertViewableThread($params->thread_id);

        if (!\XF::visitor()->hasPermission('demo_portal', 'featureThread')) {
            return $this->noPermission();
        }

        /** @var \Demo\Portal\Repository\FeaturedThread $repo */
        $repo = $this->repository('Demo\Portal:FeaturedThread');
        $repo->featureThread($thread);

        return $this->redirect($this->buildLink('threads', $thread));
    }

    /**
     * POST /threads/{thread_id}/unfeature
     * Remove this thread from the portal.
     */
    public function actionDemoPortalUnfeature(ParameterBag $params): \XF\Mvc\Reply\AbstractReply
    {
        $this->assertPostOnly();

        /** @var \XF\Entity\Thread $thread */
        $thread = $this->assertViewableThread($params->thread_id);

        if (!\XF::visitor()->hasPermission('demo_portal', 'featureThread')) {
            return $this->noPermission();
        }

        /** @var \Demo\Portal\Repository\FeaturedThread $repo */
        $repo = $this->repository('Demo\Portal:FeaturedThread');
        $repo->unfeatureThread($thread);

        return $this->redirect($this->buildLink('threads', $thread));
    }
}
