<?php

namespace Mongo\Session;

class Model extends \Mongo\Model {

    public $lifetime;
    public $created;
    public $updated;
    public $expires;
    public $data;

    public function isExpired() {
        $lifetime   = ini_get('session.gc_maxlifetime');
        $invoked    = time();

        // remembered session having fixed lifetime;
        // using expires as the cookie session cookie expires
        // at defined time
        if ($this->lifetime > 0 && $this->expires < $invoked)
            return true;

        // browser sessions using timeout of updated
        // because of not-fixed cookie expire date
        if (0 == $this->lifetime && $this->updated < ($invoked - $lifetime))
            return true;

        return false;
    }

    public static function collection()
    {
        $collection = parent::collection();
        $collection->ensureIndex(array(
            'lifetime'      => 1,
            'expires'       => 1
        ));
        $collection->ensureIndex(array(
            'lifetime'      => 1,
            'updated'       => 1
        ));
        return $collection;
    }
}