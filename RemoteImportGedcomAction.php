<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2024 webtrees development team
 *                    <http://webtrees.net>

 * DownloadGedcomWithURL (webtrees custom module):
 * Copyright (C) 2024 Markus Hemprich
 *                    <http://www.familienforschung-hemprich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\DownloadGedcomWithURL;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\RequestHandlers\ManageTrees;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Services\GedcomImportService;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToReadFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Throwable;

/**
 * Remotely import a GEDCOM file into a tree.
 */
class RemoteImportGedcomAction implements RequestHandlerInterface
{
    private StreamFactoryInterface $stream_factory;

    private TreeService $tree_service;

    private ModuleService $module_service;

    /**
     * @param StreamFactoryInterface $stream_factory
     * @param TreeService            $tree_service
     */
    public function __construct()
    {
        $this->tree_service   = new TreeService(new GedcomImportService);
        $this->stream_factory = new Psr17Factory();
        $this->module_service = new ModuleService();
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws FilesystemException
     * @throws UnableToReadFile
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $download_gedcom_with_URL = $this->module_service->findByName(DownloadGedcomWithURL::activeModuleName());
        $encoding = 'UTF-8';

        $key                  = Validator::queryParams($request)->string('key', ''); 
        $control_panel_token  = Validator::queryParams($request)->string('control_panel_token', '');        

        //Check preferences if upload is allowed
        $allow_upload         = boolval($download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_ALLOW_UPLOAD, '0'));

        //An upload from the control panel is allowed if a valid token is submitted
        $allow_control_panel_upload = $control_panel_token === md5($download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_SECRET_KEY, '') . Session::getCsrfToken()) ?? true;
        
		//Load secret key from preferences
        $secret_key           = $download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_SECRET_KEY, ''); 

        //If upload from control panel
        if ($control_panel_token !== '') {
            $tree_name        = Validator::queryParams($request)->string('tree', $download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_TREE_NAME, ''));
            $file_name        = Validator::queryParams($request)->string('file',  $download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_FiLE_NAME, ''));
        }
        //Otherwise treat as remote upload called with URL
        else {
            try {           
                $tree_name    = Validator::queryParams($request)->string('tree');
                $file_name    = Validator::queryParams($request)->string('file');
            }
            catch (Throwable $ex) {
                $message = I18N::translate('One of the parameters "file, tree" is missing in the called URL.');
                return $download_gedcom_with_URL->showErrorMessage($message);
            }    
        }

        //Error if upload is not allowed
        if (!$allow_upload) {
			return $download_gedcom_with_URL->showErrorMessage(I18N::translate('Upload is not enabled. Please check the module settings in the control panel.'));
		}
        //Error if key is empty
        if ($key === '') {
			return $download_gedcom_with_URL->showErrorMessage(I18N::translate('No key provided. For checking of the access rights, it is mandatory to provide a key as parameter in the URL.'));
		}
		//Error if secret key is empty
        elseif ($secret_key === '') {
			return $download_gedcom_with_URL->showErrorMessage(I18N::translate('No secret key defined. Please define secret key in the module settings: Control Panel / Modules / All Modules / ') . $download_gedcom_with_URL->title());
		}
		//Error if no hashing and key is not valid
        elseif (!boolval($download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_USE_HASH, '0')) && !$allow_control_panel_upload && ($key !== $secret_key)) {
			return $download_gedcom_with_URL->showErrorMessage(I18N::translate('Key not accepted. Access denied.'));
		}
		//Error if hashing and key does not fit to hash
        elseif (boolval($download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_USE_HASH, '0')) && !$allow_control_panel_upload && (!password_verify($key, $secret_key))) {
			return $download_gedcom_with_URL->showErrorMessage(I18N::translate('Key (encrypted) not accepted. Access denied.'));
		}
   
        //Get tree and error if tree name is not valid
        try {
            $tree = $this->tree_service->all()[$tree_name];
            assert($tree instanceof Tree);
        }
        catch (Throwable $ex) {
            $message = I18N::translate('Could not find the requested tree "%s".', $tree_name);
            return $download_gedcom_with_URL->showErrorMessage($message);
        }        

        //Get folder from module settings, create server file name, and read from file
        $folder = $download_gedcom_with_URL->getPreference(DownloadGedcomWithURL::PREF_FOLDER_TO_SAVE, '');
        $root_filesystem = Registry::filesystem()->root();
        $server_file = $folder . $file_name . '.ged';

        try {
            $resource = $root_filesystem->readStream($server_file);
        }
        catch (Throwable $ex) {
            $message = I18N::translate('Unable to read file "%s".', $server_file);
            return $download_gedcom_with_URL->showErrorMessage($message);
        }        

        //Import the Gedcom from file
        try {
            $stream   = $this->stream_factory->createStreamFromResource($resource);
            $this->tree_service->importGedcomFile($tree, $stream, $server_file, $encoding);

            $message = I18N::translate('The file "%s" was sucessfully uploaded for the family tree "%s"', $file_name . '.ged', $tree->name());
            FlashMessages::addMessage($message, 'success');
        }
        catch (Throwable $ex) {
            return $download_gedcom_with_URL->showErrorMessage($ex->getMessage());
        }        

        //Redirect in order to process the Gedcom data of the imported file
        return redirect(route(ManageTrees::class, ['tree' => $tree->name()]));        
    }
}
