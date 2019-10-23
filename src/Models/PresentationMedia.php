<?php

namespace XD\Ovis\Models;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\Security\Security;
use XD\Ovis\Ovis;

/**
 * Class PresentationMedia
 *
 * @author Bram de Leeuw
 * @package XD\Ovis\Models
 *
 * @property string OriginalURL
 * @property boolean Default
 * @property int Sort
 *
 * @method Presentation Presentation
 */
class PresentationMedia extends Image
{
    private static $table_name = 'Ovis_PresentationMedia';

    private static $singular_name = 'Media';

    private static $plural_name = 'Media';

    private static $db = [
        'OriginalURL' => 'Varchar',
        'Default' => 'Boolean',
        'Sort' => 'Int'
    ];

    private static $default_sort = 'Sort ASC';

    private static $has_one = array(
        'Presentation' => Presentation::class
    );

    private static $summary_fields = [
        'CMSThumbnail' => 'Thumbnail',
        'Name',
        'Title',
        'Default.Nice' => 'Default'
    ];


    /**
     * Download the image
     *
     * @param string $imageSource
     * @param string $fileName
     * @param Folder $folder
     * @throws Exception
     * @throws GuzzleException
     */
    public function downloadImageTo($imageSource, $fileName, Folder $folder)
    {
        $client = Ovis::mediaClient();
        $request = $client->request('GET', $imageSource);
        $stream = $request->getBody();
        if ($stream->isReadable()) {
            $this->setFromStream($stream->detach(), $fileName);
            $this->ParentID = $folder->ID;
            $this->OwnerID = ($user = Security::getCurrentUser()) ? $user->ID : 0;
        } else {
            throw new Exception("Error while downloading file: $imageSource");
        }
    }

    /**
     * Generate thumbnails for use in the CMS
     */
    public function generateThumbnails()
    {
        $assetAdmin = AssetAdmin::singleton();
        $this->FitMax(
            $assetAdmin->config()->get('thumbnail_width'),
            $assetAdmin->config()->get('thumbnail_height')
        );
        $this->FitMax(
            UploadField::config()->uninherited('thumbnail_width'),
            UploadField::config()->uninherited('thumbnail_height')
        );
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        $this->doArchive();
        $this->deleteFile();
    }
}
