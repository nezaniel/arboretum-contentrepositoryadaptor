<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Service;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Nezaniel\Arboretum\Domain as Arboretum;
use Nezaniel\Arboretum\Utility\TreeUtility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * The Graph service
 */
class GraphService
{
    /**
     * @var Arboretum\Model\Graph
     */
    protected $graph;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @var array|Arboretum\Model\Node[]
     */
    protected $nodesByPath = [];

    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;


    /**
     * @param callable $nodeCallback
     * @return Arboretum\Model\Graph
     */
    public function getGraph(callable $nodeCallback = null)
    {
        if (is_null($this->graph)) {
            $this->initializeGraph($nodeCallback);
        }

        return $this->graph;
    }

    /**
     * @param callable $nodeCallback
     * @param boolean $initializeNodes
     * @return void
     */
    protected function initializeGraph(callable $nodeCallback = null, $initializeNodes = true)
    {
        $this->graph = new Arboretum\Model\Graph('');

        $dimensionPresets = $this->contentDimensionPresetSource->getAllPresets();
        $rootTree = $this->getRootTree($dimensionPresets);

        $this->collectVariantTrees($rootTree, $dimensionPresets);

        $rootNodeData = $this->fetchChildren('')['/'][0];
        $this->graph->getRootNode()->setIdentifier($rootNodeData->getIdentifier());
        $this->graph->getRootNode()->setType($rootNodeData->getNodeType()->getName());
        foreach ($this->graph->getTrees() as $tree) {
            $this->nodesByPath['/'][$tree->getIdentityHash()] = $this->graph->getRootNode();
        }
        if ($nodeCallback) {
            $nodeCallback($rootNodeData);
        }
        if ($initializeNodes) {
            $this->initializeNodes('/', $nodeCallback);
        }
    }

    /**
     * @param string $parentPath
     * @param callable $nodeCallback
     * @return void
     */
    protected function initializeNodes($parentPath, callable $nodeCallback = null)
    {
        foreach ($this->fetchChildren($parentPath) as $path => $nodes) {
            foreach ($nodes as $nodeData) {
                /** @var NodeData $nodeData */
                $tree = $this->graph->getTree($this->getTreeIdentifier($nodeData->getWorkspace()->getName(), $nodeData->getDimensionValues()));
                if (!$tree) {
                    \TYPO3\Flow\var_dump($nodeData->getIdentifier());
                    exit();
                }
                if (!isset($this->nodesByPath[$parentPath][$tree->getIdentityHash()])) {
                    continue;
                }
                $properties = $nodeData->getProperties();
                $properties['_accessroles'] = $nodeData->getAccessRoles();
                $node = new Arboretum\Model\Node($tree, $nodeData->getNodeType()->getName(), $nodeData->getIdentifier(), $properties);
                $parentNode = $this->nodesByPath[$parentPath][$tree->getIdentityHash()];
                $tree->connectNodes($parentNode, $node, $nodeData->getIndex(), $nodeData->getName());

                $this->nodesByPath[$path][$tree->getIdentityHash()] = $node;

                if ($nodeCallback) {
                    $nodeCallback($nodeData);
                }
            }

            foreach ($this->graph->getTrees() as $tree) {
                if (!isset($this->nodesByPath[$path][$tree->getIdentityHash()])) {
                    $treeInFallbackHierarchy = $tree->getFallback();
                    $found = false;
                    while ($treeInFallbackHierarchy && !$found) {
                        if (isset($this->nodesByPath[$path][$treeInFallbackHierarchy->getIdentityHash()])) {
                            /** @var Arboretum\Model\Node $fallbackNode */
                            $fallbackNode = $this->nodesByPath[$path][$treeInFallbackHierarchy->getIdentityHash()];
                            $this->nodesByPath[$path][$tree->getIdentityHash()] = $fallbackNode;
                            $parentNode = $this->nodesByPath[$parentPath][$tree->getIdentityHash()];
                            $fallbackEdge = $fallbackNode->getIncomingEdgeInTree($fallbackNode->getTree());
                            $tree->connectNodes($parentNode, $fallbackNode, $fallbackEdge->getPosition(), $fallbackEdge->getName());
                            $found = true;
                        } else {
                            $treeInFallbackHierarchy = $treeInFallbackHierarchy->getFallback();
                        }
                    }
                }
            }
            #if (substr_count($path, '/') < 3) {
                $this->getEntityManager()->clear();
                $this->initializeNodes($path, $nodeCallback);
            #}
        }
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
     * @param array $dimensionValues
     * @return string
     */
    protected function getTreeIdentifier($workspaceName, array $dimensionValues)
    {
        $treeIdentity = [
            'workspace' => $workspaceName,
            'dimensionValues' => []
        ];
        foreach ($dimensionValues as $dimensionName => $dimensionValue) {
            $treeIdentity['dimensionValues'][$dimensionName] = $dimensionValue[0];
        }

        return TreeUtility::hashIdentityComponents($treeIdentity);
    }

    /**
     * @param array $dimensionPresets
     * @return Arboretum\Model\Tree
     */
    protected function getRootTree(array $dimensionPresets)
    {
        $rootTreeIdentity = [
            'workspace' => 'live',
            'dimensionValues' => []
        ];
        foreach ($dimensionPresets as $dimensionName => $presetConfiguration) {
            $rootTreeIdentity['dimensionValues'][$dimensionName] = $presetConfiguration['defaultPreset'];
        }

        return new Arboretum\Model\Tree($this->graph, $rootTreeIdentity);
    }

    /**
     * @param Arboretum\Model\Tree $fallbackTree
     * @param array $remainingDimensions
     * @return void
     */
    protected function collectVariantTrees(Arboretum\Model\Tree $fallbackTree, array $remainingDimensions)
    {
        $variantTreeIdentity = $fallbackTree->getIdentityComponents();
        reset($remainingDimensions);
        $dimensionName = key($remainingDimensions);
        $dimensionConfiguration = array_shift($remainingDimensions);
        foreach (array_keys($dimensionConfiguration['presets']) as $dimensionValue) {
            $variantTreeIdentity['dimensionValues'][$dimensionName] = $dimensionValue;
            $variantTree = new Arboretum\Model\Tree($fallbackTree->getGraph(), $variantTreeIdentity, $fallbackTree);
            if (!empty($remainingDimensions)) {
                $this->collectVariantTrees($variantTree, $remainingDimensions);
            }
        }
    }
}
