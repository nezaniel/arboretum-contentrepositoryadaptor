<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Service;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Nezaniel\Arboretum\Domain as Arboretum;
use Nezaniel\Arboretum\Utility\TreeUtility;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain as ContentRepository;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;

/**
 * The Graph service
 */
class GraphService
{
    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $contentDimensionCombinator;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;


    /**
     * @var array|Arboretum\Model\Node[]
     */
    protected $nodesByPath = [];

    /**
     * @var Arboretum\Model\Graph
     */
    protected $graph;


    /**
     * @param callable $nodeCallback
     * @param bool $initializeNodes
     * @param int $maximumDepth
     * @return Arboretum\Model\Graph
     */
    public function getGraph(callable $nodeCallback = null, $initializeNodes = true, $maximumDepth = null)
    {
        if (is_null($this->graph)) {
            $this->initializeGraph($nodeCallback, $initializeNodes, $maximumDepth);
        }

        return $this->graph;
    }

    /**
     * @param callable $nodeCallback
     * @param boolean $initializeNodes
     * @param null $maximumDepth
     * @todo add workspace support
     */
    protected function initializeGraph(callable $nodeCallback = null, $initializeNodes = true, $maximumDepth = null)
    {
        $this->graph = new Arboretum\Model\Graph('');

        $this->initializeTrees();

        if ($initializeNodes) {
            $this->initializeRootNode($nodeCallback);
            $this->initializeNodes('/', $nodeCallback, $maximumDepth);
        }
    }

    /**
     * @return void
     */
    protected function initializeTrees()
    {
        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {
            $treeIdentity = [
                'workspace' => 'live',
                'dimensionValues' => []
            ];
            foreach ($dimensionCombination as $dimensionName => $dimensionValues) {
                $treeIdentity['dimensionValues'][$dimensionName] = $dimensionValues[0];
            }


            $fallbackTreeIdentity = $treeIdentity;
            foreach (array_reverse($dimensionCombination) as $dimensionName => $dimensionValues) {
                $currentKey = array_search($treeIdentity['dimensionValues'][$dimensionName], $dimensionValues);
                if (isset($dimensionValues[$currentKey + 1])) {
                    $fallbackTreeIdentity['dimensionValues'][$dimensionName] = $dimensionValues[$currentKey + 1];
                    break;
                }
            }
            $fallbackTree = $this->graph->getTree(TreeUtility::hashIdentityComponents($fallbackTreeIdentity));

            new Arboretum\Model\Tree($this->graph, $treeIdentity, $fallbackTree);
        }
    }

    /**
     * @param callable|null $nodeCallback
     */
    protected function initializeRootNode(callable $nodeCallback = null)
    {
        $rootNodeData = $this->fetchChildren('')['/'][0];
        $this->graph->getRootNode()->setIdentifier($rootNodeData->getIdentifier());
        $this->graph->getRootNode()->setType($rootNodeData->getNodeType()->getName());
        foreach ($this->graph->getTrees() as $tree) {
            $this->nodesByPath['/'][$tree->getIdentityHash()] = $this->graph->getRootNode();
        }
        if ($nodeCallback) {
            $nodeCallback($rootNodeData);
        }
    }

