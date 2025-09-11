<?php
namespace DIQA\WikiFarm\Special;

use DateTime;
use DIQA\WikiFarm\WikiRepository;
use eftec\bladeone\BladeOne;
use Exception;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\LabelWidget;
use OOUI\Tag;
use OOUI\TextInputWidget;
use OutputPage;

class SpecialCreateWiki extends \SpecialPage {

    private $repository;
    private $blade;

    function __construct() {
        parent::__construct( 'SpecialCreateWiki', 'edit');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new BladeOne( $views, $cache );

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

        $user = RequestContext::getMain()->getUser();
        if ($user->isAnon()) {
            $output->addHTML(wfMessage('wfarm-must-be-logged-in-with-edit'));
            return;
        }

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
        $user = RequestContext::getMain()->getUser();
        $allWikiCreated = $this->repository->getAllWikisCreatedById($user->getId());
        return $this->blade->run ( "wiki-created-by",
            ['allWikiCreated' => $allWikiCreated,
                'baseURL' => $wgServer
                ]
        );
    }
}