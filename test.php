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
	public $points = array();

	private $dAnd;
	private $dOr;
	private $minDelta;

	/*
	 * accepts deviation margins used for error checking.
	 * $dAnd - if both long and lat change absolute exceed this then the point is deviant
	 * $dOr - if either long or lat change absolute exceed this then the point is deviant
	 * $minDelta - when testing for invalid points, use this delta to determine it "comes back" to the path
	 */
	function __construct($dAnd, $dOr, $minDelta)
	{
		$this->dAnd = $dAnd;
		$this->dOr = $dOr;
		$this->minDelta = $minDelta;
	}

	/*
	 * accepts data in csv line string array format (eg. from file('data.csv')), optionally print information about the points
	 */
	public function load($pathData, $verbose=false)
	{
		$len = count($pathData);
		printf("\nLoading %d points...\n", $len);

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

			if ($verbose)
				printf("#%d %s %s\n", $i, $this->points[$i]->toString(), ($this->points[$i]->isDeviant()) ? ' Deviant!' : '');
		}
	}

	/*
	 * returns an array of points that are considered invalid, optionally remove them by default
	 */
	public function printInvalids($remove=true)
	{
		$len = count($this->points);
		printf("\nListing invalid points%s...\n", $remove ? ' (and removing them from the dataset)' : '');

		for ($i=0; $i<$len; $i++)
		{
			// no point in testing the first and last point
			if ($this->points[$i]->next && $this->points[$i]->prev)
			{
				if ($this->points[$i]->isDeviant() && $this->points[$i]->next->isDeviant())
					//abs(abs($this->points[$i]->getChangeLat()) - abs($this->points[$i]->getChangeLong())) <= $this->minDelta)
				{

					printf("Point #%d %s is invalid!\n", $i, $this->points[$i]->toString());
				}
			}
		}
	}
}

// these parameters should work well for a car jorney in the city
// for country drive, train, airplane etc different parameters can be used
$v = new PathValidator(0.001, 0.005, 0.001);
$v->load(file('points.csv'));
$v->printInvalids();
?>