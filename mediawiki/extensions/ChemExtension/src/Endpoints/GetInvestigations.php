<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\NavigationBar\InvestigationFinder;
use MediaWiki\Rest\SimpleHandler;
use Philo\Blade\Blade;
use Wikimedia\ParamValidator\ParamValidator;
use Title;

class GetInvestigations extends SimpleHandler
{
    private $blade;

    /**
     * GetPublications constructor.
     */
    public function __construct()
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);
    }

    public function run()
    {

        $params = $this->getValidatedParams();
        $title = Title::newFromText($params['title']);
        $searchTerm = trim(strtolower($params['searchTerm'] ?? ''));

        $investigationFinder = new InvestigationFinder();
        if ($title->getNamespace() === NS_CATEGORY) {
            $list = $investigationFinder->getInvestigationsForTopic($title, $searchTerm);
            $type = "topic";
        } else {
            $list = $investigationFinder->getInvestigationsForPublication($title, $searchTerm);
            $type = "publication";
        }
        $investigationList = $this->blade->view()->make("navigation.investigation-list",
            [
                'list' => $list,
                'type' => $type,
            ]
        )->render();
        return ['html' => $investigationList];
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [
            'title' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
            'searchTerm' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
        ];
    }
}