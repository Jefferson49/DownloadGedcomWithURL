<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2023 webtrees development team
 *                    <http://webtrees.net>

 * DownloadGedcomWithURL (webtrees custom module):
 * Copyright (C) 2023 Markus Hemprich
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

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Encodings\UTF16BE;
use Fisharebest\Webtrees\Encodings\UTF16LE;
use Fisharebest\Webtrees\Encodings\UTF8;
use Fisharebest\Webtrees\Encodings\Windows1252;
use Fisharebest\Webtrees\Factories\AbstractGedcomRecordFactory;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\GedcomFilters\GedcomEncodingFilter;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Header;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

use function addcslashes;
use function date;
use function explode;
use function fclose;
use function fopen;
use function fwrite;
use function is_string;
use function pathinfo;
use function preg_match_all;
use function preg_replace;
use function rewind;
use function stream_filter_append;
use function stream_get_meta_data;
use function strlen;
use function strpos;
use function strtolower;
use function strtoupper;
use function tmpfile;

use const PATHINFO_EXTENSION;
use const PREG_SET_ORDER;
use const STREAM_FILTER_WRITE;

/**
 * Export data in GEDCOM format
 */
class GedcomSevenExportService
{
    //Custom tags and schema definitions
    private const SCHEMAS = [

        //Collection of known custom tags
        //Source: https://wiki.genealogy.net/GEDCOM/_Nutzerdef-Tag#Tabelle_1
        ['https://wiki.genealogy.net/GEDCOM/_Nutzerdef-Tag#Tabelle_1' =>
            [
                '_ABBR', '_ADPF', '_ADPM', '_ADPN', '_AHNNR', '_AIDN', '_AKA', '_AKAN', '_ALIA', '_ALTPATH', '_AON', '_APID', '_ASSO', '_AUTO', '_BIRN', 
                '_BRTM', '_BKM', '_BUCH', '_BUERGERORT', '_CALL', '_CDATE', '_CENN', '_CIRC', '_COML', '_CONF_FLAG', '_COR', '_CORR', '_CRE', '_CREAT',
                '_Creat', '_CTYP', '_CURN', '_CUTOUT', '_DATE', '_DATE_TYPE', '_DATE2', '_DCAUSE', '_DEFN', '_DEG', '_DEP', '_DETS', '_DIVERSES', '_DMGD',
                '_DNA', '_ELEC', '_EMAIL', '_EMPLOY', '_EVENT_DEFN', '_EVID', '_EVN', '_EXCM', '_EXPORTED_FROM_SITE_ID', '_EYEC', '_EYES', '_FARN', '_FA1',
                '_FCTRY', '_FID', '_FILESIZE', '_FKAN', '_FNRL', '_FOKOID', '_FOOT', '_FPOST', '_FREL', '_FRKA', '_FSFTID', '_FSTAE', '_FUN', '_GERN',
                '_GODF', '_GODP', '_GODT', '_GOV', '_GOVTYPE', '_GRUPPE', '_HAIR', '_HEBN', '_HEIG', '_HEIM', '_HEIRATNAME', '_HME', '_HNM', '_HOL', '_HOME',
                '_HUSB', '_IMPF', '_INDG', '_INDN', '_INET', '_INFO', '_INTE', '_ITALIC', '_JAG', '_JUST', '_KTIT', '_LAD ', '_LAM ', '_LAS ', '_LAN', '_LEBENSORT',
                '_LINK', '_LIV', '_LNCH', '_LOC', '_LOD ', '_LOM ', '_LOS ', '_LON', '_MAIDENHEAD', '_MARI', '_MARN', '_MARNM', '_MARR', '_MARRNAME', 
                '_MARRNAMEHUSB', '_MARRNAMEWIFE', '_MASTER', '_MBON', '_MDCL', '_MEDC', '_MEDI', '_MEND', '_MHRM', '_MHSM', '_MHAV', '_MILI', '_MILT', '_MILTID',
                '_MISN', '_MREL', '_MREL', '_MSTAT', '_NAM', '_NAMC', '_NAME', '_NAMM', '_NAMS', '_NAMW', '_NAVI', '_NAVM', '_NCHI', '_NEW', '_NLIV', '_NMAR',
                '_NMR', '_NONE', '_NONE', '_NOTH', '_NR', '_ORGSOUR', '_ORI', '_OTHN', '_OVER', '_PAREN', '_PEI', '_PERC', '_PHOM', '_PHOTO', '_PHOTO_RIN',
                '_PLAC', '_PLAC_DEFN', '_PLACE_TYPE', '_PLACE', '_PLACTODAY', '_PMOB', '_POSITION', '_POST', '_POST', '_PREF', '_PREP', '_PRI', '_PRIM',
                '_PRIM', '_PRIMARY', '_PRIM_CUTOUT', '_PRIO', '_PRIV', '_PRMN', '_PROJECT_GUID', '_QUAL', '_QUAY', '_QUOTED', '_RDATE', '_REC', '_REL', '_RELN',
                '_RINS', '_RTLSAVE', '_RUFNAME', '_RUID', '_SCBK', '_SCHA', '_SCHEMA', '_SDATE', '_SENDOF', '_SENDOM', '_SENDOU', '_SENDPF', '_SENDPM', '_SENDPU',
                '_SENF', '_SENM', '_SENPOF', '_SENPOM', '_SENPOU', '_SENU', '_SEPR', '_SHON', '_SIC', '_SIGN', '_SLDN', '_SM_MERGES', '_SOUND', '_SOUR', '_SSHOW',
                '_STAT', '_STP', '_STYLE', '_SUBM', '_SURN', '_TASK', '_TODO', '_TXT', '_TYPE', '_TYPE', '_UID', '_UNKN', '_UPD', '_URL', '_URKU', '_VERI',
                '_WEIG', '_WGFM', '_WIFE', '_WITN', '_WT_USER', '_WT_OBJE_SORT', '_WTN', '_YART', '_ZUS', '_ZVST',
            ],
        ],

        //webtrees
        ['https://www.webtrees.net/' =>
            [
                '_WT_USER',
            ],
        ],        
    ];

