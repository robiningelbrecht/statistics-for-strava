<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use Twig\Attribute\AsTwigFunction;

final class SvgsTwigExtension
{
    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(
        private readonly KernelProjectDir $kernelProjectDir,
    ) {
    }

    #[AsTwigFunction('svg', isSafe: ['html'])]
    public function svg(string $name, ?string $size = null, ?string $iconColor = null): string
    {
        $svg = $this->load('icons', $name);

        if ($size) {
            $svg = preg_replace_callback('/class="([^"]+)"/', function (array $matches) use ($size): string {
                $classes = explode(' ', $matches[1]);
                $classes = array_filter($classes, fn (string $class): bool => !preg_match('/^(w-|h-|size-)/', $class));
                $classes[] = $size;

                return 'class="'.implode(' ', $classes).'"';
            }, $svg);
            assert(is_string($svg) && '' !== $svg);
        }

        if (null !== $iconColor) {
            $svg = preg_replace_callback('/class="([^"]+)"/', function (array $matches) use ($iconColor): string {
                $classes = explode(' ', $matches[1]);
                $classes = array_filter($classes, fn (string $class): bool => !preg_match('/^(text-|hover:text|fill-|stroke-)/', $class));
                $classes[] = $iconColor;

                return 'class="'.trim(implode(' ', $classes)).'"';
            }, $svg);
            assert(is_string($svg) && '' !== $svg);
        }

        return $svg;
    }

    #[AsTwigFunction('svgSportType', isSafe: ['html'])]
    public function svgSportType(SportType $sportType, ?string $classes = null): string
    {
        $classes ??= 'h-4 shrink-0';
        $filename = strtolower(str_replace('_', '-', $sportType->name));
        $svg = $this->load('sport-types', $filename);

        return str_replace('<svg ', '<svg class="'.$classes.'" ', $svg);
    }

    private function load(string $subdirectory, string $name): string
    {
        $key = $subdirectory.'/'.$name;

        if (!isset($this->cache[$key])) {
            $path = $this->kernelProjectDir.'/templates/svg/'.$subdirectory.'/'.$name.'.svg';

            if (!file_exists($path)) {
                throw new \RuntimeException('No svg icon found for "'.$name.'"');
            }

            $this->cache[$key] = trim((string) file_get_contents($path));
        }

        return $this->cache[$key];
    }
}
