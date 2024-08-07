<?php

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\DownloadGedcomWithURL;

use Fisharebest\Webtrees\I18N;

/**
 * A GEDCOM filter, which changes some of the webtrees GEDCOM structures in order to be compliant to the GEDCOM 7.0 standard
 */
class OptimizeWebtreesGedcomFilter extends AbstractGedcomFilter implements GedcomFilterInterface
{
    protected const WRAP_LINES_WITHOUT_LEADING_AND_TRAILING_SPACES = true;
    
    protected const GEDCOM_FILTER_RULES = [
        //GEDCOM tag                => Regular expression to be applied for the chosen GEDCOM tag
        //                             ["search pattern" => "replace pattern"],

        //Remove * from names (indicates first name underlined by webtrees)
        'INDI:NAME'                 => ["PHP_function" => "customConvert"],

        //Capitalize languages, because some other progams do not understand capital languages
        '*:LANG'                    => ["PHP_function" => "customConvert"],
        '*:*:LANG'                  => ["PHP_function" => "customConvert"],

        //Convert some structures to GEDCOM-L standard, because it is better to have some kind of standard than none
        'FAM:MARR:TYPE'            	=> ["2 TYPE (?i)RELIGIOUS" => "2 TYPE RELI",
                                        "2 TYPE (?i)CIVIL" => "2 TYPE CIVIL"],
        'INDI:ASSO:RELA'            => ["RegExp_macro" => "Godparent"],
        '*:*:_ASSO:RELA'            => ["RegExp_macro" => "Godparent"],

        //Convert certain structures to lower case, because some other progams do not understand capital forms
        //INDI:RESN, FAM:RESN is converted below
        'INDI:NAME:TYPE'            => ["PHP_function" => "customConvert"],
        'INDI:FAMC:STAT'            => ["PHP_function" => "customConvert"],   
        'INDI:FAMC:PEDI'            => ["PHP_function" => "customConvert"],   
        'OBJE:FILE:FORM:TYPE'       => ["PHP_function" => "customConvert"],
        '*:OBJE:FILE:FORM:MEDI'     => ["PHP_function" => "customConvert"],
        '*:*:OBJE:FILE:FORM:MEDI'   => ["PHP_function" => "customConvert"],
        '*:*:*:OBJE:FILE:FORM:MEDI' => ["PHP_function" => "customConvert"],
        'SOUR:REPO:CALN:MEDI'     	=> ["PHP_function" => "customConvert"],

        //Allow RESN for INDI, FAM. However, remove 'RESN none' structures, because 'none' is not allowed by the standard
        //Note: OBJE:RESN is NOT allowed in GEDCOM 5.5.1
        'INDI:RESN'                 => ["1 RESN (?i)NONE\n" => "",
                                        "RegExp_macro" => "Lowercase_enumvalues"],
        'FAM:RESN'                  => ["1 RESN (?i)NONE\n" => "",
                                        "RegExp_macro" => "Lowercase_enumvalues"],
        '!INDI:NOTE:RESN'           => [],
        '!INDI:OBJE:RESN'           => [],
        '!INDI:SOUR:RESN'           => [],
        'INDI:*:RESN'				=> ["1 RESN (?i)NONE\n" => "",
                                        "RegExp_macro" => "Lowercase_enumvalues"],
        '!FAM:NOTE:RESN'            => [],
        '!FAM:OBJE:RESN'            => [],
        '!FAM:SOUR:RESN'            => [],
        'FAM:*:RESN'				=> ["1 RESN (?i)NONE\n" => "",
                                        "RegExp_macro" => "Lowercase_enumvalues"],
                                                                    
        //Remove RESN structures, where not allowed by the standard
        '!*:RESN'                   => [],
        '!*:*:RESN'                 => [],
        '!*:*:*:RESN'               => [],

        //Remove CHAN and _WT_USER structures
        '!*:CHAN'                   => [],
        '!*:CHAN:*'                 => [],
        '!FAM:_TODO:_WT_USER'       => [],
        '!INDI:_TODO:_WT_USER'      => [],

        //Export other structures      
        '*'                         => [],
    ];

    protected const REGEXP_MACROS = [
        //Macro Name                => Regular expression to be applied for the chosen GEDCOM tag
        //                             ["search pattern" => "replace pattern"],
        
        "Godparent"                 => ["([\d]) RELA (?i)GODPARENT" => "$1 RELA Godparent"],
        "Lowercase_enumvalues"      => ["PHP_function" => "customConvert"],
    ];

    protected const ENUMSET_VALUES = [
        "AUDIO", "BOOK","CARD", "ELECTRONIC", "FICHE", "FILM", "MAGAZINE", "MANUSCRIPT", "MAP", "NEWSPAPER", "PHOTO", "TOMBSTONE", "VIDEO",
        "ADOPTED", "BIRTH", "FOSTER", "SEALING",
        "CONFIDENTIAL", "LOCKED", "PRIVACY",
        "AKA", "BIRTH", "IMMIGRANT", "MAIDEN", "MARRIED",
        "CHALLENGED", "DISPROVEN", "PROVEN",
    ];      

    /**
     * Get the name of the GEDCOM filter
     * 
     * @return string
     */
    public function name(): string {

        return I18N::translate('Optimization of webtrees export for GEDCOM 5.5.1');
    } 

   /**
    * Custom conversion of a Gedcom string
    *
    * @param string $pattern       The pattern of the filter rule, e. g. INDI:*:DATE
    * @param string $gedcom        The Gedcom to convert
    * @param array  $records_list  A list with all xrefs and the related records: array <string xref => Record record>
    * 
    * @return string               The converted Gedcom
    */
    public function customConvert(string $pattern, string $gedcom, array &$records_list): string {

        if ($pattern === 'INDI:NAME') {

            //Remove all * characters from INDI:NAME
            $gedcom = str_replace('*' , '', $gedcom);
        }
        elseif (in_array($pattern, ['*:LANG', '*:*:LANG'])) {

            //Convert languages to capitalized string
            preg_match_all("/([\d]) LANG (.)(.*)/", $gedcom, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
        
            $search =  $match[1] . " LANG " .            $match[2]  .            $match[3];
            $replace = $match[1] . " LANG " . strtoupper($match[2]) . strtolower($match[3]);
            $gedcom = str_replace($search, $replace, $gedcom);
            }
        }
        else {
            //Convert certain ENUM values to lowercase string
            preg_match_all("/([\d]) (FORM|PEDI|MEDI|RESN|STAT|TYPE) (.*)/", $gedcom, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {

                if (in_array(strtoupper($match[3]), static::ENUMSET_VALUES)) {

                    $search =  $match[1]  . ' ' .  $match[2] . ' ' .  $match[3];
                    $replace = $match[1]  . ' ' .  $match[2] . ' ' .  strtolower($match[3]);
                    $gedcom = str_replace($search, $replace, $gedcom);   
                }
            }      
        }
        return $gedcom;
    }
}
