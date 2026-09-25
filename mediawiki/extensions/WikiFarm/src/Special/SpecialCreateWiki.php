<?php
namespace DIQA\WikiFarm\Special;

use DateTime;
use DIQA\WikiFarm\WikiRepository;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
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

    function __construct() {
        parent::__construct( 'SpecialCreateWiki', 'edit');

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

    /**
     * Renders the "wikis created by you" table using MediaWiki's Html abstraction.
     *
     * @return string
     * @throws \OOUI\Exception
     */
    private function getWikiTable(): string
    {
        global $wgServer;
        $user = RequestContext::getMain()->getUser();
        $allWikiCreated = $this->repository->getAllWikisCreatedById($user->getId());

        $inner = Html::element( 'p', [], $this->msg( 'wfarm-wikis-created-by-you' )->text() );

        // Table header
        $headerRow = Html::rawElement( 'tr', [],
            Html::element( 'th', [], $this->msg( 'wfarm-wiki-name' )->text() ) .
            Html::element( 'th', [], $this->msg( 'wfarm-wiki-creation-date' )->text() ) .
            Html::element( 'th', [], '' )
        );

        $rowsHtml = '';
        foreach ( $allWikiCreated as $row ) {
            $rowsHtml .= $this->renderWikiRow( $row, $wgServer );
        }

        $tableHtml = Html::rawElement( 'table', [], $headerRow . $rowsHtml );
        $inner .= $tableHtml;

        if ( count( $allWikiCreated ) === 0 ) {
            $inner .= Html::element( 'p', [], $this->msg( 'wfarm-no-wikis-found' )->text() );
        }

        return Html::rawElement(
            'div',
            [
                'id' => 'wfarm-createdwikis-table',
                'class' => 'wfarm-createdwikis-table',
            ],
            $inner
        );
    }

    /**
     * Renders one row of the wiki table.
     *
     * @param array $row
     * @param string $baseURL
     * @return string Raw HTML
     * @throws \OOUI\Exception
     */
    private function renderWikiRow( array $row, string $baseURL ): string
    {
        $status = $row['wiki_status'];

        // First cell: name / link / status text
        if ( $status === 'CREATED' ) {
            $nameCellContent = Html::element(
                'a',
                [
                    'target' => '_blank',
                    'href' => $baseURL . '/wiki' . $row['id'] . '/mediawiki',
                ],
                $row['wiki_name']
            );
        } elseif ( $status === 'TO_BE_DELETED' ) {
            $nameCellContent = htmlspecialchars( $row['wiki_name'] ) . ' '
                . htmlspecialchars( $this->msg( 'wfarm-wiki-to-be-deleted' )->text() );
        } else {
            $nameCellContent = htmlspecialchars( $row['wiki_name'] ) . ' '
                . htmlspecialchars( $this->msg( 'wfarm-wiki-in-creation' )->text() );
        }

        // Second cell: creation date, with optional "(recent)" suffix
        $dateText = $row['created_at'];
        if ( self::within2Days( $row['created_at'] ) && $status !== 'IN_CREATION' ) {
            $dateText .= ' (' . $this->msg( 'wfarm-recent' )->text() . ')';
        }

        // Third cell: delete button (OOUI widget rendered to string)
        $deleteButton = new ButtonWidget( [
            'classes' => [ 'wfarm-remove-wiki' ],
            'label' => $this->msg( 'wfarm-remove-wiki' )->text(),
            'flags' => [ 'primary', 'destructive' ],
            'infusable' => true,
        ] );
        $deleteButton->setAttributes( [ 'wiki-id' => $row['id'] ] );

        $rowAttribs = [
            'wiki-id' => $row['id'],
        ];        if ( $status === 'IN_CREATION' ) {
            $rowAttribs['class'] = 'wfarm-in-creation';
        }

        return Html::rawElement( 'tr', $rowAttribs,
            Html::rawElement( 'td', [], $nameCellContent ) .
            Html::element( 'td', [], $dateText ) .
            Html::rawElement( 'td', [], (string)$deleteButton )
        );
    }
}