<?php

namespace Vulcan\Search\Pages;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\HasManyList;
use SilverStripe\View\Requirements;
use Vulcan\Search\Models\SearchIndexEntry;
use Vulcan\Search\Models\SearchTank;
use Vulcan\Search\Models\SortFilter;

/**
 * Class SearchPageController
 * @package Vulcan\Search\Pages
 *
 * @property int TankID
 *
 * @method SearchTank Tank
 * @method HasManyList Filters
 */
class SearchPageController extends \PageController
{
    private static $allowed_actions = [
        'index'
    ];

    public function init()
    {
        parent::init();

        Requirements::javascript('/resources/vulcandigital/silverstripe-search/js/search.js');
    }

    /**
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function index(HTTPRequest $request)
    {
        $q = $request->getVar('q');

        if ($q) {
            return $this->getResults($request);
        }

        return $this->render();
    }

    /**
     * @param HTTPRequest $request
     *
     * @return string
     */
    public function getResults(HTTPRequest $request)
    {
        $q = $request->getVar('q');
        $pageSize = $request->getVar('show') ?? 25;
        $filter = $request->getVar('filter') ?? null;
        $sort = $request->getVar('sort') ?? null;

        if ($sort) {
            /** @var SortFilter $sortFilter */
            $sortFilter = SortFilter::get()->filter('Title', $sort)->first();

            if ($sortFilter) {
                $sort = $sortFilter->SortSql;
            }
        }

        $classFilters = null;

        if ($filter) {
            $filters = explode(',', $filter);

            foreach ($filters as $key) {
                $class = $this->getFilterClassFromKey($key);

                if ($class) {
                    if (!is_array($classFilters)) {
                        $classFilters = [];
                    }

                    $classFilters[] = $class;
                }
            }
        }

        $term = new DBHTMLText();
        $term->setValue($q);

        return $this->render([
            'SearchTerm'    => $term,
            'SearchResults' => SearchIndexEntry::paginatedSearch($q, $this->Tank(), $sort, $classFilters, $request, $pageSize)
        ]);
    }

    /**
     * Dynamically generates a map used for filtering searched records based on which tank is currently active
     *
     * @return array
     */
    public function filterClassMap()
    {
        $tankClasses = SearchTank::classesImplementTank($this->Tank());
        $filterMap = [];

        foreach ($tankClasses as $class) {
            if (singleton($class) instanceof \Page) {
                $filterMap['pages'] = \Page::class;
                continue;
            }

            $name = Config::inst()->get($class, 'search_filter_name') ?? basename($class);

            $filterMap[$name] = $class;
        }

        return $filterMap;
    }

    /**
     * @return ArrayList
     */
    public function FilterMap()
    {
        $map = [];
        $active = $this->getRequest()->getVar('filter');
        foreach ($this->filterClassMap() as $key => $value) {
            $map[] = [
                'Key'      => $key,
                'Value'    => ucwords($key),
                'IsActive' => strstr($active, $key)
            ];
        }

        return ArrayList::create($map);
    }

    /**
     * @param $key
     *
     * @return bool|mixed
     */
    public function getFilterClassFromKey($key)
    {
        $map = $this->filterClassMap();

        return $map[$key] ?? false;
    }

    /**
     * @return mixed
     */
    public function getSortValue()
    {
        return $this->getRequest()->getVar('sort');
    }
}