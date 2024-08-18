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
 * ExtendedImportExport (webtrees custom module):
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
 * ExtendedImportExport
 *
 * A weebtrees(https://webtrees.net) 2.1 custom module for advanced GEDCOM import, export
 * and filter operations. The module also supports remote downloads/uploads via URL requests.
 * 
 */
 

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\ExtendedImportExport;

use Composer\Autoload\ClassLoader;

require __DIR__ . '/ConvertGedcomPage.php';
require __DIR__ . '/DownloadGedcomWithURL.php';
require __DIR__ . '/DownloadGedcomWithUrlException.php';
require __DIR__ . '/GedcomFilterInterface.php';
require __DIR__ . '/AbstractGedcomFilter.php';
require __DIR__ . '/ExportGedcomPage.php';
require __DIR__ . '/FilteredGedcomExportService.php';
require __DIR__ . '/ImportGedcomPage.php';
require __DIR__ . '/Record.php';
require __DIR__ . '/SelectionPage.php';

$loader = new ClassLoader();
$loader->addPsr4('Cissee\\WebtreesExt\\', __DIR__ . "/vendor/vesta-webtrees-2-custom-modules/vesta_common/patchedWebtrees");
$loader->register();

return new DownloadGedcomWithURL();
