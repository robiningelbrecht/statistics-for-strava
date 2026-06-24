<?php

namespace App\Tests\Infrastructure\Config;

use App\Infrastructure\Config\AdminAllowedIpAddresses;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminAllowedIpAddressesTest extends TestCase
{
    public function testContainsListedIpAddress(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('192.168.1.1,10.0.0.1');

        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('10.0.0.1'));
        $this->assertFalse($allowed->contains('10.0.0.2'));
        $this->assertFalse(AdminAllowedIpAddresses::fromString('192.168.1.1')->contains(null));
    }

    public function testTrimsWhitespaceAroundEntries(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('  192.168.1.1 ,  10.0.0.1  ');

        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('10.0.0.1'));
    }

    public function testFiltersOutEmptyEntries(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('192.168.1.1,,, ,10.0.0.1,');

        $this->assertFalse($allowed->isEmpty());
        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('10.0.0.1'));
    }

    public function testSupportsIpv6(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('2001:db8::1');

        $this->assertTrue($allowed->contains('2001:db8::1'));
        $this->assertFalse($allowed->contains('2001:db8::2'));
    }

    public function testMatchesIpv6AddressesWithinACidrRange(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('2a02:a03f:e02e:c301::/64');

        // Different devices on the same /64 (e.g. laptop and phone on the same home network).
        $this->assertTrue($allowed->contains('2a02:a03f:e02e:c301:d94e:9dce:b47e:2a47'));
        $this->assertTrue($allowed->contains('2a02:a03f:e02e:c301:1111:2222:3333:4444'));
        // A different /64 prefix is not trusted.ip d
        $this->assertFalse($allowed->contains('2a02:a03f:e02e:c302:d94e:9dce:b47e:2a47'));
    }

    public function testMatchesIpv4AddressesWithinACidrRange(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('192.168.1.0/24');

        $this->assertTrue($allowed->contains('192.168.1.1'));
        $this->assertTrue($allowed->contains('192.168.1.254'));
        $this->assertFalse($allowed->contains('192.168.2.1'));
    }

    public function testMixesPlainAddressesAndCidrRanges(): void
    {
        $allowed = AdminAllowedIpAddresses::fromString('10.0.0.5, 192.168.1.0/24');

        $this->assertTrue($allowed->contains('10.0.0.5'));
        $this->assertTrue($allowed->contains('192.168.1.42'));
        $this->assertFalse($allowed->contains('10.0.0.6'));
    }

    public function testIsNotEmptyWhenPopulated(): void
    {
        $this->assertFalse(AdminAllowedIpAddresses::fromString('192.168.1.1')->isEmpty());
    }

    #[DataProvider('provideEmptyStrings')]
    public function testIsEmpty(string $string): void
    {
        $allowed = AdminAllowedIpAddresses::fromString($string);

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

        AdminAllowedIpAddresses::fromString($string);
    }

    public static function provideInvalidIpAddresses(): iterable
    {
        yield 'not an ip' => ['not-an-ip', 'not-an-ip'];
        yield 'incomplete ipv4' => ['192.168.1', '192.168.1'];
        yield 'octet out of range' => ['999.999.999.999', '999.999.999.999'];
        yield 'invalid among valid' => ['192.168.1.1,nope', 'nope'];
        yield 'cidr with invalid address' => ['nope/24', 'nope/24'];
        yield 'cidr without prefix' => ['192.168.1.0/', '192.168.1.0/'];
        yield 'cidr with non-numeric prefix' => ['192.168.1.0/ab', '192.168.1.0/ab'];
        yield 'ipv4 prefix out of range' => ['192.168.1.0/33', '192.168.1.0/33'];
        yield 'ipv6 prefix out of range' => ['2a02:a03f:e02e:c301::/129', '2a02:a03f:e02e:c301::/129'];
    }
}
