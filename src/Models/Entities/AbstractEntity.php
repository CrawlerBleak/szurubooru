<?php
class AbstractEntity
{
	public $id;
	protected $__cache;

	public function setCache($key, $value)
	{
		$this->__cache[$key] = $value;
	}

	public function getCache($key)
	{
		return isset($this->__cache[$key])
			? $this->__cache[$key]
			: null;
	}

	public function hasCache($key)
	{
		return isset($this->__cache[$key]);
	}
}
