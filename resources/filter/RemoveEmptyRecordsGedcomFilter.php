<?php

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\DownloadGedcomWithURL;

use Fisharebest\Webtrees\I18N;

/**
 * A GEDCOM filter to remove empty records (FAM, NOTE, OBJE, REPO, SOUR)
 */
class RemoveEmptyRecordsGedcomFilter extends RemoveEmptyOrUnlinkedRecordsGedcomFilter implements GedcomFilterInterface
{
    /**
     * Get the name of the GEDCOM filter
     * 
     * @return string
     */
    public function name(): string {

        return I18N::translate('Remove empty records');
    } 

    /**
    * Custom conversion of a Gedcom string
    *
    * @param string $pattern       The pattern of the filter rule, e. g. INDI:BIRT:DATE
    * @param string $gedcom        The Gedcom to convert
    * @param array  $records_list  A list with all xrefs and the related records: array <string xref => Record record>
    * 
    * @return string               The converted Gedcom
    */
    public function customConvert(string $pattern, string $gedcom, array &$records_list): string {

        //Call parent method for emtpy records only 
        $gedcom = parent::removeEmptyOrUnlinkedRecords($pattern, $gedcom, $records_list, true, false);
        
        return $gedcom;
    }   
}
