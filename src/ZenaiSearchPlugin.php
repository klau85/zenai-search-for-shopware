<?php

declare(strict_types=1);

namespace Zenai\ZenaiSearchPlugin;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class ZenaiSearchPlugin extends Plugin
{
    const string ZENAI_EXPORT_PROFILE_TECHNICAL_NAME = 'zenai_product_export';
    const string ZENAI_EXPORT_PROFILE_LABEL = 'Zenai Product Export';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->ensureZenaiExportProfile($installContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        // Ensure the profile also exists when the plugin gets activated on an existing system
        $this->ensureZenaiExportProfile($activateContext->getContext());
    }

    private function ensureZenaiExportProfile(Context $context): void
    {
        $container = $this->container;
        if ($container === null) {
            return;
        }

        /** @var EntityRepository $profileRepo */
        $profileRepo = $container->get('import_export_profile.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', self::ZENAI_EXPORT_PROFILE_TECHNICAL_NAME));
        $existing = $profileRepo->search($criteria, $context);
        if ($existing->getTotal() > 0) {
            return; // already present
        }

        $mapping = [
            ['key' => 'id', 'mappedKey' => 'product_id'],
            ['key' => 'translations.DEFAULT.description', 'mappedKey' => 'description'],
            ['key' => 'translations.DEFAULT.name', 'mappedKey' => 'title'],
            ['key' => 'categories', 'mappedKey' => 'category'],
            ['key' => 'price.DEFAULT.net', 'mappedKey' => 'price'],
        ];

        $payload = [
            'technicalName' => self::ZENAI_EXPORT_PROFILE_TECHNICAL_NAME,
            'type' => 'export',
            'systemDefault' => false,
            'sourceEntity' => 'product',
            'fileType' => 'text/csv',
            'delimiter' => ',',
            'enclosure' => '"',
            'mapping' => $mapping,
            'config' => [
                'createEntities' => false,
                'updateEntities' => false,
            ],
            'label' => self::ZENAI_EXPORT_PROFILE_LABEL,
        ];

        $profileRepo->create([$payload], $context);
    }
}
