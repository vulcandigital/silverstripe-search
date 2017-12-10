<?php

namespace Vulcan\Search\Models;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use Vulcan\Search\Extensions\SearchIndexExtension;

/**
 * Class SearchIndex
 * @package Vulcan\Search\Models
 *
 * @property string Model
 * @property int    RecordID
 * @property string SearchableText
 *
 * @property int    TankID
 *
 * @method SearchTank Tank
 */
class SearchIndexEntry extends DataObject
{
    private static $db = [
        'Model'          => 'Varchar(255)',
        'RecordID'       => 'Int',
        'SearchableText' => 'Text'
    ];

    private static $has_one = [
        'Tank' => SearchTank::class
    ];

    /**
     * @return SearchIndexExtension|DataObject
     */
    public function getRecord()
    {
        /** @var static $class */
        $class = $this->Model;

        return $class::get()->byID($this->RecordID);
    }

    /**
     * @param DataObject|SearchIndexExtension $record
     * @param array                           $searchableColumns
     *
     * @return bool|SearchIndexEntry Will return false if the record is prevented from displaying in search
     * @throws \Exception
     */
    public static function indexRecord(DataObject $record, array $searchableColumns = null)
    {
        if (!$record->hasExtension(SearchIndexExtension::class)) {
            throw new \RuntimeException(sprintf('%s does not have the SearchIndexExtension', $record->ClassName));
        }

        $tank = $record->config()->get('search_tank');

        if (!$tank) {
            $tank = 'Main';
        }

        if (!$tank instanceof SearchTank) {
            $tank = SearchTank::findOrCreateTank($tank);
        }

        $values = [];

        if ($record instanceof \Page && !$record->ShowInSearch) {
            return false;
        }

        if (!$searchableColumns) {
            $searchableColumns = $record->searchableColumns();
        }

        foreach ($searchableColumns as $column) {
            $values[] = strip_tags(html_entity_decode($record->relObject($column)));
        }

        $string = strtolower(implode(' ## ', $values));

        /** @var static $index */
        $index = static::get()->filter([
            'Model'    => $record->ClassName,
            'RecordID' => $record->ID,
            'TankID'   => $tank->ID
        ])->first();

        if ($index) {
            if ($index->SearchableText == $string) {
                return $index;
            }

            $index->SearchableText = $string;
            $index->write();

            return $index;
        }

        $index = SearchIndexEntry::create();
        $index->Model = $record->ClassName;
        $index->RecordID = $record->ID;
        $index->SearchableText = $string;
        $index->TankID = $tank->ID;
        $index->write();

        $record->SearchTankID = $tank->ID;

        return $index;
    }

    /**
     * @param DataObject $record
     *
     * @return bool
     * @throws \Exception
     */
    public static function unindexRecord(DataObject $record)
    {
        $tank = $record->config()->get('search_tank');

        if (!$tank) {
            $tank = SearchTank::get()->filter('Title', 'Main')->first();
        }

        if (!$tank instanceof SearchTank) {
            $tank = SearchTank::get()->filter('Title', $tank)->first();
            if (!$tank) {
                throw new \Exception("No tank with the title {$tank} was found.");
            }
        }

        $record = SearchIndexEntry::get()->filter([
            'Model'    => $record->ClassName,
            'RecordID' => $record->ID,
            'TankID'   => $tank->ID
        ])->first();

        if (!$record) {
            return true;
        }

        $record->delete();

        return true;
    }

    /**
     * @param                     $query
     * @param string|SortFilter   $sortSql
     * @param string|array        $classFilter
     *
     * @return DataList|SearchIndexEntry[]
     */
    public static function search($query, $sortSql = null, $classFilter = null)
    {
        $records = SearchIndexEntry::get()->filter([
            'SearchableText:PartialMatch' => explode(' ', $query)
        ]);

        if ($classFilter) {
            $records = $records->filter([
                'Model' => $classFilter
            ]);
        }

        if ($sortSql) {
            if ($sortSql instanceof SortFilter) {
                $records = $records->sort($sortSql->SortSql);
            } else {
                $records = $records->sort($sortSql);
            }
        } else {
            $records->sort('LastEdited DESC');
        }

        return $records;
    }

    /**
     * @param                   $query
     * @param string|SortFilter $sortSql
     * @param string|array      $classFilter
     *
     * @param HTTPRequest|null  $request
     * @param int               $pageSize
     *
     * @return PaginatedList
     */
    public static function paginatedSearch($query, $sortSql = null, $classFilter = null, HTTPRequest $request = null, $pageSize = 25)
    {
        $results = static::search($query, $sortSql, $classFilter);

        $pagedList = PaginatedList::create($results, ($request) ? $request : Controller::curr()->getRequest())->setPageLength($pageSize);

        return $pagedList;
    }
}