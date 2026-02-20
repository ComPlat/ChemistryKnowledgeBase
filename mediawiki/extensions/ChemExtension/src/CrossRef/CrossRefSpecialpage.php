<?php

namespace DIQA\ChemExtension\CrossRef;

use SpecialPage;
use Html;
use RequestContext;

class CrossRefSpecialpage extends SpecialPage {

    public function __construct() {
        parent::__construct( 'CrossRefSpecialpage' );
    }



    /**
     * @param string|null $subPage
     */
    public function execute( $subPage ): void {
        $out = $this->getOutput();
        $out->setPageTitle( $this->msg( 'publicationlist-title' ) );
        $out->addModules( 'mediawiki.special.publicationList' );

        $topic = RequestContext::getMain()->getRequest()->getText('category', '');
        $out->addHTML( $this->buildForm( $topic ) );

        $publications = $this->fetchPublications($topic);

        $out->addHTML( $this->buildTable( $publications ) );
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
    private function fetchPublications(string $topic): array {
        // Example: return data from your repository here.
        // Each entry must have keys: title, abstract, doi, date.
        $crossRefApi = new CrossRefAPI();
        $res = $crossRefApi->find($topic, 300);

        return CrossRefResult::fromResult($res);
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
        foreach ( [ 'title', 'abstract', 'doi', 'date' ] as $col ) {
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
    private function buildRow( CrossRefResult $pub ): string {
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
            $html .= Html::rawElement( 'td', [], $doiLink );
        } else {
            $html .= Html::element( 'td', [], '' );
        }

        // Date
        $html .= Html::element( 'td', [], $pub->getPublished() ?? '' );

        $html .= Html::closeElement( 'tr' );

        return $html;
    }
}