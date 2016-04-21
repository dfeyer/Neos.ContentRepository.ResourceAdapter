<?php
namespace Neos\ContentRepository\ResourceAdapter\Domain\Model;

/*
 * This file is part of the Neos.ContentRepository.ResourceAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\TYPO3CR\Domain\Model\Node;

/**
 * Asset Node
 *
 * @api
 */
class AssetNode extends Node implements AssetInterface
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getProperty('title');
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->setProperty('title', $title);
    }

    /**
     * @param Resource $resource
     * @return void
     */
    public function setResource(Resource $resource)
    {
        $this->setContentObject($resource);
    }

    /**
     * @return Resource
     */
    public function getResource()
    {
        return $this->getResource();
    }

    /**
     * @return string
     */
    public function getMediaType()
    {
        return $this->getResource() ? $this->getResource()->getMediaType() : null;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return $this->getResource() ? $this->getResource()->getFileExtension() : null;
    }

    /**
     * @param integer $maximumWidth
     * @param integer $maximumHeight
     * @param string $ratioMode
     * @param boolean $allowUpScaling
     * @return void
     */
    public function getThumbnail($maximumWidth = null, $maximumHeight = null, $ratioMode = ImageInterface::RATIOMODE_INSET, $allowUpScaling = null)
    {

    }

    /**
     * @param Thumbnail $thumbnail
     * @return void
     */
    public function addThumbnail(Thumbnail $thumbnail)
    {

    }

    /**
     * @return void
     */
    public function refresh()
    {

    }


}
