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
		return sprintf('at %d: (%.5f,%.5f)', $this->getTime(), $this->getLat(), $this->getLong());
	}

	/*
	 * a hack to convert either to float or int
	 */
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
	private $deviant = false;

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

	public function toString()
	{
		return parent::toString() . sprintf(' change: (%.3f,%.3f) %s', $this->getChangeLat(), $this->getChangeLong(),
			($this->deviant) ? ' Deviant!' : '');
	}

	/*
	 * calculates information about deviations.
	 */
	public function testDeviation($margin, $mode='or')
	{
		if ($this->prev)
		{
			if ($mode == 'or')
				$this->deviant = abs($this->getChangeLat()) >= $margin || abs($this->getChangeLong()) >= $margin;
			else
				$this->deviant = abs($this->getChangeLat()) >= $margin && abs($this->getChangeLong()) >= $margin;
			return $this->deviant;
		}
		else
			return false;
	}

	public function setNext($next)
	{
		$this->next = $next;
	}

	public function getChangeLong()
	{
		return $this->changeLong;
	}

	public function getChangeLat()
	{
		return $this->changeLat;
	}
}

/*
 * An utility to validate the point data
 */
class PathValidator
{
	public $points = array();

	private $dAnd;
	private $dOr;

	/*
	 * accepts deviation margins used for error checking.
	 * $dAnd - if both long and lat change absolute exceed this then the point is deviant
	 * $dOr - if either long or lat change absolute exceed this then the point is deviant
	 */
	function __construct($dAnd, $dOr)
	{
		$this->dAnd = $dAnd;
		$this->dOr = $dOr;
	}

	/*
	 * accepts data in csv line string array format (eg. from file('data.csv'))
	 */
	public function load($pathData)
	{
		$len = count($pathData);
		printf("Loading %d points...\n", $len);

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

				// only test for And deviation if Or fails
				if (!$this->points[$i]->testDeviation($this->dOr))
					$this->points[$i]->testDeviation($this->dAnd, 'and');
			}

			printf("%d %s \n", $i, $this->points[$i]->toString());
		}
	}
}

//$v = new PathValidator(array(0.001, 0.005, 0.01));
$v = new PathValidator(0.001, 0.005);
$v->load(file('points.csv'));
?>