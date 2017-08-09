<?php

namespace Soldo\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Soldo\Exceptions\SoldoInvalidRelationshipException;
use Soldo\Resources\SoldoResource;
use Soldo\Tests\Fixtures\MockResource;


/**
 * Class SoldoResourceTest
 */
class SoldoResourceTest extends TestCase
{
    /** @var MockResource */
    private static $resource;

    public static function setUpBeforeClass()
    {
        $r = new MockResource(['foo' => 'bar']);
        self::$resource = $r;
    }


    public function testFill()
    {
        /** @var SoldoResource $resource */
        $resource = new MockResource();

        $this->assertNull($resource->foo);
        $resource->fill([
            'foo' => 'bar',
            'castable_attribute' => [
                'foo' => 'bar',
                'john' => 'doe',
            ]
        ]);

        $this->assertNotNull($resource->foo);
        $this->assertEquals('bar', $resource->foo);

        $this->assertNotNull($resource->castable_attribute);
        $this->assertInternalType('array', $resource->castable_attribute);
        $this->assertEquals([
            'foo' => 'bar',
            'john' => 'doe',
        ], $resource->castable_attribute);
    }

    /**
     * @expectedException \Soldo\Exceptions\SoldoCastException
     * @expectedExceptionMessage Could not cast castable_attribute. NotExistentClassName doesn't exist
     */
    public function testFillCastableInvalidClassName()
    {
        /** @var SoldoResource $resource */
        $resource = new MockResource();
        $resource->setCast(
            ['castable_attribute' => 'NotExistentClassName']
        );

        $resource->fill([
            'castable_attribute' => [
                'foo' => 'bar',
                'john' => 'doe',
            ]
        ]);
    }

    /**
     * @expectedException \Soldo\Exceptions\SoldoCastException
     * @expectedExceptionMessage Could not cast castable_attribute. stdClass is not a SoldoResource child
     */
    public function testFillCastableNotChildOfSoldoResource()
    {
        /** @var SoldoResource $resource */
        $resource = new MockResource();
        $resource->setCast(
            ['castable_attribute' => \stdClass::class]
        );

        $resource->fill([
            'castable_attribute' => [
                'foo' => 'bar',
                'john' => 'doe',
            ]
        ]);
    }

    /**
     * @expectedException \Soldo\Exceptions\SoldoCastException
     * @expectedExceptionMessage Could not cast castable_attribute. $data is not a valid data set
     */
    public function testFillCastableNotValidDataset()
    {
        /** @var SoldoResource $resource */
        $resource = new MockResource();
        $resource->setCast(
            ['castable_attribute' => MockResource::class]
        );

        $resource->fill(
            ['castable_attribute' => 'not_an_array']
        );
    }

    public function testFillWithCastableAttribute()
    {
        /** @var SoldoResource $resource */
        $resource = new MockResource([]);
        $resource->setCast(
            ['castable_attribute' => MockResource::class]
        );

        $resource->fill([
            'foo' => 'bar',
            'castable_attribute' => [
                'foo' => 'bar',
                'john' => 'doe',
            ]
        ]);

        $this->assertInstanceOf(MockResource::class, $resource->castable_attribute);
        $this->assertEquals('bar', $resource->castable_attribute->foo);
        $this->assertEquals('doe', $resource->castable_attribute->john);
    }

    public function testToArrayEmptyData()
    {
        $resource = new MockResource();
        $this->assertInternalType('array', $resource->toArray());
        $this->assertEmpty($resource->toArray());
    }

    public function testToArrayLinearData()
    {
        $data = ['foo' => 'bar'];
        $resource = new MockResource($data);
        $this->assertEquals(
            $data,
            $resource->toArray()
        );
    }

    public function testToArrayMultidimensionalArray()
    {
        $data = [
            'foo' => 'bar',
            'lorem_ipsum' => [
                'foo' => 'bar',
                'john' => 'doe',
            ]
        ];
        $resource = new MockResource($data);
        $this->assertEquals(
            $data,
            $resource->toArray()
        );
    }

