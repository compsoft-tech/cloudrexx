<?php

/**
 * Countries
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  module_checkout
 */

/**
 * Countries
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  module_checkout
 */
class Countries {

    /**
     * Database object.
     *
     * @access      private
     * @var         ADONewConnection
     */
    private $objDatabase;

    /**
     * Initialize the database object.
     *
     * @access      public
     * @param       ADONewConnection    $objDatabase
     */
    public function __construct($objDatabase)
    {
        $this->objDatabase = $objDatabase;
    }

    /**
     * Get all countries.
     *
     * @access      public
     * @return      array       $arrCountries   contains all countries
     * @return      boolean                     contains false if there are no countries
     */
    public function getAll()
    {
        $arrCountries = array();
    
        $objResult = $this->objDatabase->Execute('
            SELECT `id`, `name` as `country`
            FROM `'.DBPREFIX.'lib_country`
            ORDER BY `name` ASC
        ');

        if ($objResult) {
            $i = 0;
            while (!$objResult->EOF) {
                $arrCountries[$objResult->fields['id']] = $objResult->fields['country'];
                $objResult->MoveNext();
                $i++;
            }
        }

        if (!empty($arrCountries)) {
            return $arrCountries;
        } else {
            return false;
        }
    }

}
