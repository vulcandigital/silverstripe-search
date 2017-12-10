<?php

namespace Vulcan\Search\Extensions;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;
use Vulcan\Search\Models\SearchIndexEntry;

/**
 * Class SearchIndexExtension
 * @package Vulcan\Search\Extensions
 */
class SearchIndexExtension extends DataExtension implements Flushable
{
    /**
     * We want to index the record on write. If the record is an instance of Page the record will be indexed only if the page is published
     * or unindexed if otherwise
     */
    public function onAfterWrite()
    {
        /** @var DataObject|\Page $owner */
        $owner = $this->owner;

        if ($owner instanceof \Page) {
            if ($owner->isPublished()) {
                SearchIndexEntry::indexRecord($owner);
            } else {
                SearchIndexEntry::unindexRecord($owner);
            }

            return;
        }

        SearchIndexEntry::indexRecord($owner);
    }

    /**
     * We want to remove the index record when the owning record gets deleted
     */
    public function onBeforeDelete()
    {
        /** @var DataObject|\Page $owner */
        $owner = $this->owner;
        SearchIndexEntry::unindexRecord($owner);
    }

    /**
     * Return an array of all Columns that contain searchable text
     *
     * @return array
     */
    public function searchableColumns()
    {
        return [
            'Title',
            'Content'
        ];
    }

    /**
     * Finds and caches all classes who have me added as an extension
     *
     * @return array|mixed
     */
    public static function extendedClasses()
    {
        /** @var CacheInterface $cache */
        $cache = Injector::inst()->get(CacheInterface::class . '.vulcanSearch');

        if ($classes = $cache->get('extendedClasses')) {
            return $classes;
        }

        $classes = ClassInfo::subclassesFor(DataObject::class);

        $classesWithExtension = [];
        /** @var DataObject $className */
        foreach ($classes as $className) {
            if ($className::has_extension(SearchIndexExtension::class)) {
                $classesWithExtension[] = $className;
            }
        }

        $cache->set('extendedClasses', $classes);

        return $classesWithExtension;
    }

    /**
     * Flushes the search cache manifest and recreates it
     *
     * @see FlushMiddleware
     */
    public static function flush()
    {
        /** @var CacheInterface $cache */
        $cache = Injector::inst()->get(CacheInterface::class . '.vulcanSearch');
        $cache->delete('extendedClasses');

        static::extendedClasses();
    }

    /**
     * Renders the current object for display in search results
     *
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    public function SearchRender()
    {
        return $this->getRenderedSearchItem();
    }

    /**
     * Attempts to find a Render template for the owning class name otherwise fallsback to default
     *
     * @return \SilverStripe\ORM\FieldType\DBHTMLText
     */
    private function getRenderedSearchItem()
    {
        $self = $this->owner->ClassName;
        $pieces = explode('\\', $self);
        $me = 'Render' . array_pop($pieces);
        $namespace = 'Vulcan\Search\Render';

        $template = sprintf('%s\%s', $namespace, $me);

        $template = ThemeResourceLoader::inst()->findTemplate($template, SSViewer::get_themes());

        if (!$template) {
            $template = sprintf('%s\%s', $namespace,'RenderStandardSearch');
        }

        return $this->owner->renderWith($template);
    }
}