    /**
     * @param string $parentPath
     * @param callable $nodeCallback
     * @param integer $maximumDepth
     * @return void
     */
    protected function initializeNodes($parentPath, callable $nodeCallback = null, $maximumDepth = null)
    {
        foreach ($this->fetchChildren($parentPath) as $path => $nodes) {
            if ($path === '/sites' && count($nodes) === 1) {
                $nodeData = reset($nodes);
                foreach ($this->graph->getTrees() as $tree) {
                    $edgeProperties = [];
                    $node = $this->createNodeFromNodeData($tree, $nodeData, $edgeProperties);
                    $parentNode = $this->nodesByPath[$parentPath][$tree->getIdentityHash()];
                    $newEdge = $tree->connectNodes($parentNode, $node, $nodeData->getIndex(), $nodeData->getName(), $edgeProperties);
                    $newEdge->mergeStructurePropertiesWithParent();

                    $this->nodesByPath[$path][$tree->getIdentityHash()] = $node;

                    if ($nodeCallback) {
                        $nodeCallback($nodeData);
                    }
                }
            } else {
                foreach ($nodes as $nodeData) {
                    /** @var NodeData $nodeData */
                    $tree = $this->graph->getTree($this->getTreeIdentifier($nodeData->getWorkspace()->getName(), $nodeData->getDimensionValues()));
                    if (!isset($this->nodesByPath[$parentPath][$tree->getIdentityHash()])) {
                        continue;
                    }

                    $edgeProperties = [];
                    $node = $this->createNodeFromNodeData($tree, $nodeData, $edgeProperties);
                    $parentNode = $this->nodesByPath[$parentPath][$tree->getIdentityHash()];
                    $newEdge = $tree->connectNodes($parentNode, $node, $nodeData->getIndex(), $nodeData->getName(), $edgeProperties);
                    $newEdge->mergeStructurePropertiesWithParent();

                    $this->nodesByPath[$path][$tree->getIdentityHash()] = $node;

                    if ($nodeCallback) {
                        $nodeCallback($nodeData);
                    }
                }
            }

            foreach ($this->graph->getTrees() as $tree) {
                if ($path === '/sites' || !isset($this->nodesByPath[$path][$tree->getIdentityHash()])) {
                    $treeInFallbackHierarchy = $tree->getFallback();
                    $found = false;
                    while ($treeInFallbackHierarchy && !$found) {
                        if (isset($this->nodesByPath[$path][$treeInFallbackHierarchy->getIdentityHash()])) {
                            /** @var Arboretum\Model\Node $fallbackNode */
                            $fallbackNode = $this->nodesByPath[$path][$treeInFallbackHierarchy->getIdentityHash()];
                            $this->nodesByPath[$path][$tree->getIdentityHash()] = $fallbackNode;
                            $parentNode = $this->nodesByPath[$parentPath][$tree->getIdentityHash()];
                            $fallbackEdge = $fallbackNode->getIncomingEdgeInTree($fallbackNode->getTree());
                            $tree->connectNodes($parentNode, $fallbackNode, $fallbackEdge->getPosition(), $fallbackEdge->getName(), $fallbackEdge->getProperties());

                            $found = true;
                        } else {
                            $treeInFallbackHierarchy = $treeInFallbackHierarchy->getFallback();
                        }
                    }
                }
            }

            if (is_null($maximumDepth) || substr_count($path, '/') <= $maximumDepth) {
                $this->getEntityManager()->clear();
                $this->initializeNodes($path, $nodeCallback, $maximumDepth);
            }
        }
    }

    /**
     * @param Arboretum\Model\Tree $tree
     * @param NodeData $nodeData
     * @param array $edgeProperties
     * @return Arboretum\Model\Node
     */
    protected function createNodeFromNodeData(Arboretum\Model\Tree $tree, ContentRepository\Model\NodeData $nodeData, array& $edgeProperties)
    {
        $nodeProperties = $nodeData->getProperties();
        $nodeProperties['_creationDateTime'] = $nodeData->getCreationDateTime();
        $nodeProperties['_lastModificationDateTime'] = $nodeData->getLastModificationDateTime();
        $nodeProperties['_lastPublicationDateTime'] = $nodeData->getLastPublicationDateTime();
        $edgeProperties = [
            'accessRoles' => empty($nodeData->getAccessRoles()) ? ['Neos.Flow:Everybody'] : $nodeData->getAccessRoles(),
            'hidden' => $nodeData->isHidden(),
            'hiddenBeforeDateTime' => $nodeData->getHiddenBeforeDateTime(),
            'hiddenAfterDateTime' => $nodeData->getHiddenAfterDateTime(),
            'hiddenInIndex' => $nodeData->isHiddenInIndex(),
        ];

        return new Arboretum\Model\Node($tree, $nodeData->getNodeType()->getName(), $nodeData->getIdentifier(), $nodeProperties);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param string $parentPath
     * @return array|NodeData[]
     */
    protected function fetchChildren($parentPath)
    {
        $childrenAtEdge = [];
        $nodeQuery = $this->nodeDataRepository->createQuery();
        /** @var NodeData[] $nodeData */
        $nodeData = $nodeQuery->matching(
            $nodeQuery->logicalAnd([
                $nodeQuery->equals('workspace', 'live'),
                $nodeQuery->equals('parentPathHash', md5($parentPath))
            ])
        )->execute();
        foreach ($nodeData as $entry) {
            $childrenAtEdge[$entry->getPath()][] = $entry;
        }

        return $childrenAtEdge;
    }

    /**
     * @param string $workspaceName
     * @param array $dimensionCombination
     * @return string
     */
    protected function getTreeIdentifier($workspaceName, array $dimensionCombination)
    {
        $treeIdentity = [
            'workspace' => $workspaceName,
            'dimensionValues' => []
        ];
        foreach ($dimensionCombination as $dimensionName => $dimensionValues) {
            $treeIdentity['dimensionValues'][$dimensionName] = $dimensionValues[0];
        }

        return TreeUtility::hashIdentityComponents($treeIdentity);
    }
}
