<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Literature\PublicationRenderer;
use Philo\Blade\Blade;
use SpecialPage;

class ShowPublications extends SpecialPage
{
    private $blade;

    public function __construct()
    {
        parent::__construct('ShowPublications');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);


    }

    function execute($par)
    {
        $output = $this->getOutput();
        $orcid = $this->getRequest()->getText('orcid', '');
        $author = $this->getRequest()->getText('author', '');
        if ($orcid === '' && $author === '') {
            $output->addHTML("Parameter 'orcid' and 'author' is missing");
            return;
        }
        
        $this->setHeaders();
        $orcidPublications = '';
        if ($orcid !== '') {
            $author = PublicationRenderer::getAuthorFromOrcid($orcid);
            if (is_null($author)) {
                $output->addHTML("Author with ORCID $orcid does not exist");
                return;
            }
            $output->setPageTitle("Publications of \"$author\"");
            $orcidPublications = PublicationRenderer::renderPublicationList($orcid);
        }
        $authorPublications = PublicationRenderer::renderPublicationListByAuthor($author);

        $output->addHTML($this->blade->view()->make("showPublications.show-publication",
            [
                'orcidPublications' => $orcidPublications,
                'authorPublications' => $authorPublications,
                'name' => $author,
                'orcid' => $orcid,
            ]
        )->render());
    }



}