<?php

namespace App\Tests\Unit\Service\Gallery;

use App\Service\Gallery\YouTubeUrlParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class YouTubeUrlParserTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function validUrls(): iterable
    {
        $id = 'dQw4w9WgXcQ';

        yield 'watch' => ['https://www.youtube.com/watch?v='.$id, $id];
        yield 'watch sem www' => ['https://youtube.com/watch?v='.$id, $id];
        yield 'watch sem protocolo' => ['youtube.com/watch?v='.$id, $id];
        yield 'watch sem protocolo com www' => ['www.youtube.com/watch?v='.$id, $id];
        yield 'watch http' => ['http://www.youtube.com/watch?v='.$id, $id];
        yield 'watch com tempo' => ['https://www.youtube.com/watch?v='.$id.'&t=42s', $id];
        yield 'watch com playlist' => ['https://www.youtube.com/watch?v='.$id.'&list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG&index=3', $id];
        yield 'watch com v depois de outro parâmetro' => ['https://www.youtube.com/watch?feature=share&v='.$id, $id];
        yield 'watch com barra final' => ['https://www.youtube.com/watch/?v='.$id, $id];
        yield 'watch com âncora' => ['https://www.youtube.com/watch?v='.$id.'#t=30', $id];
        yield 'mobile' => ['https://m.youtube.com/watch?v='.$id, $id];
        yield 'mobile com parâmetros' => ['https://m.youtube.com/watch?v='.$id.'&feature=youtu.be', $id];
        yield 'music' => ['https://music.youtube.com/watch?v='.$id.'&si=abc', $id];
        yield 'youtu.be' => ['https://youtu.be/'.$id, $id];
        yield 'youtu.be com tempo' => ['https://youtu.be/'.$id.'?t=10', $id];
        yield 'youtu.be com si' => ['https://youtu.be/'.$id.'?si=Ab_Cd-123', $id];
        yield 'youtu.be sem protocolo' => ['youtu.be/'.$id, $id];
        yield 'shorts' => ['https://www.youtube.com/shorts/'.$id, $id];
        yield 'shorts com parâmetros' => ['https://youtube.com/shorts/'.$id.'?si=xyz&feature=share', $id];
        yield 'embed' => ['https://www.youtube.com/embed/'.$id, $id];
        yield 'embed com início' => ['https://www.youtube.com/embed/'.$id.'?start=30&autoplay=1', $id];
        yield 'nocookie embed' => ['https://www.youtube-nocookie.com/embed/'.$id, $id];
        yield 'live' => ['https://www.youtube.com/live/'.$id, $id];
        yield 'live com feature' => ['https://www.youtube.com/live/'.$id.'?feature=share', $id];
        yield 'v antigo' => ['https://www.youtube.com/v/'.$id, $id];
        yield 'maiúsculas no domínio' => ['HTTPS://WWW.YOUTUBE.COM/watch?v='.$id, $id];
        yield 'espaços em volta' => ['   https://youtu.be/'.$id."  \n", $id];
        yield 'id com hífen e sublinhado' => ['https://youtu.be/a-B_c1D2e3F', 'a-B_c1D2e3F'];
    }

    #[DataProvider('validUrls')]
    public function testExtractsVideoIdFromSupportedFormats(string $url, string $expectedId): void
    {
        self::assertSame($expectedId, YouTubeUrlParser::extractId($url));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidUrls(): iterable
    {
        yield 'vazio' => [''];
        yield 'texto qualquer' => ['não é um link'];
        yield 'somente o id' => ['dQw4w9WgXcQ'];
        yield 'outro domínio' => ['https://vimeo.com/123456789'];
        yield 'domínio parecido' => ['https://notyoutube.com/watch?v=dQw4w9WgXcQ'];
        yield 'subdomínio falso' => ['https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'];
        yield 'id curto' => ['https://www.youtube.com/watch?v=abc123'];
        yield 'id longo' => ['https://youtu.be/dQw4w9WgXcQXYZ'];
        yield 'id com caractere inválido' => ['https://youtu.be/dQw4w9WgX$Q'];
        yield 'canal' => ['https://www.youtube.com/@procordis'];
        yield 'playlist sem vídeo' => ['https://www.youtube.com/playlist?list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG'];
        yield 'watch sem v' => ['https://www.youtube.com/watch?list=PLx0sYbCqOb8TBPRdmBHs5Iftvv9TPboYG'];
        yield 'protocolo não http' => ['javascript://youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'dois links na mesma linha' => ['https://youtu.be/dQw4w9WgXcQ https://youtu.be/a-B_c1D2e3F'];
    }

    #[DataProvider('invalidUrls')]
    public function testRejectsInvalidInput(string $input): void
    {
        self::assertNull(YouTubeUrlParser::extractId($input));
    }

    public function testParsesSeveralLinksOnePerLineIgnoringDuplicates(): void
    {
        $text = "https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1\n"
            ."https://youtu.be/a-B_c1D2e3F\n"
            ."\n"
            ."https://youtu.be/dQw4w9WgXcQ\n"
            ."link-invalido\n";

        self::assertSame([
            ['input' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1', 'videoId' => 'dQw4w9WgXcQ'],
            ['input' => 'https://youtu.be/a-B_c1D2e3F', 'videoId' => 'a-B_c1D2e3F'],
            ['input' => 'link-invalido', 'videoId' => null],
        ], YouTubeUrlParser::parseMany($text));
    }
}
