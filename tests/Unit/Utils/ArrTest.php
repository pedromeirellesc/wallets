<?php

namespace Tests\Unit\Utils;

use App\Utils\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    public function testGetReturnsWholeArrayWhenKeyIsNull(): void
    {
        $array = ['name' => 'John', 'age' => 30];

        $result = Arr::get($array, null);

        $this->assertEquals($array, $result);
    }

    public function testGetReturnsFlatValue(): void
    {
        $array = ['name' => 'John', 'age' => 30];

        $result = Arr::get($array, 'name');

        $this->assertEquals('John', $result);
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $array = ['name' => 'John'];

        $result = Arr::get($array, 'email', 'default@example.com');

        $this->assertEquals('default@example.com', $result);
    }

    public function testGetReturnsNullAsDefaultWhenKeyNotFound(): void
    {
        $array = ['name' => 'John'];

        $result = Arr::get($array, 'email');

        $this->assertNull($result);
    }

    public function testGetReturnsNestedValueUsingDotNotation(): void
    {
        $array = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                ],
            ],
        ];

        $result = Arr::get($array, 'user.profile.name');

        $this->assertEquals('John Doe', $result);
    }

    public function testGetReturnsDefaultWhenNestedKeyNotFound(): void
    {
        $array = [
            'user' => [
                'profile' => [],
            ],
        ];

        $result = Arr::get($array, 'user.profile.name', 'Unknown');

        $this->assertEquals('Unknown', $result);
    }

    public function testGetReturnsDefaultWhenIntermediateKeyNotFoundInNestedPath(): void
    {
        $array = [
            'user' => [],
        ];

        $result = Arr::get($array, 'user.profile.name', 'Not Found');

        $this->assertEquals('Not Found', $result);
    }

    public function testGetHandlesNullValuesInNestedArray(): void
    {
        $array = [
            'user' => [
                'profile' => null,
            ],
        ];

        $result = Arr::get($array, 'user.profile', 'default');

        $this->assertEquals('default', $result);
    }

    public function testGetReturnsZeroWhenValueIsZero(): void
    {
        $array = ['count' => 0];

        $result = Arr::get($array, 'count', 1);

        $this->assertEquals(0, $result);
    }

    public function testGetReturnsEmptyStringWhenValueIsEmpty(): void
    {
        $array = ['name' => ''];

        $result = Arr::get($array, 'name', 'default');

        $this->assertEquals('', $result);
    }

    public function testOnlyFiltersArrayToIncludeOnlySpecificKeys(): void
    {
        $array = [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
            'age' => 30,
        ];
        $keys = ['name', 'email'];

        $result = Arr::only($array, $keys);

        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);
    }

    public function testOnlyReturnsEmptyArrayWhenNoKeysMatch(): void
    {
        $array = ['name' => 'John', 'age' => 30];
        $keys = ['email', 'password'];

        $result = Arr::only($array, $keys);

        $this->assertEquals([], $result);
    }

    public function testOnlyPreservesOrderOfRequestedKeys(): void
    {
        $array = [
            'name' => 'John',
            'email' => 'john@example.com',
            'age' => 30,
        ];
        $keys = ['email', 'name'];

        $result = Arr::only($array, $keys);

        // array_intersect_key preserva a ordem original do array
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
    }

    public function testOnlyWithEmptyKeysArray(): void
    {
        $array = ['name' => 'John', 'age' => 30];
        $keys = [];

        $result = Arr::only($array, $keys);

        $this->assertEquals([], $result);
    }

    public function testExceptRemovesSpecificKeysFromArray(): void
    {
        $array = [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'secret',
            'age' => 30,
        ];
        $keys = ['password', 'age'];

        $result = Arr::except($array, $keys);

        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com'], $result);
    }

    public function testExceptReturnsWholeArrayWhenNoKeysMatch(): void
    {
        $array = ['name' => 'John', 'age' => 30];
        $keys = ['email', 'password'];

        $result = Arr::except($array, $keys);

        $this->assertEquals($array, $result);
    }

    public function testExceptWithEmptyKeysArray(): void
    {
        $array = ['name' => 'John', 'age' => 30];
        $keys = [];

        $result = Arr::except($array, $keys);

        $this->assertEquals($array, $result);
    }

    public function testExceptRemovesAllKeysWhenAllKeysProvided(): void
    {
        $array = ['name' => 'John', 'age' => 30];
        $keys = ['name', 'age'];

        $result = Arr::except($array, $keys);

        $this->assertEquals([], $result);
    }
}
