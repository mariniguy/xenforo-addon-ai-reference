<?php

namespace Demo\Portal\Finder;

use XF\Mvc\Entity\Finder;

class FeaturedThread extends Finder
{
    /**
     * Limit results to threads in forums that have auto-feature enabled.
     */
    public function fromAutoFeatureForums(): self
    {
        $this->with('Thread.Forum');
        $this->where('Thread.Forum.demo_portal_auto_feature', 1);

        return $this;
    }

    /**
     * Eager-load everything needed to render the portal view in one query.
     */
    public function withFullData(): self
    {
        $this->with([
            'Thread',
            'Thread.User',
            'Thread.Forum',
            'Thread.FirstPost',
        ]);

        return $this;
    }
}