    public function testToArrayWithCastedAttributes()
    {
        $data = [
            'foo' => 'bar',
            'lorem_ipsum' => [
                'foo' => 'bar',
                'john' => 'doe',
            ]
        ];
        $resource = new MockResource();
        $resource->setCast(
            [ 'lorem_ipsum' => MockResource::class ]
        );
        $resource->fill($data);

        // no real need for testing this, it's just to be sure
        $this->assertInstanceOf(MockResource::class, $resource->lorem_ipsum);
        $this->assertNotNull($resource->foo);

        $this->assertEquals(
            [
                'foo' => 'bar',
                'lorem_ipsum' => [
                    'foo' => 'bar',
                    'john' => 'doe',
                ]
            ],
            $resource->toArray()
        );
    }


    public function testGetRemotePath()
    {
        $resource = new MockResource();
        $this->assertEquals('/', $resource->getRemotePath());

        $resource->id = null;
        $this->assertEquals('/', $resource->getRemotePath());

        $resource->id = 1;
        $this->assertEquals('/1', $resource->getRemotePath());

        $resource->id = 'a-string';
        $this->assertEquals('/a-string', $resource->getRemotePath());

        $resource->id = 'a string with spaces';
        $this->assertEquals('/a+string+with+spaces', $resource->getRemotePath());

        $resource = new MockResource();
        $resource->setBasePath('/paths');
        $this->assertEquals('/paths/', $resource->getRemotePath());

        $resource->id = null;
        $this->assertEquals('/paths/', $resource->getRemotePath());

        $resource->id = 1;
        $this->assertEquals('/paths/1', $resource->getRemotePath());

        $resource->id = 'a-string';
        $this->assertEquals('/paths/a-string', $resource->getRemotePath());

        $resource->id = 'a string with spaces';
        $this->assertEquals('/paths/a+string+with+spaces', $resource->getRemotePath());


    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There is no relationship mapped with "resources" name
     */
    public function testBuildRelationshipNotMappedRelationship()
    {
        $resource = new MockResource();
        $resources = $resource->buildRelationship('resources', []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid resource class name InvalidClassName doesn't exist
     */
    public function testBuildRelationshipWithInvalidClassName()
    {
        /** @var MockResource $resource */
        $resource = new MockResource();
        $resource->setRelationships(['resources' => 'InvalidClassName']);
        $resources = $resource->buildRelationship('resources', []);
    }

    /**
     * @expectedException \Soldo\Exceptions\SoldoInvalidRelationshipException
     */
    public function testBuildRelationshipRawDataNotAnArray()
    {
        /** @var MockResource $resource */
        $resource = new MockResource();
        $resource->setRelationships(['resources' => MockResource::class]);
        $resources = $resource->buildRelationship('resources', 'not-an-array');

    }

    /**
     * @expectedException \Soldo\Exceptions\SoldoInvalidRelationshipException
     */
    public function testBuildRelationshipEmptyRowData()
    {
        /** @var MockResource $resource */
        $resource = new MockResource();
        $resource->setRelationships(['resources' => MockResource::class]);
        $resources = $resource->buildRelationship('resources', []);

    }

    /**
     * @expectedException \Soldo\Exceptions\SoldoInvalidRelationshipException
     */
    public function testBuildRelationshipNotAMultidimensionalArray()
    {
        $resource = new MockResource();
        $resource->setRelationships(['resources' => MockResource::class]);
        $resources = $resource->buildRelationship('resources', ['resources' => ['foo' => 'bar']]);

    }

    public function testBuildRelationship()
    {
        $resource = new MockResource();
        $resource->setRelationships(['resources' => MockResource::class]);
        $resources = $resource->buildRelationship('resources', ['resources' => [
            ['foo' => 'bar'],
            ['lorem' => 'ipsum'],
        ]]);

        $this->assertCount(2, $resources);
        foreach ($resources as $r) {
            /** @var MockResource $r */
            $this->assertInstanceOf(MockResource::class, $r);
        }
    }



}
