<?php
/**
 * Provides a simple memory cache for promo and group pricing
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 11.27.2013
 * @package shop_extendedpricing
 */
class PriceCache
{
    /** @var array */
    protected $cache = array();

    /** @var bool */
    protected $disabled = false;


    /**
     * @return PriceCache
     */
    public static function inst()
    {
        return Injector::inst()->get('PriceCache');
    }


    /**
     * Disable the cache
     */
    public function disable()
    {
        $this->disabled = true;
    }


    /**
     * Re-enable the cache
     */
    public function enable()
    {
        $this->disabled = false;
    }


    /**
     * @param DataObject $buyable
     * @param string     $type - just an additional key element
     * @return mixed
     */
    public function get($buyable, $type)
    {
        $key = $this->keyFor($buyable, $type);
        if ($key && !$this->disabled && isset($this->cache[$key])) {
            return $this->cache[$key];
        } else {
            return false;
        }
    }


    /**
     * @param DataObject $buyable
     * @param string     $type - just an additional key element
     * @param mixed      $data - variable to set
     * @return bool
     */
    public function fetch($buyable, $type, &$data)
    {
        $key = $this->keyFor($buyable, $type);
        if ($key && !$this->disabled && isset($this->cache[$key])) {
            $data = $this->cache[$key];
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param DataObject $buyable
     * @param string     $type - just an additional key element
     * @param mixed      $data - usually a price
     * @return double - returns the price
     */
    public function set($buyable, $type, $data)
    {
        if ($this->disabled) {
            return $data;
        }
        $key = $this->keyFor($buyable, $type);
        if (!$key) {
            return $data;
        }
        $this->cache[$key] = $data;
        return $data;
    }


    /**
     * Clears the cache (mainly for testing - shouldn't be needed otherwise)
     */
    public function clear()
    {
        $this->cache = array();
    }


    /**
     * @param DataObject $buyable
     * @param string     $type
     * @return string
     */
    protected function keyFor($buyable, $type='')
    {
        return $buyable->ID
            ? $buyable->ClassName . $buyable->ID . $type
            : null; //serialize($buyable->toMap()) . $type;
    }
}
