<?php

namespace App\Tests\Infrastructure\ValueObject\String;

use App\Infrastructure\ValueObject\String\AllowedIpAddresses;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AllowedIpAddressesTest extends TestCase
{
    public function testContainsListedIpAddress(): void
    {
        $allowed = AllowedIpAddresses::fromString('192.168.1.1,10.0.0.1');

        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('10.0.0.1'));
        $this->assertFalse($allowed->contains('10.0.0.2'));
        $this->assertFalse(AllowedIpAddresses::fromString('192.168.1.1')->contains(null));
    }

    public function testTrimsWhitespaceAroundEntries(): void
    {
        $allowed = AllowedIpAddresses::fromString('  192.168.1.1 ,  10.0.0.1  ');

        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('10.0.0.1'));
    }

    public function testFiltersOutEmptyEntries(): void
    {
        $allowed = AllowedIpAddresses::fromString('192.168.1.1,,, ,10.0.0.1,');

        $this->assertFalse($allowed->isEmpty());
        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('10.0.0.1'));
    }

    public function testSupportsIpv6(): void
    {
        $allowed = AllowedIpAddresses::fromString('2001:db8::1');

        $this->assertTrue($allowed->contains('2001:db8::1'));
        $this->assertFalse($allowed->contains('2001:db8::2'));
    }

    public function testIsNotEmptyWhenPopulated(): void
    {
        $this->assertFalse(AllowedIpAddresses::fromString('192.168.1.1')->isEmpty());
    }

    #[DataProvider('provideEmptyStrings')]
    public function testIsEmpty(string $string): void
    {
        $allowed = AllowedIpAddresses::fromString($string);

        $this->assertTrue($allowed->isEmpty());
        $this->assertFalse($allowed->contains('192.168.1.1'));
        $this->assertFalse($allowed->contains(null));
    }

    public static function provideEmptyStrings(): iterable
    {
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
        yield 'only commas' => [',,,'];
        yield 'commas and whitespace' => [' , , '];
    }

    #[DataProvider('provideInvalidIpAddresses')]
    public function testItShouldThrowOnInvalidIpAddress(string $string, string $invalidValue): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException(sprintf('"%s" is not a valid IP address', $invalidValue)));

        AllowedIpAddresses::fromString($string);
    }

    public static function provideInvalidIpAddresses(): iterable
    {
        yield 'not an ip' => ['not-an-ip', 'not-an-ip'];
        yield 'incomplete ipv4' => ['192.168.1', '192.168.1'];
        yield 'octet out of range' => ['999.999.999.999', '999.999.999.999'];
        yield 'invalid among valid' => ['192.168.1.1,nope', 'nope'];
    }
}
