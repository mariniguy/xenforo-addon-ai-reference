<?php

namespace Demo\Portal\XF\Entity;

/**
 * Class extension of XF\Entity\Thread.
 * Adds the FeaturedThread relation so $thread->FeaturedThread works everywhere.
 */
class Thread extends XFCP_Thread
{
    public static function getStructure(\XF\Mvc\Entity\Structure $structure): \XF\Mvc\Entity\Structure
    {
        $structure = parent::getStructure($structure);

        $structure->relations['FeaturedThread'] = [
            'entity'     => 'Demo\Portal:FeaturedThread',
            'type'       => self::TO_ONE,
            'conditions' => 'thread_id',
            'primary'    => true,
        ];

        return $structure;
    }

    /**
     * Convenience helper used in templates and controllers.
     */
    public function isFeaturedOnPortal(): bool
    {
        return (bool) $this->FeaturedThread;
    }
}
