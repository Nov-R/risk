<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Container;

/**
 * 依赖注入容器测试类
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = Container::getInstance();
        $this->container->flush(); // 清空容器状态
    }

    public function testGetInstance(): void
    {
        $instance1 = Container::getInstance();
        $instance2 = Container::getInstance();
        
        $this->assertSame($instance1, $instance2, '容器应该是单例模式');
    }

    public function testBind(): void
    {
        $this->container->bind('test', 'TestClass');
        $this->assertTrue($this->container->bound('test'));
    }

    public function testSingleton(): void
    {
        $this->container->singleton('test', function() {
            return new \stdClass();
        });
        
        $instance1 = $this->container->make('test');
        $instance2 = $this->container->make('test');
        
        $this->assertSame($instance1, $instance2, '单例绑定应该返回相同实例');
    }

    public function testMakeWithClosure(): void
    {
        $this->container->bind('test', function() {
            $obj = new \stdClass();
            $obj->value = 'test_value';
            return $obj;
        });
        
        $instance = $this->container->make('test');
        $this->assertEquals('test_value', $instance->value);
    }

    public function testInstance(): void
    {
        $obj = new \stdClass();
        $obj->value = 'instance_value';
        
        $this->container->instance('test', $obj);
        $instance = $this->container->make('test');
        
        $this->assertSame($obj, $instance);
        $this->assertEquals('instance_value', $instance->value);
    }

    public function testForgetBinding(): void
    {
        $this->container->bind('test', 'TestClass');
        $this->assertTrue($this->container->bound('test'));
        
        $this->container->forgetBinding('test');
        $this->assertFalse($this->container->bound('test'));
    }

    public function testFlush(): void
    {
        $this->container->bind('test1', 'TestClass1');
        $this->container->bind('test2', 'TestClass2');
        
        $this->assertTrue($this->container->bound('test1'));
        $this->assertTrue($this->container->bound('test2'));
        
        $this->container->flush();
        
        $this->assertFalse($this->container->bound('test1'));
        $this->assertFalse($this->container->bound('test2'));
    }
}