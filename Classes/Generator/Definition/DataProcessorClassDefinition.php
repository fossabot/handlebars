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

namespace Fr\Typo3Handlebars\Generator\Definition;

use Fr\Typo3Handlebars\DataProcessing\AbstractDataProcessor;
use Laminas\Code\Generator\DocBlock\Tag\GenericTag;
use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\MethodGenerator;

/**
 * DataProcessorClassDefinition
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
final class DataProcessorClassDefinition extends AbstractClassDefinition
{
    public function build(string $name, array $options = []): array
    {
        $providerClassName = $options['providerClassName'];
        $presenterClassName = $options['presenterClassName'];

        return [
            'extendedclass' => AbstractDataProcessor::class,
            'methods' => [
                $this->buildMethodGenerator($name),
            ],
            'docblock' => DocBlockGenerator::fromArray([
                'shortdescription' => sprintf('Handlebars processor "%s".', $name),
                'longdescription' => 'This class is auto-generated by EXT:handlebars.',
                'tags' => [
                    new GenericTag('see', $this->decorateGeneratorMethod()),
                    new PropertyTag('provider', [$providerClassName]),
                    new PropertyTag('presenter', [$presenterClassName]),
                ],
            ])->setWordWrap(false),
        ];
    }

    private function buildMethodGenerator(string $name): MethodGenerator
    {
        $methodGenerator = $this->getMethodGeneratorFromReflection(AbstractDataProcessor::class, 'render');
        $methodGenerator->removeDocBlock();

        // Set method body
        $methodGenerator->setBody(
            <<<BODY
// TODO: Implement Handlebars processor "$name".

\$data = \$this->provider->get(\$this->cObj->data);
return \$this->presenter->present(\$data);
BODY
        );

        return $methodGenerator;
    }
}
