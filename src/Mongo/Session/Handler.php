<?php

namespace Mongo\Session;

use Mongo\Session\Model as Session;

class Handler implements \SessionHandlerInterface
{
    public function open($savePath, $sessionName)
    {
        try {
            Session::collection();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        Session::delete(Session::load($session_id));
        return true;
    }

    public function gc($lifetime)
    {
        $invoked    = time();
        $sessions   = Session::collection();

        // delete sessions having fixed lifetime
        // using expires as the cookie session cookie expires
        // at defined time
        $sessions->remove(array(
            'lifetime'  => array('$gt' => 0),
            'expires'   => array('$lt' => $invoked)
        ));

        // delete browser sessions, using timeout of updated
        // because of not-fixed cookie expire date
        $sessions->remove(array(
            'lifetime'  => 0,
            'updated'   => array('$lt' => $invoked - $lifetime)
        ));

        return true;
    }

    /**
     * @param string $session_id
     * @param string $data
     * @return bool
     */
    public function write($session_id, $data)
    {
        $now            = time();
        $lifetime       = (int) ini_get('session.cookie_lifetime');

        /** @var Session $session */

        if (null === ($session = Session::load($session_id))) {
            $session = Session::create(array(
                '_id'       => $session_id,
                'lifetime'  => $lifetime,
                'created'   => $now,
                'updated'   => $now,
                'expires'   => $now + $lifetime,
                'data'      => $data
            ));
        } else {
            $session->updated   = $now;
            $session->data      = $data;
        }

        Session::persist($session);
        return true;
    }

    public function read($session_id)
    {
        /** @var Session $session */
        $session = Session::load($session_id);

        // the order of invocation of sessionHandler is
        // ::open => ::read => ::gc
        // so we should check opened session for expiration
        // additionally

        if (null === $session || $session->isExpired())
            return '';

        return $session->data;
    }
}