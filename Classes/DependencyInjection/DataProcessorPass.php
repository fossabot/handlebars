<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "handlebars".
 *
 * Copyright (C) 2020 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace Fr\Typo3Handlebars\DependencyInjection;

use Fr\Typo3Handlebars\DataProcessing\DataProcessorInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * DataProcessorPass
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @codeCoverageIgnore
 */
final class DataProcessorPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DataProcessorInterface::class)->addTag($this->tagName);

        foreach ($container->findTaggedServiceIds($this->tagName) as $id => $tags) {
            $service = $container->findDefinition($id);
            $service->setPublic(true);

            // Autowire related presenters and providers
            $processingBridge = new ProcessingBridge($id, $service);
            if (!$processingBridge->hasMethodCall('setPresenter')) {
                $presenterService = $processingBridge->getPresenter();
                $service->addMethodCall('setPresenter', [$presenterService]);
            }
            if (!$processingBridge->hasMethodCall('setProvider')) {
                $providerService = $processingBridge->getProvider();
                $service->addMethodCall('setProvider', [$providerService]);
            }
        }
    }
}