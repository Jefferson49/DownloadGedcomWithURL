<?php

declare(strict_types=1);

namespace Jefferson49\Webtrees\Module\DownloadGedcomWithURL;

/**
 * An export filter, which combines several other export filters
 * 
 * In this example the filters are included "Before" the current export filter.
 * An alternative method 'getIncludedFiltersAfter' can be used to include filters 'After' the current filter.
 */
class CombinedExportFilter extends AbstractExportFilter implements ExportFilterInterface
{
    /**
     * Include a set of other filters, which shall be executed before the current filter
     *
     * @return array<ExportFilterInterface>    A set of included export filters
     */
    public function getIncludedFiltersBefore(): array {

        return [
        new BirthMarriageDeathExportFilter(),
        new Gedcom_7_ExportFilter(),
        new RemoveEmptyRecordsExportFilter(),      
        ];
    }
}
