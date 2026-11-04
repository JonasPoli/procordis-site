<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Indicadores da região atendida pela Procordis (24 municípios do DRS III – Araraquara).
 *
 * Os dados são estáticos e ficam em public/data/regiao-atendida/indicadores.json, gerado por
 * bin/regiao-atendida/gerar_dados.py a partir do mapa do DRS III (CNES/IBGE) e da API do IBGE.
 * O mesmo arquivo é lido pelo JavaScript da página.
 */
class RegiaoAtendida
{
    public const ARQUIVO = '/public/data/regiao-atendida/indicadores.json';

    private ?array $dados = null;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectDir)
    {
    }

    /** Todos os indicadores, ou null se o arquivo não existir ou estiver inválido. */
    public function dados(): ?array
    {
        if ($this->dados === null) {
            $conteudo = @file_get_contents($this->projectDir . self::ARQUIVO);
            $dados = $conteudo !== false ? json_decode($conteudo, true) : null;
            $this->dados = is_array($dados) ? $dados : [];
        }

        return $this->dados ?: null;
    }

    /** Números da chamada da home: totais e Regiões de Saúde. */
    public function resumo(): ?array
    {
        $dados = $this->dados();
        if ($dados === null) {
            return null;
        }

        return [
            'total' => $dados['total'],
            'regioes' => $dados['regioes'],
            'unidadesOrigem' => count($dados['unidadesOrigem'] ?? []),
        ];
    }
}
