<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Config\Environment;

/**
 * 环境配置测试类
 */
class EnvironmentTest extends TestCase
{
    private Environment $environment;

    protected function setUp(): void
    {
        $this->environment = Environment::getInstance();
    }

    public function testGetInstance(): void
    {
        $instance1 = Environment::getInstance();
        $instance2 = Environment::getInstance();
        
        $this->assertSame($instance1, $instance2, '环境配置应该是单例模式');
    }

    public function testGetDefaultValues(): void
    {
        $this->assertEquals('production', $this->environment->get('APP_ENV'));
        $this->assertEquals('false', $this->environment->get('APP_DEBUG'));
        $this->assertEquals('localhost', $this->environment->get('DB_HOST'));
        $this->assertEquals('risk_management', $this->environment->get('DB_DATABASE'));
    }

    public function testGetWithDefault(): void
    {
        $value = $this->environment->get('NON_EXISTENT_KEY', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function testSetAndGet(): void
    {
        $this->environment->set('TEST_KEY', 'test_value');
        $this->assertEquals('test_value', $this->environment->get('TEST_KEY'));
    }

    public function testIsDebug(): void
    {
        // 在测试环境中应该是调试模式
        $this->environment->set('APP_DEBUG', 'true');
        $this->assertTrue($this->environment->isDebug());
        
        $this->environment->set('APP_DEBUG', 'false');
        $this->assertFalse($this->environment->isDebug());
    }

    public function testAll(): void
    {
        $config = $this->environment->all();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('APP_ENV', $config);
        $this->assertArrayHasKey('DB_HOST', $config);
    }
}