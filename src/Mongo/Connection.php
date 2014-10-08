<?php

namespace Mongo;

class Connection
{

    protected static $_instances = array(

    );

    /**
     * @param $name
     * @param \MongoDB $db
     */
    public static function set($name, \MongoDB $db) {
        self::$_instances[$name] = $db;
    }

    /**
     * @param string $name
     * @return \MongoDB|mixed
     */
    public static function get($name = 'default') {
        return self::$_instances[$name];
    }

}