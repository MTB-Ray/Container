<?php
namespace ClanCats\Container\Tests\ContainerParser\Parser;

use ClanCats\Container\Tests\TestCases\ParserTestCase;

use ClanCats\Container\ContainerParser\{
    Parser\ServiceDefinitionParser,
    Nodes\ServiceDefinitionNode,
    Nodes\ValueNode,
    Token as T
};

class ServiceDefinitionParserTest extends ParserTestCase
{
    protected function serviceDefnitionParserFromCode(string $code) : ServiceDefinitionParser 
    {
        return $this->parserFromCode(ServiceDefinitionParser::class, $code);
    }

    protected function serviceDefnitionNodeFromCode(string $code) : ServiceDefinitionNode 
    {
        return $this->serviceDefnitionParserFromCode($code)->parse();
    }

	public function testConstruct()
    {
    	$this->assertInstanceOf(ServiceDefinitionParser::class, $this->serviceDefnitionParserFromCode(''));
    }
    
    public function testSimpleAssign()
    {
        $def = $this->serviceDefnitionNodeFromCode('@logger: Acme\\Log()');

        $this->assertEquals('logger', $def->getName());
        $this->assertEquals('Acme\\Log', $def->getClassName());

        // should have arguments but they are empty
        $this->assertTrue($def->hasArguments());
        $this->assertCount(0, $def->getArguments()->getArguments());
    }

    public function testWithNoArgument()
    {
        $def = $this->serviceDefnitionNodeFromCode('@logger: Acme\\Log');

        $this->assertEquals('logger', $def->getName());
        $this->assertEquals('Acme\\Log', $def->getClassName());
        $this->assertFalse($def->hasArguments());
    }

    public function testAssignWithArguments()
    {
        $def = $this->serviceDefnitionNodeFromCode('@logger: Acme\\Log(@log.handler, :path)');
        $this->assertTrue($def->hasArguments());

        $arguments = $def->getArguments()->getArguments();

        $this->assertCount(2, $arguments);
        $this->assertEquals('log.handler', $arguments[0]->getName());
        $this->assertEquals('path', $arguments[1]->getName());
    }

    public function testOverride()
    {
        $def = $this->serviceDefnitionNodeFromCode('@logger: Acme\\Log');
        $this->assertFalse($def->isOverride());

        $def = $this->serviceDefnitionNodeFromCode('override @logger: Acme\\Log');
        $this->assertTrue($def->isOverride());
    }

    public function testMethodCalls()
    {
        $def = $this->serviceDefnitionNodeFromCode("@logger: Acme\\Log\n- setName('app')");

        $actions = $def->getConstructionActions();

        $this->assertCount(1, $actions);

        $this->assertEquals('setName', $actions[0]->getName());
    }

    public function testMultipleMethodCalls()
    {
        $def = $this->serviceDefnitionNodeFromCode("@logger: Acme\\Log\n- setName('app')\n- setLevel(1)");

        $actions = $def->getConstructionActions();

        $this->assertCount(2, $actions);

        $this->assertEquals('setName', $actions[0]->getName());
        $this->assertEquals('setLevel', $actions[1]->getName());
    }

    /**
     * @expectedException ClanCats\Container\Exceptions\ContainerParserException
     */
    public function testMissingDependencyIndicator()
    {
        $this->serviceDefnitionNodeFromCode('logger: Acme\\Log');
    }

    /**
     * @expectedException ClanCats\Container\Exceptions\ContainerParserException
     */
    public function testMissingAssignIndicator()
    {
        $this->serviceDefnitionNodeFromCode('@logger Acme\\Log');
    }

    /**
     * @expectedException ClanCats\Container\Exceptions\ContainerParserException
     */
    public function testWrongAssignment()
    {
        $this->serviceDefnitionNodeFromCode('@logger: @antoherone');
    }
}