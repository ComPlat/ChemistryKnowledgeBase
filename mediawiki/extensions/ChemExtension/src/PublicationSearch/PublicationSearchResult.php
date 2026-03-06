<?php
namespace DIQA\ChemExtension\PublicationSearch;

use DateTime;

class PublicationSearchResult
{

    private $doi;
    private $title;
    private $abstract;
    private $published;

    public function __construct($doi, $title, $abstract, $published)
    {
        $this->doi = $doi;
        $this->title = $title;
        $this->abstract = $abstract;
        $this->published = $published;
    }

    /**
     * @return mixed
     */
    public function getDoi()
    {
        return $this->doi;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @return mixed
     */
    public function getPublished()
    {
        return $this->published;
    }


    static function fromResult($result): array
    {
        $results = [];
        $items = $result->message->items;
        foreach ($items as $item) {
            $results[] = new PublicationSearchResult($item->DOI,
                $item->title[0],
                strip_tags($item->abstract) ?? '',
                self::parseDateFromPublished($item->published));
        }
        return $results;
    }

    static function parseDateFromPublished($published): ?string {
        if ($published === null) {
            return null;
        }

        $dateParts = $published->{'date-parts'}[0] ?? null;

        if (!is_array($dateParts) || empty($dateParts)) {
            return null;
        }

        return match (count($dateParts)) {
            1 => (string) $dateParts[0],
            2 => DateTime::createFromFormat('!m', (string) $dateParts[1])->format('F') . ' ' . $dateParts[0],
            default => DateTime::createFromFormat('j/n/Y', "{$dateParts[2]}/{$dateParts[1]}/{$dateParts[0]}")->format('d.m.Y'),
        };
    }
}