    //GEDCOM-L custom tags and schema definitions
    private const GEDCOM_L_SCHEMAS = [
        
        //GEDCOM-L Addendum, R2
        ['https://genealogy.net/GEDCOM/' =>
            [
                '_ASSO', '_CAT', '_CDATE', '_GODP', '_GOV', '_GOVTYPE', '_LOC', '_NAME', '_POST', '_PRIM', '_RDATE', '_RUFNAME', '_SCHEMA', '_STAT', '_TODO',
                '_UID', '_WITN',
            ],
        ],
    ];

    public const ACCESS_LEVELS = [
        'gedadmin' => Auth::PRIV_NONE,
        'user'     => Auth::PRIV_USER,
        'visitor'  => Auth::PRIV_PRIVATE,
        'none'     => Auth::PRIV_HIDE,
    ];

    private ResponseFactoryInterface $response_factory;

    private StreamFactoryInterface $stream_factory;

	private array $language_to_code_table;

    //List of schemas which ware used for the export
    private array $schemas;

    //List of custom tags, which were found in the GEDCOM data
    private array $custom_tags_found;


    /**
     * @param ResponseFactoryInterface $response_factory
     * @param StreamFactoryInterface   $stream_factory
     */
	public function __construct(ResponseFactoryInterface $response_factory, StreamFactoryInterface $stream_factory)
	{
		$this->response_factory = $response_factory;
		$this->stream_factory   = $stream_factory;
        $this->custom_tags_found = [];
        $this->schemas = [];
        
		$iana_language_registry_file_name = __DIR__ . '/vendor/iana/iana_languages.txt';

		$iana_language_registry = file_get_contents($iana_language_registry_file_name);

		//Create language table
		preg_match_all("/Type: language\nSubtag: ([^\n]+)\nDescription: ([^\n]+)\n/", $iana_language_registry, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$this->language_to_code_table[strtoupper($match[2])]= $match[1];
		}
	}

