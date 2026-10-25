<?php

namespace App\Provider\ZtmGdansk;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZtmGdanskDisplayToStopResolver
{
    private const string DISPLAY_CODES_PATH = '/ee910ad8-8ffa-4e24-8ef9-d5a335b07ccb/download/displays.json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        #[Autowire(env: 'ZTM_GDANSK_DATASET_URL')]
        private readonly string $datasetUrl,
    ) {
    }

    public function mapDisplayCodeToStops(string $displayCode)
    {
        $mapping = $this->getMapping();

        return $mapping[$displayCode] ?? [];
    }

    private function getMapping(): array
    {
        return $this->cache->get('ztm_gdansk_display_to_stop_map', function (ItemInterface $item) {
            $item->expiresAfter(new \DateInterval('P1D'));

            $response = $this->httpClient->request('GET', $this->datasetUrl . self::DISPLAY_CODES_PATH);
            $json     = json_decode($response->getContent(), true);

            $entries = array_map(fn (array $display) => [
                $display['displayCode'],
                array_filter([
                    $display['idStop1'],
                    $display['idStop2'],
                    $display['idStop3'],
                    $display['idStop4'],
                ], fn ($id) => $id !== 0),
            ], $json['displays']);

            return array_combine(
                array_column($entries, 0),
                array_column($entries, 1),
            );
        });
    }
}
