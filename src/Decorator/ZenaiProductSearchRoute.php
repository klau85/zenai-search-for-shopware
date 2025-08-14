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
 * (e.g. zenai=1), we override the default behavior (e.g. enforce a specific sorting).
 */
class ZenaiProductSearchRoute extends AbstractProductSearchRoute
{
    public const ZENAI_FLAG = 'zenai';

    public function __construct(private readonly AbstractProductSearchRoute $decorated)
    {
    }

    public function getDecorated(): AbstractProductSearchRoute
    {
        return $this->decorated;
    }

    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSearchRouteResponse
    {
        // When our special button is used, the frontend will submit zenai=1
        $useZenai = (string) $request->get(self::ZENAI_FLAG) === '1';

        if ($useZenai) {
            // Example override: enforce a deterministic sorting to show that our plugin took over.
            // This keeps UX intact but makes the result order clearly different from default.
            // You can later replace this with your actual ZenAI search integration.
            if (!$request->get('order')) {
                // price-asc is a common storefront sort param
                $request->query->set('order', 'price-asc');
            }

            // Also set a criteria title to ease debugging in profiler/logs
            $criteria->setTitle('zenai-overridden-search');
        }

        return $this->decorated->load($request, $context, $criteria);
    }
}
