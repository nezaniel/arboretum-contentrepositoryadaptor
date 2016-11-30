<?php
namespace Nezaniel\Arboretum\ContentRepositoryAdaptor\Application\Model;

/*
 * This file is part of the Nezaniel.Arboretum package.
 */

use Nezaniel\Arboretum\Domain as Arboretum;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

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
     * @var Arboretum\Model\Node
     */
    protected $node;

    /**
     * @var Arboretum\Model\Tree
     */
    protected $tree;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Arboretum\Model\Node $node
     * @param Arboretum\Model\Tree $tree
     * @param Workspace $workspace
     */
    public function __construct(Arboretum\Model\Node $node, Arboretum\Model\Tree $tree, Workspace $workspace)
    {
        $this->node = $node;
        $this->tree = $tree;
        $this->context = new Context($tree, $workspace);
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
        return $this->node->getIncomingEdgeInTree($this->tree)->getName();
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

    public function getAccessRoles()
    {
        return [];
    }

    public function getPath()
    {
        $incomingEdge = $this->node->getIncomingEdgeInTree($this->tree);
        $path = '';
        while ($incomingEdge) {
            $path .= '/' . $incomingEdge->getName();
            $parentNode = $incomingEdge->getParent();
            $incomingEdge = $parentNode->getIncomingEdgeInTree($this->tree);
        }

        return $path;
    }

    public function getContextPath()
    {
        return $this->getPath();
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
        return $this->context->getWorkspace();
    }

    public function getIdentifier()
    {
        return $this->node->getIdentifier();
    }

    public function setIndex($index)
    {
    }

    public function getIndex()
    {
        $incomingEdge = $this->node->getIncomingEdgeInTree($this->tree);
        if ($incomingEdge) {
            return $incomingEdge->getPosition();
        }

        return 0;
    }

    public function getParent()
    {
        $incomingEdge = $this->node->getIncomingEdgeInTree($this->tree);
        if ($incomingEdge) {
            return new Node($incomingEdge->getParent(), $this->tree, $this->context->getWorkspace());
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
        $outgoingEdge = $this->node->getOutgoingEdgesInTree($this->tree)[$path] ?? null;
        if ($outgoingEdge) {
            return new Node($outgoingEdge->getChild(), $this->tree, $this->context->getWorkspace());
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
