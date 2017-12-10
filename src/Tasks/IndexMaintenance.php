<?php

namespace Vulcan\Search\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;
use Vulcan\Search\Models\SearchIndexEntry;
use Vulcan\Search\Models\SearchTank;

class IndexMaintenance extends BuildTask
{
    protected $indexedIds = [];

    protected $title = "Search Index Maintenance";

    protected $description = "This will perform any necessary maintenance on the index such as tank swaps and obsolete records";

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
        $classes = SearchIndexEntry::get()->column('Model');

        foreach ($classes as $class) {
            $this->outputText('');
            $this->outputText('# PERFORMING MAINTENANCE ON ' . $class);

            /** @var DataList|SearchIndexEntry $record */
            $records = SearchIndexEntry::get()->filter('Model', $class);

            if (!class_exists($class)) {
                // class has been removed, clear all obsolete records
                $this->outputText(' - Class no longer exists, deleting all records');
                $count = 0;
                foreach ($records as $record) {
                    $record->delete();
                    $count++;
                    $this->outputText('X', false);
                }
                $this->outputText('');
                $this->outputText(' - Removed ' . $count);

                continue;
            }

            $singleton = self::singleton($class);

            /** @var SearchIndexEntry $first */
            $first = $records->first();
            $tank = $singleton->config()->get('search_tank');

            if ($tank != $first->Tank()->Title) {
                // tank name has swapped, update all records
                $this->outputText('');
                $this->outputText(sprintf('- Tank on %s has changed from %s to %s', $class, $first->Tank()->Title, $tank));
                $count = 0;
                foreach ($records as $record) {
                    $record->TankID = SearchTank::findOrCreateTank($tank);
                    $record->write();
                    $count++;
                    $this->outputText('.', false);
                }
                $this->outputText('');
                $this->outputText(sprintf(' - Updated %s records', $count));
            }

            $this->outputText('');
            $this->outputText(' - Searching for obsolete records');
            $count = 0;
            foreach ($records as $record) {
                if (!$record->getRecord()) {
                    $record->delete();
                    $this->outputText('X', false);
                    $count++;
                }
            }

            $this->outputText('');
            $this->outputText(sprintf(' - Cleared %s obsolete records', $count));
        }

        $this->outputText('');
        $this->outputText('');
        $this->outputText('## COMPLETED');
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