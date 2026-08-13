<?php

namespace DIQA\ChemExtension\PublicationImport;

use Html;
use Title;

class ImportProcessTableRenderer
{

    /** @var array<int,array<string,mixed>> */
    private $rows;

    /**
     * @param array<int,array<string,mixed>> $rows Rows as returned by ImportProcessRepository
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * Render the import processes as an HTML table.
     *
     * @return string
     */
    public function render(): string
    {
        $html = Html::openElement('h2');
        $html .= Html::element('span', ['class' => 'chemext-import-process-title'], 'Import processes');
        $html .= Html::closeElement('h2');
        if (count($this->rows) === 0) {
            $html .= Html::element('p', ['class' => 'chemext-import-process-empty'],
                'No import processes found.');
            return $html;
        }

        $html .= Html::openElement('table', [
            'class' => 'wikitable sortable chemext-import-process-table',
        ]);

        $html .= $this->renderHeader();
        $html .= $this->renderBody();

        $html .= Html::closeElement('table');
        return $html;
    }

    private function renderHeader(): string
    {
        $headers = [
            'ID',
            'Page title',
            'DOI',
            'Status',
            'Created at',
            'Started at',
        ];

        $cells = '';
        foreach ($headers as $header) {
            $cells .= Html::element('th', [], $header);
        }

        return Html::rawElement('thead', [],
            Html::rawElement('tr', [], $cells));
    }

    private function renderBody(): string
    {
        $body = '';
        foreach ($this->rows as $row) {
            $body .= $this->renderRow($row);
        }
        return Html::rawElement('tbody', [], $body);
    }

    private function renderRow(array $row): string
    {
        $status = $row['status'] ?? '';
        $rowAttribs = [
            'class' => 'chemext-import-process-row chemext-import-process-status-'
                . strtolower((string)$status),
        ];

        $cells = '';
        $cells .= Html::element('td', ['class' => 'chemext-col-id'], (string)($row['id'] ?? ''));
        $cells .= Html::rawElement('td', ['class' => 'chemext-col-page-title'],
            $this->renderPageTitle($row['page_title'] ?? ''));
        $cells .= Html::rawElement('td', ['class' => 'chemext-col-doi'],
            $this->renderDoi($row['doi'] ?? ''));
        $cells .= Html::rawElement('td', ['class' => 'chemext-col-status chemext-status-' . strtolower($status)],
            $this->renderStatusBadge((string)$status));
        $cells .= Html::element('td', ['class' => 'chemext-col-created-at'],
            $this->formatDate($row['created_at'] ?? null));
        $cells .= Html::element('td', ['class' => 'chemext-col-started-at'],
            $this->formatDate($row['started_at'] ?? null));

        return Html::rawElement('tr', $rowAttribs, $cells);
    }

    private function renderPageTitle(string $pageTitle): string
    {
        if ($pageTitle === '') {
            return '';
        }
        $title = Title::newFromText($pageTitle);
        if ($title === null) {
            return htmlspecialchars($pageTitle);
        }
        return Html::element('a', ['href' => $title->getFullURL()], $title->getPrefixedText());
    }

    private function renderDoi(string $doi): string
    {
        if ($doi === '') {
            return '';
        }
        return Html::element('a', [
            'href' => 'https://doi.org/' . $doi,
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ], $doi);
    }

    private function renderStatusBadge(string $status): string
    {
        $class = 'chemext-status-badge';
        return Html::element('span', ['class' => $class], $status);
    }

    private function formatDate($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return (string)$value;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}