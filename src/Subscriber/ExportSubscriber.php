<?php

namespace Zenai\ZenaiSearchPlugin\Subscriber;

use Monolog\Logger;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\ImportExport\Event\EnrichExportCriteriaEvent;
use Shopware\Core\Content\ImportExport\Event\ImportExportBeforeExportRecordEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenai\ZenaiSearchPlugin\ZenaiSearchPlugin;

class ExportSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // Ensure product export loads categories + translations so breadcrumb is available
            EnrichExportCriteriaEvent::class       => 'onEnrichCriteria',
            // Overwrite the field value right before the record is written
            ImportExportBeforeExportRecordEvent::class => 'onBeforeExportRecord',
        ];
    }

    public function onEnrichCriteria(EnrichExportCriteriaEvent $event): void
    {
        if (($event->getLogEntity()->getProfileName() ?? '') !== ZenaiSearchPlugin::ZENAI_EXPORT_PROFILE_LABEL) {
            return;
        }

        $criteria = $event->getCriteria();

        $criteria->addAssociation('mainCategories.category');
        $criteria->addAssociation('mainCategories.category.translations');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('categories.translations');
    }

    public function onBeforeExportRecord(ImportExportBeforeExportRecordEvent $event): void
    {
        if ($event->getConfig()->get('profileName') !== ZenaiSearchPlugin::ZENAI_EXPORT_PROFILE_LABEL) {
            return;
        }

        $record = $event->getRecord();
        $record['category'] = $this->createCsvRowCategory($event->getOriginalRecord()['categories']);

        $event->setRecord($record);
    }

    private function createCsvRowCategory(CategoryCollection $categories = null): ?string
    {
        if (!$categories || $categories->count() === 0) {
            return 'no category';
        }

        $csvRowCategory = [];
        foreach ($categories as $category) {
            $csvRowCategory[] = implode(' > ', $category->getBreadcrumb());
        }

        return implode(' | ', $csvRowCategory);
    }
}