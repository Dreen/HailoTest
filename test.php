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
	public $prev;
	public $next;

	private $changeLat;
	private $changeLong;

	/*
	 * A deviant point may be invalid because it differs in some significant way from its neighbors
	 */
	private $deviant = false;

	function __construct($lineData, $prev=NULL)
	{
		parent::__construct($lineData);
		$this->setPrev($prev);
	}

	public function toString()
	{
		return parent::toString() . sprintf(' change: (%.3f,%.3f)', $this->getChangeLat(), $this->getChangeLong());
	}

	/*
	 * detects potentially invalid points by marking them deviant
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

	public function setPrev($prev)
	{
		if ($prev)
		{
			$this->prev = $prev;

			// calculate change in location in relation to the previous point
			$this->changeLat = $this->getLat() - $this->prev->getLat();
			$this->changeLong = $this->getLong() - $this->prev->getLong();
		}
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

	public function isDeviant()
	{
		return $this->deviant;
	}
}

/*
 * An utility to validate the point data
 */
class PathValidator
{
	private $firstPoint;
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
	 * accepts data in csv line string array format (eg. from file('data.csv')), optionally print information about the points
	 */
	public function load($pathData, $verbose=false)
	{
		$len = count($pathData);
		$points = array();
		printf("\nLoading %d points...\n", $len);

		for ($i=0; $i<$len; $i++)
		{
			if ($i===0)
			{
				$points[$i] = new PathPoint(explode(',',$pathData[$i]));
				$this->firstPoint = $points[$i];
			}
			else
			{
				$points[$i] = new PathPoint(explode(',',$pathData[$i]), $points[$i - 1]);
				$points[$i -1]->setNext($points[$i]);

				// only test for And deviation if Or fails
				if (!$points[$i]->testDeviation($this->dOr))
					$points[$i]->testDeviation($this->dAnd, 'and');
			}

			if ($verbose)
				printf("#%d %s %s\n", $i, $points[$i]->toString(), ($points[$i]->isDeviant()) ? ' Deviant!' : '');
		}
	}

	/*
	 * removes points that are considered invalid
	 */
	public function killInvalids()
	{
		printf("\nRemoving invalid points...\n");
		$point = $this->firstPoint;

		while ($point->next)
		{
			if ($point->prev && $point->isDeviant() && $point->next->isDeviant())
			{
				printf("Point %s is invalid!\n", $point->toString());

				// remove the point from the path
				$point->prev->setNext($point->next);
				$point->next->setPrev($point->prev);
			}
			$point = $point->next;
		}
	}

	public function countPoints()
	{
		$point = $this->firstPoint;
		$count = 1;
		while ($point->next)
		{
			$count++;
			$point = $point->next;
		}
		printf("\nThere are %d points in the path\n", $count);
	}
}

// these parameters should work well for a car jorney in the city
// for country drive, train, airplane etc different parameters can be used
$v = new PathValidator(0.001, 0.005);
$v->load(file('points.csv'));
$v->countPoints();
$v->killInvalids();
$v->countPoints();
?>