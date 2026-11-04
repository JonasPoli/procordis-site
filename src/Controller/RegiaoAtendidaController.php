<?php

namespace App\Controller;

use App\Service\RegiaoAtendida;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Página "Região atendida": mapas e indicadores dos 24 municípios do DRS III – Araraquara
 * de onde vêm os pacientes encaminhados à Procordis.
 */
class RegiaoAtendidaController extends AbstractController
{
    #[Route('/regiao-atendida', name: 'app_regiao_atendida', methods: ['GET'])]
    public function index(RegiaoAtendida $regiao): Response
    {
        $dados = $regiao->dados();
        if ($dados === null) {
            throw $this->createNotFoundException('Dados da região atendida indisponíveis.');
        }

        // Tabela por município: maior população primeiro.
        $municipios = $dados['municipios'];
        usort($municipios, fn (array $a, array $b) => $b['populacao'] <=> $a['populacao']);

        $origem = ['primaria' => [], 'especializada' => []];
        foreach ($dados['unidadesOrigem'] as $unidade) {
            $origem[$unidade['tipo']][] = $unidade;
        }

        // Municípios com maior parcela de 60 anos ou mais (texto do perfil etário).
        $maisIdosos = $dados['municipios'];
        usort($maisIdosos, fn (array $a, array $b) => $b['pct60'] <=> $a['pct60']);

        return $this->render('regiao_atendida/index.html.twig', [
            'd' => $dados,
            'municipios' => $municipios,
            'origem' => $origem,
            'maisIdosos' => array_slice($maisIdosos, 0, 3),
        ]);
    }
}
