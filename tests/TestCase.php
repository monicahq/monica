<?php

namespace Tests;

use Illuminate\Testing\TestResponse;
use Tests\Traits\CreatesApplication;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Call protected/private method of a class.
     *
     * @param  object  &$object
     * @param  string  $methodName
     * @param  array  $parameters
     * @return mixed
     */
    public function invokePrivateMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Set protected/private property of a class.
     *
     * @param  object  &$object
     * @param  string  $propertyName
     * @param  mixed  $value
     * @return void
     */
    public function setPrivateValue(&$object, string $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        $property->setValue($object, $value);
    }

    /**
     * Get protected/private property of a class.
     *
     * @param  object  &$object
     * @param  string  $propertyName
     * @return mixed
     */
    public function getPrivateValue(&$object, string $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * Test that the response contains an ObjectDeleted response.
     *
     * @param  TestResponse  $response
     * @param  int  $id
     */
    public function expectObjectDeleted(TestResponse $response, int $id)
    {
        $response->assertStatus(200);

        $response->assertJson([
            'deleted' => true,
            'id' => $id,
        ]);
    }
}
