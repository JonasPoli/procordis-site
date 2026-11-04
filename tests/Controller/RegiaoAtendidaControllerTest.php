<?php

namespace App\Tests\Controller;

use App\Tests\Support\AppWebTestCase;

/**
 * Página "Região atendida" e a chamada "Área atendida" da home
 * (dados em public/data/regiao-atendida, gerados por bin/regiao-atendida/gerar_dados.py).
 */
final class RegiaoAtendidaControllerTest extends AppWebTestCase
{
    public function testPaginaMostraMapasIndicadoresETabela(): void
    {
        $crawler = $this->client->request('GET', '/regiao-atendida');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Região atendida');
        foreach (['#ra-mapa-estado', '#ra-mapa-drs3', '#ra-mapa-densidade', '#ra-piramide', '#ra-comparativo'] as $id) {
            $this->assertSelectorExists($id);
        }
        $this->assertSelectorTextContains('#no-estado h2', 'Região atendida no estado de São Paulo');
        $this->assertSelectorTextContains('#densidade h2', 'Densidade populacional');
        $this->assertSelectorTextContains('.ra-numeros', '992.306');

        // Tabela: os 24 municípios, do mais populoso para o menos populoso.
        $linhas = $crawler->filter('#ra-tabela tbody tr');
        $this->assertCount(24, $linhas);
        $this->assertSame('São Carlos', trim($linhas->first()->filter('th')->text()));
        $this->assertSame('Trabiju', trim($linhas->last()->filter('th')->text()));

        // Diretório das unidades de origem em Araraquara (37 de atenção primária + 7 especializadas).
        $this->assertCount(44, $crawler->filter('.ra-diretorio li'));
        $this->assertSelectorTextContains('.ra-diretorio', 'CMSC Jardim Paulistano Dr. Genaro Granata');

        // Dados embutidos para o JavaScript e caminhos das geometrias.
        $dados = json_decode($crawler->filter('#ra-dados')->text(), true);
        $this->assertSame(24, $dados['total']['municipios']);
        $this->assertCount(3, $dados['regioes']);
        $this->assertStringEndsWith('/data/regiao-atendida/drs3.json', $crawler->filter('#ra-app')->attr('data-drs3'));
    }

    public function testHomeTemChamadaParaARegiaoAtendida(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#area-atendida h2', '24 municípios');
        $this->assertSelectorExists('#area-atendida svg.ra-svg path[data-regiao="central"]');
        $this->assertCount(24, $crawler->filter('#area-atendida .ra-svg-municipios path'));
        $this->assertSame('/regiao-atendida', $crawler->filter('#area-atendida a.ra-home-botao')->attr('href'));
    }

    public function testRodapeLevaARegiaoAtendida(): void
    {
        $crawler = $this->client->request('GET', '/regiao-atendida');

        $this->assertGreaterThan(0, $crawler->filter('footer a[href="/regiao-atendida"]')->count());
    }
}
