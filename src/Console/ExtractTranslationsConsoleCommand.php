<?php

declare(strict_types=1);

namespace App\Console;

use App\Infrastructure\Localisation\Locale;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:translations:extract', description: 'Extract translations for all locales')]
class ExtractTranslationsConsoleCommand extends Command
{
    public function __construct(
        private readonly ExtractorInterface $extractor,
        private readonly KernelProjectDir $kernelProjectDir
    ){
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->addOption('removeObsoleteTranslatables', null, InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (Locale::cases() as $locale) {
            $process = new Process(
                command: [
                    '/var/www/bin/console',
                    'translation:extract',
                    '--force',
                    '--prefix=',
                    '--domain=messages',
                    '--format=yaml',
                    '--sort=ASC',
                    '--domain=messages',
                    $locale->value,
                ],
                timeout: null,
            );

            $process->run();
            $output->writeln(sprintf('<info>Extracted translations for "%s"</info>', $locale->value));
        }

        if($input->getOption('removeObsoleteTranslatables')){
            $messages = new MessageCatalogue(Locale::en_US->value);

            $this->extractor->extract($this->kernelProjectDir.'/templates', $messages);
            $this->extractor->extract($this->kernelProjectDir.'/src', $messages);
            $translatables = $messages->all()['messages'];

            $translatableKeys = array_keys($translatables ?? []);

            $translationFilePath = sprintf('%s/translations/messages%s.%s.yaml', $this->kernelProjectDir, MessageCatalogue::INTL_DOMAIN_SUFFIX, Locale::en_US->value);
            $parsedTranslations = Yaml::parse(file_get_contents($translationFilePath));
            $translationKeysToRemove = array_diff(array_keys($parsedTranslations), $translatableKeys);
        }

        return Command::SUCCESS;
    }
}
