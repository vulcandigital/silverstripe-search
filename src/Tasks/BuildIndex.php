<?php

namespace Vulcan\Search\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use Vulcan\Search\Extensions\SearchIndexExtension;
use Vulcan\Search\Models\SearchIndexEntry;
use Vulcan\Search\Models\SearchTank;

class BuildIndex extends BuildTask
{
    protected $indexedIds = [];

    protected $title = "Build Search Index Manifest";

    protected $description = "Only needs to be called once on production, or to re-build the manifest";

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param HTTPRequest $request
     *
     * @return void
     */
    public function run($request)
    {
        $allDataObjects = ClassInfo::subclassesFor(DataObject::class);
        $classes = [];

        /** @var DataObject $className */
        foreach ($allDataObjects as $className) {
            if ($className::has_extension(SearchIndexExtension::class)) {
                $classes[] = $className;
            }
        }

        if (empty($classes)) {
            $this->outputText('No models implement Indexable. Nothing to do...');
            exit;
        }

        foreach ($classes as $class) {
            $this->runOnClass($class);
        }

        $this->outputText('');
        $this->outputText('');

        if ($cleared = $this->clearObsoleteRecords()) {
            $this->outputText(sprintf('Cleared %s obsolete records', $cleared));

            $this->outputText('');
            $this->outputText('');
        }

        $this->outputText('#### COMPLETED');
    }

    /**
     * @param string|SearchIndexExtension|DataObject $className
     */
    public function runOnClass($className)
    {
        $this->outputText('');
        $this->outputText('');
        $this->outputText('# BEGINNING TO INDEX ' . $className);

        $searchableColumns = singleton($className)->searchableColumns();

        if (!is_array($searchableColumns) || empty($searchableColumns)) {
            $this->outputText('Class does not return searchable columns as an array, or the array is empty');
            return;
        }

        /** @var DataList|DataObject[] $records */
        $records = $className::get();

        if (!$records->exists()) {
            $this->outputText('That model has nothing to index...');
            return;
        }

        foreach ($records as $record) {
            $this->indexRecord($record, $searchableColumns);
        }
    }

    /**
     * @param DataObject|\Page $record
     * @param array            $searchableColumns
     */
    public function indexRecord(DataObject $record, array $searchableColumns)
    {
        $values = [];

        $tank = $record->config()->get('search_tank');

        if (!$tank) {
            $tank = 'Main';
        }

        if (!$tank instanceof SearchTank) {
            $tank = SearchTank::findOrCreateTank($tank);
        }

        if ($record instanceof \Page && !$record->ShowInSearch) {
            $this->outputText('X', false);
            return;
        }

        foreach ($searchableColumns as $column) {
            $values[] = strip_tags(html_entity_decode($record->relObject($column)));
        }

        $string = strtolower(implode(' ## ', $values));

        /** @var SearchIndexEntry $index */
        $index = SearchIndexEntry::get()->filter([
            'Model'    => $record->ClassName,
            'RecordID' => $record->ID,
            'TankID' => $tank->ID
        ])->first();

        if ($index) {
            $this->indexedIds[] = $index->ID;

            if ($index->SearchableText == $string) {
                $this->outputText('-', false);
                return;
            }

            $index->SearchableText = $string;
            $index->write();

            $this->outputText('U', false);
            return;
        }

        $index = SearchIndexEntry::create();
        $index->Model = $record->ClassName;
        $index->RecordID = $record->ID;
        $index->SearchableText = $string;
        $index->TankID = $tank->ID;
        $this->indexedIds[$tank->ID][] = $index->write();

        $this->outputText('.', false);
    }

    public function clearObsoleteRecords()
    {
        $count = 0;

        foreach ($this->indexedIds as $tankId => $ids) {
            /** @var DataList|SearchIndexEntry[] $records */
            $records = SearchIndexEntry::get()->filter([
                'ID:not' => $ids,
                'TankID' => $tankId
            ]);

            foreach ($records as $record) {
                $count++;
                $record->delete();
            }
        }

        return $count;
    }

    public function outputText($text, $break = true)
    {
        if ($break) {
            $break = "<br>";

            if (Director::is_cli()) {
                $break = PHP_EOL;
            }

            echo $text . $break;
            return;
        }

        echo $text;
    }
}