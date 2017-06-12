<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Service;

/*
 * This file is part of the Nezaniel.Arboretum.ContentRepositoryAdaptor package.
 */

use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Service\Context as ContentContext;

/**
 * The Tree service
 */
class TreeService
{
    /**
     * @param ContentContext $contentContext
     * @return array
     */
    public function translateContentContextToTreeIdentity(ContentContext $contentContext)
    {
        $treeIdentity = [
            'workspace' => $contentContext->getWorkspaceName(),
        ];
        foreach ($contentContext->getTargetDimensionValues() as $dimensionName => $contextDimensionValues) {
            $treeIdentity['dimensionValues'][$dimensionName] = reset($contextDimensionValues);
        }

        return $treeIdentity;
    }
}
