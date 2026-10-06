<?php

namespace App\Controller;

use App\Service\AtendimentosPainelClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Evolução dos atendimentos realizados (Portal da Transparência).
 * Os números vêm do procordis-painel (API Medware consolidada); o site só exibe.
 * Prioridade alta para não cair em /transparencia/{slug}.
 */
#[Route('/transparencia/atendimentos', priority: 10)]
class AtendimentosController extends AbstractController
{
    public function __construct(private AtendimentosPainelClient $painel)
    {
    }

    #[Route('', name: 'app_atendimentos', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('atendimentos/index.html.twig', [
            'resumo' => $this->painel->resumo(),
        ]);
    }

    /** Séries para os gráficos (proxy com cache da API do painel). */
    #[Route('/dados', name: 'app_atendimentos_dados', methods: ['GET'])]
    public function dados(Request $request): JsonResponse
    {
        $agrupamento = (string) $request->query->get('agrupamento', 'mes');
        $de = $request->query->get('de');
        $ate = $request->query->get('ate');
        if (!in_array($agrupamento, AtendimentosPainelClient::AGRUPAMENTOS, true)
            || ($de !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $de))
            || ($ate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate))) {
            return new JsonResponse(['erro' => 'Parâmetros inválidos.'], 400);
        }

        $serie = $this->painel->serie($agrupamento, $de, $ate);
        if ($serie === null) {
            return new JsonResponse(['erro' => 'Dados de atendimentos indisponíveis no momento.'], 503);
        }

        $resposta = new JsonResponse($serie);
        $resposta->setPublic();
        $resposta->setMaxAge(600);
        $resposta->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $resposta;
    }
}