    /**
     * @param Tree            $tree         - Export data from this tree
     * @param bool            $sort_by_xref - Write GEDCOM records in XREF order
     * @param string          $encoding     - Convert from UTF-8 to other encoding
     * @param string          $privacy      - Filter records by role
     * @param string          $line_endings
     * @param string          $filename     - Name of download file, without an extension
     * @param string          $format       - One of: gedcom, zip, zipmedia, gedzip
	 * @param bool            $gedcom_l     - Whether export should consider GEDCOM-L
     * @param Collection|null $records
     *
     * @return ResponseInterface
     */
    public function downloadGedcomSevenResponse(
        Tree $tree,
        bool $sort_by_xref,
        string $encoding,
        string $privacy,
        string $line_endings,
        string $filename,  //without .ged
        string $format,
		bool $gedcom_l = false,
        Collection $records = null
    ): ResponseInterface {
        $access_level = self::ACCESS_LEVELS[$privacy];

        //Create schema list
        $this->schemas = [];
        $this->addToSchemas(self::SCHEMAS);
        if($gedcom_l) {
            $this->addToSchemas(self::GEDCOM_L_SCHEMAS);
        }

        //First, check custom tags only => flag $check_custom_tags = true
        $this->export($tree, $sort_by_xref, $encoding, $access_level, $line_endings, $gedcom_l, true, $records);

        if ($format === 'gedcom') {
            $resource = $this->export($tree, $sort_by_xref, $encoding, $access_level, $line_endings, $gedcom_l, false, $records);
            $stream   = $this->stream_factory->createStreamFromResource($resource);

            return $this->response_factory->createResponse()
                ->withBody($stream)
                ->withHeader('content-type', 'text/x-gedcom; charset=' . UTF8::NAME)
                ->withHeader('content-disposition', 'attachment; filename="' . addcslashes($filename, '"') . '.ged"');
        }

        // Create a new/empty .ZIP file
        $temp_zip_file  = stream_get_meta_data(tmpfile())['uri'];
        $zip_provider   = new FilesystemZipArchiveProvider($temp_zip_file, 0755);
        $zip_adapter    = new ZipArchiveAdapter($zip_provider);
        $zip_filesystem = new Filesystem($zip_adapter);

        if ($format === 'zipmedia') {
            $media_path = $tree->getPreference('MEDIA_DIRECTORY');
        } elseif ($format === 'gedzip') {
            $media_path = '';
        } else {
            // Don't add media
            $media_path = null;
        }

        $resource = $this->export($tree, $sort_by_xref, $encoding, $access_level, $line_endings, $gedcom_l, false, $records, $zip_filesystem, $media_path);

        if ($format === 'gedzip') {
            $zip_filesystem->writeStream('gedcom.ged', $resource);
            $extension = '.gdz';
        } else {
            $zip_filesystem->writeStream($filename . '.ged', $resource);
            $extension = '.zip';
        }

        fclose($resource);

        $stream = $this->stream_factory->createStreamFromFile($temp_zip_file);

        return $this->response_factory->createResponse()
            ->withBody($stream)
            ->withHeader('content-type', 'application/zip')
            ->withHeader('content-disposition', 'attachment; filename="' . addcslashes($filename, '"') . $extension . '"');
    }

