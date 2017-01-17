<?php
namespace ClanCats\Container\Tests;

use ClanCats\Container\{
    ContainerBuilder,
    ServiceDefinition
};
use ClanCats\Container\Tests\TestServices\{
    Car, Engine, Producer
};

class ContainerBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testContainerName()
    {
        $builder = new ContainerBuilder('TestContainer');

        $this->assertEquals('TestContainer', $builder->getContainerName());
    }

    /**
     * @expectedException TypeError
     */
    public function testContainerNameInvalidType()
    {
        new ContainerBuilder(null);
    }

    public function invalidContainerNameProvider()
    {
        return [
            [''],
            [0],
            [1],
            [42],
            [true],
            [false]
        ];
    }

    /**
     * @expectedException ClanCats\Container\Exceptions\ContainerBuilderException
     * @dataProvider invalidContainerNameProvider
     */
    public function testContainerNameEmpty($name)
    {
        new ContainerBuilder($name);
    }

    public function testAddService()
    {
        $builder = new ContainerBuilder('TestContainer');

        $this->assertEquals([], $builder->getServices());
        $this->assertEquals([], $builder->getSharedNames());

        $engineDefinition = ServiceDefinition::for(Engine::class);
        $builder->addService('engine', $engineDefinition);

        // test the default
        $this->assertEquals(['engine' => $engineDefinition], $builder->getServices());
        $this->assertEquals(['engine'], $builder->getSharedNames());

        // removing the shared service
        $builder->addService('engine', $engineDefinition, false);

        $this->assertEquals(['engine' => $engineDefinition], $builder->getServices());
        $this->assertEquals([], $builder->getSharedNames());

        // test adding a second
        $builder->addService('engine', $engineDefinition, true);
        $carDefinition = ServiceDefinition::for(Car::class);
        $builder->addService('car', $carDefinition);

        $this->assertEquals(['engine' => $engineDefinition, 'car' => $carDefinition], $builder->getServices());
        $this->assertEquals(['engine', 'car'], array_values($builder->getSharedNames()));
    }

    /**
     * @expectedException ClanCats\Container\Exceptions\ContainerBuilderException
     * @dataProvider invalidContainerNameProvider
     */
    public function testAddServiceInvalidName($name)
    {
        $builder = new ContainerBuilder('TestContainer');
        $builder->addService($name, ServiceDefinition::for(Car::class));
    }

    public function testAdd()
    {
        $builder = new ContainerBuilder('TestContainer');

        $this->assertInstanceOf(ServiceDefinition::class, $builder->add('engine', Engine::class));

        $this->assertEquals(['engine'], array_keys($builder->getServices()));
        $this->assertEquals(['engine'], $builder->getSharedNames());
    }

    public function testAddArray()
    {
        $builder = new ContainerBuilder('TestContainer');

        $builder->addArray([
            'car' => [
                'class' => Car::class,
                'arguments' => ['@engine', '@producer']
            ],
            
            'producer' => [
                'class' => Producer::class,
                'arguments' => ['BMW']
            ],

            'engine' => [
                'class' => Engine::class,
                'shared' => false,
                'calls' => [
                    ['method' => 'setPower', 'arguments' => [137]]
                ]
            ],
        ]);

        $this->assertEquals(['car', 'producer', 'engine'], array_keys($builder->getServices()));
        $this->assertEquals(['car', 'producer'], $builder->getSharedNames());
    }

    public function testGenerate()
    {
        $builder = new ContainerBuilder('TestContainer');

        $code = $builder->generate();

        // has class definition
        $this->assertContains('class TestContainer', $code);

        // has superclass
        $this->assertContains('extends ClanCatsContainer', $code);
    }

    public function testGenerateArgumentsCode()
    {
        $builder = new ContainerBuilder('TestContainer');

        // Test dependency
        $builder->add('foo', 'Test', ['@foo']);
        $this->assertContains("Test(\$this->get('foo'))", $builder->generate());

        // Test parameter
        $builder->add('foo', 'Test', [':foo']);
        $this->assertContains("Test(\$this->getParameter('foo'))", $builder->generate());

        // Test string
        $builder->add('foo', 'Test', ['foo']);
        $this->assertContains("Test('foo')", $builder->generate());

        // Test number
        $builder->add('foo', 'Test', [42]);
        $this->assertContains("Test(42)", $builder->generate());

        // Test bool
        $builder->add('foo', 'Test', [true]);
        $this->assertContains("Test(true)", $builder->generate());

        // Test array
        $builder->add('foo', 'Test', [['a', 2, 'c' => 'b']]);
        $this->assertContains("Test(array (
  0 => 'a',
  1 => 2,
  'c' => 'b',
))", $builder->generate());
    }

    /**
     * @expectedException ClanCats\Container\Exceptions\ContainerBuilderException
     */
    public function testGenerateArgumentsCodeInvalid()
    {
        $builder = new ContainerBuilder('TestContainer');
        $builder->add('foo', 'Test', ['@42']);
        $builder->generate();
    }

    public function testGenerateResolverTypes()
    {
        $builder = new ContainerBuilder('TestContainer');

        $builder->add('foo', 'Test');
        $this->assertContains("\$serviceResolverType = ['foo' => 0];", $builder->generate());
        
        $builder->add('bar', 'Test');
        $this->assertContains("\$serviceResolverType = ['foo' => 0, 'bar' => 0];", $builder->generate());
    }

    public function testGenerateResolverMapping()
    {
        $builder = new ContainerBuilder('TestContainer');

        $builder->add('foo', 'Test');
        $this->assertContains("protected \$resolverMethods = ['foo' => 'resolveFoo'];", $builder->generate());
    }
}
