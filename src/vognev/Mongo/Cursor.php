<?php
namespace Mongo;

use Iterator;
use MongoCursor;


class Cursor implements Iterator
{
    public $cursor;

    protected $collection;

    protected $class;

    protected $iterated;

    public function __construct(MongoCursor $cursor, $class = null, $method = null)
    {
        $this->cursor     = $cursor;
        $this->class      = $class;
        $this->collection = null;
        $this->iterated   = false;
        $this->method     = $method;
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this->cursor, $method)) {
            call_user_func_array(array($this->cursor, $method), $parameters);
            return $this;
        } else {
            throw new \Exception;
        }
    }

    public function current()
    {
        $current = $this->cursor->current();
        if (is_null($current)) {
            return null;
        }
        else {
            $class = $this->class;
            return new $class($this->cursor->current());
        }
    }

    public function next()
    {
        $this->cursor->next();
        if (!$this->iterated) {
            $this->iterated = true;
        }
    }

    public function rewind()
    {
        $this->cursor->rewind();
        $this->collection = null;
    }

    public function key()
    {
        return $this->cursor->key();
    }

    public function valid()
    {
        return $this->cursor->valid();
    }

    /**
     * Count the number of items in the cursor.
     * @param $applySkipLimit
     * @return int
     */
    public function count($applySkipLimit = false)
    {
        return $this->cursor->count($applySkipLimit);
    }
}