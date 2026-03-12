<?php

namespace DIQA\ChemExtension\PublicationSearch;

use DIQA\ChemExtension\Utils\QueryUtils;
use Html;
use MediaWiki\MediaWikiServices;
use RequestContext;
use SpecialPage;

class PublicationSearchSpecialpage extends SpecialPage {

    private const PAGE_SIZE = 10;
    private $publicationRepo;

    public function __construct() {
        parent::__construct( 'PublicationSearchSpecialpage' );
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->publicationRepo = new PublicationSearchRepository($dbr);
    }

    /**
     * @param string|null $subPage
     */
    public function execute( $subPage ): void {
        $out = $this->getOutput();
        $out->setPageTitle( $this->msg( 'publicationlist-title' ) );
        $out->addModules( 'mediawiki.special.publicationList' );

        $request = RequestContext::getMain()->getRequest();
        $topic   = $request->getText( 'category', '' );
        $page    = max( 0, $request->getInt( 'page', 0 ) );

        $out->addHTML( $this->buildForm( $topic ) );

        $publications = $this->fetchPublications( $topic, self::PAGE_SIZE, $page );
        $total        = $this->publicationRepo->getRelevantPublicationsCount($topic);
        $pageSize     = self::PAGE_SIZE;
        $totalPages   = max( 1, (int)ceil( $total / $pageSize ) );
        $page         = min( $page, $totalPages - 1 );

        $out->addHTML( $this->buildTable( $publications ) );
        $out->addHTML( $this->buildPager( $topic, $page, $totalPages, $total ) );
    }

    private function buildForm( string $selectedCategory ): string {
        $categories = $this->fetchTopicCategories();

        $html = Html::openElement( 'form', [
            'method' => 'get',
            'action' => $this->getPageTitle()->getLocalURL(),
        ] );

        $html .= Html::openElement( 'div', [ 'class' => 'crossref-filter' ] );

        $html .= Html::label(
            $this->msg( 'crossref-category-label' )->text(),
            'crossref-category-input'
        );

        $html .= '&#160;';

        // Build the datalist with all category options
        $datalistOptions = '';
        foreach ( $categories as $category ) {
            $datalistOptions .= Html::element( 'option', [ 'value' => $category ] );
        }
        $html .= Html::rawElement( 'datalist', [ 'id' => 'crossref-category-list' ], $datalistOptions );

        // The combobox input references the datalist
        $html .= Html::element( 'input', [
            'type'        => 'text',
            'id'          => 'crossref-category-input',
            'name'        => 'category',
            'list'        => 'crossref-category-list',
            'value'       => $selectedCategory,
            'placeholder' => $this->msg( 'crossref-category-placeholder' )->text(),
            'autocomplete' => 'off',
        ] );

        $html .= '&#160;';

        $html .= Html::element( 'input', [
            'type'  => 'submit',
            'value' => $this->msg( 'crossref-category-submit' )->text(),
            'class' => 'mw-ui-button mw-ui-progressive',
        ] );

        $html .= Html::closeElement( 'div' );
        $html .= Html::closeElement( 'form' );

        return $html;
    }

    private function fetchTopicCategories(): array {
        $dbr = wfGetDB( DB_REPLICA );

        $res = $dbr->select(
            'category',
            [ 'cat_title' ],
            [],
            __METHOD__,
            [ 'ORDER BY' => 'cat_title ASC' ]
        );

        $categories = [];
        foreach ( $res as $row ) {
            $categories[] = str_replace( '_', ' ', $row->cat_title );
        }

        return $categories;
    }

    /**
     * Fetch publication data.
     * Replace this method body with a real data source (e.g. LiteratureRepository).
     *
     * @return array[]
     */
    private function fetchPublications(string $topic, int $pageSize, int $pageNumber): array {
        return $this->publicationRepo->getRelevantPublications($topic, $pageSize, $pageNumber * $pageSize);
    }

