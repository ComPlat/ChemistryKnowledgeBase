<?php
namespace DIQA\WikiFarm\Special;

use DateTime;
use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\LabelWidget;
use OOUI\Tag;
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

        $html = $this->getWikiGUIControls();
        $html .= $this->getWikiTable();
        $html .= $this->getManageUserGUIControls();

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
    private function getWikiGUIControls(): string
    {
        $createWikiButton = new ButtonWidget([
            'classes' => ['wfarm-button'],
            'id' => 'wfarm-create-wiki',
            'label' => $this->msg('wfarm-create-wiki')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);
        $wikiNameInput = new FieldLayout(
            new TextInputWidget(['id' => 'wfarm-wikiName', 'placeholder' => $this->msg('wfarm-wiki-name')]),
            [
                'align' => 'top',
                'label' => $this->msg('wfarm-wiki-name')->text()
            ]
        );
        return new FormLayout(['items' => [$wikiNameInput, $createWikiButton] ]);

    }

    private function getManageUserGUIControls() {

        $label = new LabelWidget();
        $label->setLabel('Benutzer des Wikis');
        $saveButton = new ButtonWidget([
            'classes' => ['wfarm-button'],
            'id' => 'wfarm-add-user',
            'label' => $this->msg('wfarm-save-users')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);
        $usersList = new Tag('div');
        $usersList->setAttributes(['id' => 'wfarm-wikiUserList']);
        $section = new FormLayout(['items' => [$label, $usersList, $saveButton] ]);
        $div = new Tag('div');
        $div->setAttributes(['id' => 'wfarm-wikiUserList-section', 'style' => 'display: none;']);
        $div->appendContent($section);
        return $div;
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