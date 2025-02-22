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

namespace Fr\Typo3Handlebars\Tests\Unit\Traits;

use Fr\Typo3Handlebars\Tests\Unit\Fixtures\Classes\Traits\DummyErrorHandlingTraitClass;
use Psr\Log\Test\TestLogger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * ErrorHandlingTraitTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
class ErrorHandlingTraitTest extends UnitTestCase
{
    /**
     * @var TestLogger
     */
    protected $logger;

    /**
     * @var DummyErrorHandlingTraitClass
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestLogger();
        $this->subject = new DummyErrorHandlingTraitClass();
        $this->subject->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function handleErrorLogsLogsCriticalError(): void
    {
        $exception = new \Exception();

        $this->subject->doHandleError($exception);
        self::assertTrue($this->logger->hasCriticalThatPasses(function ($logRecord) use ($exception) {
            $expectedMessage = 'Data processing for ' . get_class($this->subject) . ' failed.';
            static::assertSame($expectedMessage, $logRecord['message']);
            static::assertSame($exception, $logRecord['context']['exception']);
            return true;
        }));
    }
}
