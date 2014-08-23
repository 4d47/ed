<?php
namespace Ed;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\AssetInterface;

class AssetsResource extends Base
{
    public static $path = '/assets/*';
    public $rest;
    public $asset;

    public function get()
    {
        switch ($this->rest) {
        case 'js':
            $this->asset = $this->createAssetCollection(array(
                'jquery-2.0.3.min.js',
                'bootstrap.min.js',
                'bootstrap-datetimepicker.min.js',
                'bootstrap-timepicker.min.js',
                'bootstrap-datepicker.js',
                'select2.min.js',
                'bootbox.min.js',
                'main.js',
            ));
	    break;
        case 'css':
            $this->asset = $this->createAssetCollection(array(
                'datepicker.css',
                'bootstrap-datetimepicker.min.css',
                'bootstrap-timepicker.min.css',
                'select2.css',
                'main.css'
            ));
	    break;
        default:
            // Any other file in the assets directory
            // eg. images used in css, etc
            $source = realpath(__DIR__ . "/assets/$this->rest");
            if (strpos($source, __DIR__ . '/assets/') !== 0) {
                throw new \Http\BadRequest();
            }
            if (!file_exists($source)) {
                throw new \Http\NotFound();
            }
            $this->asset = new FileAsset($source);
        }
    }

    public function render()
    {
        header('Content-Type:');
            // Removes Content-Type header and let
            // the browser deal with it by context
        echo $this->asset->dump();
    }

    /**
     * Creates a new AssetCollection from a list of $assets
     * names relative to the assets directory.
     *
     * @param array $assets
     * @return AssetCollection
     */
    public function createAssetCollection(array $assets)
    {
        return new AssetCollection(array_map(
            function ($asset) {
                return new FileAsset(__DIR__ . "/assets/$asset");
            },
            $assets
        ));
    }
}
