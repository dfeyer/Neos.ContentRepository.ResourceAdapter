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
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\VariantSupportInterface;

/**
 * Image
 */
class Image extends Asset implements ImageInterface, VariantSupportInterface
{
    public function getWidth()
    {
        return $this->getProperty('width');
    }

    public function getHeight()
    {
        return $this->getProperty('height');
    }

    public function getAspectRatio($respectOrientation = false)
    {
        return $this->getProperty('aspectRatio');
    }

    public function getOrientation()
    {
        // TODO: Implement getOrientation() method.
    }

    public function isOrientationSquare()
    {
        // TODO: Implement isOrientationSquare() method.
    }

    public function isOrientationLandscape()
    {
        // TODO: Implement isOrientationLandscape() method.
    }

    public function isOrientationPortrait()
    {
        // TODO: Implement isOrientationPortrait() method.
    }

    public function getResource()
    {
        // TODO: Implement getResource() method.
    }

    public function refresh()
    {
        // TODO: Implement refresh() method.
    }

    public function getVariants()
    {
        // TODO: Implement getVariants() method.
    }

}
