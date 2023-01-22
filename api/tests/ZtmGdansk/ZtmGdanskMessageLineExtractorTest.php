<?php

namespace App\Tests\ZtmGdansk;

use App\Dto\Message;
use App\Provider\ZtmGdansk\ZtmGdanskMessageLineExtractor;
use PHPUnit\Framework\TestCase;

use function App\Functions\setup;

class ZtmGdanskMessageLineExtractorTest extends TestCase
{
    /**
     * @dataProvider messageProvider
     * @testdox $message yields $expected lines.
     */
    public function testExtractsLinesFromMessage(string $message, $expected)
    {
        $lineExtractor    = new ZtmGdanskMessageLineExtractor();
        $messageToExtract = $this->createMessageForExtraction($message);

        $this->assertEqualsCanonicalizing(
            iterator_to_array($lineExtractor->extractLinesFromMessage($messageToExtract)),
            $expected
        );
    }

    private function createMessageForExtraction(string $message)
    {
        return setup(new Message(), function (Message $object) use ($message) {
            $object->setType(Message::TYPE_INFO);
            $object->setMessage($message);
        });
    }

    public static function messageProvider(): \Generator
    {
        yield [
            'Od 23.01. zmiany na liniach: 120, 126, 157, N3. Szczegóły: ztm.gda.pl ',
            ['120', '126', '157', 'N3'],
        ];

        yield [
            'Od 16.01 do 27.01, w związku z zimową przerwą w nauce szkolnej, autobusy linii: 126, 127, 199 i 269 jeżdżą wg feryjnych powszednich rozkładów jazdy.',
            ['126', '127', '199', '269'],
        ];

        yield [
            'Awaria tramwaju linii nr 9 w kierunku Srzyża KSM przy przystanku Opera Bałtycka, opóźnienia na liniach: 6, 9, 12.',
            ['6', '9', '12'],
        ];
    }
}
