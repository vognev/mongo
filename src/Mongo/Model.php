<?php

namespace Mongo;

/**
 * @method static array         aggregate       ( array $pipeline, array $options = null )
 * @method static int           count           (array $query = array(), int $limit = 0, int $skip = 0)
 * @method static Cursor        find            (array $query = array(), array $fields = array() )
 * @method static array         findAndModify   ( array $query, array $update, array $fields, array $options )
 * @methid static array         group           ( mixed $keys , array $initial , MongoCode $reduce, array $options = array() )
 * @method static bool|array    insert          ( mixed $a, array $options = array() )
 * @method static bool|array    remove          (array $criteria = array(), array $options = array() )
 * @method static mixed         save            ( mixed $a, array $options = array() )
 * @method bool|array           update          ( array $criteria , array $new_object, array $options = array() )
 */
abstract class Model
{

    /**
     * @var mixed model identifier
     */
    public $_id;

    public function __construct(array $attributes = array())
    {
        foreach($attributes as $name => $attribute)
            $this->$name = $attribute;
    }

    public function __set($name, $value) {
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter))
            return $this->$setter($value);
        throw new \Exception(sprintf('The setter "%s" does not exists in "%s"', $setter, get_called_class()));
    }

    public function __get($name) {
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter))
            return $this->$getter();
        throw new \Exception(sprintf('The getter "%s" does not exists in "%s"', $getter, get_called_class()));
    }

    public function toArray()
    {
        return @array_filter(get_object_vars($this), function ($value) {
            return $value !== null;
        });
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
        return static::findOne(array(
            '_id' => $id
        ));
    }

    public static function findOne(array $query = array(), array $fields = array(), array $options = array()) {
        $found = static::collection()->findOne($query, $fields, $options);
        return $found ? new static($found) : null;
    }

    public static function create(array $attributes = array())
    {
        return new static($attributes);
    }

    public static function persist(Model $model) {
        $a = $model->toArray();
        static::collection()->save($a, array(
            'safe' => true
        ));
        $model->_id = $a['_id'];
    }

    public static function delete($modelOrId) {
        static::collection()->remove(array(
            '_id' => ($modelOrId instanceof Model) ? $modelOrId->_id : $modelOrId
        ));
    }

    /**
     * @return \MongoCollection
     */
    public static function collection()
    {
        $db = Connection::get(static::$_connection);
        return $db->{static::$_collection};
    }

}