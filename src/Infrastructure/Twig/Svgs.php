<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

class Svgs
{
    public function get(): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
SVG;
    }
}
