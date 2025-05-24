<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class RegisterYamlConfig implements CompilerPassInterface
{
    public function __construct(
        /** @var YamlConfigFile[] */
        private array $yamlFilesToProcess,
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->yamlFilesToProcess as $yamlFile) {
            if ($yamlFile->isRequired() && !file_exists($yamlFile->getFilePath())) {
                throw CouldNotParseYamlConfig::configFileNotFound();
            }

            if (!file_exists($yamlFile->getFilePath())) {
                continue;
            }

            try {
                $this->processYamlConfig(
                    container: $container,
                    ymlConfig: Yaml::parseFile($yamlFile->getFilePath()),
                    prefix: $yamlFile->getPrefix(),
                    needsNestedProcessing: $yamlFile->needsNestedProcessing()
                );
            } catch (ParseException $e) {
                throw CouldNotParseYamlConfig::invalidYml($e->getMessage());
            }
        }
    }

    /**
     * @param array<string, mixed> $ymlConfig
     */
    private function processYamlConfig(ContainerBuilder $container, array $ymlConfig, string $prefix, bool $needsNestedProcessing): void
    {
        if (!$needsNestedProcessing) {
            $container->setParameter($prefix, $ymlConfig);

            return;
        }

        foreach ($ymlConfig as $key => $value) {
            $fullKey = '' === $prefix ? $key : $prefix.'.'.$key;
            $container->setParameter($fullKey, $value);

            if (is_array($value)) {
                $this->processYamlConfig($container, $value, $fullKey, true);
            }
        }
    }
}
