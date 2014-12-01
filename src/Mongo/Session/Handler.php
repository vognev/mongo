<?php

namespace Mongo\Session;

use Mongo\Session\Model as Session;

class Handler implements \SessionHandlerInterface
{
    protected $renewRememberedSessions = false;

    public function __construct($renewRememberedSessions)
    {
        $this->renewRememberedSessions = !!$renewRememberedSessions;
    }

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
        $session = Session::load($session_id);
        $session && Session::delete($session);
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
        $invoked        = time();
        $lifetime       = (int) ini_get('session.cookie_lifetime');

        /** @var Session $session */

        if (null === ($session = Session::load($session_id))) {
            $session = Session::create(array(
                '_id'       => $session_id,
                'lifetime'  => $lifetime,
                'created'   => $invoked,
                'updated'   => $invoked,
                'expires'   => $invoked + $lifetime,
                'data'      => $data
            ));
        } else {
            $session->updated   = $invoked;
            $session->data      = $data;
        }

        Session::persist($session);
        return true;
    }

    public function read($session_id)
    {
        /** @var Session $session */
        $session = Session::load($session_id);
        $invoked = time();
        // the order of invocation of sessionHandler is
        // ::open => ::read => ::gc
        // so we should check opened session for expiration
        // additionally

        if (null === $session || $session->isExpired())
            return '';

        if ($this->renewRememberedSessions)
        if ($session->lifetime > 0)
            $this->_prolongate($session, $invoked);

        return $session->data;
    }

    protected function _prolongate(Session $session, $now) {

        if (headers_sent()) {
            trigger_error(__METHOD__ . ' :: headers were already sent');
            return false;
        }

        $coll = Session::collection();

        $coll->update(array(
            '_id' => $session->_id
        ), array(
            '$set' => array(
                'expires' => ($session->expires = $now + $session->lifetime),
                'updated' => ($session->updated = $now)
            )
        ));

        setcookie(
            ini_get('session.name'),
            $session->_id,
            $session->expires,
            ini_get('session.cookie_path'),
            ini_get('session.cookie_domain'),
            ini_get('session.cookie_secure'),
            ini_get('session.cookie_httponly')
        );

        return true;
    }
}