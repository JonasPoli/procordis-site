<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Lê a API pública de atendimentos realizados do procordis-painel (só totais agregados, sem dados de pacientes).
 *
 * Cache de 15 minutos; a última resposta boa fica guardada por 7 dias e é servida se o painel estiver fora do ar,
 * para a página de transparência nunca quebrar.
 */
class AtendimentosPainelClient
{
    public const AGRUPAMENTOS = ['dia', 'mes', 'ano'];
    private const TTL = 900;
    private const TTL_RESERVA = 604800;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger,
        #[Autowire('%env(PAINEL_ATENDIMENTOS_API)%')] private string $baseUrl,
    ) {
    }

    /** Série para os gráficos de linha. */
    public function serie(string $agrupamento = 'mes', ?string $de = null, ?string $ate = null): ?array
    {
        if (!in_array($agrupamento, self::AGRUPAMENTOS, true)) {
            throw new \InvalidArgumentException('Agrupamento inválido.');
        }

        return $this->buscar('/serie', array_filter(['agrupamento' => $agrupamento, 'de' => $de, 'ate' => $ate]));
    }

    /** Totais por categoria em todo o histórico. */
    public function resumo(): ?array
    {
        return $this->buscar('/resumo', []);
    }

    private function buscar(string $caminho, array $query): ?array
    {
        ksort($query);
        $chave = 'atendimentos_' . md5($caminho . '?' . http_build_query($query));
        $item = $this->cache->getItem($chave);
        if ($item->isHit()) {
            return $item->get();
        }

        $reserva = $this->cache->getItem($chave . '_reserva');
        try {
            $resposta = $this->httpClient->request('GET', rtrim($this->baseUrl, '/') . $caminho, [
                'query' => $query,
                'timeout' => 15,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $dados = $resposta->toArray();
        } catch (\Throwable $e) {
            $this->logger->warning('Painel de atendimentos indisponível: ' . $e->getMessage(), ['caminho' => $caminho, 'query' => $query]);
            if ($reserva->isHit()) {
                return $reserva->get() + ['desatualizado' => true];
            }

            return null;
        }

        $this->cache->save($item->set($dados)->expiresAfter(self::TTL));
        $this->cache->save($reserva->set($dados)->expiresAfter(self::TTL_RESERVA));

        return $dados;
    }
}
