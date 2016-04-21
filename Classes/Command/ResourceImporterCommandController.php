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

use Cocur\Slugify\Slugify;
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
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\Audio;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Model\Tag;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Model\Video;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\TagRepository;
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
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

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
        $this->outputLine('<b>Create default storage "%s"</b>', [$storageName]);
        $this->outputLine();

        $context = $this->createContext();
        $rootNode = $context->getRootNode();

        $this->outputLine();
        $this->outputLine('  <b>Create asset storage</b>');
        $this->createAssetStorage($rootNode, $storageName);

        $this->outputLine();
        $this->outputLine('  <b>Create asset collection storage</b>');
        $this->createAssetCollectionStorage($rootNode, $storageName);

        $this->outputLine();
        $this->outputLine('  <b>Create tag storage</b>');
        $this->createTagStorage($rootNode, $storageName);
    }

    /**
     * @param NodeInterface $rootNode
     * @param string $storageName
     */
    protected function createAssetStorage(NodeInterface $rootNode, $storageName)
    {
        $baseNode = $rootNode->getNode('assets');
        if ($baseNode === null) {
            $template = new NodeTemplate();
            $template->setName('assets');
            $baseNode = $rootNode->createNodeFromTemplate($template);
        }
        $assetStorage = $baseNode->getNode($storageName);
        if ($assetStorage === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::ASSET_STORAGE));
            $template->setName($storageName);
            $assetStorage = $baseNode->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> collection storage created');
        } else {
            $this->outputLine('  <comment>~~</comment> collection storage exists');
        }
        $collection = $assetStorage->getNode('persistent');
        if ($collection === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::COLLECTION));
            $template->setName('persistent');
            $assetStorage->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> persistent collection created');
        } else {
            $this->outputLine('  <comment>~~</comment> persistent collection exists');
        }
        $collection = $assetStorage->getNode('static');
        if ($collection === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::COLLECTION));
            $template->setName('static');
            $assetStorage->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> static collection created');
        } else {
            $this->outputLine('  <comment>~~</comment> static collection exists');
        }
    }

    /**
     * @param NodeInterface $rootNode
     * @param string $storageName
     */
    protected function createAssetCollectionStorage(NodeInterface $rootNode, $storageName)
    {
        $baseNode = $rootNode->getNode('assetcollections');
        if ($baseNode === null) {
            $template = new NodeTemplate();
            $template->setName('assetcollections');
            $baseNode = $rootNode->createNodeFromTemplate($template);
        }
        $collectionGroupNode = $baseNode->getNode($storageName);
        if ($collectionGroupNode === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::ASSETCOLLECTION_STORAGE));
            $template->setName($storageName);
            $collectionGroupNode = $baseNode->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> Asset Storage created');
        } else {
            $this->outputLine('  <comment>~~</comment> Asset Storage exists');
        }
    }

    /**
     * @param NodeInterface $rootNode
     * @param string $storageName
     */
    protected function createTagStorage(NodeInterface $rootNode, $storageName)
    {
        $baseNode = $rootNode->getNode('tags');
        if ($baseNode === null) {
            $template = new NodeTemplate();
            $template->setName('tags');
            $baseNode = $rootNode->createNodeFromTemplate($template);
        }
        $collectionGroupNode = $baseNode->getNode($storageName);
        if ($collectionGroupNode === null) {
            $template = new NodeTemplate();
            $template->setNodeType($this->nodeTypeManager->getNodeType(NodeTypes::TAG_STORAGE));
            $template->setName($storageName);
            $collectionGroupNode = $baseNode->createNodeFromTemplate($template);
            $this->outputLine('  <info>++</info> Tag Storage created');
        } else {
            $this->outputLine('  <comment>~~</comment> Tag Storage exists');
        }
    }

    /**
     * Flush the content of the content repository
     *
     * @param string $storageName
     */
    public function flushCommand($storageName)
    {
        $this->outputLine();
        $this->outputLine('<b>Flush all resources to path "%s"</b>', [$storageName]);
        $this->outputLine();
        $context = $this->createContext();
        $rootNode = $context->getRootNode();
        $assets = $rootNode->getNode('assets/' . $storageName);
        if ($assets !== null) {
            $this->outputLine('  <info>--</info> Flush assets');
            $assets->remove();
        }
        $tags = $rootNode->getNode('tags/' . $storageName);
        if ($tags !== null) {
            $this->outputLine('  <info>--</info> Flush tags');
            $tags->remove();
        }
        $assetCollections = $rootNode->getNode('assetcollections/' . $storageName);
        if ($tags !== null) {
            $this->outputLine('  <info>--</info> Flush asset collections');
            $assetCollections->remove();
        }
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
        $storage = $rootNode->getNode('assets/' . $storageName . '/persistent');
        if ($storage === null) {
            $this->outputLine('  <error>!!</error> assets storage not found');
            $this->sendAndExit(1);
        }
        $this->outputLine('  <b>Import assets ...</b>');
        $this->importAssets($storage);

        $storage = $rootNode->getNode('tags/' . $storageName);
        if ($storage === null) {
            $this->outputLine('  <error>!!</error> tags Storage not found');
            $this->sendAndExit(1);
        }
        $this->outputLine('  <b>Import tags ...</b>');
        $this->importTags($storage);

        $storage = $rootNode->getNode('assetcollections/' . $storageName);
        if ($storage === null) {
            $this->outputLine('  <error>!!</error> asset collection storage not found');
            $this->sendAndExit(1);
        }
        $this->outputLine('  <b>Import asset collections ...</b>');
        $this->importAssetCollections($storage);

    }

    /**
     * @param NodeInterface $storage
     * @throws Exception
     */
    protected function importTags(NodeInterface $storage)
    {
        $slugify = new Slugify();
        /** @var Tag $tag */
        foreach ($this->tagRepository->findAll() as $tag) {
            $identifier = $this->persistenceManager->getIdentifierByObject($tag);
            $name = $slugify->slugify($tag->getLabel());
            $properties = [
                'label' => $tag->getLabel(),
            ];
            $this->createOrUpdateNode($this->createContext(), $storage, $identifier, NodeTypes::TAG, $properties, null, $name);
        }
    }

    /**
     * @param NodeInterface $storage
     * @throws Exception
     */
    protected function importAssetCollections(NodeInterface $storage)
    {
        $slugify = new Slugify();
        /** @var AssetCollection $assetCollection */
        foreach ($this->assetCollectionRepository->findAll() as $assetCollection) {
            $identifier = $this->persistenceManager->getIdentifierByObject($assetCollection);
            $name = $slugify->slugify($assetCollection->getTitle());
            $properties = [
                'title' => $assetCollection->getTitle(),
            ];
            $this->createOrUpdateNode($this->createContext(), $storage, $identifier, NodeTypes::TAG, $properties, null, $name);
        }
    }

    /**
     * @param NodeInterface $storage
     * @throws Exception
     */
    protected function importAssets(NodeInterface $storage)
    {
        $iterator = $this->assetRepository->findAllIterator();
        /** @var AssetInterface $asset */
        foreach ($this->assetRepository->iterate($iterator) as $asset) {
            switch (get_class($asset)) {
                case Document::class:
                    /** @var Document $asset */
                    $this->importDocument($asset, $storage);
                    break;
                case Video::class:
                    /** @var Video $asset */
                    $this->importVideo($asset, $storage);
                    break;
                case Audio::class:
                    /** @var Audio $asset */
                    $this->importAudio($asset, $storage);
                    break;
                case Image::class:
                    /** @var Image $asset */
                    $this->importImage($asset, $storage);
                    break;
            }
        }
    }

    /**
     * @param Document $asset
     * @param NodeInterface $parentNode
     * @return void
     */
    protected function importDocument(Document $asset, NodeInterface $parentNode)
    {
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
    protected function importAudio(Audio $asset, NodeInterface $parentNode)
    {
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
    protected function importImage(Image $asset, NodeInterface $parentNode)
    {
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
     * @param string $name
     * @return NodeInterface
     */
    protected function createOrUpdateNode(ResourceContext $context, NodeInterface $parentNode, $identifier, $nodeType, array $properties, Resource $resource = null, $name = null)
    {
        $node = $context->getNodeByIdentifier($identifier);
        if ($node === null) {
            $node = $parentNode->createNodeFromTemplate($this->createAssetNodeTemplate($identifier, $nodeType, $properties));
            if ($name !== null) {
                $node->setName($name);
            }
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
    protected function createAssetNodeTemplate($identifier, $nodeType, array $properties)
    {
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
