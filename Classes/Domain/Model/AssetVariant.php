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

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Model\AssetVariantInterface;
use TYPO3\Media\Domain\Model\ImageInterface;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\TYPO3CR\Domain\Model\Node;

/**
 * Asset Variant Node
 *
 * @api
 */
class AssetVariant extends Asset implements AssetVariantInterface
{
    /**
     * @return AssetInterface
     */
    public function getOriginalAsset()
    {
        $query = new FlowQuery([$this]);
        return $query->closest('[instanceof Neos.ContentRepository.ResourceAdapter:Asset]')->get(0);
    }

}
