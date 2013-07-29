<?php

namespace Salla\ContentGenerator\Tests\DataSource;

use Salla\ContentGenerator\DataSource\EntityDataSource;

class EntityDataSourceTest extends \PHPUnit_Framework_TestCase
{

	protected function getFixtures()
	{
		return array(
			new DummyEntity(1, 'one', 'first', 'a secret'),
			new DummyEntity(2, 'two', 'second', 'another secret'),
		);
	}

	public function testMethodAccess()
	{
		$source = new EntityDataSource(
			$this->getFixtures(),
			EntityDataSource::USE_METHODS
		);

		$vars = array('id', 'name', 'slug', 'secret');

		$this->assertEquals(array(
			array('id' => 1, 'name' => 'one', 'secret' => 'a secret'),
			array('id' => 2, 'name' => 'two', 'secret' => 'another secret')
		), $source->getData($vars));
	}

	public function testPropertyAccess()
	{
		$source = new EntityDataSource(
			$this->getFixtures(),
			EntityDataSource::USE_PROPERTIES
		);

		$vars = array('name', 'slug');

		$this->assertEquals(array(
			array('name' => 'one', 'slug' => 'first'),
			array('name' => 'two', 'slug' => 'second')
		), $source->getData($vars));
	}

	public function testDualAccess()
	{
		$source = new EntityDataSource(
			$this->getFixtures(),
			EntityDataSource::USE_BOTH
		);

		$vars = array('id', 'name', 'slug', 'secret');

		$this->assertEquals(array(
			array('id' => 1, 'secret' => 'a secret', 'name' => 'one', 'slug' => 'first'),
			array('id' => 2, 'secret' => 'another secret', 'name' => 'two', 'slug' => 'second')			
		), $source->getData($vars));
	}

}

class DummyEntity
{

	private $id;

	public $name;

	public $slug;

	protected $secret;

	public function __construct($id, $name, $slug, $secret)
	{
		$this->id     = $id;
		$this->name   = $name;
		$this->slug   = $slug;
		$this->secret = $secret;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getSecret()
	{
		return $this->secret;
	}

}
