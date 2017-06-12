<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Command;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Service\GraphService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * The Graph service
 */
class GraphCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var GraphService
     */
    protected $graphService;

    /**
     * @return void
     */
    public function buildCommand() {
        $time = microtime(true);
        $this->graphService->getGraph();
        $this->outputLine('built graph in ' . round((microtime(true) - $time) * 1000) . ' ms.');
    }
}
