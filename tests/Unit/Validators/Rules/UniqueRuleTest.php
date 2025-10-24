<?php

namespace Tests\Unit\Validators\Rules;

use App\Contracts\ExistsCheckerInterface;
use App\Validators\Rules\UniqueRule;
use PHPUnit\Framework\TestCase;

class UniqueRuleTest extends TestCase
{
    private ExistsCheckerInterface $existsChecker;
    private UniqueRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->existsChecker = $this->createMock(ExistsCheckerInterface::class);
        $this->rule = new UniqueRule($this->existsChecker);
    }

    public function testValidateReturnsTrueWhenValueIsUnique(): void
    {
        $this->existsChecker->method('exists')->willReturn(false);

        $isValid = $this->rule->validate('users', 'email', 'unique@example.com');

        $this->assertTrue($isValid);
    }

    public function testValidateReturnsFalseWhenValueIsNotUnique(): void
    {
        $this->existsChecker->method('exists')->willReturn(true);

        $isValid = $this->rule->validate('users', 'email', 'non-unique@example.com');

        $this->assertFalse($isValid);
    }

    public function testValidateCallsExistsCheckerWithCorrectParameters(): void
    {
        $this->existsChecker->expects($this->once())
            ->method('exists')
            ->with('posts', 'slug', 'my-awesome-post')
            ->willReturn(false);

        $this->rule->validate('posts', 'slug', 'my-awesome-post');
    }
}
