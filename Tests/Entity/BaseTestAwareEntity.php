<?php

namespace Algolia\AlgoliaSearchSymfonyDoctrineBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;


class BaseTestAwareEntity
{
	private $test_props = array();

	public function setTestProp($prop, $value)
	{
		$this->test_props[$prop] = $value;
		return $this;
	}

	public function getTestProp($prop, $default = null)
	{
		if (isset($this->test_props[$prop]))
			return $this->test_props[$prop];
		else return $default;
	}
}