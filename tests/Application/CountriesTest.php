<?php

namespace App\Tests\Application;

use App\Application\Countries;
use App\Tests\ContainerTestCase;

class CountriesTest extends ContainerTestCase
{
    private Countries $countries;

    public function testFindCountryCodeByCountryName(): void
    {
        $this->assertEquals(
            'BE',
            $this->countries->findCountryCodeByCountryName('Belgium')
        );
        $this->assertNull($this->countries->findCountryCodeByCountryName('Robinstan'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->countries = $this->getContainer()->get(Countries::class);
    }
}
