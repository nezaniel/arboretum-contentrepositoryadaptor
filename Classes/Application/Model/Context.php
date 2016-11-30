<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Model;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Nezaniel\Arboretum\Domain as Arboretum;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * The Context adaptor
 */
class Context
{
    /**
     * @var Arboretum\Model\Tree
     */
    protected $tree;

    /**
     * @var Workspace
     */
    protected $workspace;


    /**
     * @param Arboretum\Model\Tree $tree
     * @param Workspace $workspace
     */
    public function __construct(Arboretum\Model\Tree $tree, Workspace $workspace)
    {
        $this->tree = $tree;
        $this->workspace = $workspace;
    }


    /**
     * @return array
     */
    public function getWorkspaceName()
    {
        return $this->tree->getIdentityComponents()['workspace'];
    }

    /**
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    public function getDimensions()
    {
        $dimensions = [];
        foreach ($this->tree->getIdentityComponents()['dimensionValues'] as $dimensionName => $dimensionValue) {
            $dimensions[$dimensionName][] = $dimensionValue;
        }
        $fallbackTree = $this->tree->getFallback();
        while ($fallbackTree) {
            foreach ($fallbackTree->getIdentityComponents()['dimensionValues'] as $dimensionName => $dimensionValue) {
                $dimensions[$dimensionName][] = $dimensionValue;
            }
            $fallbackTree = $fallbackTree->getFallback();
        }

        return $dimensions;
    }

    public function getProperties() {
        return $this->tree->getIdentityComponents();
    }
}
