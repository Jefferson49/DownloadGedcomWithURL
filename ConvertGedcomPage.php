<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2024 webtrees development team
 *                    <http://webtrees.net>
 *
 * Fancy Research Links (webtrees custom module):
 * Copyright (C) 2022 Carmen Just
 *                    <https://justcarmen.nl>
 *
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
 *
 * 
 * DownloadGedcomWithURL
 *
 * A weebtrees(https://webtrees.net) 2.1 custom module to download or store GEDCOM files on URL requests 
 * with the tree name, GEDCOM file name and authorization provided as parameters within the URL.
 * 
 */

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\DownloadGedcomWithURL;

use Fisharebest\Webtrees\Encodings\UTF8;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\ViewResponseTrait;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\AdminService;
use Fisharebest\Webtrees\Services\GedcomImportService;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Validator;
use Jefferson49\Webtrees\Module\DownloadGedcomWithURL\DownloadGedcomWithURL;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function e;

/**
 * Convert a GEDCOM file
 */
class ConvertGedcomPage implements RequestHandlerInterface
{
    use ViewResponseTrait;

    private AdminService $admin_service;

    /**
     * @param AdminService $admin_service
     */
    public function __construct(AdminService $admin_service)
    {
        $this->admin_service = $admin_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $tree_name               = Validator::queryParams($request)->string('tree_name');
        $default_gedcom_filter1  = Validator::queryParams($request)->string('default_gedcom_filter1', I18N::translate('None'));
        $default_gedcom_filter2  = Validator::queryParams($request)->string('default_gedcom_filter2', I18N::translate('None'));
        $default_gedcom_filter2  = Validator::queryParams($request)->string('default_gedcom_filter3', I18N::translate('None'));

        $tree_service = new TreeService(new GedcomImportService());
        $tree = $tree_service->all()[$tree_name];

        $module_service = new ModuleService();
        $download_gedcom_with_url = $module_service->findByName(DownloadGedcomWithURL::activeModuleName());

        $folder          = $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_FOLDER_TO_SAVE, '');
        $data_filesystem = Registry::filesystem()->root($folder);
        $gedcom_files    = $this->admin_service->gedcomFiles($data_filesystem);

        //Load export filters
        try {
            DownloadGedcomWithURL::loadGedcomFilterClasses();
        }
        catch (DownloadGedcomWithUrlException $ex) {
            FlashMessages::addMessage($ex->getMessage(), 'danger');
        }       

        $gedcom_filter_list = $download_gedcom_with_url->getGedcomFilterList();
        $tree_list = $download_gedcom_with_url->getTreeNameTitleList();
        $control_panel_secret_key= $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_CONTROL_PANEL_SECRET_KEY, '');


        return $this->viewResponse(
            DownloadGedcomWithURL::viewsNamespace() . '::convert',
            [
                'title'                    => I18N::translate('GEDCOM Conversion'),
                'control_panel_secret_key' => $control_panel_secret_key,
                'tree'                     => $tree,
                'tree_list'                => $tree_list,
                'folder'                   => $folder,
                'gedcom_files'             => $gedcom_files,
                'zip_available'            => extension_loaded('zip'),
                'default_format'           => $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_EXPORT_FORMAT, 'gedcom'),
                'default_encoding'         => $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_ENCODING,  UTF8::NAME),
                'default_endings'          => $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_ENDING, 'CRLF'),
                'default_privacy'          => $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_PRIVACY_LEVEL, 'visitor'),
                'default_time_stamp'       => $download_gedcom_with_url->getPreference(DownloadGedcomWithURL::PREF_DEFAULT_TIME_STAMP, DownloadGedcomWithURL::TIME_STAMP_NONE),
                'gedcom_filter_list'       => $gedcom_filter_list,
                'default_gedcom_filter1'   => $default_gedcom_filter1,
                'default_gedcom_filter2'   => $default_gedcom_filter2,
                'default_gedcom_filter3'   => $default_gedcom_filter2,
            ]
        );
    }
}
