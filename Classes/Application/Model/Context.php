<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Model;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Nezaniel\Arboretum\Domain as Arboretum;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\Workspace;

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
    public function __construct(Arboretum\Model\Tree $tree = null, Workspace $workspace = null)
    {
        $this->tree = $tree;
        $this->workspace = $workspace;
    }


    /**
     * @return array
     */
    public function getWorkspaceName()
    {
        return $this->tree ? $this->tree->getIdentityComponents()['workspace'] : 'live';
    }

    /**
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->workspace;
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        $dimensions = [];
        if ($this->tree) {
            foreach ($this->tree->getIdentityComponents()['dimensionValues'] as $dimensionName => $dimensionValue) {
                $dimensions[$dimensionName][] = $dimensionValue;
            }
            $fallbackTree = $this->tree->getFallback();
            while ($fallbackTree) {
                foreach ($fallbackTree->getIdentityComponents()['dimensionValues'] as $dimensionName => $dimensionValue) {
                    if (!in_array($dimensionValue, $dimensions[$dimensionName])) {
                        $dimensions[$dimensionName][] = $dimensionValue;
                    }
                }
                $fallbackTree = $fallbackTree->getFallback();
            }
        }

        return $dimensions;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->tree ? $this->tree->getIdentityComponents() : [];
    }
}
