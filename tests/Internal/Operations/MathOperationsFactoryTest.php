<?php

namespace TinyBlocks\Math\Internal\Operations;

use TinyBlocks\Math\Internal\Exceptions\MathOperationsNotAvailable;
use TinyBlocks\Math\Mock\ExtensionAdapterMock;
use PHPUnit\Framework\TestCase;

final class MathOperationsFactoryTest extends TestCase
{
    public function testMathOperationsNotAvailable(): void
    {
        $template = 'There are no implementations available. Enable one of these <%s> extensions.';

        $this->expectException(MathOperationsNotAvailable::class);
        $this->expectExceptionMessage(sprintf($template, 'bcmath'));

        (new MathOperationsFactory(extension: new ExtensionAdapterMock()))->build();
    }
}