    /**
     * Build an HTML table for the given publications.
     *
     * @param array[] $publications
     * @return string HTML
     */
    private function buildTable( array $publications ): string {
        $html = Html::openElement( 'table', [
            'class' => 'wikitable sortable publication-list-table',
        ] );

        // Table header
        $html .= Html::openElement( 'thead' );
        $html .= Html::openElement( 'tr' );
        foreach ( [ 'title', 'abstract', 'doi', 'date', 'check_result', 'approved' ] as $col ) {
            $html .= Html::element(
                'th',
                [],
                $this->msg( 'publicationlist-col-' . $col )->text()
            );
        }
        $html .= Html::closeElement( 'tr' );
        $html .= Html::closeElement( 'thead' );

        // Table body
        $html .= Html::openElement( 'tbody' );

        if ( empty( $publications ) ) {
            $html .= Html::openElement( 'tr' );
            $html .= Html::element( 'td', [ 'colspan' => 4 ],
                $this->msg( 'publicationlist-empty' )->text()
            );
            $html .= Html::closeElement( 'tr' );
        } else {
            foreach ( $publications as $pub ) {
                $html .= $this->buildRow( $pub );
            }
        }

        $html .= Html::closeElement( 'tbody' );
        $html .= Html::closeElement( 'table' );

        return $html;
    }

    /**
     * Build a single table row.
     *
     * @return string HTML
     */
    private function buildRow(PublicationSearchResult $pub ): string {
        $html = Html::openElement( 'tr' );

        // Title
        $html .= Html::element( 'td', [], $pub->getTitle() ?? '' );

        // Abstract
        $html .= Html::element( 'td', [ 'class' => 'publication-abstract' ], $pub->getAbstract() ?? '' );

        // DOI — rendered as a link when present
        $doi = $pub->getDoi() ?? '';
        if ( $doi !== '' ) {
            $doiLink = Html::element(
                'a',
                [
                    'href'   => 'https://doi.org/' . htmlspecialchars( $doi ),
                    'target' => '_blank',
                    'rel'    => 'noopener noreferrer',

                ],
                $doi
            );
            $html .= Html::rawElement( 'td', ['class'  => self::isDOIKnown($doi) ? 'doi-link-known' : 'doi-link-unknown'], $doiLink );
        } else {
            $html .= Html::element( 'td', [], '' );
        }

        // Date
        $html .= Html::element( 'td', [], $pub->getPublished() ?? '' );
        $html .= Html::element( 'td', [], $pub->getCheckResult() ?? '' );
        $approvedElement = Html::check($pub->getDoi(), $pub->getApproved() == '1');
        $html .= Html::rawElement( 'td', ['class' => 'approved-checkbox'],  $approvedElement);

        $html .= Html::closeElement( 'tr' );

        return $html;
    }

    /**
     * Build Previous / Next / page-number pager.
     */
    private function buildPager( string $topic, int $page, int $totalPages, int $total ): string {
        if ( $totalPages <= 1 ) {
            return '';
        }

        $baseUrl = $this->getPageTitle()->getLocalURL( [
            'category' => $topic,
        ] );

        $html = Html::openElement( 'div', [ 'class' => 'publication-pager mw-pager' ] );

        // Prev
        if ( $page > 0 ) {
            $html .= Html::element( 'a', [
                'href'  => $baseUrl . '&page=' . ( $page - 1 ),
                'class' => 'mw-ui-button',
            ], $this->msg( 'crossref-pager-prev' )->text() );
        } else {
            $html .= Html::element( 'span', [
                'class' => 'mw-ui-button mw-ui-quiet',
                'aria-disabled' => 'true',
            ], $this->msg( 'crossref-pager-prev' )->text() );
        }
        // Page indicator
        $html .= Html::element( 'span', [ 'class' => 'publication-pager-info' ],
            $this->msg( 'crossref-pager-info' )
                ->numParams( $page + 1, $totalPages, $total )
                ->text()
        );


        // Next
        if ( $page < $totalPages - 1 ) {
            $html .= Html::element( 'a', [
                'href'  => $baseUrl . '&page=' . ( $page + 1 ),
                'class' => 'mw-ui-button',
            ], $this->msg( 'crossref-pager-next' )->text() );
        } else {
            $html .= Html::element( 'span', [
                'class' => 'mw-ui-button mw-ui-quiet',
                'aria-disabled' => 'true',
            ], $this->msg( 'crossref-pager-next' )->text() );
        }

        $html .= Html::closeElement( 'div' );

        return $html;
    }

    private static function isDOIKnown(string $doi): bool {
        $res = QueryUtils::executeBasicQuery("[[DOI::" . $doi . "]]", [], ['limit' => 1]);
        return $res->getCount() > 0;
    }
}