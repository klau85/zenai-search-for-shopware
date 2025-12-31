<?php

declare(strict_types=1);

namespace Zenai\ZenaiSearchPlugin\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZenaiAPIClient
{
    private const API_BASE_URL = 'https://zenaisoftware.com';
    private const RECOMMENDATIONS_ENDPOINT = '/api/recommendations';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SystemConfigService $systemConfig,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Fetches recommended product IDs for the provided free-form search query.
     * Returns an empty array when the query is empty, the token is missing, or the request fails.
     *
     * @return list<string>
     */
    public function fetchProductIds(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $token = $this->systemConfig->get('ZenaiSearchPlugin.config.apiToken');
        if (!is_string($token) || $token === '') {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . self::RECOMMENDATIONS_ENDPOINT, [
                'query' => ['q' => $query],
                'headers' => $this->getHeaders($token),
                'timeout' => 5.0,
            ]);

            $payload = $response->toArray(false);
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | DecodingExceptionInterface $exception) {
            $this->logger->warning('Zenai recommendation request failed', ['exception' => $exception]);

            return [];
        } catch (\Throwable $exception) {
            $this->logger->warning('Unexpected error while requesting Zenai recommendations', ['exception' => $exception]);

            return [];
        }

        if (!isset($payload['results']) || !is_array($payload['results'])) {
            return [];
        }

        $productIds = [];
        foreach ($payload['results'] as $result) {
            if (!is_array($result)) {
                continue;
            }

            $productId = $result['product_id'] ?? null;
            if (is_string($productId) && $productId !== '') {
                $productIds[] = $productId;
            }
        }

        return array_values(array_unique($productIds));
    }

    private function getHeaders(string $token): array
    {
        return [
            'x-token' => $token,
            'Accept' => 'application/json',
        ];
    }
}
