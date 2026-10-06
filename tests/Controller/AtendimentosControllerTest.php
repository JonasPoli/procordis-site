<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Página "Atendimentos realizados" e proxy dos dados do procordis-painel (API simulada).
 */
final class AtendimentosControllerTest extends AppWebTestCase
{
    private bool $painelFora = false;
    private array $requisicoes = [];

    protected function setUp(): void
    {
        parent::setUp();
        static::getContainer()->set('http_client', new MockHttpClient(fn (string $m, string $url) => $this->painel($url)));
        static::getContainer()->get('cache.app')->clear();
    }

    public function testDadosRepassaSerieDoPainel(): void
    {
        $this->client->request('GET', '/transparencia/atendimentos/dados?agrupamento=mes&de=2026-08-01&ate=2026-09-30');

        $this->assertResponseIsSuccessful();
        $d = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['2026-08', '2026-09'], $d['periodos']);
        $this->assertStringContainsString('/api/publico/atendimentos/serie?agrupamento=mes&ate=2026-09-30&de=2026-08-01', $this->requisicoes[0]);
    }

    public function testDadosUsaCache(): void
    {
        $this->client->request('GET', '/transparencia/atendimentos/dados?agrupamento=ano');
        $this->client->request('GET', '/transparencia/atendimentos/dados?agrupamento=ano');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $this->requisicoes);
    }

    public function testDadosServeReservaQuandoPainelCai(): void
    {
        $this->client->request('GET', '/transparencia/atendimentos/dados?agrupamento=mes');
        // Expira só o cache curto: a reserva continua guardada.
        $cache = static::getContainer()->get('cache.app');
        foreach (['/serie?agrupamento=mes'] as $c) {
            $cache->deleteItem('atendimentos_' . md5($c));
        }
        $this->painelFora = true;

        $this->client->request('GET', '/transparencia/atendimentos/dados?agrupamento=mes');
        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_decode($this->client->getResponse()->getContent(), true)['desatualizado']);
    }

    public function testDadosIndisponivelSemReserva(): void
    {
        $this->painelFora = true;
        $this->client->request('GET', '/transparencia/atendimentos/dados?agrupamento=dia');

        $this->assertResponseStatusCodeSame(503);
    }

    #[DataProvider('parametrosInvalidos')]
    public function testDadosParametrosInvalidos(string $query): void
    {
        $this->client->request('GET', '/transparencia/atendimentos/dados?' . $query);

        $this->assertResponseStatusCodeSame(400);
        $this->assertCount(0, $this->requisicoes);
    }

    public static function parametrosInvalidos(): iterable
    {
        yield 'agrupamento' => ['agrupamento=semana'];
        yield 'data' => ['agrupamento=mes&de=01/08/2026'];
        yield 'injeção' => ['agrupamento=mes&ate=2026-09-30%26x=1'];
    }

    public function testPaginaMostraTotais(): void
    {
        $crawler = $this->client->request('GET', '/transparencia/atendimentos');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Atendimentos realizados');
        $this->assertSame('1.234', $crawler->filter('[data-contador]')->text());
        $this->assertStringContainsString('Consultas', $crawler->filter('#atd-totais')->text());
        $this->assertStringNotContainsString('MAPA', $crawler->filter('#atd-totais')->text(), 'Tipos sem atendimento não aparecem');
        $this->assertSame('/transparencia/atendimentos/dados', $crawler->filter('#atd-app')->attr('data-endpoint'));
    }

    public function testPaginaFuncionaComPainelFora(): void
    {
        $this->painelFora = true;
        $this->client->request('GET', '/transparencia/atendimentos');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'estão sendo atualizados');
    }

    private function painel(string $url): MockResponse
    {
        $this->requisicoes[] = $url;
        if ($this->painelFora) {
            return new MockResponse('', ['http_code' => 502]);
        }
        if (str_contains($url, '/resumo')) {
            return new MockResponse(json_encode([
                'categorias' => [
                    ['slug' => 'consulta', 'nome' => 'Consultas', 'cor' => '#2a78d6', 'total' => 1000],
                    ['slug' => 'ecocardiograma', 'nome' => 'Ecocardiogramas', 'cor' => '#eb6834', 'total' => 234],
                    ['slug' => 'mapa', 'nome' => 'MAPA', 'cor' => '#008300', 'total' => 0],
                ],
                'total' => 1234,
                'historico' => ['primeiraData' => '2012-08-09', 'ultimaData' => '2026-10-05'],
                'atualizadoEm' => '2026-10-06 04:45:00',
            ]));
        }

        return new MockResponse(json_encode([
            'agrupamento' => 'mes', 'de' => '2026-08-01', 'ate' => '2026-09-30',
            'periodos' => ['2026-08', '2026-09'], 'rotulos' => ['ago/2026', 'set/2026'],
            'series' => [['slug' => 'consulta', 'nome' => 'Consultas', 'cor' => '#2a78d6', 'total' => 3, 'valores' => [1, 2]]],
            'total' => ['nome' => 'Total de atendimentos', 'total' => 3, 'valores' => [1, 2]],
            'atualizadoEm' => '2026-10-06 04:45:00',
        ]));
    }
}
