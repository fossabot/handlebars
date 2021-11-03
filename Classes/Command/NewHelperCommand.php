<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "handlebars".
 *
 * Copyright (C) 2021 Elias Häußler <e.haeussler@familie-redlich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Fr\Typo3Handlebars\Command;

use Fr\Typo3Handlebars\Generator\Definition\HelperClassDefinition;
use Fr\Typo3Handlebars\Generator\HelperGenerator;
use Fr\Typo3Handlebars\Generator\Resolver\ClassResolver;
use Highlight\Decorator\StatefulCliDecorator;
use Highlight\Highlighter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * NewHelperCommand
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @property HelperGenerator $generator
 */
final class NewHelperCommand extends BaseFileGenerationCommand
{
    /**
     * @var ClassResolver
     */
    private $classResolver;

    public function __construct(
        HelperGenerator $generator,
        ClassResolver $classResolver,
        PackageManager $packageManager,
        FrontendInterface $diCache,
        string $name = null
    ) {
        parent::__construct($name);
        $this->generator = $generator;
        $this->classResolver = $classResolver;
        $this->packageManager = $packageManager;
        $this->diCache = $diCache;
        $this->highlighter = new Highlighter(new StatefulCliDecorator());
    }

    protected function configure(): void
    {
        $this->setDescription('Create a new Handlebars helper');
        $this->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Unique name of the new helper'
        );
        $this->addOption(
            'extension-key',
            'e',
            InputOption::VALUE_REQUIRED,
            'Extension key of the extension where to create the new helper'
        );
        $this->addOption(
            'class-name',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Optional class name to be used for the resulting PHP class (automatically generated by default)'
        );
        $this->addOption(
            'method-name',
            'm',
            InputOption::VALUE_OPTIONAL,
            'Optional method name to be used in the resulting PHP class',
            HelperClassDefinition::DEFAULT_METHOD_NAME
        );
        $this->addOption(
            'force-overwrite',
            null,
            InputOption::VALUE_NONE,
            'Force overwrite of existing files that need to be changed'
        );
        $this->addOption(
            'flush-cache',
            null,
            InputOption::VALUE_NONE,
            'Flush DI cache after successful file generation (only if Services.yaml is written)'
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $hasInteracted = false;
        $helper = $this->getHelper('question');

        // Ask for input argument "name"
        if (!$input->getArgument('name')) {
            $hasInteracted = true;
            $question = new Question('Name: ');
            $question->setValidator([$this, 'validateNameArgument']);
            $input->setArgument('name', $helper->ask($input, $output, $question));
        }

        // Ask for input option "extension-key"
        if (!$input->getOption('extension-key')) {
            $hasInteracted = true;
            $question = new Question('Extension key: ');
            $question->setValidator([$this, 'validateExtensionKeyOption']);
            $question->setAutocompleterValues($this->getAvailableExtensions());
            $input->setOption('extension-key', $helper->ask($input, $output, $question));
        }

        // Ask for input option "class-name"
        if (!$input->getOption('class-name')) {
            $hasInteracted = true;
            ['namespace' => $namespace, 'className' => $classNameProposal] = $this->generator->resolveClassParts(
                $input->getOption('extension-key'),
                $input->getArgument('name')
            );
            $question = new Question('Class name [<comment>' . $classNameProposal . '</comment>]: ');
            $question->setValidator(function ($value) use ($input, $namespace, $classNameProposal): string {
                $this->extensionKey = $input->getOption('extension-key');
                return $this->validateClassNameOption($value, $namespace)
                    ?? $this->validateClassNameOption($classNameProposal, $namespace);
            });
            $input->setOption('class-name', $helper->ask($input, $output, $question) ?: $classNameProposal);
        }

        // Ask for input option "method-name"
        if (!($methodName = $input->getOption('method-name')) || $methodName === HelperClassDefinition::DEFAULT_METHOD_NAME) {
            $hasInteracted = true;
            $methodNameProposal = HelperClassDefinition::DEFAULT_METHOD_NAME;
            $question = new Question('Method name [<comment>' . $methodNameProposal . '</comment>]: ');
            $question->setValidator([$this, 'validateMethodNameOption']);
            $input->setOption('method-name', $helper->ask($input, $output, $question) ?: $methodNameProposal);
        }

        // Ask for input option "force-overwrite"
        if ($hasInteracted && !$input->getOption('force-overwrite')) {
            $question = new ConfirmationQuestion('Overwrite existing files? [<info>no</info>] ', false);
            $input->setOption('force-overwrite', $helper->ask($input, $output, $question));
        }

        // Ask for input option "flush"
        if ($hasInteracted && !$input->getOption('flush-cache')) {
            $question = new ConfirmationQuestion('Flush DI cache afterwards? [<info>no</info>] ', false);
            $input->setOption('flush-cache', $helper->ask($input, $output, $question));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Resolve input options
        $name = $this->validateNameArgument($input->getArgument('name'));
        $this->extensionKey = $this->validateExtensionKeyOption($input->getOption('extension-key'));
        ['namespace' => $namespace] = $this->generator->resolveClassParts($this->extensionKey, $name);
        $className = $this->validateClassNameOption($input->getOption('class-name'), $namespace);
        $methodName = $this->validateMethodNameOption($input->getOption('method-name'));
        $forceOverwrite = $input->getOption('force-overwrite');
        $flushDiCache = $input->getOption('flush-cache');

        // Run Helper generation
        $generatorOptions = [
            'extensionKey' => $this->extensionKey,
            'className' => $className,
            'methodName' => $methodName,
        ];
        $result = $this->generator->generate($name, $generatorOptions, $forceOverwrite);
        $this->handleResult($result, $name, 'helper', $flushDiCache);

        return 0;
    }

    /**
     * @param mixed $value
     * @param string $namespace
     * @return string
     */
    public function validateClassNameOption($value, string $namespace): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new \RuntimeException(
                sprintf('The class name must be of type string, "%s" given..', gettype($value)),
                1622465793
            );
        }

        $sanitizedClassName = $this->classResolver->sanitizeNamespacePart($value);
        $fullClassName = $namespace . '\\' . $sanitizedClassName;

        try {
            $this->classResolver->locateClass($this->extensionKey, $fullClassName);
            return $sanitizedClassName;
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('The class name is not valid: %s', $e->getMessage()),
                1622466678,
                $e
            );
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function validateMethodNameOption($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) || '' === trim($value)) {
            throw new \RuntimeException('The method name cannot be empty.', 1622467549);
        }

        return $value;
    }
}