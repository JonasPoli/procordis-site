<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Fuso horário único da aplicação (site, painel, console e testes), independente do
     * date.timezone do PHP do servidor. As colunas DATETIME do banco não guardam fuso e valem
     * neste horário.
     */
    public const TIMEZONE = 'America/Sao_Paulo';

    public function boot(): void
    {
        date_default_timezone_set(self::TIMEZONE);

        parent::boot();
    }
}
