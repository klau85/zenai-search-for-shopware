<?php
declare(strict_types=1);

namespace Zenai\ZenaiSearchPlugin\Decorator;

use Shopware\Core\Content\Product\SalesChannel\Search\AbstractProductSearchRoute;
use Shopware\Core\Content\Product\SalesChannel\Search\ProductSearchRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * Decorates the default product search. When the zenai flag is present in the request
 * (e.g. zenai=1), we override the default behavior.
 */
class ZenaiProductSearchRoute extends AbstractProductSearchRoute
{
    public const ZENAI_FLAG = 'zenai';

    public function __construct(private readonly AbstractProductSearchRoute $decorated) {
    }

    public function getDecorated(): AbstractProductSearchRoute
    {
        return $this->decorated;
    }

    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSearchRouteResponse
    {
        $isZenaiSearch = $this->isZenaiSearch($request);

        if ($isZenaiSearch) {
            // Save and temporarily remove 'search' so ProductSearchRoute won't build search query
            $originalSearch = $request->get('search');
            $hadQuery = $request->query->has('search');
            $hadRequest = $request->request->has('search');
            $hadAttr = $request->attributes->has('search');

            if ($hadQuery) {
                $request->query->remove('search');
            }
            if ($hadRequest) {
                $request->request->remove('search');
            }
            if ($hadAttr) {
                $request->attributes->remove('search');
            }

            $criteria->setTitle('zenai-overridden-search');
            // make zenai api call to get productIds
            // $criteria->setIds(['11dc680240b04f469ccba354cbf0b967', '2a88d9b59d474c7e869d8071649be43c']);
        }

        // Execute the actual product search without the search term influencing the query
        $response = $this->decorated->load($request, $context, $criteria);

        // Restore the original 'search' on the Request for downstream consumers
        if ($isZenaiSearch) {
            if ($hadQuery) {
                $request->query->set('search', $originalSearch);
            }
            if ($hadRequest) {
                $request->request->set('search', $originalSearch);
            }
            if ($hadAttr || (!$hadQuery && !$hadRequest)) {
                $request->attributes->set('search', $originalSearch);
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
}
