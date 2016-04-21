<?php
namespace Neos\ContentRepository\ResourceAdapter\Command;

/*
 * This file is part of the Neos.ContentRepository.ResourceAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\ResourceAdapter\Domain\Model\NodeTypes;
use Neos\ContentRepository\ResourceAdapter\Domain\Service\ResourceContext;
use Neos\ContentRepository\ResourceAdapter\Domain\Service\ResourceContextFactory;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Media\Domain\Model\Adjustment\AbstractAdjustment;
use TYPO3\Media\Domain\Model\Adjustment\CropImageAdjustment;
use TYPO3\Media\Domain\Model\Adjustment\ResizeImageAdjustment;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Audio;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Model\Video;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

class ResourceImporterCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ResourceContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Create the storage node
     *
     * @param string $storageName
     * @return void
     */
    public function createCommand($storageName)
    {
        $this->outputLine();
        $this->outputLine('<b>Create default storage at path "%s"</b>', [$storageName]);
        $this->outputLine();
        $context = $this->createContext();
        $rootNode = $context->getRootNode();
        $baseNode = $rootNode->getNode('resources');
        if ($baseNode === null) {
            $template = new NodeTemplate();
            $template->setName('resources');
            $baseNode = $rootNode->createNodeFromTemplate($template);
        }
        $collectionGroupNode = $baseNode->getNode($storageName);
        if ($collectionGroupNode === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::COLLECTION_GROUP));
            $template->setName($storageName);
            $collectionGroupNode = $baseNode->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> Collection Group node created');
        } else {
            $this->outputLine('  <comment>~~</comment> Collection Group exists');
        }
        $collectionNode = $collectionGroupNode->getNode('persistent');
        if ($collectionNode === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::COLLECTION));
            $template->setName('persistent');
            $collectionGroupNode->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> Persistent Collection node created');
        } else {
            $this->outputLine('  <comment>~~</comment> Persistent Collection exists');
        }
    }

    /**
     * Flush the content of the content repository
     *
     * @param string $storageName
     */
    public function flushCommand($storageName) {
        $this->outputLine();
        $this->outputLine('<b>Flush all resources to path "%s"</b>', [$storageName]);
        $this->outputLine();
        $context = $this->createContext();
        $rootNode = $context->getRootNode();
        $collectionNode = $rootNode->getNode('resources/' . $storageName);
        if ($collectionNode === null) {
            $this->outputLine('  <error>!!</error> Mount Point node not found');
            $this->sendAndExit(1);
        }
        $collectionNode->remove();
    }

    /**
     * Import all resources to the content repository
     *
     * @param string $storageName
     * @return void
     */
    public function importCommand($storageName)
    {
        $this->outputLine();
        $this->outputLine('<b>Import all resources to path "%s"</b>', [$storageName]);
        $this->outputLine();
        $context = $this->createContext();
        $rootNode = $context->getRootNode();
        $collectionNode = $rootNode->getNode('resources/' . $storageName . '/persistent');
        if ($collectionNode === null) {
            $this->outputLine('  <error>!!</error> Peristente Collection node not found');
            $this->sendAndExit(1);
        }
        $iterator = $this->assetRepository->findAllIterator();
        /** @var AssetInterface $asset */
        foreach ($this->assetRepository->iterate($iterator) as $asset) {
            switch (get_class($asset)) {
                case Document::class:
                    /** @var Document $asset */
                    $this->importDocument($asset, $collectionNode);
                    break;
                case Video::class:
                    /** @var Video $asset */
                    $this->importVideo($asset, $collectionNode);
                    break;
                case Audio::class:
                    /** @var Audio $asset */
                    $this->importAudio($asset, $collectionNode);
                    break;
                case Image::class:
                    /** @var Image $asset */
                    $this->importImage($asset, $collectionNode);
                    break;
            }
        }
    }

    /**
     * @param Document $asset
     * @param NodeInterface $parentNode
     * @return void
     */
    protected function importDocument(Document $asset, NodeInterface $parentNode) {
        $identifier = $asset->getIdentifier();
        $properties = [
            'title' => $asset->getTitle(),
        ];
        $resource = $asset->getResource();
        $node = $this->createOrUpdateNode($this->createContext(), $parentNode, $identifier, NodeTypes::DOCUMENT, $properties, $resource);
        $this->importThumbnails($asset, $node);
    }

    /**
     * @param Video $asset
     * @param NodeInterface $parentNode
     * @return void
     */
    protected function importVideo(Video $asset, NodeInterface $parentNode)
    {
        $identifier = $asset->getIdentifier();
        $properties = [
            'title' => $asset->getTitle(),
            'width' => $asset->getWidth(),
            'height' => $asset->getHeight(),
        ];
        $resource = $asset->getResource();
        $node = $this->createOrUpdateNode($this->createContext(), $parentNode, $identifier, NodeTypes::VIDEO, $properties, $resource);
        $this->importThumbnails($asset, $node);
    }

    /**
     * @param Audio $asset
     * @param NodeInterface $parentNode
     * @return void
     */
    protected function importAudio(Audio $asset, NodeInterface $parentNode) {
        $identifier = $asset->getIdentifier();
        $properties = [
            'title' => $asset->getTitle(),
        ];
        $resource = $asset->getResource();
        $node = $this->createOrUpdateNode($this->createContext(), $parentNode, $identifier, NodeTypes::AUDIO, $properties, $resource);
        $this->importThumbnails($asset, $node);
    }

    /**
     * @param Image $asset
     * @param NodeInterface $parentNode
     * @return void
     * @throws Exception
     */
    protected function importImage(Image $asset, NodeInterface $parentNode) {
        $identifier = $asset->getIdentifier();
        $properties = [
            'title' => $asset->getTitle(),
            'caption' => $asset->getCaption(),
        ];
        $resource = $asset->getResource();
        $node = $this->createOrUpdateNode($this->createContext(), $parentNode, $identifier, NodeTypes::IMAGE, $properties, $resource);
        $this->importThumbnails($asset, $node);
        $imageVariantStorage = $node->getNode('variants');
        /** @var ImageVariant $imageVariant */
        foreach ($asset->getVariants() as $imageVariant) {
            $identifier = $imageVariant->getIdentifier();
            $properties = [
                'name' => $imageVariant->getName(),
                'width' => $imageVariant->getWidth(),
                'height' => $imageVariant->getHeight()
            ];
            $resource = $imageVariant->getResource();
            $imageVariantNode = $this->createOrUpdateNode($this->createContext(), $imageVariantStorage, $identifier, NodeTypes::IMAGE_VARIANT, $properties, $resource);
            $this->importThumbnails($imageVariant, $imageVariantNode);
            /** @var AbstractAdjustment $adjustment */
            $adjustmentStorage = $imageVariantNode->getNode('adjustments');
            foreach ($imageVariant->getAdjustments() as $adjustment) {
                $identifier = $this->persistenceManager->getIdentifierByObject($adjustment);
                switch (get_class($adjustment)) {
                    case CropImageAdjustment::class:
                        /** @var CropImageAdjustment $adjustment */
                        $properties = [
                            'position' => $adjustment->getPosition(),
                            'x' => $adjustment->getX(),
                            'y' => $adjustment->getY(),
                            'width' => $adjustment->getWidth(),
                            'height' => $adjustment->getHeight(),
                        ];
                        $nodeType = NodeTypes::CROP_IMAGE_ADJUSTEMENT;
                        break;
                    case ResizeImageAdjustment::class:
                        /** @var ResizeImageAdjustment $adjustment */
                        $properties = [
                            'position' => $adjustment->getPosition(),
                            'width' => $adjustment->getWidth(),
                            'height' => $adjustment->getHeight(),
                            'maximumWidth' => $adjustment->getMaximumWidth(),
                            'minimumHeight' => $adjustment->getMinimumHeight(),
                            'ratioMode' => $adjustment->getRatioMode(),
                            'allowUpScaling' => $adjustment->getAllowUpScaling(),
                        ];
                        $nodeType = NodeTypes::RESIZE_IMAGE_ADJUSTEMENT;
                        break;
                    default:
                        throw new Exception(sprintf('Unsupported adjustment: %s', get_class($adjustment)), 1461220426);
                        break;
                }
                $this->createOrUpdateNode($this->createContext(), $adjustmentStorage, $identifier, $nodeType, $properties);
            }
        }
    }

    /**
     * @param AssetInterface $asset
     * @param NodeInterface $parentNode
     * @return array
     */
    protected function importThumbnails(AssetInterface $asset, NodeInterface $parentNode)
    {
        $storage = $parentNode->getNode('thumbnails');
        $nodes = [];
        $thumbnails = ObjectAccess::getProperty($asset, 'thumbnails', true);
        /** @var Thumbnail $thumbnail */
        foreach ($thumbnails as $thumbnail) {
            $identifier = $this->persistenceManager->getIdentifierByObject($thumbnail);
            $resource = $thumbnail->getResource();
            $properties = [
                'staticResource' => $thumbnail->getStaticResource(),
                'configuration' => ObjectAccess::getProperty($thumbnail, 'configuration', true),
                'configurationHash' => ObjectAccess::getProperty($thumbnail, 'configurationHash', true),
            ];
            $this->createOrUpdateNode($this->createContext(), $storage, $identifier, NodeTypes::THUMBNAIL, $properties, $resource);
        }
        return $nodes;
    }

    /**
     * @param ResourceContext $context
     * @param NodeInterface $parentNode
     * @param string $identifier
     * @param string $nodeType
     * @param array $properties
     * @param Resource $resource
     * @return NodeInterface
     */
    protected function createOrUpdateNode(ResourceContext $context, NodeInterface $parentNode, $identifier, $nodeType, array $properties, Resource $resource = null)
    {
        $node = $context->getNodeByIdentifier($identifier);
        if ($node === null) {
            $node = $parentNode->createNodeFromTemplate($this->createAssetNodeTemplate($identifier, $nodeType, $properties));
            if ($resource !== null) {
                $node->getNode('resource')->setContentObject($resource);
            }
        }
        return $node;
    }

    /**
     * @param string $identifier
     * @param string $nodeType
     * @param array $properties
     * @return NodeTemplate
     * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
     */
    protected function createAssetNodeTemplate($identifier, $nodeType, array $properties) {
        $template = new NodeTemplate();
        $template->setIdentifier($identifier);
        $template->setNodeType($this->nodeTypeManager->getNodeType($nodeType));
        foreach ($properties as $propertyName => $propertyValue) {
            $template->setProperty($propertyName, $propertyValue);
        }
        return $template;
    }

    /**
     * @return ResourceContext
     */
    protected function createContext()
    {
        return $this->contextFactory->create([
            'workspaceName' => 'live',
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ]);
    }

}