    /**
     * Write GEDCOM data to a stream.
     *
     * @param Tree                        $tree              - Export data from this tree
     * @param bool                        $sort_by_xref      - Write GEDCOM records in XREF order
     * @param string                      $encoding          - Convert from UTF-8 to other encoding
     * @param int                         $access_level      - Apply privacy filtering
     * @param string                      $line_endings      - CRLF or LF
	 * @param bool                        $gedcom_l          - Whether export should consider GEDCOM-L
     * @param bool                        $check_custom_tags - Just check custom tags; do not create a stream
     * @param Collection<int,string>|null $records           - Just export these records
     * @param FilesystemOperator|null     $zip_filesystem    - Write media files to this filesystem
     * @param string|null                 $media_path        - Location within the zip filesystem
     *
     * @return ?resource
     */
    public function export(
        Tree $tree,
        bool $sort_by_xref = false,
        string $encoding = UTF8::NAME,
        int $access_level = Auth::PRIV_HIDE,
        string $line_endings = 'CRLF',
		bool $gedcom_l = false,
        bool $check_custom_tags = false,
        Collection $records = null,
        FilesystemOperator $zip_filesystem = null,
        string $media_path = null
    ) {
        if(!$check_custom_tags) {
            $stream = fopen('php://memory', 'wb+');

            if ($stream === false) {
                throw new RuntimeException('Failed to create temporary stream');
            }

            stream_filter_append($stream, GedcomEncodingFilter::class, STREAM_FILTER_WRITE, ['src_encoding' => UTF8::NAME, 'dst_encoding' => $encoding]);
        }

        if ($records instanceof Collection) {
            // Export just these records - e.g. from clippings cart.
            $data = [
                new Collection([$this->createHeader($tree, $encoding, false)]),
                $records,
                new Collection(['0 TRLR']),
            ];
        } elseif ($access_level === Auth::PRIV_HIDE) {
            // If we will be applying privacy filters, then we will need the GEDCOM record objects.
            $data = [
                new Collection([$this->createHeader($tree, $encoding, true)]),
                $this->individualQuery($tree, $sort_by_xref)->cursor(),
                $this->familyQuery($tree, $sort_by_xref)->cursor(),
                $this->sourceQuery($tree, $sort_by_xref)->cursor(),
                $this->otherQuery($tree, $sort_by_xref)->cursor(),
                $this->mediaQuery($tree, $sort_by_xref)->cursor(),
                new Collection(['0 TRLR']),
            ];
        } else {
            // Disable the pending changes before creating GEDCOM records.
            Registry::cache()->array()->remember(AbstractGedcomRecordFactory::class . $tree->id(), static function (): Collection {
                return new Collection();
            });

            $data = [
                new Collection([$this->createHeader($tree, $encoding, true)]),
                $this->individualQuery($tree, $sort_by_xref)->get()->map(Registry::individualFactory()->mapper($tree)),
                $this->familyQuery($tree, $sort_by_xref)->get()->map(Registry::familyFactory()->mapper($tree)),
                $this->sourceQuery($tree, $sort_by_xref)->get()->map(Registry::sourceFactory()->mapper($tree)),
                $this->otherQuery($tree, $sort_by_xref)->get()->map(Registry::gedcomRecordFactory()->mapper($tree)),
                $this->mediaQuery($tree, $sort_by_xref)->get()->map(Registry::mediaFactory()->mapper($tree)),
                new Collection(['0 TRLR']),
            ];
        }

        $media_filesystem = $tree->mediaFilesystem();

        foreach ($data as $rows) {
            foreach ($rows as $datum) {
                if (is_string($datum)) {
                    $gedcom = $datum;
                } elseif ($datum instanceof GedcomRecord) {
                    $gedcom = $datum->privatizeGedcom($access_level);
                } else {
                    $gedcom =
                        $datum->i_gedcom ??
                        $datum->f_gedcom ??
                        $datum->s_gedcom ??
                        $datum->m_gedcom ??
                        $datum->o_gedcom;
                }

                if (!$check_custom_tags && $media_path !== null && $zip_filesystem !== null && preg_match('/0 @' . Gedcom::REGEX_XREF . '@ OBJE/', $gedcom) === 1) {
                    preg_match_all('/\n1 FILE (.+)/', $gedcom, $matches, PREG_SET_ORDER);

                    foreach ($matches as $match) {
                        $media_file = $match[1];

                        if ($media_filesystem->fileExists($media_file)) {
                            $zip_filesystem->writeStream($media_path . $media_file, $media_filesystem->readStream($media_file));
                        }
                    }
                }

				//Do NOT wrap long lines for Gedcom 7
                //$gedcom = $this->wrapLongLines($gedcom, Gedcom::LINE_LENGTH) . "\n";
				$gedcom .= "\n";

                //Convert to Gedcom 7
                $gedcom = $this->convertToGedcom7($gedcom, $gedcom_l);

                if($check_custom_tags) {
                    //Find known custom tags
                    $this->findCustomTags($gedcom);
                }
                else {
                    if ($line_endings === 'CRLF') {
                        $gedcom = strtr($gedcom, ["\n" => "\r\n"]);
                    }

                    $bytes_written = fwrite($stream, $gedcom);

                    if ($bytes_written !== strlen($gedcom)) {
                        throw new RuntimeException('Unable to write to stream.  Perhaps the disk is full?');
                    }
                }
			}
        }

        if(!$check_custom_tags) {        
            if (rewind($stream) === false) {
                throw new RuntimeException('Cannot rewind temporary stream');
            }

            return $stream;
        }

        return;
    }

