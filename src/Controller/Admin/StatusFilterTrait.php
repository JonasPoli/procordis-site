<?php

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filtro "Todos / Ativos / Inativos" das listagens do painel (?status=ativos|inativos).
 */
trait StatusFilterTrait
{
    /**
     * @return array{0: ?bool, 1: string} [filtro para o repositório, valor normalizado para o template]
     */
    private function resolveStatusFilter(Request $request): array
    {
        return match ($request->query->getString('status')) {
            'ativos' => [true, 'ativos'],
            'inativos' => [false, 'inativos'],
            default => [null, 'todos'],
        };
    }
}
