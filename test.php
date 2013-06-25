<?php

/*
 * Represents a single timestamped point on the map
 */
class Point
{
	private $long;
	private $lat;
	private $t;

	function __construct($lineData)
	{
		list($this->long, $this->lat, $this->t) = array_map(array($this, '_cast'), $lineData);
	}

	public function toString()
	{
		var_dump($this);
	}

	// a hack to convert either to float or int
	private function _cast($numStr)
	{
		return $numStr+0;
	}

	public getLong()
	{
		return $this->long;
	}

	public getLat()
	{
		return $this->lat;
	}

	public getTime()
	{
		return $this->t;
	}
}

/*
 * Represents a point that is a part of a path and aware of its neighbors
 */
class PathPoint extends Point
{
	private $changeLat;
	private $changeLong;
	private $prev;
	private $next;

	function __construct($lineData, $prev)
	{
		parent::__construct($lineData);

		// calculate change in location in relation to the previous point
		$this->changeLat = $this->getLat() - $prev->getLat();
		$this->changeLong = $this->getLong() - $prev->getLong();
	}

	public function setNext($next)
	{
		$this->next = $next;
	}
}

?>