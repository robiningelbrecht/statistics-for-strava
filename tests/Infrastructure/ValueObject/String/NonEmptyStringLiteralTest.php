<?php

namespace App\Tests\Infrastructure\ValueObject\String;

use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class NonEmptyStringLiteralTest extends TestCase
{
    use MatchesSnapshots;

    public function testJsonSerialize(): void
    {
        $this->assertMatchesJsonSnapshot(
            Json::encode(TestNonEmptyStringLiteral::fromString('a'))
        );
    }

    public function testFromOptionalString(): void
    {
        self::assertNull(TestNonEmptyStringLiteral::fromOptionalString(null));
        self::assertEquals(
            'test',
            (string) TestNonEmptyStringLiteral::fromOptionalString('test')
        );
    }

    public function testToString(): void
    {
        static::assertEquals('a', (string) TestNonEmptyStringLiteral::fromString('a'));
    }

    public function testItShouldThrowWhenEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('App\Tests\Infrastructure\ValueObject\String\TestNonEmptyStringLiteral can not be empty');

        TestNonEmptyStringLiteral::fromString('');
    }

    public function testCamelCase(): void
    {
        static::assertEquals('helloWorld', TestNonEmptyStringLiteral::fromString('hello world')->camelCase());
        static::assertEquals('helloWorld', TestNonEmptyStringLiteral::fromString(' Hello   World ')->camelCase());
        static::assertEquals('helloWorld', TestNonEmptyStringLiteral::fromString('hello-world')->camelCase());
        static::assertEquals('helloWorld', TestNonEmptyStringLiteral::fromString('hello_world')->camelCase());
        static::assertEquals('helloWorld', TestNonEmptyStringLiteral::fromString('helloWorld')->camelCase());
        static::assertEquals('leadingAndTrailing', TestNonEmptyStringLiteral::fromString('  leading and trailing  ')->camelCase());
        static::assertEquals('multipleSpaces', TestNonEmptyStringLiteral::fromString('multiple   spaces')->camelCase());
        static::assertEquals('specialCharacters', TestNonEmptyStringLiteral::fromString('special@#characters!')->camelCase());
        static::assertEquals('123numbers456', TestNonEmptyStringLiteral::fromString('123numbers456')->camelCase());
        static::assertEquals('mixedCASEStringExample', TestNonEmptyStringLiteral::fromString('mixed-CASE_string Example')->camelCase());
    }

    public function testStudlyCase(): void
    {
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('hello world')->studlyCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString(' Hello   World ')->studlyCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('hello-world')->studlyCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('hello_world')->studlyCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('helloWorld')->studlyCase());
        static::assertEquals('LeadingAndTrailing', TestNonEmptyStringLiteral::fromString('  leading and trailing  ')->studlyCase());
        static::assertEquals('MultipleSpaces', TestNonEmptyStringLiteral::fromString('multiple   spaces')->studlyCase());
        static::assertEquals('SpecialCharacters', TestNonEmptyStringLiteral::fromString('special@#characters!')->studlyCase());
        static::assertEquals('123numbers456', TestNonEmptyStringLiteral::fromString('123numbers456')->studlyCase());
        static::assertEquals('MixedCASEStringExample', TestNonEmptyStringLiteral::fromString('mixed-CASE_string Example')->studlyCase());
    }

    public function testPascalCase(): void
    {
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('hello world')->pascalCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString(' Hello   World ')->pascalCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('hello-world')->pascalCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('hello_world')->pascalCase());
        static::assertEquals('HelloWorld', TestNonEmptyStringLiteral::fromString('helloWorld')->pascalCase());
        static::assertEquals('LeadingAndTrailing', TestNonEmptyStringLiteral::fromString('  leading and trailing  ')->pascalCase());
        static::assertEquals('MultipleSpaces', TestNonEmptyStringLiteral::fromString('multiple   spaces')->pascalCase());
        static::assertEquals('SpecialCharacters', TestNonEmptyStringLiteral::fromString('special@#characters!')->pascalCase());
        static::assertEquals('123numbers456', TestNonEmptyStringLiteral::fromString('123numbers456')->pascalCase());
        static::assertEquals('MixedCASEStringExample', TestNonEmptyStringLiteral::fromString('mixed-CASE_string Example')->pascalCase());
    }

    public function testSnakeCase(): void
    {
        static::assertEquals('hello_world', TestNonEmptyStringLiteral::fromString('hello world')->snakeCase());
        static::assertEquals('hello_world', TestNonEmptyStringLiteral::fromString(' Hello   World ')->snakeCase());
        static::assertEquals('hello_world', TestNonEmptyStringLiteral::fromString('hello-world')->snakeCase());
        static::assertEquals('hello_world', TestNonEmptyStringLiteral::fromString('hello_world')->snakeCase());
        static::assertEquals('leading_and_trailing', TestNonEmptyStringLiteral::fromString('  leading and trailing  ')->snakeCase());
        static::assertEquals('multiple_spaces', TestNonEmptyStringLiteral::fromString('multiple   spaces')->snakeCase());
        static::assertEquals('special_characters', TestNonEmptyStringLiteral::fromString('special@#characters!')->snakeCase());
        static::assertEquals('123numbers456', TestNonEmptyStringLiteral::fromString('123numbers456')->snakeCase());
        static::assertEquals('mixed_case_string_example', TestNonEmptyStringLiteral::fromString('mixed-CASE_string Example')->snakeCase());
    }

    public function testKebabCase(): void
    {
        static::assertEquals('hello-world', TestNonEmptyStringLiteral::fromString('hello world')->kebabCase());
        static::assertEquals('hello-world', TestNonEmptyStringLiteral::fromString(' Hello   World ')->kebabCase());
        static::assertEquals('hello-world', TestNonEmptyStringLiteral::fromString('hello-world')->kebabCase());
        static::assertEquals('hello-world', TestNonEmptyStringLiteral::fromString('hello_world')->kebabCase());
        static::assertEquals('leading-and-trailing', TestNonEmptyStringLiteral::fromString('  leading and trailing  ')->kebabCase());
        static::assertEquals('multiple-spaces', TestNonEmptyStringLiteral::fromString('multiple   spaces')->kebabCase());
        static::assertEquals('special-characters', TestNonEmptyStringLiteral::fromString('special@#characters!')->kebabCase());
        static::assertEquals('123numbers456', TestNonEmptyStringLiteral::fromString('123numbers456')->kebabCase());
        static::assertEquals('mixed-case-string-example', TestNonEmptyStringLiteral::fromString('mixed-CASE_string Example')->kebabCase());
    }
}
