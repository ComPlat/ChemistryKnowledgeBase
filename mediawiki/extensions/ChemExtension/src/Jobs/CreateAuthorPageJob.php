<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use Title;

class CreateAuthorPageJob extends Job {

    private $logger;
    private $name;
    private $orcid;

    public function __construct($command, $params)
    {
        $this->name = $params['name'] ?? '';
        $this->orcid = $params['orcid'] ?? '';
        if (is_null($this->title)) {
            $this->title = self::getAuthorPageTitle($this->name);
        }

        parent::__construct('CreateAuthorPageJob', $params);
        $this->logger = new LoggerUtils('CreateAuthorPageJob', 'ChemExtension');

    }

    public function run()
    {
        try {

            $this->createAuthorPage();
            $this->logger->log("Created author page: " . $this->title->getPrefixedText());

        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public static function getAuthorPageTitle($author): ?Title
    {
        return Title::newFromText($author, NS_AUTHOR);
    }

    public function createAuthorPage(): bool
    {
        if (!$this->title->isValid()) {
            return false;
        }

        $text = '';
        if ($this->orcid !== '-') {
            $text .= <<<TEXT
== These publications are are assigned to the author's ORCID ==
{{#ask:
[[Has subobject::<q>[[Orcid::$this->orcid]]</q>]]
|?DOI
|?Journal
|?Publication date
|?Publisher
|mainlabel=Publication
|format=table
|default=No publications yet
}}

TEXT;

        }

        if ($this->orcid === '-') {
            $text .= <<<TEXT
This author does not have an ORCID, so we show all publications with the name "$this->name" 

TEXT;
        } else {
            $text .= <<<TEXT
== These publications are from authors with the name "$this->name" ==

TEXT;

        }
        $text .= <<<TEXT
{{#ask:
[[Has subobject::<q>[[Author::$this->name]][[Orcid::-]]</q>]]
|?DOI
|?Journal
|?Publication date
|?Publisher
|mainlabel=Publication
|format=table
|default=No publications yet
}}

TEXT;

        $text .= "\n\n[[Category:Author]]";
        return WikiTools::doEditContent($this->title, $text, "auto-generated", $this->title->exists() ? EDIT_UPDATE : EDIT_NEW);
    }
}