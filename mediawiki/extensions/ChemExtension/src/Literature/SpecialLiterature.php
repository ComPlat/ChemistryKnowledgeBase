<?php

namespace DIQA\ChemExtension\Literature;

use DateTime;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\TextInputWidget;
use OutputPage;
use Philo\Blade\Blade;
use SpecialPage;
use Exception;

class SpecialLiterature extends SpecialPage {

    /**
     * @var LiteratureRepository
     */
    private $repo;
    /**
     * @var Blade
     */
    private $blade;

    public function __construct() {

        parent::__construct ( 'Literature', '', true);

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ( $views, $cache );
        
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER );
        $this->repo = new LiteratureRepository($dbr);

    }

    /**
     *
     * {@inheritDoc}
     * @see SpecialPage::execute()
     */
    public function execute($subPage) {

        $this->getOutput()->setPageTitle('Literature');

        global $wgRequest;
        $doi = $wgRequest->getVal('doi');
        if (is_null($doi)) {
            OutputPage::setupOOUI();
            $html = $this->getWikiGUIControls();
            $this->getOutput()->addHTML($html);
            return;
        }

        $literature = $this->repo->getLiterature($doi);
        if (is_null($literature)) {
            try {
                $resolver = new DOIResolver();
                $data = $resolver->resolve($doi);

                if ($data === false || is_null($data)) {
                    throw new Exception("Fehler beim Auflösen des DOIs");
                }
            } catch (Exception $e) {
                $this->getOutput()->addHTML($e->getMessage());
                return;
            }
        } else {
            $data = $literature['data'];
        }

        $html = $this->blade->view ()->make ( "doi-special-literature",
            [
                'doi' => $data->DOI,
                'title' => $data->title,
                'authors' => $this->formatAuthors($data->author),
                'submittedAt' => date('d.m.Y', ($data->created->timestamp / 1000)),
                'publishedOnlineAt' => $this->parseDateFromDateParts($data->{'published-online'}->{'date-parts'}),
                'publishedPrintAt' => $this->parseDateFromDateParts($data->{'published-print'}->{'date-parts'}),
                'publisher' => $data->publisher,
                'licenses' => $this->formatLicenses($data->license),
                'issue' => $data->issue,
                'volume' => $data->volume,
                'pages' => $data->page,
                'subjects' => $data->subject,
                'funders' => count($data->funder) === 0 ? "-" : array_map(function($e) { return $e->name; }, $data->funder),
            ]
        )->render ();

        $html = str_replace("\n", "", $html);
        $this->getOutput()->addHTML($html);
    }

    private function formatLicenses($licenses): array
    {
        if ($licenses == '') {
            return [];
        }
        $result = [];
        foreach($licenses as $license) {
            $date = $this->parseDateFromDateParts($license->start->{'date-parts'});
            $result[] = [ 'date' => $date, 'URL' => $license->URL];
        }
        return $result;
    }

    private function parseDateFromDateParts($dateParts) {
        if ($dateParts == '') {
            return '-';
        }
        $first = $dateParts[0]; // why several at all??
        if (count($first) === 1) {
            return $first[0]; // year
        } else if (count($first) === 2) {
            $year = $first[0];
            $dateObj   = DateTime::createFromFormat('!m', $first[1]);
            $monthName = $dateObj->format('F');
            return "$monthName $year";
        } else {
            return date('d.m.Y', strtotime("{$first[0]}/{$first[1]}/{$first[2]}"));
        }
    }
    private function formatAuthors($authors): array
    {
        if ($authors == '') {
            return [];
        }
        $result = [];
        foreach($authors as $author) {
            $affiliation = implode(", ", array_map(function($e) { return $e->name; }, $author->affiliation));
            $name = "{$author->given} {$author->family}";
            $result[] = [ 'nameAndAfiliation' => "$name, $affiliation", 'orcidUrl' => $author->ORCID];
        }
        return $result;
    }

    private function getWikiGUIControls(): string
    {
        $resolveDOIButton = new ButtonInputWidget([
            'classes' => [''],
            'type' => 'submit',
            'id' => 'submit-doi',
            'label' => $this->msg('submit-doi')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);
        $wikiNameInput = new FieldLayout(
            new TextInputWidget(['id' => 'doi', 'name' => 'doi', 'placeholder' => $this->msg('doi-hint')]),
            [
                'align' => 'top',
                'label' => $this->msg('enter-doi')->text()
            ]
        );
        return new FormLayout(['items' => [$wikiNameInput, $resolveDOIButton] ]);

    }
}
