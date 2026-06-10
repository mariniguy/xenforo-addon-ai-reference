<?php

namespace Demo\Portal\Repository;

use XF\Mvc\Entity\Repository;

class FeaturedThread extends Repository
{
    /**
     * Return a finder pre-configured for the portal listing page.
     * Caller chains ->fetch() or ->fetchOne() as needed.
     */
    public function findFeaturedThreadsForPortal(): \Demo\Portal\Finder\FeaturedThread
    {
        /** @var \Demo\Portal\Finder\FeaturedThread $finder */
        $finder = $this->finder('Demo\Portal:FeaturedThread');

        $finder
            ->with(['Thread', 'Thread.User', 'Thread.Forum'])
            ->where('Thread.discussion_state', 'visible')
            ->setDefaultOrder('featured_date', 'DESC');

        return $finder;
    }

    /**
     * Feature a thread.  Idempotent — safe to call more than once.
     */
    public function featureThread(\XF\Entity\Thread $thread): \Demo\Portal\Entity\FeaturedThread
    {
        $visitor = \XF::visitor();

        /** @var \Demo\Portal\Entity\FeaturedThread $featuredThread */
        $featuredThread = $this->em->find('Demo\Portal:FeaturedThread', $thread->thread_id);

        if (!$featuredThread) {
            /** @var \Demo\Portal\Entity\FeaturedThread $featuredThread */
            $featuredThread = $this->em->create('Demo\Portal:FeaturedThread');
            $featuredThread->thread_id        = $thread->thread_id;
            $featuredThread->featured_user_id = $visitor->user_id;
            $featuredThread->featured_date    = \XF::$time;
            $featuredThread->save();
        }

        return $featuredThread;
    }

    /**
     * Unfeature a thread.
     */
    public function unfeatureThread(\XF\Entity\Thread $thread): void
    {
        $featuredThread = $this->em->find('Demo\Portal:FeaturedThread', $thread->thread_id);

        if ($featuredThread) {
            $featuredThread->delete();
        }
    }
}
