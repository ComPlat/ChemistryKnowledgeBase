<?php
namespace DIQA\WikiFarm\Special;

use DateTime;
use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonWidget;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use OutputPage;
use Philo\Blade\Blade;

class SpecialCreateWiki extends \SpecialPage {

    private $repository;
    private $blade;

    function __construct() {
        parent::__construct( 'SpecialCreateWiki' );

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ( $views, $cache );

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $this->repository = new WikiRepository($dbr);
    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par ) {

        $output = $this->getOutput();
        $this->setHeaders();

        OutputPage::setupOOUI();

        $html = $this->getGUIElements();
        $html .= $this->getWikiTable();

        $output->addHTML($html);
    }

    /**
     * @throws \Exception
     */
    public static function within2Days($createdAt) {
        $createdAtDateTime = new DateTime($createdAt);
        return $createdAtDateTime->diff(new DateTime())->days < 2;
    }

    /**
     * @return string
     * @throws \OOUI\Exception
     */
    private function getGUIElements(): string
    {
        $button = new ButtonWidget([
            'classes' => ['wfarm-button'],
            'id' => 'chemextension-create-wiki',
            'label' => $this->msg('wfarm-create-wiki')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);
        $html = '';
        $text = new FieldLayout(
            new TextInputWidget(['id' => 'wfarm-wikiName']),
            [
                'align' => 'top',
                'label' => "Wikiname"
            ]
        );
        $html .= $text;
        $html .= $button;
        return $html;
    }

    private function getWikiTable() {
        global $wgServer;
        global $wgUser;
        $allWikiCreated = $this->repository->getAllWikisCreatedById($wgUser->getId());
        return $this->blade->view ()->make ( "wiki-created-by",
            ['allWikiCreated' => $allWikiCreated,
                'baseURL' => $wgServer ]
        )->render ();
    }
}