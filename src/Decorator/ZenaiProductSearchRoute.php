<?php

declare(strict_types=1);

namespace Zenai\ZenaiSearchPlugin\Decorator;

use Shopware\Core\Content\Product\SalesChannel\Search\AbstractProductSearchRoute;
use Shopware\Core\Content\Product\SalesChannel\Search\ProductSearchRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Zenai\ZenaiSearchPlugin\Service\ZenaiAPIClient;

/**
 * Decorates the default product search. When the zenai flag is present in the request
 * (e.g. zenai=1), we override the default behavior.
 */
class ZenaiProductSearchRoute extends AbstractProductSearchRoute
{
    public const ZENAI_FLAG = 'zenai';

    public function __construct(
        private readonly AbstractProductSearchRoute $decorated,
        private readonly ZenaiAPIClient $recommendationClient,
    ) {
    }

    public function getDecorated(): AbstractProductSearchRoute
    {
        return $this->decorated;
    }

    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSearchRouteResponse
    {
        $isZenaiSearch = $this->isZenaiSearch($request);
        $originalSearch = $request->get('search');
        $hadQuery = false;
        $hadRequest = false;
        $overrideSearch = false;
        $recommendedIds = [];

        if ($isZenaiSearch) {
            $searchString = is_string($originalSearch) ? $originalSearch : '';
            $recommendedIds = $this->recommendationClient->fetchProductIds($searchString);

            if ($recommendedIds !== []) {
                $criteria->setTitle('zenai-overridden-search');
                $criteria->setIds($recommendedIds);
                $overrideSearch = true;

                // Save and temporarily remove 'search' so ProductSearchRoute won't build a search query
                $hadQuery = $request->query->has('search');
                $hadRequest = $request->request->has('search');

                if ($hadQuery) {
                    $request->query->remove('search');
                }
                if ($hadRequest) {
                    $request->request->remove('search');
                }
            }
        }

        // Execute the actual product search without the search term influencing the query
        $response = $this->decorated->load($request, $context, $criteria);

        if ($overrideSearch && $recommendedIds !== []) {
            $this->updateListing($response, $recommendedIds);
        }

        // Restore the original 'search' on the Request for downstream consumers
        if ($overrideSearch) {
            if ($hadQuery) {
                $request->query->set('search', $originalSearch);
            }
            if ($hadRequest) {
                $request->request->set('search', $originalSearch);
            }
        }

        return $response;
    }

    private function isZenaiSearch(Request $request): bool
    {
        if ((string) $request->get(self::ZENAI_FLAG) === '1') {
            return true;
        }

        return false;
    }

    private function updateListing(
        ProductSearchRouteResponse $response,
        array $recommendedIds
    ): void
    {
        $listingResult = $response->getListingResult();
        $elements = $listingResult->getElements();
        $sorted = [];

        foreach ($recommendedIds as $recommendedId) {
            if (\is_string($recommendedId) && array_key_exists($recommendedId, $elements)) {
                $sorted[$recommendedId] = $elements[$recommendedId];
            }
        }

        if ($sorted !== []) {
            $listingResult->clear();

            foreach ($sorted as $entity) {
                $listingResult->add($entity);
            }
        }
    }
}