    /**
     * Convert to Gedcom 7
     *
     * @param string $gedcom
	 * @param bool $gedcom_l
     *
     * @return string
     */
    public function convertToGedcom7(string $gedcom, bool $gedcom_l= false): string
    {
		$replace_pairs = [
			"ROLE (Godparent)\n" => "ROLE GODP\n",
			"RELA godparent\n" => "RELA GODP\n",
			"RELA witness\n" => "RELA WITN\n",
			"2 LANG SERB\n" => "2 LANG Serbian\n",				//Otherwise not found by language replacement below
			"2 LANG Serbo_Croa\n" => "2 LANG Serbo-Croatian\n",	//Otherwise not found by language replacement below
			"2 LANG BELORUSIAN\n" => "2 LANG Belarusian\n",		//Otherwise not found by language replacement below
		];

		foreach ($replace_pairs as $search => $replace) {
			$gedcom = str_replace($search, $replace, $gedcom);
		}

		//GEDCOM-L
		if($gedcom_l) {

			$replace_pairs_gedcom_l = [
				"1 _STAT NOT MARRIED\n" => "1 NO MARR\n",			//Convert former GEDCOM-L structure to new GEDCOM 7 structure
				"1 _STAT NEVER MARRIED\n" => "1 NO MARR\n",			//Convert former GEDCOM-L structure to new GEDCOM 7 structure
				"2 TYPE RELIGIOUS\n" => "2 TYPE RELI\n",			//Convert webtrees value to GEDCOM-L standard value
			];
	
			foreach ($replace_pairs_gedcom_l as $search => $replace) {
				$gedcom = str_replace($search, $replace, $gedcom);
			}	
		}

		$preg_replace_pairs = [
			//Date and age values
			"/0([\d]) (JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC) (.[\d]{1,4})/" => "$1 $2 $3",
			"/2 AGE 0([\d]{1,2})y/" => "2 AGE $1y",
			"/2 AGE ([\d]{1,3})y 0(.)m/" => "2 AGE $1y $2m",
			"/2 AGE ([\d]{1,3})y ([\d]{1,2})m 0([\d]{1,2})d/" => "2 AGE $1y $2m $3d",
			"/2 AGE ([\d]{1,2})m 00([\d])d/" => "2 AGE $1m $2d",
			"/2 AGE ([\d]{1,2})m 0([\d]{1,2})d/" => "2 AGE $1m $2d",

			//RELA, ROLE, ASSO
			"/([\d]) RELA/" => "$1 ROLE",
			"/([\d]) _ASSO/" => "$1 ASSO",

			//Media types
			//Allowed GEDCOM 7 media types: https://www.iana.org/assignments/media-types/media-types.xhtml
			//GEDCOM 5.5.1 media types: bmp | gif | jpg | ole | pcx | tif | wav
			"/2 FORM (bmp|BMP)(\n3 TYPE .[^\n]+)*/" => "2 FORM image/bmp",
			"/2 FORM (gif|GIF)(\n3 TYPE .[^\n]+)*/" => "2 FORM image/gif",
			"/2 FORM (jpg|JPG|jpeg|JPEG)(\n3 TYPE .[^\n]+)*/" => "2 FORM image/jpeg",
			"/2 FORM (tif|TIF|tiff|TIFF)(\n3 TYPE .[^\n]+)*/" => "2 FORM image/tiff",
			"/2 FORM (pdf|PDF)(\n3 TYPE .[^\n]+)*/" => "2 FORM application/pdf",
			"/2 FORM (emf|EMF)(\n3 TYPE .[^\n]+)*/" => "2 FORM image/emf",
			"/2 FORM (htm|HTM|html|HTML)(\n3 TYPE .[^\n]+)*/" => "2 FORM text/html",

            //Shared notes (SNOTE)
			"/([\d)]) NOTE @(.[^\n]+)@/" => "$1 SNOTE @$2@",
			"/0 @(.[^\n]+)@ NOTE (.[^\n]+)/" => "0 @$1@ SNOTE $2",

            //External IDs (EXID)
			"/1 (AFN|RFN|RIN) (.[^\n]+)/" => "1 EXID $2\n2 TYPE https://gedcom.io/terms/v7/$1",
		];

		foreach ($preg_replace_pairs as $pattern => $replace) {
			$gedcom = preg_replace($pattern, $replace, $gedcom);
		}

		//GEDCOM-L _GODP, _WITN
		if($gedcom_l) {
			$preg_replace_pairs_gedcom_l = [
				"_GODP",
				"_WITN",
			];

			foreach ($preg_replace_pairs_gedcom_l as $pattern) {

				preg_match_all("/([\d]) " . $pattern . " (.[^\n]+)/", $gedcom, $matches, PREG_SET_ORDER);

				foreach ($matches as $match) {
					$level = (int) $match[1];
					$role = str_replace("_", "", $pattern);

					$search =  (string) $level . " " . $pattern . " " . $match[2];
					$replace = (string) $level . " " . "ASSO @VOID@\n" . (string) ($level + 1) . " PHRASE " . $match[2] . "\n" .  (string) ($level + 1) . " ROLE " . $role;
					$gedcom = str_replace($search, $replace, $gedcom);
				}			
			}
		}

		//Languages
		//Allowed GEDCOM 7 language tags: https://www.iana.org/assignments/language-subtag-registry/language-subtag-registry

		preg_match_all("/([12]) LANG (.[^\n]+)\n/", $gedcom, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {

			if (isset($this->language_to_code_table[strtoupper($match[2])])) {

				$search =  $match[1] . " LANG " . $match[2] . "\n";
				$replace = $match[1] . " LANG " . $this->language_to_code_table[strtoupper($match[2])] . "\n";
				$gedcom = str_replace($search, $replace, $gedcom);
			}
		}

		//enumsets

		$enumsets = [
			"ADOP" => ["HUSB", "WIFE", "BOTH",],
			"MEDI" => ["AUDIO", "BOOK","CARD", "ELECTRONIC", "FICHE", "FILM", "MAGAZINE", "MANUSCRIPT", "MAP", "NEWSPAPER", "PHOTO", "TOMBSTONE", "VIDEO", "OTHER",],
			"PEDI" => ["ADOPTED", "BIRTH", "FOSTER", "SEALING", "OTHER",],
			"QUAY" => ["1", "2", "3",],
			"RESN" => ["CONFIDENTIAL", "LOCKED", "PRIVACY",],
			"ROLE" => ["CHIL", "CLERGY", "FATH", "FRIEND", "GODP", "HUSB", "MOTH", "MULTIPLE", "NGHBR", "OFFICIATOR", "PARENT", "SPOU", "WIFE", "WITN", "OTHER",],
			"SEX" =>  ["M", "F", "X", "U",],
		];

		foreach ($enumsets as $enumset => $values) {

			preg_match_all("/([\d]) " . $enumset . " (.[^\n]+)/", $gedcom, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {
				$level = (int) $match[1];

				//If allowed value
				if (in_array(strtoupper($match[2]), $values)) {
					$search =  (string) $level . " " . $enumset . " " . $match[2];
					$replace = (string) $level . " " . $enumset . " " . strtoupper($match[2]);
					$gedcom = str_replace($search, $replace, $gedcom);
				}
				//Use phrase instead
				else {
					$search =  (string) $level . " " . $enumset . " " . $match[2];
                    //For specific role descriptions
                    if ($enumset == "ROLE") {
                        $match[2] = str_replace(['(', ')'], ['', ''], $match[2]);  // (<ROLE_DESCRIPTOR>)
                    }
					$replace = (string) $level . " " . $enumset . " OTHER\n" . (string) ($level + 1) . " PHRASE " . $match[2];
					$gedcom = str_replace($search, $replace, $gedcom);
				}
			}
		}		

		//Nested enumsets

		$nested_enumsets = [
			[ "tags" => ["NAME", "TYPE"], "values" => ["AKA", "BIRTH", "IMMIGRANT", "MAIDEN", "MARRIED", "PROFESSIONAL",]],
			[ "tags" => ["FAMC", "STAT"], "values" => ["CHALLENGED", "DISPROVEN", "PROVEN",]],
		];

		foreach ($nested_enumsets as $enumset) {

			$tags = $enumset["tags"];
			$enum_values = $enumset["values"];
			$level1_tag = $tags[0];
			$level2_tag = $tags[1];

			preg_match_all("/([\d]) " . $level1_tag . " (.[^\n]+)\n([\d]) " . $level2_tag . " (.[^\n]+)/", $gedcom, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {

				$size = sizeof($match);
				$level = (int) $match[$size - 2];
				$found_type =  $match[$size - 1];		

				//If allowed type
				if (in_array(strtoupper($found_type), $enum_values)) {
					$search =  (string) $level . " " . $level2_tag . " " . $found_type;
					$replace = (string) $level . " " . $level2_tag . " " . strtoupper($found_type);
					$gedcom = str_replace($search, $replace, $gedcom);
				}
				//Use OTHER/PHRASE instead
				else {
					$search =  (string) $level  . " " . $level2_tag . " " . $found_type;
					$replace = (string) $level  . " " . $level2_tag . " OTHER\n" . (string) ($level + 1) . " PHRASE " . $found_type;
					$gedcom = str_replace($search, $replace, $gedcom);
				}
			}	
		}	

		return $gedcom;
	}
	
    /**
     * Create a header record for a gedcom file.
     *
     * @param Tree   $tree
     * @param string $encoding
     * @param bool   $include_sub
     *
     * @return string
     */
    public function createHeader(Tree $tree, string $encoding, bool $include_sub): string
    {
        // Force a ".ged" suffix
        $filename = $tree->name();

        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'ged') {
            $filename .= '.ged';
        }

        $gedcom_encodings = [
            UTF16BE::NAME     => 'UNICODE',
            UTF16LE::NAME     => 'UNICODE',
            Windows1252::NAME => 'ANSI',
        ];

        $encoding = $gedcom_encodings[$encoding] ?? $encoding;

        // Build a new header record
        $gedcom = '0 HEAD';
        $gedcom .= "\n1 SOUR " . Webtrees::NAME;
        $gedcom .= "\n2 NAME " . Webtrees::NAME;
        $gedcom .= "\n2 VERS " . Webtrees::VERSION;
        $gedcom .= "\n1 DEST DISKETTE";
        $gedcom .= "\n1 DATE " . strtoupper(date('d M Y'));
        $gedcom .= "\n2 TIME " . date('H:i:s');
        $gedcom .= "\n1 GEDC\n2 VERS 7.0.11";

		// Add schemas with extension tags
        if (sizeof($this->custom_tags_found) > 0) {

            $gedcom .= "\n1 SCHMA";

            foreach($this->custom_tags_found as $tag) {
                $gedcom .= "\n2 TAG " . $tag . " " . $this->schemas[$tag];
            }
        }

        // Preserve some values from the original header
        $header = Registry::headerFactory()->make('HEAD', $tree) ?? Registry::headerFactory()->new('HEAD', '0 HEAD', null, $tree);

        foreach ($header->facts(['COPR', 'LANG', 'PLAC', 'NOTE']) as $fact) {
            $gedcom .= "\n" . $fact->gedcom();
        }

        if ($include_sub) {
            // Apply access level of 'none', because the export needs to be consistent if a submitter/submission exists
            // Privacy of the submitter/submission is handled in the submitter/submission object itself
            // Note: HEAD:SUBN does not exist in GEDCOM 7. It will still be exported, because it is subject to the user to change it

            foreach ($header->facts(['SUBM', 'SUBN'], false, Auth::PRIV_HIDE) as $fact) {
                $gedcom .= "\n" . $fact->gedcom();
            }
        }

        return $gedcom;
    }

    /**
     * Wrap long lines using concatenation records.
     *
     * @param string $gedcom
     * @param int    $max_line_length
     *
     * @return string
     */
    public function wrapLongLines(string $gedcom, int $max_line_length): string
    {
        $lines = [];

        foreach (explode("\n", $gedcom) as $line) {
            // Split long lines
            // The total length of a GEDCOM line, including level number, cross-reference number,
            // tag, value, delimiters, and terminator, must not exceed 255 (wide) characters.
            if (mb_strlen($line) > $max_line_length) {
                [$level, $tag] = explode(' ', $line, 3);
                if ($tag !== 'CONT') {
                    $level++;
                }
                do {
                    // Split after $pos chars
                    $pos = $max_line_length;
                    // Split on a non-space (standard gedcom behavior)
                    while (mb_substr($line, $pos - 1, 1) === ' ') {
                        --$pos;
                    }
                    if ($pos === strpos($line, ' ', 3)) {
                        // No non-spaces in the data! Can’t split it :-(
                        break;
                    }
                    $lines[] = mb_substr($line, 0, $pos);
                    $line    = $level . ' CONC ' . mb_substr($line, $pos);
                } while (mb_strlen($line) > $max_line_length);
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * @param Tree $tree
     * @param bool $sort_by_xref
     *
     * @return Builder
     */
    private function familyQuery(Tree $tree, bool $sort_by_xref): Builder
    {
        $query = DB::table('families')
            ->where('f_file', '=', $tree->id())
            ->select(['f_gedcom', 'f_id']);


        if ($sort_by_xref) {
            $query
                ->orderBy(new Expression('LENGTH(f_id)'))
                ->orderBy('f_id');
        }

        return $query;
    }

    /**
     * @param Tree $tree
     * @param bool $sort_by_xref
     *
     * @return Builder
     */
    private function individualQuery(Tree $tree, bool $sort_by_xref): Builder
    {
        $query = DB::table('individuals')
            ->where('i_file', '=', $tree->id())
            ->select(['i_gedcom', 'i_id']);

        if ($sort_by_xref) {
            $query
                ->orderBy(new Expression('LENGTH(i_id)'))
                ->orderBy('i_id');
        }

        return $query;
    }

    /**
     * @param Tree $tree
     * @param bool $sort_by_xref
     *
     * @return Builder
     */
    private function sourceQuery(Tree $tree, bool $sort_by_xref): Builder
    {
        $query = DB::table('sources')
            ->where('s_file', '=', $tree->id())
            ->select(['s_gedcom', 's_id']);

        if ($sort_by_xref) {
            $query
                ->orderBy(new Expression('LENGTH(s_id)'))
                ->orderBy('s_id');
        }

        return $query;
    }

    /**
     * @param Tree $tree
     * @param bool $sort_by_xref
     *
     * @return Builder
     */
    private function mediaQuery(Tree $tree, bool $sort_by_xref): Builder
    {
        $query = DB::table('media')
            ->where('m_file', '=', $tree->id())
            ->select(['m_gedcom', 'm_id']);

        if ($sort_by_xref) {
            $query
                ->orderBy(new Expression('LENGTH(m_id)'))
                ->orderBy('m_id');
        }

        return $query;
    }

    /**
     * @param Tree $tree
     * @param bool $sort_by_xref
     *
     * @return Builder
     */
    private function otherQuery(Tree $tree, bool $sort_by_xref): Builder
    {
        $query = DB::table('other')
            ->where('o_file', '=', $tree->id())
            ->whereNotIn('o_type', [Header::RECORD_TYPE, 'TRLR'])
            ->select(['o_gedcom', 'o_id']);

        if ($sort_by_xref) {
            $query
                ->orderBy('o_type')
                ->orderBy(new Expression('LENGTH(o_id)'))
                ->orderBy('o_id');
        }

        return $query;
    }

    /**
     * Find custom tags.
     * 
     * @param string $gedcom
     * 
     * @return void 
     */
    public function findCustomTags(string $gedcom) : void
    {
        foreach ($this->schemas as $tag => $uri) {

            if(str_contains($gedcom, $tag)) {

                if(!in_array($tag, $this->custom_tags_found)) {

                    $this->custom_tags_found[] = $tag;
                }
            } 
        }
    }

    /**
     * Add to schemas
     * 
     * @param array $schemas     //An array with schemas to add
     * 
     * @return void
     */
    public function addToSchemas(array $schemas) : void
    {
        foreach ($schemas as $schema) {
        
            foreach($schema as $uri => $custom_tags) {

                foreach($custom_tags as $tag) {
                    $this->schemas[$tag] = $uri;
                }
            }
        }
    }

}
