<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "handlebars_components".
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

namespace Fr\Typo3Handlebars\Event;

use Fr\Typo3Handlebars\Renderer\HandlebarsRenderer;

/**
 * BeforeRenderingEvent
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
class BeforeRenderingEvent
{
    /**
     * @var string
     */
    private $templatePath;

    /**
     * @var array<mixed, mixed>
     */
    private $data;

    /**
     * @var HandlebarsRenderer
     */
    private $renderer;

    /**
     * @param string $templatePath
     * @param array<mixed, mixed> $data
     * @param HandlebarsRenderer $renderer
     */
    public function __construct(string $templatePath, array $data, HandlebarsRenderer $renderer)
    {
        $this->templatePath = $templatePath;
        $this->data = $data;
        $this->renderer = $renderer;
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    /**
     * @return array<mixed, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<mixed, mixed> $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getRenderer(): HandlebarsRenderer
    {
        return $this->renderer;
    }
}
