<?php

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
}

?>