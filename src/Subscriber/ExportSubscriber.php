<?php

namespace Zenai\ZenaiSearchPlugin\Subscriber;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\ImportExport\Event\EnrichExportCriteriaEvent;
use Shopware\Core\Content\ImportExport\Event\ImportExportBeforeExportRecordEvent;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductSearchKeyword\ProductSearchKeywordCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\System\Tag\TagCollection;
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
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('manufacturer.translations');
        $criteria->addAssociation('properties');
        $criteria->addAssociation('properties.translations');
        $criteria->addAssociation('properties.group');
        $criteria->addAssociation('properties.group.translations');
        $criteria->addAssociation('tags');
        $criteria->addAssociation('searchKeywords');
    }

    public function onBeforeExportRecord(ImportExportBeforeExportRecordEvent $event): void
    {
        if ($event->getConfig()->get('profileName') !== ZenaiSearchPlugin::ZENAI_EXPORT_PROFILE_LABEL) {
            return;
        }

        $record = $event->getRecord();
        $originalRecord = $event->getOriginalRecord();

        $record['category'] = $this->createCsvRowCategory($originalRecord['categories'] ?? null);
        $record['description'] = $this->buildDescription(
            $record['description'] ?? null,
            $originalRecord['manufacturer'] ?? null,
            $originalRecord['properties'] ?? null,
            $originalRecord['tags'] ?? null,
            $originalRecord['searchKeywords'] ?? null
        );

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

    private function buildDescription(
        ?string $description,
        ?ProductManufacturerEntity $manufacturer,
        ?PropertyGroupOptionCollection $properties,
        ?TagCollection $tags,
        ?ProductSearchKeywordCollection $searchKeywords
    ): string {
        $parts = [];

        $description = trim((string) $description);
        if ($description !== '') {
            $parts[] = rtrim($description, ". \t\n\r\0\x0B");
        }

        $manufacturerName = trim((string) ($manufacturer?->getTranslation('name') ?? $manufacturer?->getName()));
        if ($manufacturerName !== '') {
            $parts[] = 'Brand: ' . $manufacturerName;
        }

        if ($properties !== null) {
            foreach ($properties as $property) {
                $formattedProperty = $this->formatProperty($property);
                if ($formattedProperty !== null) {
                    $parts[] = $formattedProperty;
                }
            }
        }

        $tagNames = $this->extractTagNames($tags);
        if ($tagNames !== []) {
            $parts[] = 'Tags: ' . implode(', ', $tagNames);
        }

        $keywords = $this->extractSearchKeywords($searchKeywords);
        if ($keywords !== []) {
            $parts[] = 'Search keywords: ' . implode(', ', $keywords);
        }

        return implode('. ', $parts);
    }

    private function formatProperty(PropertyGroupOptionEntity $property): ?string
    {
        $groupName = trim((string) ($property->getGroup()?->getTranslation('name') ?? $property->getGroup()?->getName()));
        $optionName = trim((string) ($property->getTranslation('name') ?? $property->getName()));

        if ($groupName === '' || $optionName === '') {
            return null;
        }

        return $groupName . ': ' . $optionName;
    }

    /**
     * @return list<string>
     */
    private function extractTagNames(?TagCollection $tags): array
    {
        if ($tags === null) {
            return [];
        }

        $tagNames = [];
        foreach ($tags as $tag) {
            $name = trim($tag->getName());
            if ($name !== '') {
                $tagNames[] = $name;
            }
        }

        return array_values(array_unique($tagNames));
    }

    /**
     * @return list<string>
     */
    private function extractSearchKeywords(?ProductSearchKeywordCollection $searchKeywords): array
    {
        if ($searchKeywords === null) {
            return [];
        }

        $keywords = [];
        foreach ($searchKeywords as $searchKeyword) {
            $keyword = trim($searchKeyword->getKeyword());
            if ($keyword !== '') {
                $keywords[] = $keyword;
            }
        }

        return array_values(array_unique($keywords));
    }
}
