<?php

namespace App\Tests\Unit;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * O fuso da aplicação é fixado no Kernel::boot(), qualquer que seja o date.timezone do PHP
 * (em produção, o PHP de linha de comando roda em UTC).
 */
final class KernelTimezoneTest extends KernelTestCase
{
    public function testBootSetsBrasiliaTimezoneEvenWhenPhpIsInUtc(): void
    {
        date_default_timezone_set('UTC');

        self::bootKernel();

        self::assertSame('America/Sao_Paulo', Kernel::TIMEZONE);
        self::assertSame('America/Sao_Paulo', date_default_timezone_get());
        self::assertSame('-03:00', (new \DateTimeImmutable('2026-11-10 00:00:00'))->format('P'));
    }
}
