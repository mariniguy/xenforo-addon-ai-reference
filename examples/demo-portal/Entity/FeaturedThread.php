<?php

namespace Demo\Portal\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $thread_id
 * @property int $featured_date
 * @property int $featured_user_id
 *
 * RELATIONS
 * @property \XF\Entity\Thread $Thread
 * @property \XF\Entity\User   $FeaturedUser
 */
class FeaturedThread extends Entity
{
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table      = 'xf_demo_portal_featured_thread';
        $structure->shortName  = 'Demo\Portal:FeaturedThread';
        $structure->primaryKey = 'thread_id';

        $structure->columns = [
            'thread_id'       => ['type' => self::UINT, 'required' => true],
            'featured_date'   => ['type' => self::UINT, 'default' => \XF::$time],
            'featured_user_id' => ['type' => self::UINT, 'default' => 0],
        ];

        $structure->relations = [
            'Thread' => [
                'entity'     => 'XF:Thread',
                'type'       => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary'    => true,
            ],
            'FeaturedUser' => [
                'entity'     => 'XF:User',
                'type'       => self::TO_ONE,
                'conditions' => [['user_id', '=', '$featured_user_id']],
                'primary'    => true,
            ],
        ];

        $structure->defaultWith = ['Thread', 'Thread.User', 'Thread.Forum'];

        return $structure;
    }
}
