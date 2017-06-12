<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Model;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Nezaniel\Arboretum\Domain as Arboretum;
use Nezaniel\Arboretum\Domain\Model\Tree;
use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;

/**
 * The Node adaptor
 */
class Node implements NodeInterface
{
    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @var Arboretum\Model\Node
     */
    protected $node;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Workspace
     */
    protected $workspace;

    /**
     * @param Arboretum\Model\Node $node
     */
    public function __construct(Arboretum\Model\Node $node)
    {
        $this->node = $node;
    }

    public function setName($newName)
    {
        /*
        foreach ($this->node->getIncomingEdges() as $incomingEdge) {
            $incomingEdge->setName($newName);
        }
        */
    }

    public function getName()
    {
        return $this->node->getIncomingEdgeInTree($this->node->getTree())->getName();
    }

    public function getLabel()
    {
        return $this->getProperty('title') ?: $this->getName();
    }

    public function getFullLabel()
    {
        return $this->getLabel();
    }

    public function setProperty($propertyName, $value)
    {
    }

    public function hasProperty($propertyName)
    {
        return $this->node->getProperty($propertyName) !== null;
    }

    public function getProperty($propertyName)
    {
        return $this->node->getProperty($propertyName);
    }

    public function removeProperty($propertyName)
    {
    }

    public function getProperties()
    {
        return $this->node->getProperties();
    }

    public function getPropertyNames()
    {
        return array_keys($this->getProperties());
    }

    public function setContentObject($contentObject)
    {
    }

    public function getContentObject()
    {
    }

    public function unsetContentObject()
    {
    }

    public function setNodeType(NodeType $nodeType)
    {
    }

    public function getNodeType()
    {
        return $this->nodeTypeManager->getNodeType($this->node->getType());
    }

    public function setHidden($hidden)
    {
    }

    public function isHidden()
    {
        return false;
    }

    public function setHiddenBeforeDateTime(\DateTime $dateTime = null)
    {
    }

    public function getHiddenBeforeDateTime()
    {
    }

    public function setHiddenAfterDateTime(\DateTime $dateTime = null)
    {
    }

    public function getHiddenAfterDateTime()
    {
    }

    public function setHiddenInIndex($hidden)
    {
    }

    public function isHiddenInIndex()
    {
        return false;
    }

    public function setAccessRoles(array $accessRoles)
    {
    }

    /**
     * @return array
     */
    public function getAccessRoles()
    {
        return $this->getProperty('_accessroles');
    }

    public function getPath()
    {
        if (!$this->node->getTree()) {
            return '/';
        }
        $incomingEdge = $this->node->getIncomingEdgeInTree($this->node->getTree());
        $path = '';
        while ($incomingEdge) {
            $path = '/' . $incomingEdge->getName() . $path;
            $parentNode = $incomingEdge->getParent();
            $incomingEdge = $parentNode->getIncomingEdgeInTree($this->node->getTree());
        }

        return $path;
    }

    public function getContextPath()
    {
        return NodePaths::generateContextPath($this->getPath(), $this->getContext()->getWorkspaceName(), $this->context->getDimensions());
    }

    public function getDepth()
    {
        return substr_count($this->getPath(), '/');
    }

    public function setWorkspace(Workspace $workspace)
    {
    }

    public function getWorkspace()
    {
        if (!$this->workspace) {
            $this->workspace = $this->workspaceRepository->findByIdentifier($this->node->getTree() ? $this->node->getTree()->getIdentityComponents()['workspace'] : 'live');
        }
        return $this->workspace;
    }

    public function getIdentifier()
    {
        return $this->node->getIdentifier();
    }

    /**
     * @return array|Tree[]
     */
    public function getContainingTrees()
    {
        $trees = [];
        foreach ($this->node->getIncomingEdges() as $incomingEdge) {
            $trees[] = $incomingEdge->getTree();
        }
        return $trees;
    }

    public function setIndex($index)
    {
    }

    public function getIndex()
    {
        if (!$this->node->getTree()) {
            return 0;
        }
        $incomingEdge = $this->node->getIncomingEdgeInTree($this->node->getTree());
        if ($incomingEdge) {
            return $incomingEdge->getPosition();
        }

        return 0;
    }

    public function getParent()
    {
        if (!$this->node->getTree()) {
            return null;
        }
        $incomingEdge = $this->node->getIncomingEdgeInTree($this->node->getTree());
        if ($incomingEdge) {
            return new Node($incomingEdge->getParent());
        }

        return null;
    }

    public function getParentPath()
    {
        if ($this->getParent()) {
            return $this->getParent()->getPath();
        } else {
            return '';
        }
    }

    public function createNode($name, NodeType $nodeType = null, $identifier = null)
    {
    }

    public function createSingleNode($name, NodeType $nodeType = null, $identifier = null)
    {
    }

    public function createNodeFromTemplate(NodeTemplate $nodeTemplate, $nodeName = null)
    {
    }

    public function getNode($path)
    {
        $outgoingEdge = $this->node->getOutgoingEdgesInTree($this->node->getTree())[$path] ?? null;
        if ($outgoingEdge) {
            return new Node($outgoingEdge->getChild());
        }

        return null;
    }

    public function getPrimaryChildNode()
    {
    }

    public function getChildNodes($nodeTypeFilter = null, $limit = null, $offset = null)
    {
    }

    public function hasChildNodes($nodeTypeFilter = null)
    {
    }

    public function remove()
    {
    }

    public function setRemoved($removed)
    {
    }

    public function isRemoved()
    {
    }

    public function isVisible()
    {
    }

    public function isAccessible()
    {
    }

    public function hasAccessRestrictions()
    {
    }

    public function isNodeTypeAllowedAsChildNode(NodeType $nodeType)
    {
    }

    public function moveBefore(NodeInterface $referenceNode)
    {
    }

    public function moveAfter(NodeInterface $referenceNode)
    {
    }

    public function moveInto(NodeInterface $referenceNode)
    {
    }

    public function copyBefore(NodeInterface $referenceNode, $nodeName)
    {
    }

    public function copyAfter(NodeInterface $referenceNode, $nodeName)
    {
    }

    public function copyInto(NodeInterface $referenceNode, $nodeName)
    {
    }

    public function getNodeData()
    {
    }

    public function getContext()
    {
        if (!$this->context) {
            $this->context = new Context($this->node->getTree(), $this->getWorkspace());
        }
        return $this->context;
    }

    public function getDimensions()
    {
    }

    public function createVariantForContext($context)
    {
    }

    public function isAutoCreated()
    {
    }

    public function getOtherNodeVariants()
    {
    }
}
