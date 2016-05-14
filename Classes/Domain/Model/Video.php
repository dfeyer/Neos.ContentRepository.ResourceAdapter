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

/**
 * Video
 */
class Video extends Asset
{
    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->getProperty('width');
    }

    /**
     * @return integer
     */
    public function getHeight()
    {
        return $this->getProperty('height');
    }
}
