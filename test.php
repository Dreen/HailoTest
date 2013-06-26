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

	public function getLong()
	{
		return $this->long;
	}

	public function getLat()
	{
		return $this->lat;
	}

	public function getTime()
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

	function __construct($lineData, $prev=NULL)
	{
		parent::__construct($lineData);

		if ($prev)
		{
			// calculate change in location in relation to the previous point
			$this->changeLat = $this->getLat() - $prev->getLat();
			$this->changeLong = $this->getLong() - $prev->getLong();
			$this->prev = $prev;
		}
	}

	public function setNext($next)
	{
		$this->next = $next;
	}
}

/*
 * An utility to validate the point data
 */
class PathValidator
{
	public $points = array();

	// accepts data in csv line string array format (eg. from file('data.csv'))
	function __construct($pathData)
	{
		$len = count($pathData);
		for ($i=0; $i<$len; $i++)
		{
			if ($i===0)
			{
				$this->points[$i] = new PathPoint(explode(',',$pathData[$i]));
			}
			else
			{
				$this->points[$i] = new PathPoint(explode(',',$pathData[$i]), $this->points[$i - 1]);
				$this->points[$i -1]->setNext($this->points[$i]);
			}
		}
	}
}

$v = new PathValidator(file('points.csv'));
$v->points[0]->toString();
$v->points[10]->toString();
$v->points[226]->toString();
?>