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
 * The Content Context
 *
 * @api
 */
class NodeTypes
{
    const TAG = 'Neos.ContentRepository.ResourceAdapter:Tag';
    
    const TAG_STORAGE = 'Neos.ContentRepository.ResourceAdapter:TagStorage';
    const ASSET_STORAGE = 'Neos.ContentRepository.ResourceAdapter:AssetStorage';
    const ASSETCOLLECTION_STORAGE = 'Neos.ContentRepository.ResourceAdapter:AssetCollectionStorage';

    const COLLECTION = 'Neos.ContentRepository.ResourceAdapter:Collection';

    const ASSET_COLLECTION = 'Neos.ContentRepository.ResourceAdapter:AssetCollection';

    const DOCUMENT = 'Neos.ContentRepository.ResourceAdapter:Document';

    const AUDIO = 'Neos.ContentRepository.ResourceAdapter:Audio';

    const VIDEO = 'Neos.ContentRepository.ResourceAdapter:Video';

    const IMAGE = 'Neos.ContentRepository.ResourceAdapter:Image';

    const THUMBNAIL = 'Neos.ContentRepository.ResourceAdapter:Thumbnail';

    const IMAGE_VARIANT = 'Neos.ContentRepository.ResourceAdapter:ImageVariant';

    const CROP_IMAGE_ADJUSTEMENT = 'Neos.ContentRepository.ResourceAdapter:CropImageAdjustment';

    const RESIZE_IMAGE_ADJUSTEMENT = 'Neos.ContentRepository.ResourceAdapter:ResizeImageAdjustment';
}
