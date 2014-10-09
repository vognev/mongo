<?php

namespace Mongo;

/**
 * @property mixed $_id
 *
 * @method static array         aggregate       ( array $pipeline, array $options = null )
 * @method static int           count           (array $query = array(), int $limit = 0, int $skip = 0)
 * @method static Cursor        find            (array $query = array(), array $fields = array() )
 * @method static array         findAndModify   ( array $query, array $update, array $fields, array $options )
 * @method static array         findOne         (array $query = array(), array $fields = array(), array $options = array() )
 * @methid static array         group           ( mixed $keys , array $initial , MongoCode $reduce, array $options = array() )
 * @method static bool|array    insert          ( mixed $a, array $options = array() )
 * @method static bool|array    remove          (array $criteria = array(), array $options = array() )
 * @method static mixed         save            ( mixed $a, array $options = array() )
 * @method bool|array           update          ( array $criteria , array $new_object, array $options = array() )
 */
abstract class Model implements \ArrayAccess
{

    // object methods are modl-level
    protected $_attributes;

    public function __construct(array $attributes = array())
    {
        $this->_attributes = $attributes;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_attributes);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset))
            return $this->_attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_attributes[$offset]);
    }

    public function toArray()
    {
        return @array_filter($this->_attributes, function ($value) {
            return $value !== null;
        });
    }

    // convenient magic methods
    function __set($offset, $value) {
        $this->offsetSet($offset, $value);
    }

    function __get($offset) {
        return $this->offsetGet($offset);
    }

    function __isset($offset) {
        return $this->offsetExists($offset);
    }

    function __unset($offset) {
        $this->offsetUnset($offset);
    }

    protected static $_connection = 'default';

    protected static $_collection = 'sessions';

    const ASCENDING         = 1 ;
    const DESCENDING        = -1 ;

    public static function __callStatic($method, $parameters)
    {
        $results = call_user_func_array(array(static::collection(), $method), $parameters);
        if ($results instanceof \MongoCursor)
            $results = new Cursor($results, get_called_class(), $method);
        return $results;
    }

    public static function load($id) {
        $found = self::collection()->findOne(array(
            '_id' => $id
        ));
        return null === $found ? null : static::create($found);
    }

    public static function create(array $attributes = array())
    {
        return new static($attributes);
    }

    public static function persist(Model $model) {
        $a = $model->toArray();
        self::collection()->save($a, array(
            'safe' => true
        ));
        $model->_id = $a['_id'];
    }

    public static function delete($modelOrId) {
        self::collection()->remove(array(
            '_id' => ($modelOrId instanceof Model) ? $modelOrId->_id : $modelOrId
        ));
    }

    /**
     * @return \MongoCollection
     */
    public static function collection()
    {
        $db = Connection::get(self::$_connection);
        return $db->{self::$_collection};
    }

}