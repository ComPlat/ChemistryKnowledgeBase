<?php

namespace DIQA\FacetedSearch2\Update;

use Exception;
use Job;
use MediaWiki\Title\Title;

/**
 * Asynchronous updates for Index
 * Update a page in the backend Index and also update dependant pages
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

        // when indexing with jobs, we must ensure not to create new updating jobs
        global $fs2gCreateUpdateJob;
        $fs2gCreateUpdateJob = false;

        try {
            $messages = [];
            $xml = FSIndexer::indexArticlesWithDependent($title, $messages);
            print "\tindexed with xml:\n $xml\n";
            if ($consoleMode && count($messages) > 0) {
                print "\tindexed with messages:\n";
                print implode("\t\n", $messages);
            }
        } catch (Exception $e) {
            if ($consoleMode) {
                print sprintf("\tnot indexed, reason: %s \n", $e->getMessage());
            }

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
