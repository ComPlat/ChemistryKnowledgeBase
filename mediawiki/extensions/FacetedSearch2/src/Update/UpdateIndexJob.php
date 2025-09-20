<?php

namespace DIQA\FacetedSearch2\Update;

use Exception;
use Job;
use SMW\DIProperty as SMWDIProperty;
use SMW\Services\ServicesFactory as ApplicationFactory;
use WikiPage;
use Title;
use SMW\DIWikiPage as SMWDIWikiPage;

/**
 * Asynchronous updates for Index
 * Updates a page in the backend Index and also updates dependant pages
 * (= pages with an incoming relation)
 *
 * @author Kai
 *
 */
class UpdateIndexJob extends Job
{

    /**
     * @param Title $title
     * @param array $params job parameters (timestamp)
     */
    function __construct($title, $params)
    {
        parent::__construct('UpdateIndexJob', $title, $params);
        $this->removeDuplicates = true;
    }

    /**
     * implementation of the actual job
     *
     * {@inheritDoc}
     * @see Job::run()
     */
    public function run()
    {
        $consoleMode = PHP_SAPI === 'cli' && !defined('UNITTEST_MODE');
        $title = $this->params['title'];

        // when indexing with jobs, we must assure not to create new updating jobs
        global $fs2gCreateUpdateJob;
        $fs2gCreateUpdateJob = false;

        $dependantPages = $this->retrieveDependent($title);
        try {

            $this->updatePageInIndex($title, $consoleMode);

            foreach ($dependantPages as $dp) {
                $this->updatePageInIndex($dp, $consoleMode);
            }
        } catch (Exception $e) {
            if ($consoleMode) {
                print sprintf("\tnot indexed, reason: %s \n", $e->getMessage());
            }

        }
    }

    private function retrieveDependent($title): array
    {

        $dependant = [];
        $subject = SMWDIWikiPage::newFromTitle($title);
        $store = ApplicationFactory::getInstance()->getStore();
        $inProperties = $store->getInProperties($subject);

        foreach ($inProperties as $inProperty) {
            /** @var SMWDIProperty $inProperty */
            $subjects = $store->getPropertySubjects($inProperty, $subject);
            foreach ($subjects as $subj) {
                $dependant[] = $subj->getTitle();
            }
        }

        // remove duplicate titles. works because of Title::__toString()
        return array_unique($dependant);
    }

    private function updatePageInIndex(Title $title, bool $consoleMode): void
    {
        try {

            $messages = [];
            FSIndexer::indexArticle($title, $messages);
            if ($consoleMode && count($messages) > 0) {
                print implode("\t\n", $messages);
            }
        } catch (Exception $e) {
            if ($consoleMode) {
                print sprintf("\tnot indexed, reason: %s \n", $e->getMessage());
            }

        }
        if ($consoleMode) {
            echo "Updated (Index): $title\n";
        }
    }

    /**
     * {@inheritDoc}
     * @see Job::getDeduplicationInfo()
     */
    public function getDeduplicationInfo()
    {
        $info = parent::getDeduplicationInfo();
        if (isset($info['params'])) {
            // timestamp not relevant for duplicate detection
            unset($info['params']['timestamp']);
        }
        return $info;
    }


}
