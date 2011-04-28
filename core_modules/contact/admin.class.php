<?php

/**
 * Contact
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  core_module_contact
 * @todo        Edit PHP DocBlocks!
 */

/**
 * @ignore
 */
require_once ASCMS_CORE_MODULE_PATH.'/contact/lib/ContactLib.class.php';

/**
 * Contact manager
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @access      public
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  core_module_contact
 */
class ContactManager extends ContactLib
{
    var $_objTpl;

    var $_statusMessageOk;
    var $_statusMessageErr;

    var $_arrFormFieldTypes;

    var $boolHistoryEnabled = false;
    var $boolHistoryActivate = false;

    var $_csvSeparator = null;
    var $_csvEnclosure = null;
    var $_csvCharset = null;
    var $_csvLFB = null;

    var $_pageTitle = '';

    var $_invalidRecipients = false;


    /**
    * PHP5 constructor
    * @global HTML_Template_Sigma
    * @global array
    * @global array
    */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG, $_CONFIG;

        $this->_objTpl = new HTML_Template_Sigma(ASCMS_CORE_MODULE_PATH.'/contact/template');
        CSRF::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $objTemplate->setVariable("CONTENT_NAVIGATION", "   <a href='index.php?cmd=contact' title=".$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'].">".$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS']."</a>
                                                            <a href='index.php?cmd=contact&amp;act=settings' title=".$_ARRAYLANG['TXT_CONTACT_SETTINGS'].">".$_ARRAYLANG['TXT_CONTACT_SETTINGS']."</a>");

        $this->_arrFormFieldTypes = array(
            'text'          => $_ARRAYLANG['TXT_CONTACT_TEXTBOX'],
            'label'         => $_ARRAYLANG['TXT_CONTACT_TEXT'],
            'checkbox'      => $_ARRAYLANG['TXT_CONTACT_CHECKBOX'],
            'checkboxGroup' => $_ARRAYLANG['TXT_CONTACT_CHECKBOX_GROUP'],
            'date'          => $_ARRAYLANG['TXT_CONTACT_DATE'],
            'file'          => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD'],
            'hidden'        => $_ARRAYLANG['TXT_CONTACT_HIDDEN_FIELD'],
            'password'      => $_ARRAYLANG['TXT_CONTACT_PASSWORD_FIELD'],
            'radio'         => $_ARRAYLANG['TXT_CONTACT_RADIO_BOXES'],
            'select'        => $_ARRAYLANG['TXT_CONTACT_SELECTBOX'],
            'textarea'      => $_ARRAYLANG['TXT_CONTACT_TEXTAREA'],
            'recipient'     => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'],
        );

        $this->initContactForms(true);
        $this->initCheckTypes();

        $this->boolHistoryEnabled = ($_CONFIG['contentHistoryStatus'] == 'on') ? true : false;

        if (Permission::checkAccess(78, 'static', true)) {
            $this->boolHistoryActivate = true;
        }
    }

    /**
    * Get page
    *
    * Get the development page
    *
    * @access public
    * @global HTML_Template_Sigma
    */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        }

        if (!isset($_REQUEST['tpl'])) {
            $_REQUEST['tpl'] = '';
        }

        switch ($_REQUEST['act']) {
        case 'settings':
            Permission::checkAccess(85, 'static');
            $this->_getSettingsPage();
            break;

        case 'entries':
            $this->_getEntriesPage();
            break;

        default:
            $this->_getContactFormPage();
            break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->_pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->_statusMessageOk,
            'CONTENT_STATUS_MESSAGE'    => $this->_statusMessageErr,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));
    }

    function _getEntriesPage()
    {
        global $_ARRAYLANG;

        $entryId = isset($_REQUEST['entryId']) ? intval($_REQUEST['entryId']) : 0;
        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;

        $arrEntry = &$this->getFormEtry($entryId);

        if (is_array($arrEntry)) {

            $this->_objTpl->loadTemplateFile('module_contact_entries_details.html');
            $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_ENTRIE_DETAILS'];

            $this->_objTpl->setVariable(array(
                'CONTACT_FORM_ENTRY_ID'                 => $entryId,
                'CONTACT_ENTRY_TITLE'                   => str_replace('%DATE%', date(ASCMS_DATE_FORMAT, $arrEntry['time']), $_ARRAYLANG['TXT_CONTACT_ENTRY_OF_DATE']),
                'CONTACT_ENTRY'                         => $this->_getEntryDetails($arrEntry, $formId),
                'CONTACT_FORM_ID'                       => $formId
            ));

            $this->_objTpl->setVariable(array(
                'TXT_CONTACT_BACK'                      => $_ARRAYLANG['TXT_CONTACT_BACK'],
                'TXT_CONTACT_DELETE'                    => $_ARRAYLANG['TXT_CONTACT_DELETE'],
                'TXT_CONTACT_CONFIRM_DELETE_ENTRY'      => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_ENTRY'],
                'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'    => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
                'TXT_CONTACT_CONFIRM_DELETE_ENTRIES'    => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_ENTRIES']
            ));
        } else {
            $this->_contactFormEntries();
        }
    }

    function _getSettingsPage()
    {
        switch ($_REQUEST['tpl']) {
        case 'save':
            $this->_saveSettings();

        default:
            $this->_settings();
            break;
        }
    }

    function _settings()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_settings.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_SETTINGS'];

        $arrSettings = &$this->getSettings();

        $this->_objTpl->setVariable(array(
            'TXT_CONTACT_SETTINGS'                          => $_ARRAYLANG['TXT_CONTACT_SETTINGS'],
            'TXT_CONTACT_SAVE'                              => $_ARRAYLANG['TXT_CONTACT_SAVE'],
            'TXT_CONTACT_FILE_UPLOAD_DEPOSITION_PATH'       => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD_DEPOSITION_PATH'],
            'TXT_CONTACT_SPAM_PROTECTION_WORD_LIST'         => $_ARRAYLANG['TXT_CONTACT_SPAM_PROTECTION_WORD_LIST'],
            'TXT_CONTACT_SPAM_PROTECTION_WW_DESCRIPTION'    => $_ARRAYLANG['TXT_CONTACT_SPAM_PROTECTION_WW_DESCRIPTION'],
            'TXT_CONTACT_DATE'                              => $_ARRAYLANG['TXT_CONTACT_DATE'],
            'TXT_CONTACT_HOSTNAME'                          => $_ARRAYLANG['TXT_CONTACT_HOSTNAME'],
            'TXT_CONTACT_BROWSER_LANGUAGE'                  => $_ARRAYLANG['TXT_CONTACT_BROWSER_LANGUAGE'],
            'TXT_CONTACT_IP_ADDRESS'                        => $_ARRAYLANG['TXT_CONTACT_IP_ADDRESS'],
            'TXT_CONTACT_META_DATE_BY_EXPORT'               => $_ARRAYLANG['TXT_CONTACT_META_DATE_BY_EXPORT']
        ));

        $this->_objTpl->setVariable(array(
            'CONTACT_FILE_UPLOAD_DEPOSITION_PATH'   => $arrSettings['fileUploadDepositionPath'],
            'CONTACT_SPAM_PROTECTION_WORD_LIST'     => $arrSettings['spamProtectionWordList'],
            'CONTACT_FIELD_META_DATE'               => $arrSettings['fieldMetaDate'] == '1' ? 'checked="checked"' : '',
            'CONTACT_FIELD_META_LANG'               => $arrSettings['fieldMetaLang'] == '1' ? 'checked="checked"' : '',
            'CONTACT_FIELD_META_HOST'               => $arrSettings['fieldMetaHost'] == '1' ? 'checked="checked"' : '',
            'CONTACT_FIELD_META_IP'                 => $arrSettings['fieldMetaIP'] == '1' ? 'checked="checked"' : '',
        ));
    }

    function _saveSettings()
    {
        global $objDatabase, $_ARRAYLANG;

        $saveStatus = true;

        if (isset($_REQUEST['save'])) {
            $arrSettings = &$this->getSettings();

            $arrNewSettings = array(
                'fileUploadDepositionPath'  => isset($_POST['contactFileUploadDepositionPath']) ? trim(contrexx_stripslashes($_POST['contactFileUploadDepositionPath'])) : '',
                'spamProtectionWordList'    => isset($_POST['contactSpamProtectionWordList']) ? explode(',', $_POST['contactSpamProtectionWordList']) : '',
                'fieldMetaDate'             => isset($_POST['contactFieldMetaDate']) ? intval($_POST['contactFieldMetaDate']) : 0,
                'fieldMetaHost'             => isset($_POST['contactFieldMetaHost']) ? intval($_POST['contactFieldMetaHost']) : 0,
                'fieldMetaLang'             => isset($_POST['contactFieldMetaLang']) ? intval($_POST['contactFieldMetaLang']) : 0,
                'fieldMetaIP'               => isset($_POST['contactFieldMetaIP']) ? intval($_POST['contactFieldMetaIP']) : 0
            );

            if (strpos($arrNewSettings['fileUploadDepositionPath'], '..') || empty($arrNewSettings['fileUploadDepositionPath'])) {
                $arrNewSettings['fileUploadDepositionPath'] = $arrSettings['fileUploadDepositionPath'];
            }

            if (!empty($arrNewSettings['spamProtectionWordList'])) {
                $arrTmpWordList = array();
                foreach ($arrNewSettings['spamProtectionWordList'] as $word) {
                    array_push($arrTmpWordList, contrexx_stripslashes(trim($word)));
                }
                $arrNewSettings['spamProtectionWordList'] = implode(',', $arrTmpWordList);
            } else {
                $arrNewSettings['spamProtectionWordList'] = $arrSettings['spamProtectionWordList'];
            }

            foreach ($arrNewSettings as $field => $status) {
                if ($status != $arrSettings[$field]) {
                    if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_contact_settings SET setvalue='".$status."' WHERE setname='".$field."'") === false) {
                        $saveStatus = false;
                    }
                }
            }

            if ($saveStatus) {
                $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_SETTINGS_UPDATED'];
            } else {
                $this->_statusMessageErr = $_ARRAYLANG['TXT_CONTACT_DATABASE_QUERY_ERROR'];
            }

            $this->initSettings();
        }
    }

    function _getContactFormPage()
    {
        switch ($_REQUEST['tpl']) {
        case 'edit':
            $this->_modifyForm();
            break;

        case 'copy':
            if (isset($_REQUEST['selectLang']) && $_REQUEST['selectLang'] == 'true') {
                $this->_selectFrontendLang();
            } else {
                $this->_modifyForm(true);
            }
            break;

        case 'save':

            $this->_saveForm();
            break;

        case 'deleteForm':
            $this->_deleteForm();
            break;

        case 'deleteEntry':
            $this->_deleteFormEntry();
            break;

        case 'code':
            $this->_sourceCode();
            break;

        case 'entries':
            $this->_contactFormEntries();
            break;

        case 'csv':
            $this->_getCsv();
            break;

        case 'newContent':
            $this->_createContentPage();
            break;

        case 'updateContent':
            $this->_updateContentSite();
            $this->_contactForms();
            break;

        default:
            $this->_contactForms();
            break;
        }
    }

    function _contactFormEntries()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_form_entries.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_FORM_ENTRIES'];

        $paging = '';
        $pos = 0;
        $maxFields = 3;
        $formId = isset($_GET['formId']) ? intval($_GET['formId']) : 0;

        if ($formId > 0) {

            if (isset($_GET['pos'])) {
                $pos = intval($_GET['pos']);
            }

            $arrCols = array();
            $arrEntries = &$this->getFormEntries($formId, $arrCols, $pos, $paging);
            if (count($arrEntries) > 0) {
                $arrFormFields = &$this->getFormFields($formId);
                $arrFormFieldNames = &$this->getFormFieldNames($formId);

                $this->_objTpl->setGlobalVariable(array(
                    'TXT_CONTACT_DELETE_ENTRY'              => $_ARRAYLANG['TXT_CONTACT_DELETE_ENTRY'],
                    'TXT_CONTACT_DETAILS'                   => $_ARRAYLANG['TXT_CONTACT_DETAILS'],
                    'CONTACT_FORM_ID'                       => $formId
                ));

                $this->_objTpl->setVariable(array(
                    'TXT_CONTACT_BACK'                      => $_ARRAYLANG['TXT_CONTACT_BACK'],
                    'TXT_CONTACT_CONFIRM_DELETE_ENTRY'      => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_ENTRY'],
                    'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'    => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
                    'TXT_CONTACT_DATE'                      => $_ARRAYLANG['TXT_CONTACT_DATE'],
                    'TXT_CONTACT_FUNCTIONS'                 => $_ARRAYLANG['TXT_CONTACT_FUNCTIONS'],
                    'TXT_CONTACT_SELECT_ALL'                => $_ARRAYLANG['TXT_CONTACT_SELECT_ALL'],
                    'TXT_CONTACT_DESELECT_ALL'              => $_ARRAYLANG['TXT_CONTACT_DESELECT_ALL'],
                    'TXT_CONTACT_SUBMIT_SELECT'             => $_ARRAYLANG['TXT_CONTACT_SUBMIT_SELECT'],
                    'TXT_CONTACT_SUBMIT_DELETE'             => $_ARRAYLANG['TXT_CONTACT_SUBMIT_DELETE'],
                    'CONTACT_FORM_COL_NUMBER'               => (count($arrCols) > $maxFields ? $maxFields+1 : count($arrCols)) + 3,
                    'CONTACT_FORM_ENTRIES_TITLE'            => str_replace('%NAME%', htmlentities($this->arrForms[$formId]['name'], ENT_QUOTES, CONTREXX_CHARSET), $_ARRAYLANG['TXT_CONTACT_ENTRIES_OF_NAME']),
                    'CONTACT_FORM_PAGING'                   => $paging
                ));

                $colNr = 0;
                foreach ($arrCols as $col) {
                    if ($colNr == $maxFields) {
                        break;
                    }
                    $this->_objTpl->setVariable('CONTACT_COL_NAME', $col);
                    $this->_objTpl->parse('contact_col_names');
                    $colNr++;
                }

                $rowNr = 0;
                foreach ($arrEntries as $entryId => $arrEntry) {
                    $this->_objTpl->setVariable('CONTACT_FORM_ENTRIES_ROW_CLASS', $rowNr % 2 == 0 ? 'row2' : 'row1');

                    $this->_objTpl->setVariable(array(
                        'CONTACT_FORM_DATE'     => '<a href="index.php?cmd=contact&amp;act=entries&amp;formId='.$formId.'&amp;entryId='.$entryId.'" title="'.$_ARRAYLANG['TXT_CONTACT_DETAILS'].'">'.date(ASCMS_DATE_FORMAT, $arrEntry['time']).'</a>',
                        'CONTACT_FORM_ENTRY_ID' => $entryId
                    ));

                    $this->_objTpl->parse('contact_form_entry_data');

                    $colNr = 0;
                    foreach ($arrCols as $col) {
                        if ($colNr == $maxFields) {
                            break;
                        }

                        if (isset($arrEntry['data'][$col])) {
                            if (isset($arrFormFields[$arrFormFieldNames[$col]]) && $arrFormFields[$arrFormFieldNames[$col]]['type'] == 'file') {
                                $fileData = $arrEntry['data'][$col];
                                if(substr($fileData,0,1) == '*') { //new style entry
                                    $value = '';
                                    if($fileData != '*' ) { //not empty
                                        //new style entry; multiple files and links
                                        $arrFiles = explode('*', substr($fileData,1)); //the substr kills the leading '*'
                                        foreach($arrFiles as $file) {
                                            $value .= '<a href="'.ASCMS_PATH_OFFSET.htmlentities($file, ENT_QUOTES, CONTREXX_CHARSET).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.htmlentities($file, ENT_QUOTES, CONTREXX_CHARSET).'</a>&nbsp;';
                                        }
                                    }
                                }
                                else { //old entry, single file and link
                                    $value = '<a href="'.ASCMS_PATH_OFFSET.htmlentities($arrEntry['data'][$col], ENT_QUOTES, CONTREXX_CHARSET).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.htmlentities($arrEntry['data'][$col], ENT_QUOTES, CONTREXX_CHARSET).'</a>';
                                }
                            } else {
                                $value = htmlentities($arrEntry['data'][$col], ENT_QUOTES, CONTREXX_CHARSET);
                            }
                        } else {
                            $value = '&nbsp;';
                        }
                        if (empty($value)) {
                            $value = '&nbsp;';
                        }

                        $this->_objTpl->setVariable('CONTACT_FORM_ENTRIES_CELL_CONTENT', $value);
                        $this->_objTpl->parse('contact_form_entry_data');

                        $colNr++;
                    }
                    $this->_objTpl->parse('contact_form_entries');

                    $rowNr++;
                }
            } else {
                $this->_contactForms();
            }
        } else {
            $this->_contactForms();
        }
    }

    function _contactForms()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_forms_overview.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'];

        $this->_objTpl->setVariable(array(
            'TXT_CONTACT_CONFIRM_DELETE_FORM'   => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_FORM'],
            'TXT_CONTACT_FORM_ENTRIES_WILL_BE_DELETED'  => $_ARRAYLANG['TXT_CONTACT_FORM_ENTRIES_WILL_BE_DELETED'],
            'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'        => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
            'TXT_CONTACT_LATEST_ENTRY'                  => $_ARRAYLANG['TXT_CONTACT_LATEST_ENTRY'],
            'TXT_CONTACT_NUMBER_OF_ENTRIES'             => $_ARRAYLANG['TXT_CONTACT_NUMBER_OF_ENTRIES'],
            'TXT_CONTACT_CONTACT_FORMS'                 => $_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'],
            'TXT_CONTACT_ID'                            => $_ARRAYLANG['TXT_CONTACT_ID'],
            'TXT_CONTACT_LANG'                          => $_ARRAYLANG['TXT_CONTACT_LANG'],
            'TXT_CONTACT_NAME'                          => $_ARRAYLANG['TXT_CONTACT_NAME'],
            'TXT_CONTACT_FUNCTIONS'                     => $_ARRAYLANG['TXT_CONTACT_FUNCTIONS'],
            'TXT_CONTACT_ADD_NEW_CONTACT_FORM'          => $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'],
            //'TXT_CONTACT_CSV_FILE'                      => $_ARRAYLANG['TXT_CONTACT_CSV_FILE'],
            'TXT_CONTACT_CONFIRM_DELETE_CONTENT_SITE'   => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_CONTENT_SITE']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_CONTACT_SHOW_ENTRIES'                  => $_ARRAYLANG['TXT_CONTACT_SHOW_ENTRIES'],
            'TXT_CONTACT_MODIFY'                        => $_ARRAYLANG['TXT_CONTACT_MODIFY'],
            'TXT_CONTACT_DELETE'                        => $_ARRAYLANG['TXT_CONTACT_DELETE'],
            'TXT_CONTACT_SHOW_SOURCECODE'               => $_ARRAYLANG['TXT_CONTACT_SHOW_SOURCECODE'],
            'TXT_CONTACT_USE_AS_TEMPLATE'               => $_ARRAYLANG['TXT_CONTACT_USE_AS_TEMPLATE'],
            'TXT_CONTACT_GET_CSV'                       => $_ARRAYLANG['TXT_CONTACT_GET_CSV'],
            'TXT_CONTACT_DOWNLOAD'                      => $_ARRAYLANG['TXT_CONTACT_DOWNLOAD']
        ));

        $rowNr = 0;
        if (is_array($this->arrForms)) {
            foreach ($this->arrForms as $formId => $arrForm) {
                $pageId = $this->_getContentSiteId($formId);

                $this->_objTpl->setGlobalVariable('CONTACT_FORM_ID', $formId);

                $this->_objTpl->setVariable(array(
                    'CONTACT_FORM_ROW_CLASS'            => $rowNr % 2 == 1 ? 'row1' : 'row2',
                    'CONTACT_FORM_NAME'                 => htmlentities($arrForm['name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'CONTACT_FORM_LAST_ENTRY'           => $arrForm['last'] ? date(ASCMS_DATE_FORMAT, $arrForm['last']) : '&nbsp;',
                    'CONTACT_FORM_NUMBER_OF_ENTRIES'    => $arrForm['number'],
                    'CONTACT_FORM_LANG'                 => FWLanguage::getLanguageParameter($arrForm['lang'], 'name'),
                    'CONTACT_DELETE_CONTENT'            => $pageId > 0 ? 'true' : 'false'
                ));

                $this->_objTpl->parse('contact_contact_forms');

                $rowNr++;
            }
        }
    }

    function _selectFrontendLang()
    {
        global $_ARRAYLANG, $_FRONTEND_LANGID;

        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;
        if ($formId > 0) {

            $this->_objTpl->loadTemplateFile('module_contact_form_selectFrontendLang.html');
            $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_COPY_FORM'];

            $menu = "<select name=\"userFrontendLangId\">\n";
            $arrLanguages = FWLanguage::getLanguageArray();
            foreach ($arrLanguages as $langId => $arrLanguage) {
                if (intval($arrLanguage['frontend']) == 1) {
                    $menu .= "<option value=\"".$langId."\"".($_FRONTEND_LANGID == $langId ? "selected=\"selected\"" : "").">".$arrLanguage['name']."</option>\n";
                }
            }
            $menu .= "</select>\n";

            $this->_objTpl->setVariable(array(
                'TXT_CONTACT_BACK'                      => $_ARRAYLANG['TXT_CONTACT_BACK'],
                'TXT_CONTACT_PROCEED'                   => $_ARRAYLANG['TXT_CONTACT_PROCEED'],
                'TXT_CONTACT_COPY_FORM'                 => $_ARRAYLANG['TXT_CONTACT_COPY_FORM'],
                'TXT_CONTACT_SELECT_FRONTEND_LANG_TXT'  => $_ARRAYLANG['TXT_CONTACT_SELECT_FRONTEND_LANG_TXT']
            ));

            $this->_objTpl->setVariable(array(
                'CONTACT_LANG_MENU' => $menu,
                'CONTACT_FORM_ID'   => $formId
            ));
        } else {
            $this->_contactForms();
        }
    }

    /**
     * display recipients in backend
     *
     * @param array $arrRecipients
     */
    function _showRecipients($arrRecipients = null){
    	global $_ARRAYLANG;
		if(!is_array($arrRecipients) || count($arrRecipients) < 1){
			$arrRecipients[$this->getLastRecipientId()+1] = array(
				'name' 	=> $_ARRAYLANG['TXT_CONTACT_NAME'],
				'email' => $_ARRAYLANG['TXT_CONTACT_REGEX_EMAIL'],
				'sort' => 1,
			);
		}
		foreach ($arrRecipients as $id => $arrRecipient) {
			$this->_objTpl->setVariable(array(
				'CONTACT_FORM_RECIPIENT_ID'		=> $id,
				'CONTACT_FORM_RECIPIENT_NAME'   => $arrRecipient['name'],
				'CONTACT_FORM_RECIPIENT_EMAIL'  => $arrRecipient['email'],
				'CONTACT_FORM_RECIPIENT_SORT'   => $arrRecipient['sort'],
			));
			$this->_objTpl->parse("contact_form_recipient_list");
		}
	}


	/**
	* update recipient list
	*
	* @param integer $formId
	* @param boolean $refresh
	* @return array
	*/
    public function setRecipients($arrRecipients)
    {
        global $objDatabase;

        $objDatabase->Execute("
            DELETE FROM `".DBPREFIX."module_contact_recipient`
            WHERE `id_form` = ". intval($_REQUEST['formId'])
        );

        foreach ($arrRecipients as $id => $arrRecipient) {
            // this is a bit radical, but it works.
			$objDatabase->Execute("
	            INSERT INTO `".DBPREFIX."module_contact_recipient`
	            SET `id`  = $id,
	            `id_form` = ".$arrRecipient['id_form'].",
	            `name`	  = '".$arrRecipient['name']."',
	            `email`	  = '".$arrRecipient['email']."',
	            `sort`	  = ".$arrRecipient['sort']);
        }
    }

    /**
     * Modify Form
     *
     * Shows the modifying page.
     * @access private
     * @param bool $copy If the form should be copied or not
     */
    function _modifyForm($copy = false)
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase, $_FRONTEND_LANGID;

        if ($copy) {
            $this->initContactForms(true);
        }
        $this->_objTpl->loadTemplateFile('module_contact_form_modify.html');
        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;

        $this->_pageTitle = (!$copy && $formId != 0) ? $_ARRAYLANG['TXT_CONTACT_MODIFY_CONTACT_FORM'] : $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'];

        $this->_objTpl->setVariable(array(
            'TXT_CONTACT_ID'                                => $_ARRAYLANG['TXT_CONTACT_ID'],
            'TXT_CONTACT_NAME'                              => $_ARRAYLANG['TXT_CONTACT_NAME'],
            'TXT_CONTACT_RECEIVER_ADDRESSES'                => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES'],
            'TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'      => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'],
            'TXT_CONTACT_SAVE'                              => $_ARRAYLANG['TXT_CONTACT_SAVE'],
            'TXT_CONTACT_SEPARATE_MULTIPLE_VALUES_BY_COMMA' => $_ARRAYLANG['TXT_CONTACT_SEPARATE_MULTIPLE_VALUES_BY_COMMA'],
            'TXT_CONTACT_SUBJECT'                           => $_ARRAYLANG['TXT_CONTACT_SUBJECT'],
            'TXT_CONTACT_FORM_DESC'                         => $_ARRAYLANG['TXT_CONTACT_FORM_DESC'],
            'TXT_CONTACT_FEEDBACK'                          => $_ARRAYLANG['TXT_CONTACT_FEEDBACK'],
            'TXT_CONTACT_VALUE_S'                           => $_ARRAYLANG['TXT_CONTACT_VALUE_S'],
            'TXT_CONTACT_FIELD_NAME'                        => $_ARRAYLANG['TXT_CONTACT_FIELD_NAME'],
            'TXT_CONTACT_TYPE'                              => $_ARRAYLANG['TXT_CONTACT_TYPE'],
            'TXT_CONTACT_MANDATORY_FIELD'                   => $_ARRAYLANG['TXT_CONTACT_MANDATORY_FIELD'],
            'TXT_CONTACT_FEEDBACK_EXPLANATION'              => $_ARRAYLANG['TXT_CONTACT_FEEDBACK_EXPLANATION'],
            'TXT_CONTACT_CONFIRM_CREATE_CONTENT_SITE'       => $_ARRAYLANG['TXT_CONTACT_CONFIRM_CREATE_CONTENT_SITE'],
            'TXT_CONTACT_CONFIRM_UPDATE_CONTENT_SITE'       => $_ARRAYLANG['TXT_CONTACT_CONFIRM_UPDATE_CONTENT_SITE'],
            'TXT_CONTACT_SHOW_FORM_AFTER_SUBMIT'            => $_ARRAYLANG['TXT_CONTACT_SHOW_FORM_AFTER_SUBMIT'],
            'TXT_CONTACT_YES'                               => $_ARRAYLANG['TXT_CONTACT_YES'],
            'TXT_CONTACT_NO'                                => $_ARRAYLANG['TXT_CONTACT_NO'],
            'TXT_CONTACT_CAPTCHA_PROTECTION'                => $_ARRAYLANG['TXT_CONTACT_CAPTCHA_PROTECTION'],
            'TXT_CONTACT_CAPTCHA_DESCRIPTION'               => $_ARRAYLANG['TXT_CONTACT_CAPTCHA_DESCRIPTION'],
            'TXT_CONTACT_SEND_COPY_DESCRIPTION'             => $_ARRAYLANG['TXT_CONTACT_SEND_COPY_DESCRIPTION'],
            'TXT_CONTACT_SEND_COPY'                         => $_ARRAYLANG['TXT_CONTACT_SEND_COPY'],
            'TXT_CONTACT_CUSTOM_STYLE_DESCRIPTION'          => $_ARRAYLANG['TXT_CONTACT_CUSTOM_STYLE_DESCRIPTION'],
            'TXT_CONTACT_CUSTOM_STYLE'                      => $_ARRAYLANG['TXT_CONTACT_CUSTOM_STYLE'],
            'TXT_CONTACT_SET_MANDATORY_FIELD'               => $_ARRAYLANG['TXT_CONTACT_SET_MANDATORY_FIELD'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_CONTACT_FORM_FIELDS'                       => $_ARRAYLANG['TXT_CONTACT_FORM_FIELDS'],
            'TXT_CONTACT_DELETE'                            => $_ARRAYLANG['TXT_CONTACT_DELETE'],
            'TXT_CONTACT_MOVE_UP'                           => $_ARRAYLANG['TXT_CONTACT_MOVE_UP'],
            'TXT_CONTACT_MOVE_DOWN'                         => $_ARRAYLANG['TXT_CONTACT_MOVE_DOWN'],
            'TXT_CONTACT_NAME'                              => $_ARRAYLANG['TXT_CONTACT_NAME'],
            'TXT_CONTACT_REGEX_EMAIL'                       => $_ARRAYLANG['TXT_CONTACT_REGEX_EMAIL'],
            'TXT_CONTACT_ADD_OTHER_FIELD'                   => $_ARRAYLANG['TXT_CONTACT_ADD_OTHER_FIELD'],
        ));

        if (!$copy && $formId > 0 && $this->_getContentSiteId($formId)) {
            $jsSubmitFunction = "updateContentSite()";
        } else {
            $jsSubmitFunction = "createContentSite()";
        }

        $lastFieldId = 0;

        if (isset($_POST['saveForm'])) {
            $null = null;
            $arrFields = $this->_getFormFieldsFromPost($null);
            $arrRecipients = $this->_getRecipientsFromPost(false);
			$this->_showRecipients($arrRecipients);
            $formName = isset($_POST['contactFormName']) ? htmlentities(strip_tags(contrexx_stripslashes($_POST['contactFormName'])), ENT_QUOTES, CONTREXX_CHARSET) : '';
            $formEmails = isset($_POST['contactFormEmail']) ? htmlentities(strip_tags(contrexx_stripslashes(trim($_POST['contactFormEmail']))), ENT_QUOTES, CONTREXX_CHARSET) : '';
            if (empty($formEmails)) {
                $formEmails = $_CONFIG['contactFormEmail'];
            }
            $formSubject = $_POST['contactFormSubject'];
            $formText = contrexx_stripslashes($_POST['contactFormText']);
            $formFeedback = contrexx_stripslashes($_POST['contactFormFeedback']);
            $formShowForm = intval($_POST['contactFormShowForm']);
            $formUseCaptcha = intval($_POST['contactFormUseCaptcha']);
            $formUseCustomStyle = intval($_POST['contactFormUseCustomStyle']);
            $formSendCopy = intval($_POST['contactFormSendCopy']);
        } elseif (isset($this->arrForms[$formId])) {
            $arrFields = &$this->getFormFields($formId);
            $formName = $this->arrForms[$formId]['name'];
            $formEmails = $this->arrForms[$formId]['emails'];
            $formSubject = $this->arrForms[$formId]['subject'];
            $formText = $this->arrForms[$formId]['text'];
            $formFeedback = stripslashes($this->arrForms[$formId]['feedback']);
            $formShowForm = $this->arrForms[$formId]['showForm'];
            $formUseCaptcha = $this->arrForms[$formId]['useCaptcha'];
            $formUseCustomStyle = $this->arrForms[$formId]['useCustomStyle'];
            $formSendCopy = $this->arrForms[$formId]['sendCopy'];
			$this->_showRecipients($this->arrForms[$formId]['recipients']);
        } else {
            $formName = '';
            $formEmails = $_CONFIG['contactFormEmail'];
            $formSubject = '';
            $formText = '';
            $formShowForm = 0;
            $formFeedback = $_ARRAYLANG['TXT_CONTACT_DEFAULT_FEEDBACK_TXT'];
            $formUseCaptcha = 1;
            $formUseCustomStyle = 0;
            $formSendCopy = 0;
			$this->_showRecipients();
            $this->_objTpl->setVariable(array(
                'CONTACT_FORM_FIELD_NAME'               => '',
                'CONTACT_FORM_FIELD_ID'                 => 1,
                'CONTACT_FORM_FIELD_TYPE_MENU'          => $this->_getFormFieldTypesMenu('contactFormFieldType[1]', 'text', 'id="contactFormFieldType_1" style="width:110px;" onchange="setFormFieldAttributeBox(this.getAttribute(\'id\'), this.value)"'),
                'CONTACT_FORM_FIELD_CHECK_MENU'         => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType[1]', 'contactFormFieldCheckType_1', 'text', 1),
                'CONTACT_FORM_FIELD_CHECK_BOX'          => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired[1]', 'contactFormFieldRequired_1', 'text', false),
                'CONTACT_FORM_FIELD_ATTRIBUTES'         => $this->_getFormFieldAttribute(1, 'text', '')
            ));
            $this->_objTpl->parse('contact_form_field_list');

            $lastFieldId = 1;
        }

        if ($copy) {
            $formId = 0;
        }


        if (isset($arrFields) && is_array($arrFields)) {
            foreach ($arrFields as $fieldId => $arrField) {
                if ($arrField['is_required'] == 1 ) {
                    $checked = true;
                } else {
                    $checked = false;
                }

                $this->_objTpl->setVariable(array(
                    'CONTACT_FORM_FIELD_NAME'               => htmlentities($arrField['name'], ENT_QUOTES, CONTREXX_CHARSET),
                    'CONTACT_FORM_FIELD_ID'                 => $fieldId,
                    'CONTACT_FORM_FIELD_TYPE_MENU'          => $this->_getFormFieldTypesMenu('contactFormFieldType['.$fieldId.']', $arrField['type'], 'id="contactFormFieldType_'.$fieldId.'" style="width:110px;" onchange="setFormFieldAttributeBox(this.getAttribute(\'id\'), this.value)"'),
                    'CONTACT_FORM_FIELD_CHECK_MENU'         => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType['.$fieldId.']', 'contactFormFieldCheckType_'.$fieldId, $arrField['type'], $arrField['check_type']),
                    'CONTACT_FORM_FIELD_CHECK_BOX'          => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired['.$fieldId.']', 'contactFormFieldRequired_'.$fieldId, $arrField['type'], $checked),
                    'CONTACT_FORM_FIELD_ATTRIBUTES'         => $this->_getFormFieldAttribute($fieldId, $arrField['type'], htmlentities($arrField['attributes'], ENT_QUOTES, CONTREXX_CHARSET))
                ));
                $this->_objTpl->parse('contact_form_field_list');

                $lastFieldId = $fieldId > $lastFieldId ? $fieldId : $lastFieldId;
            }
        }

        if (isset($this->arrForms[$formId])) {
            $actionTitle = $_ARRAYLANG['TXT_CONTACT_MODIFY_CONTACT_FORM'];
            $lang = $this->arrForms[$formId]['lang'];
        } else {
            $actionTitle = $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'];
            $lang = $_FRONTEND_LANGID;
        }

        $this->_objTpl->setVariable(array(
            'CONTACT_FORM_NAME'                             => $formName,
            'CONTACT_FORM_EMAIL'                            => $formEmails,
            'CONTACT_FORM_SUBJECT'                          => $formSubject,
            'CONTACT_FORM_FIELD_NEXT_ID'                    => $lastFieldId+1,
            'CONTACT_FORM_RECIPIENT_NEXT_SORT'              => $this->getHighestSortValue($formId)+2,
            'CONTACT_FORM_RECIPIENT_NEXT_ID'                => $this->getLastRecipientId(true)+2,
            'CONTACT_FORM_FIELD_NEXT_TEXT_TPL'              => $this->_getFormFieldAttribute($lastFieldId+1, 'text', ''),
            'CONTACT_FORM_FIELD_LABEL_TPL'                  => $this->_getFormFieldAttribute($lastFieldId+1, 'label', ''),
            'CONTACT_FORM_FIELD_CHECK_MENU_NEXT_TPL'        => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType['.($lastFieldId+1).']', 'contactFormFieldCheckType_'.($lastFieldId+1), 'text', 1),
            'CONTACT_FORM_FIELD_CHECK_MENU_TPL'             => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType[0]', 'contactFormFieldCheckType_0', 'text', 1),
            'CONTACT_FORM_FIELD_CHECK_BOX_NEXT_TPL'         => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired['.($lastFieldId+1).']', 'contactFormFieldRequired_'.($lastFieldId+1), 'text', false),
            'CONTACT_FORM_FIELD_CHECK_BOX_TPL'              => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired[0]', 'contactFormFieldRequired_0', 'text', false),
            'CONTACT_ACTION_TITLE'                          => $actionTitle,
            'CONTACT_FORM_ID'                               => $formId,
            'CONTACT_FORM_TEXT'                             => get_wysiwyg_editor('contactFormText', $formText, 'shop', $lang),
            'CONTACT_FORM_FEEDBACK'                         => get_wysiwyg_editor('contactFormFeedback', $formFeedback, 'shop', $lang),
            'CONTACT_FORM_SHOW_FORM_YES'                    => $formShowForm ? 'checked="checked"' : '',
            'CONTACT_FORM_SHOW_FORM_NO'                     => $formShowForm ? '' : 'checked="checked"',
            'CONTACT_FORM_USE_CAPTCHA_YES'                  => $formUseCaptcha ? 'checked="checked"' : '',
            'CONTACT_FORM_USE_CAPTCHA_NO'                   => $formUseCaptcha ? '' : 'checked="checked"',
            'CONTACT_FORM_USE_CUSTOM_STYLE_YES'             => $formUseCustomStyle ? 'checked="checked"' : '',
            'CONTACT_FORM_USE_CUSTOM_STYLE_NO'              => $formUseCustomStyle ? '' : 'checked="checked"',
            'CONTACT_FORM_FIELD_TYPE_MENU_TPL'              => $this->_getFormFieldTypesMenu('contactFormFieldType['.($lastFieldId+1).']', key($this->_arrFormFieldTypes), 'id="contactFormFieldType_'.($lastFieldId+1).'" style="width:110px;" onchange="setFormFieldAttributeBox(this.getAttribute(\'id\'), this.value)"'),
            'CONTACT_FORM_FIELD_TEXT_TPL'                   => $this->_getFormFieldAttribute(0, 'text', ''),
            'CONTACT_FORM_FIELD_CHECKBOX_TPL'               => $this->_getFormFieldAttribute(0, 'checkbox', 0),
            'CONTACT_FORM_FIELD_CHECKBOX_GROUP_TPL'         => $this->_getFormFieldAttribute(0, 'checkboxGroup', ''),
            'CONTACT_FORM_FIELD_DATE_TPL'                   => $this->_getFormFieldAttribute(0, 'date', ''),
            'CONTACT_FORM_FIELD_HIDDEN_TPL'                 => $this->_getFormFieldAttribute(0, 'hidden', ''),
            'CONTACT_FORM_FIELD_RADIO_TPL'                  => $this->_getFormFieldAttribute(0, 'radio', ''),
            'CONTACT_FORM_FIELD_SELECT_TPL'                 => $this->_getFormFieldAttribute(0, 'select', ''),
            'CONTACT_JS_SUBMIT_FUNCTION'                    => $jsSubmitFunction,
            'CONTACT_FORM_SEND_COPY_YES'                    => $formSendCopy ? 'checked="checked"' : '',
            'CONTACT_FORM_SEND_COPY_NO'                     => $formSendCopy ? '' : 'checked="checked"',
        ));
    }

    function _getContentSiteId($formId)
    {
        global $objDatabase;

        $objContentSite = $objDatabase->SelectLimit("SELECT `catid` FROM `".DBPREFIX."content_navigation` AS `n`, `".DBPREFIX."modules` AS `m` WHERE `m`.`name`='contact' AND `n`.`module`=`m`.`id` AND `n`.`cmd`='".$formId."'", 1);
        if ($objContentSite !== false) {
            if ($objContentSite->RecordCount() == 1) {
                return $objContentSite->fields['catid'];
            }
        }
        return false;
    }

    function _getContentSiteParCat($formId)
    {
        global $objDatabase;

        $objParentCat = $objDatabase->SelectLimit("SELECT `parcat` FROM `".DBPREFIX."content_navigation` AS `n`, `".DBPREFIX."modules` AS `m` WHERE `m`.`name`='contact' AND `n`.`module`=`m`.`id` AND `n`.`cmd`='".$formId."'", 1);
        if ($objParentCat !== false) {
            if ($objParentCat->RecordCount() == 1) {
                return $objParentCat->fields['parcat'];
            }
        }
        return false;
    }

    function _getFormFieldAttribute($id, $type, $attr)
    {
        global $_ARRAYLANG;

        switch ($type) {
        case 'text':
            return "<input style=\"width:228px;\" type=\"text\" name=\"contactFormFieldAttribute[".$id."]\" value=\"".$attr."\" />\n";
            break;

        case 'label':
            return "<input style=\"width:228px;\" type=\"text\" name=\"contactFormFieldAttribute[".$id."]\" value=\"".$attr."\" />\n";
            break;

        case 'checkbox':
            return "<select style=\"width:228px;\" name=\"contactFormFieldAttribute[".$id."]\">\n
                        <option value=\"0\"".($attr == 0 ? ' selected="selected"' : '').">".$_ARRAYLANG['TXT_CONTACT_NOT_SELECTED']."</option>\n
                        <option value=\"1\"".($attr == 1 ? ' selected="selected"' : '').">".$_ARRAYLANG['TXT_CONTACT_SELECTED']."</option>\n
                    </select>";
            break;

        case 'checkboxGroup':
            return "<input style=\"width:228px;\" type=\"text\" name=\"contactFormFieldAttribute[".$id."]\" value=\"".$attr."\" /> *\n";
            break;

        case 'hidden':
            return "<input style=\"width:228px;\" type=\"text\" name=\"contactFormFieldAttribute[".$id."]\" value=\"".$attr."\" />\n";
            break;

        case 'select':
        case 'radio':
            return "<input style=\"width:228px;\" type=\"text\" name=\"contactFormFieldAttribute[".$id."]\" value=\"".$attr."\" /> *\n";
            break;

        default:
            return '';
            break;
        }
    }

    /**
     * Save Form
     *
     * Saves the form data
     *
     * @access private
     */
    function _saveForm()
    {
        global $_ARRAYLANG, $_CONFIG;

        if (isset($_POST['saveForm'])) {
            $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;
            $formName = isset($_POST['contactFormName']) ? strip_tags(contrexx_addslashes($_POST['contactFormName'])) : '';
            $formSubject = isset($_POST['contactFormSubject']) ? strip_tags(contrexx_addslashes($_POST['contactFormSubject'])) : '';
            $formText = isset($_POST['contactFormText']) ? contrexx_addslashes($_POST['contactFormText']) : '';
            $formFeedback = isset($_POST['contactFormFeedback']) ? contrexx_addslashes($_POST['contactFormFeedback']) : '';
            $formShowForm = intval($_POST['contactFormShowForm']);
            $formUseCaptcha = intval($_POST['contactFormUseCaptcha']);
            $formUseCustomStyle = intval($_POST['contactFormUseCustomStyle']);
            $formSendCopy = intval($_POST['contactFormSendCopy']);
            if (!empty($formName)) {
                if ($this->isUniqueFormName($formName, $formId)) {
                    $uniqueFieldNames = null;
                    $arrFields = $this->_getFormFieldsFromPost($uniqueFieldNames);
                    if ($uniqueFieldNames) {
                        $formEmailsTmp = isset($_POST['contactFormEmail']) ? explode(',', strip_tags(contrexx_stripslashes($_POST['contactFormEmail']))) : '';

                        if (is_array($formEmailsTmp)) {
                            $formEmails = array();
                            foreach ($formEmailsTmp as $email) {
                                $email = trim(contrexx_strip_tags($email));
                                if (!empty($email)) {
                                    array_push($formEmails, $email);
                                }
                            }
                            $formEmails = implode(',', $formEmails);
                        } else {
                            $formEmails = '';
                        }
                        if (empty($formEmails)) {
                            $formEmails = $_CONFIG['contactFormEmail'];
                        }

                        $boolUsesRecipientField = false;
                        $fileFieldFound = false;
                        foreach ($arrFields as $arrField) {
                        	if($arrField['type'] == 'recipient'){
                        	    $boolUsesRecipientField = true;
                            }

                            if($arrField['type'] == 'file') {
                                if(!$fileFieldFound) { //first time running into a file field
                                    $fileFieldFound = true;
                                }
                                else { //multiple file fields in this form - we do not want this
                                    $this->_statusMessageErr .= $_ARRAYLANG['TXT_CONTACT_FORM_MULTIPLE_UPLOAD_FIELDS'];
                                    $this->_modifyForm();
                                    return;
                                }
                            }
                        }                       

                        if ($formId > 0) {
                            // This updates the database
                            $this->updateForm($formId, $formName, $formEmails, $formSubject, $formText, $formFeedback, $formShowForm, $formUseCaptcha, $formUseCustomStyle, $arrFields, $formSendCopy);
                        } else {
                            $this->addForm($formName, $formEmails, $formSubject, $formText, $formFeedback, $formShowForm, $formUseCaptcha, $formUseCustomStyle, $arrFields, $formSendCopy);
                        }
                        
                        $arrRecipients = $this->_getRecipientsFromPost($boolUsesRecipientField);
                        if($this->_invalidRecipients && $boolUsesRecipientField){
                            return $this->_modifyForm();
                        }else{
                            $this->setRecipients($arrRecipients);
                        }

                        $this->_statusMessageOk .= $_ARRAYLANG['TXT_CONTACT_FORM_SUCCESSFULLY_SAVED']."<br />";

                        if (isset($_POST['contentSiteAction'])) {
                            switch ($_POST['contentSiteAction']) {
                                case 'create':
                                    $this->_createContentPage();
                                    break;

                                case 'update':
                                    $this->_updateContentSite();
                                    break;

                                default:
                                    break;
                            }
                        }

                        $this->_contactForms();
                    } else {
                        $this->_statusMessageErr .= $_ARRAYLANG['TXT_CONTACT_FORM_FIELD_UNIQUE_MSG'];
                        $this->_modifyForm();
                    }
                } else {
                    $this->_statusMessageErr .= $_ARRAYLANG['TXT_CONTACT_FORM_NAME_IS_NOT_UNIQUE_MSG'];
                    $this->_modifyForm();
                }
            } else {
                $this->_statusMessageErr .= $_ARRAYLANG['TXT_CONTACT_FORM_NAME_REQUIRED_MSG'];
                $this->_modifyForm();
            }
        } else {
            $this->_modifyForm();
        }
    }

    function _deleteFormEntry()
    {
        global $_ARRAYLANG;

        if (isset($_GET['entryId'])) {
            $entryId = intval($_GET['entryId']);
            $this->deleteFormEntry($entryId);
        } elseif (isset($_POST['selectedEntries']) && count($_POST['selectedEntries']) > 0) {
            foreach ($_POST['selectedEntries'] as $entryId) {
                $this->deleteFormEntry(intval($entryId));
            }
        }
	
        $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_FORM_ENTRY_DELETED'];

        $this->initContactForms(true);
        $this->_contactFormEntries();
    }

    function _deleteForm()
    {
        global $_ARRAYLANG;

        if (isset($_GET['formId'])) {
            $formId = intval($_GET['formId']);

            if ($formId > 0) {
                if ($this->deleteForm($formId)) {
                    $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_CONTACT_FORM_SUCCESSFULLY_DELETED'];

                    if (isset($_GET['deleteContent']) && $_GET['deleteContent'] == 'true') {
                        $this->_deleteContentSite($formId);
                    }
                } else {
                    $this->_statusMessageErr = $_ARRAYLANG['TXT_CONTACT_FAILED_DELETE_CONTACT_FORM'];
                }
            }
        }
        $this->_contactForms();
    }

    function _deleteContentSite($formId)
    {
        global $objDatabase, $_ARRAYLANG;

        Permission::checkAccess(26, 'static');

        $formId = intval($_REQUEST['formId']);
        $pageId = $this->_getContentSiteId($formId);

        if ($pageId != 0) {
            if ($this->boolHistoryEnabled) {
                $objResult = $objDatabase->Execute('SELECT  id
                                                    FROM    '.DBPREFIX.'content_navigation_history
                                                    WHERE   is_active="1" AND
                                                            catid='.$pageId.'
                                                    LIMIT   1
                                                ');
                $objDatabase->Execute(' INSERT
                                        INTO    '.DBPREFIX.'content_logfile
                                        SET     action="delete",
                                                history_id='.$objResult->fields['id'].',
                                                is_validated="'.(($this->boolHistoryActivate) ? 1 : 0).'"
                                    ');
                $objDatabase->Execute(' UPDATE  '.DBPREFIX.'content_navigation_history
                                        SET     changelog='.time().'
                                        WHERE   catid='.$pageId.' AND
                                                is_active="1"
                                        LIMIT   1
                                    ');
            }

            if ($this->boolHistoryEnabled) {
                if (!$this->boolHistoryActivate) {
                    $boolDelete = false;
                    $this->_statusMessageOk .= '<br />'.$_ARRAYLANG['TXT_CONTACT_DATA_RECORD_DELETED_SUCCESSFUL_VALIDATE'];
                } else {
                    $boolDelete = true;
                }
            } else {
                $boolDelete = true;
            }

            if ($boolDelete) {
                $q1 = "DELETE FROM ".DBPREFIX."content WHERE id=".$pageId;
                $q2 = "DELETE FROM ".DBPREFIX."content_navigation WHERE catid=".$pageId;
                if ($objDatabase->Execute($q1) === false || $objDatabase->Execute($q2) === false) {
                    $this->_statusMessageErr = $_ARRAYLANG['TXT_CONTACT_DATABASE_QUERY_ERROR'];
                } else {
                     $this->_statusMessageOk .= '<br />'.$_ARRAYLANG['TXT_CONTACT_DATA_RECORD_DELETED_SUCCESSFUL'];
                }
            }

            $this->_collectLostPages();
        }
    }

    /**
    * The function collects all categories without an existing parcat and assigns it to "lost and found"
    *
    * @global    ADONewConnection
    */
    function _collectLostPages() {
        global $objDatabase;

        $objResult = $objDatabase->Execute('    SELECT  catid,
                                                        parcat,
                                                        lang
                                                FROM    '.DBPREFIX.'content_navigation
                                                WHERE   parcat <> 0
                                        ');
        if ($objResult->RecordCount() > 0) {
            //subcategories have been found
            while ($row = $objResult->FetchRow()) {
                $objSubResult = $objDatabase->Execute(' SELECT  catid
                                                        FROM    '.DBPREFIX.'content_navigation
                                                        WHERE   catid='.$row['parcat'].'
                                                        LIMIT   1
                                                    ');
                if ($objSubResult->RecordCount() != 1) {
                    //this is a "lost" category.. assign it to "lost and found"
                    $objSubSubResult = $objDatabase->Execute('  SELECT  catid
                                                                FROM    '.DBPREFIX.'content_navigation
                                                                WHERE   module=1 AND
                                                                        cmd="lost_and_found" AND
                                                                        lang='.$row['lang'].'
                                                                LIMIT   1
                                                            ');
                    $subSubRow = $objSubSubResult->FetchRow();
                    $objDatabase->Execute(' UPDATE  '.DBPREFIX.'content_navigation
                                            SET     parcat='.$subSubRow['catid'].'
                                            WHERE   catid='.$row['catid'].'
                                            LIMIT   1
                                        ');
                }
            }
        }
    }

    function _getRecipientsFromPost($logErrors = true)
    {
        global $_ARRAYLANG;
        $arrErrors = $arrRecipients = array();
		if(isset($_POST['contactFormRecipientName']) && is_array($_POST['contactFormRecipientName'])){
			$formId = intval($_REQUEST['formId']);
			foreach ($_POST['contactFormRecipientName'] as $id => $recipientName) {
                $recipientName  = strip_tags(contrexx_stripslashes($recipientName));
                $recipientEmail = strip_tags(contrexx_stripslashes($_POST['contactFormRecipientEmail'][$id]));
                if(strpos($recipientEmail, ',')){
                    foreach (explode(',', $recipientEmail) as $email) {
                        if ($logErrors && $email != $_ARRAYLANG['TXT_CONTACT_REGEX_EMAIL']  && !preg_match('/[a-z0-9]+(?:[_\.-][a-z0-9]+)*?@[a-z0-9]+(?:[\.-][a-z0-9]+)*?\.[a-z]{2,6}/', $email)){
                            $arrErrors[] = sprintf($_ARRAYLANG['TXT_CONTACT_INVALID_EMAIL'], $email);
                        }
                    }
                }elseif ($logErrors && $email != $_ARRAYLANG['TXT_CONTACT_REGEX_EMAIL'] && !preg_match('/[a-z0-9]+(?:[_\.-][a-z0-9]+)*?@[a-z0-9]+(?:[\.-][a-z0-9]+)*?\.[a-z]{2,6}/', $recipientEmail)){
                    $arrErrors[] = sprintf($_ARRAYLANG['TXT_CONTACT_INVALID_EMAIL'], $recipientEmail);
                }

                $recipientSort  = intval($_POST['contactFormRecipientSort'][$id]);
                if($recipientEmail != $_ARRAYLANG['TXT_CONTACT_REGEX_EMAIL']){
              		$arrRecipients[$id] = array(
              			'name' 		=>	$recipientName,
              			'email' 	=>	$recipientEmail,
              			'sort'		=> 	$recipientSort,
              			'id_form'	=>  $formId
              		);
                }
			}
		}
    	if(!empty($arrErrors)){
    	    $this->_invalidRecipients = true;
            $this->_statusMessageErr .= implode("<br />", $arrErrors);
    	}
		return $arrRecipients;
	}

    function _getFormFieldsFromPost(&$uniqueFieldNames)
    {
        $uniqueFieldNames = true;
        $arrFields = array();
        $arrFieldNames = array();
        $orderId = 0;

        if (isset($_POST['contactFormFieldName']) && is_array($_POST['contactFormFieldName'])) {
            foreach ($_POST['contactFormFieldName'] as $id => $fieldName) {
                $fieldName = strip_tags(contrexx_stripslashes($fieldName));
                $type = isset($_POST['contactFormFieldType'][$id]) && array_key_exists(contrexx_stripslashes($_POST['contactFormFieldType'][$id]), $this->_arrFormFieldTypes) ? contrexx_stripslashes($_POST['contactFormFieldType'][$id]) : key($this->_arrFormFieldTypes);
                $attributes = isset($_POST['contactFormFieldAttribute'][$id]) && !empty($_POST['contactFormFieldAttribute'][$id]) ? ($type == 'text' || $type == 'label' || $type == 'file' || $type == 'textarea' || $type == 'hidden' || $type == 'radio' || $type == 'checkboxGroup' || $type == 'password' || $type == 'select' ? strip_tags(contrexx_stripslashes($_POST['contactFormFieldAttribute'][$id])) : intval($_POST['contactFormFieldAttribute'][$id])) : '';
                $is_required = isset($_POST['contactFormFieldRequired'][$id]) ? 1 : 0;
                $checkType = isset($_POST['contactFormFieldCheckType'][$id]) ? intval($_POST['contactFormFieldCheckType'][$id]) : 1;

                if (!in_array($fieldName, $arrFieldNames)) {
                    array_push($arrFieldNames, $fieldName);
                } else {
                    $uniqueFieldNames = false;
                }

                switch ($type) {
                    case 'checkboxGroup':
                    case 'radio':
                    case 'select':
                        $arrAttributes = explode(',', $attributes);
                        $arrNewAttributes = array();
                        foreach ($arrAttributes as $strAttribute) {
                            array_push($arrNewAttributes, trim($strAttribute));
                        }
                        $attributes = implode(',', $arrNewAttributes);
                        break;

                    default:
                        break;
                }

                $arrFields[intval($id)] = array(
                    'name'          => $fieldName,
                    'type'          => $type,
                    'attributes'    => $attributes,
                    'order_id'      => $orderId,
                    'is_required'   => $is_required,
                    'check_type'    => $checkType
                );

                $orderId++;
            }
        }
        return $arrFields;
    }

    /**
     * Field Types Menu
     *
     * Generates a xhtml selection list with all the field types
     * @access private
     */
    function _getFormFieldTypesMenu($name, $selectedType, $attrs = '')
    {

        $menu = "<select name=\"".$name."\" ".$attrs.">\n";

        foreach ($this->_arrFormFieldTypes as $type => $desc) {
            $menu .= "<option value=\"".$type."\"".($selectedType == $type ? 'selected="selected"' : '').">".$desc."</option>\n";
        }

        $menu .= "</select>\n";
        return  $menu;
    }

    /**
     * Check Types Menu
     *
     * Generates a selection list with all possible types which can be checked
     * @access private
     * @param string $name Name of the selection list
     * @param array $list List with all of the possible types (email, url, text, numbers...)
     * @param int $selected Which option has to be selected
     */
    function _getFormFieldCheckTypesMenu($name, $id,  $type, $selected)
    {
        global $_ARRAYLANG;

        switch ($type) {
            case 'checkbox':
            case 'checkboxGroup':
            case 'date':
            case 'hidden':
            case 'radio':
            case 'select':
            case 'label':
            case 'recipient':
                $menu = '';
                break;

            case 'text':
            case 'file':
            case 'password':
            case 'textarea':
            default:
                $menu = "<select name=\"".$name."\" id=\"".$id."\">\n";
                foreach ($this->arrCheckTypes as $typeId => $type) {
                    if ($selected == $typeId) {
                        $select = "selected=\"selected\"";
                    } else {
                        $select = "";
                    }

                    $menu .= "<option value=\"".$typeId."\" $select>".$_ARRAYLANG[$type['name']]."</option>\n";
                }

                $menu .= "</select>\n";
            break;
        }
        return  $menu;
    }

    function _getFormFieldRequiredCheckBox($name, $id, $type, $selected)
    {
        global $_ARRAYLANG;

        switch ($type) {
            case 'hidden':
            case 'select':
            case 'label':
            case 'recipient':
                return '';
                break;

            default:
                return '<input type="checkbox" name="'.$name.'" id="'.$id.'" '.($selected ? 'checked="checked"' : '').' />';
                break;
        }
    }

    /**
     * Source Code page
     *
     * Gets the page for showing the source code
     * @access public
     * @global array
     */
    function _sourceCode($formId = NULL)
    {
        global $_ARRAYLANG;

        if (!isset($formId)) {
            $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;
        }

        if ($formId > 0 && isset($this->arrForms[$formId])) {
            $this->_objTpl->loadTemplateFile('module_contact_form_code.html');
            $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_SOURCECODE'];

            $this->_objTpl->setVariable(array(
                'TXT_CONTACT_SOURCECODE'            => $_ARRAYLANG['TXT_CONTACT_SOURCECODE'],
                'TXT_CONTACT_PREVIEW'               => $_ARRAYLANG['TXT_CONTACT_PREVIEW'],
                'TXT_CONTACT_COPY_SOURCECODE_MSG'   => $_ARRAYLANG['TXT_CONTACT_COPY_SOURCECODE_MSG'],
                'TXT_CONTACT_SELECT_ALL'            => $_ARRAYLANG['TXT_CONTACT_SELECT_ALL'],
                'TXT_CONTACT_BACK'                  => $_ARRAYLANG['TXT_CONTACT_BACK']
            ));

            $contentSiteExists = $this->_getContentSiteId($formId);

            $this->_objTpl->setVariable(array(
                'CONTACT_CONTENT_SITE_ACTION_TXT'   => $contentSiteExists > 0 ? $_ARRAYLANG['TXT_CONTACT_UPDATE_CONTENT_SITE'] : $_ARRAYLANG['TXT_CONTACT_NEW_PAGE'],
                'CONTACT_CONTENT_SITE_ACTION'       => $contentSiteExists > 0 ? 'updateContent' : 'newContent',
                'CONTACT_SOURCECODE_OF'             => str_replace('%NAME%', $this->arrForms[$formId]['name'], $_ARRAYLANG['TXT_CONTACT_SOURCECODE_OF_NAME']),
                'CONTACT_PREVIEW_OF'                => str_replace('%NAME%', $this->arrForms[$formId]['name'], $_ARRAYLANG['TXT_CONTACT_PREVIEW_OF_NAME']),
                'CONTACT_FORM_SOURCECODE'           => htmlentities($this->_getSourceCode($formId, false, true), ENT_QUOTES, CONTREXX_CHARSET),
                'CONTACT_FORM_PREVIEW'              => $this->_getSourceCode($formId, true),
                'FORM_ID'                           => $formId
            ));
        } else {
            $this->_contactForms();
        }
    }

    function _getSourceCode($id, $preview = false, $show = false)
    {
        global $_ARRAYLANG, $objInit, $objDatabase;

        $hasFileInput = false; //remember if we added a file input -> this would need the uploader to be initialized

        $arrFields = $this->getFormFields($id);
        $sourcecode = array();
        $this->initContactForms(true);
        $sourcecode[] = "{CONTACT_FEEDBACK_TEXT}";
        $sourcecode[] = "<!-- BEGIN formText -->".$this->arrForms[$id]['text'] . "<!-- END formText -->";
        $sourcecode[] = '<div id="contactFormError" style="color: red; display: none;">';
        $sourcecode[] = $preview ? $_ARRAYLANG['TXT_NEW_ENTRY_ERORR'] : '{TXT_NEW_ENTRY_ERORR}';
        $sourcecode[] = "</div>";
        $sourcecode[] = "<!-- BEGIN contact_form -->";
        $sourcecode[] = '<form action="'.($preview ? '../' : '')."index.php?section=contact&amp;cmd=".$id.'" ';
        $sourcecode[] = 'method="post" enctype="multipart/form-data" onsubmit="return checkAllFields();" id="contactForm'.(($this->arrForms[$id]['useCustomStyle'] > 0) ? '_'.$id : '').'" class="contactForm'.(($this->arrForms[$id]['useCustomStyle'] > 0) ? '_'.$id : '').'">';
        $sourcecode[] = '<fieldset id="contactFrame">';
        $sourcecode[] = "<legend>".$this->arrForms[$id]['name']."</legend>";

        foreach ($arrFields as $fieldId => $arrField) {
            if ($arrField['is_required']) {
                $required = '<strong class="is_required">*</strong>';
            } else {
                $required = "";
            }

            $sourcecode[] = '<p> <label for="contactFormFieldId_'.$fieldId.'">'.(($arrField['type'] != 'hidden' && $arrField['type'] != 'label') ? $arrField['name'] : '&nbsp;')." ".$required.'</label>';

            switch ($arrField['type']) {
                case 'text':
                    $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="text" name="contactFormField_'.$fieldId.'" value="'.($arrField['attributes'] == '' ? '{'.$fieldId.'_VALUE}' : $arrField['attributes']).'" />';
                    break;

                case 'label':
                    $sourcecode[] = $arrField['attributes'] == '' ? '{'.$fieldId.'_VALUE}' : $arrField['attributes'];
                    break;

                case 'checkbox':
                    $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="checkbox" name="contactFormField_'.$fieldId.'" value="1"'.($arrField['attributes'] == '1' ? ' checked="checked"' : '').' />';
                    break;

                case 'checkboxGroup':
                    $sourcecode[] = '<p class="contactFormGroup" id="contactFormFieldId_'.$fieldId.'">';
                    $options = explode(',', $arrField['attributes']);
                    foreach ($options as $index => $option) {
                        $sourcecode[] = '<input type="checkbox" class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'[]" id="contactFormField_'.$index.'_'.$fieldId.'" value="'.$option.'" /><label class="noCaption" for="contactFormField_'.$index.'_'.$fieldId.'">'.$option.'</label><br />';
                    }
                    $sourcecode[] = '</p>';
                    break;

                case 'date':
                    $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" type="text" name="contactFormField_'.$fieldId.'" id="DPC_date'.$fieldId.'_YYYY-MM-DD" />';
                    break;

                case 'file':
                    $sourcecode[] = '<div class="contactFormUpload"><div class="contactFormClass_uploadWidget" id="contactFormField_uploadWidget"></div>';
                    $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormField_upload" type="file" name="contactFormField_upload" disabled="disabled"/></div>';
                    $hasFileInput = true;
                    break;

                case 'hidden':
                    $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="hidden" name="contactFormField_'.$fieldId.'" value="'.($arrField['attributes'] == "" ? "{".$fieldId."_VALUE}" : $arrField['attributes']).'" />';
                    break;

                case 'password':
                    $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="password" name="contactFormField_'.$fieldId.'" value="" />';
                    break;

                case 'radio':
                    $sourcecode[] = '<p class="contactFormGroup" id="contactFormFieldId_'.$fieldId.'">';
                    $options = explode(',', $arrField['attributes']);
                    foreach ($options as $index => $option) {
                        $sourcecode[] .= '<input class="contactFormClass_'.$arrField['type'].'" type="radio" name="contactFormField_'.$fieldId.'" id="contactFormField_'.$index.'_'.$fieldId.'" value="'.$option.'" {SELECTED_'.$fieldId.'_'.$index.'} /><label class="noCaption" for="contactFormField_'.$index.'_'.$fieldId.'">'.$option.'</label><br />';
                    }
                    $sourcecode[] = '</p>';
                    break;

                case 'select':
                    $options = explode(',', $arrField['attributes']);
                    $sourcecode[] = '<select class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'" id="contactFormFieldId_'.$fieldId.'">';
                    foreach ($options as $index => $option) {
                        $sourcecode[] = "<option {SELECTED_".$fieldId."_".$index."}>".$option."</option>";
                    }
                    $sourcecode[] = "</select>";
                    break;

                case 'textarea':
                    $sourcecode[] = '<textarea class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'" id="contactFormFieldId_'.$fieldId.'" rows="5" cols="20">{'.$fieldId.'_VALUE}</textarea>';
                    break;
                case 'recipient':
                    $sourcecode[] = '<select class="contactFormClass_'.$arrField['type'].'" name="contactFormField_recipient" id=contactFormField_'.$fieldId.'">';
                    foreach ($this->arrForms[$id]['recipients'] as $index => $arrRecipient) {
                    	$sourcecode[] = '<option value="'.$index.'" {SELECTED_'.$fieldId.'_'.$index.'}>'.$arrRecipient['name'].'</option>';
                    }
                    $sourcecode[] = "</select>";
                    break;
            }
            $sourcecode[] = "</p>";
        }

        if ($preview) {
            if ($this->arrForms[$id]['useCaptcha']) {
                include_once ASCMS_LIBRARY_PATH.'/spamprotection/captcha.class.php';
                $captcha = new Captcha();

                $alt = $captcha->getAlt();
                $url = $captcha->getUrl();

                $frontendLang = $objInit->userFrontendLangId;
                $themeId = $objInit->arrLang[$frontendLang]['themesid'];

                if(($objRS = $objDatabase->SelectLimit("SELECT `foldername` FROM `".DBPREFIX."skins` WHERE `id` = ".$themeId, 1)) !== false){
                    $themePath = $objRS->fields['foldername'];
                }

                $sourcecode[] = '<link href="../themes/'.$themePath.'/buildin_style.css" rel="stylesheet" type="text/css" />';
                $sourcecode[] = '<p><span>'.$_ARRAYLANG['TXT_CONTACT_CAPTCHA_DESCRIPTION']."</span><br />";
                $sourcecode[] = '<img class="captcha" src="'.$url.'" alt="'.$alt.'" /></p>';
                $sourcecode[] = '<div style="color: red;"></div>';
                $sourcecode[] = "<p>";
                $sourcecode[] = '<label for="contactFormCaptcha"> CAPTCHA </label><input id="contactFormCaptcha" type="text" name="contactFormCaptcha" /><br />';
                $sourcecode[] = "</p>";
            }
        } else {
            $sourcecode[] = "<!-- BEGIN contact_form_captcha -->";
            $sourcecode[] = '<div style="color: red;">{CONTACT_CAPTCHA_ERROR}</div>';
            $sourcecode[] = "<p>";
            $sourcecode[] = "{TXT_CONTACT_CAPTCHA_DESCRIPTION}<br />";
            $sourcecode[] = '</p>';
            $sourcecode[] = '<p><span>CAPTCHA</span><img class="captcha" src="{CONTACT_CAPTCHA_URL}" alt="{CONTACT_CAPTCHA_ALT}" />';
            $sourcecode[] = '<input id="contactFormCaptcha" type="text" name="contactFormCaptcha" /><br />';
            $sourcecode[] = "</p>";
            $sourcecode[] = "<!-- END contact_form_captcha -->";
        }

        $sourcecode[] = "<p>";
        $sourcecode[] = '<input class="contactFormClass_button" type="submit" name="submitContactForm" value="'.($preview ? $_ARRAYLANG['TXT_CONTACT_SUBMIT'] : '{TXT_CONTACT_SUBMIT}').'" /><input class="contactFormClass_button" type="reset" value="'.($preview ? $_ARRAYLANG['TXT_CONTACT_RESET'] : '{TXT_CONTACT_RESET}').'" />';
        $sourcecode[] = "</p>";
        $sourcecode[] = '<input type="hidden" name="unique_id" value="{CONTACT_UNIQUE_ID}" />';
        $sourcecode[] = "</fieldset>";
        $sourcecode[] = "</form>";
        $sourcecode[] = "<!-- END contact_form -->";

        $sourcecode[] = $this->_getJsSourceCode($id, $arrFields, $preview, $show);

        if($hasFileInput)
            $sourcecode[] = $this->getUploaderSourceCode();

        if ($show) {
            $sourcecode = preg_replace('/\{([A-Z0-9_-]+)\}/', '[[\\1]]', $sourcecode);
        }

        return implode("\n", $sourcecode);
    }

    function _getEntryDetails($arrEntry, $formId)
    {
        global $_ARRAYLANG;

        $arrFormFields = $this->getFormFields($formId);
        $rowNr = 0;

        $sourcecode .= "<table border=\"0\" class=\"adminlist\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\">\n";
        foreach ($arrFormFields as $arrField) {
            $sourcecode .= "<tr class=".($rowNr % 2 == 0 ? 'row1' : 'row2').">\n";
            $sourcecode .= "<td style=\"vertical-align:top;\" width=\"15%\">".$arrField['name'].($arrField['type'] == 'hidden' ? ' (hidden)' : '')."</td>\n";
            $sourcecode .= "<td width=\"85%\">";

            switch ($arrField['type']) {
                case 'checkbox':
                    $sourcecode .= isset($arrEntry['data'][$arrField['name']]) && $arrEntry['data'][$arrField['name']] ? ' '.$_ARRAYLANG['TXT_CONTACT_YES'] : ' '.$_ARRAYLANG['TXT_CONTACT_NO'];
                    break;

                case 'file':
                    if(isset($arrEntry['data'][$arrField['name']])) {
                        $fieldData = $arrEntry['data'][$arrField['name']];
                        if(substr($fieldData,0,1) == '*') {
                            $arrFiles = explode('*', substr($fieldData,1)); //the substr kills the leading '*';
                            foreach($arrFiles as $file) {
                                $sourcecode .= '<a href="'.ASCMS_PATH_OFFSET.htmlentities($file, ENT_QUOTES, CONTREXX_CHARSET).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.htmlentities($file, ENT_QUOTES, CONTREXX_CHARSET).'</a>';
                                $sourcecode .= '&nbsp;';
                            }
                        }
                        else {
                            $sourcecode .= '<a href="'.ASCMS_PATH_OFFSET.htmlentities($fieldData, ENT_QUOTES, CONTREXX_CHARSET).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.htmlentities($fieldData, ENT_QUOTES, CONTREXX_CHARSET).'</a>';
                        }
                    }
                    else {
                        $sourcecode .= '&nbsp;';
                    }
                    break;

                case 'text':
                case 'checkboxGroup':
                case 'date':
                case 'hidden':
                case 'password':
                case 'radio':
                case 'select':
                case 'textarea':
                    $sourcecode .= isset($arrEntry['data'][$arrField['name']]) ? nl2br(htmlentities($arrEntry['data'][$arrField['name']], ENT_QUOTES, CONTREXX_CHARSET)) : '&nbsp;';
                    break;
            }

            $sourcecode .= "</td>\n";
            $sourcecode .= "</tr>\n";

            $rowNr++;
        }
        $sourcecode .= "</table>\n";

        return $sourcecode;
    }

    function csv_mb_convert_encoding($data)
    {
        static $doConvert;
    
        if (!isset($doConvert)) {
            if (function_exists("mb_detect_encoding")
                && $this->_csvCharset != CONTREXX_CHARSET
            ) {
                $doConvert = true;
            } else {
                $doConvert = false;
            }
        }

        if ($doConvert) {
            return mb_convert_encoding($data, $this->_csvCharset, CONTREXX_CHARSET);;
        } else {
            return $data;
        }
    }

    /**
     * Get CSV File
     *
     * @access private
     * @global ADONewConnection
     * @global array
     * @global array
     */
    function _getCsv()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $id = intval($_GET['formId']);

        $format = 'default';
        $csvFormat = array(
            'default' => array(
                'charset'       => CONTREXX_CHARSET,
                'delimiter'     => ';',
                'enclosure'     => '"',
                'content-type'  => 'text/comma-separated-values',
                'BOM'           => null,
                'LFB'           => "\r\n"
            ),
            'excel' => array(
                'charset'       => 'UTF-16LE',
                'delimiter'     => "\t",
                'enclosure'     => '"',
                'content-type'  => 'application/vnd.ms-excel',
                'BOM'           => chr(255).chr(254),
                'LFB'           => "\r\n"
            )
        );


        if (empty($id)) {
            CSRF::header("Location: index.php?cmd=contact");
            return;
        }

        if (isset($_GET['format']) && isset($csvFormat[$_GET['format']])) {
            $format = $_GET['format'];
        }

        // $this->_csvCharset must be set first, because the methode $this->csv_mb_convert_encoding depends on this variable
        $this->_csvCharset = $csvFormat[$format]['charset'];
        $this->_csvEnclosure = $this->csv_mb_convert_encoding($csvFormat[$format]['enclosure'], $csvFormat[$format]['charset'], CONTREXX_CHARSET);
        $this->_csvSeparator = $this->csv_mb_convert_encoding($csvFormat[$format]['delimiter'], $csvFormat[$format]['charset'], CONTREXX_CHARSET);
        $this->_csvLFB = $this->csv_mb_convert_encoding($csvFormat[$format]['LFB'], $csvFormat[$format]['charset'], CONTREXX_CHARSET);

        $filename = $this->_replaceFilename($this->arrForms[$id]['name']. ".csv");
        $arrFormFields = $this->getFormFields($id);

        // Because we return a csv, we need to set the correct header
        header("Content-Type: ".$csvFormat[$format]['content-type']."; charset=".$csvFormat[$format]['charset'], true);
        header("Content-Disposition: attachment; filename=\"$filename\"", true);

        // Print BOM
        print $csvFormat[$format]['BOM'];

        foreach ($arrFormFields as $arrField) {
            print $this->_escapeCsvValue($arrField['name']).$this->_csvSeparator;
        }

        $arrSettings = $this->getSettings();

        print ($arrSettings['fieldMetaDate'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_DATE']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaHost'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_HOSTNAME']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaLang'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_BROWSER_LANGUAGE']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaIP'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_IP_ADDRESS']) : '')
                .$this->_csvLFB;

        $query = "SELECT id, `time`, `host`, `lang`, `ipaddress`, data FROM ".DBPREFIX."module_contact_form_data WHERE id_form=".$id." ORDER BY `time` DESC";
        $objEntry = $objDatabase->Execute($query);
        if ($objEntry !== false) {
            while (!$objEntry->EOF) {
                $arrData = array();
                foreach (explode(';', $objEntry->fields['data']) as $keyValue) {
                    $arrTmp = explode(',', $keyValue);
                    $arrData[base64_decode($arrTmp[0])] = base64_decode($arrTmp[1]);
                }

                foreach ($arrFormFields as $arrField) {
                    switch ($arrField['type']) {
                        case 'checkbox':
                            print $this->_escapeCsvValue(isset($arrData[$arrField['name']]) && $arrData[$arrField['name']] ? ' '.$_ARRAYLANG['TXT_CONTACT_YES'] : ' '.$_ARRAYLANG['TXT_CONTACT_NO']);
                            break;

                        case 'file':
                            print $this->_escapeCsvValue(isset($arrData[$arrField['name']]) ? ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.$arrData[$arrField['name']] : '');
                            break;

                        case 'text':
                        case 'checkboxGroup':
                        case 'hidden':
                        case 'password':
                        case 'radio':
                        case 'select':
                        case 'textarea':
                            print isset($arrData[$arrField['name']]) ? $this->_escapeCsvValue($arrData[$arrField['name']]) : '';
                            break;
                    }

                    print $this->_csvSeparator;
                }

                print ($arrSettings['fieldMetaDate'] == '1' ? $this->_escapeCsvValue(date(ASCMS_DATE_FORMAT, $objEntry->fields['time'])).$this->_csvSeparator : '')
                    .($arrSettings['fieldMetaHost'] == '1' ? $this->_escapeCsvValue($objEntry->fields['host']).$this->_csvSeparator : '')
                    .($arrSettings['fieldMetaLang'] == '1' ? $this->_escapeCsvValue($objEntry->fields['lang']).$this->_csvSeparator : '')
                    .($arrSettings['fieldMetaIP'] == '1' ? $this->_escapeCsvValue($objEntry->fields['ipaddress']) : '')
                    .$this->_csvLFB;

                $objEntry->MoveNext();
            }
        }

        exit();
    }

    /**
     * Escape a value that it could be inserted into a csv file.
     *
     * @param string $value
     * @return string
     */
    function _escapeCsvValue($value)
    {
        $value = preg_replace('/\r\n/', "\n", $value);
        $value = $this->csv_mb_convert_encoding($value, $this->_csvCharset, CONTREXX_CHARSET);;
        $valueModified = str_replace($this->_csvEnclosure, $this->_csvEnclosure.$this->_csvEnclosure, $value);
        $value = $this->_csvEnclosure.$valueModified.$this->_csvEnclosure;

        return $value;
    }

    /**
     * Replaces the special characters
     *
     * Replaces the special characters in a filename like whitespaces or
     * umlauts. Needed by the CSV generator.
     *
     * @access private
     * @param $filename string Filename where the characters have
     *                         to be replaced
     */
    function _replaceFilename($filename)
    {
        $filename = strtolower($filename);

        // replace whitespaces
        $filename = preg_replace('/\s/', '_', $filename);

        // replace umlauts
// TODO: Use octal notation for special characters in regexes!
        $filename = preg_replace('%�%', 'oe', $filename);
        $filename = preg_replace('%�%', 'ue', $filename);
        $filename = preg_replace('%�%', 'ae', $filename);

        return $filename;
    }

    /**
     * Generates a new page in the content manager
     *
     * Adds a new page in the content manager with the source code
     * of the form the user needs.
     *
     * @access private
     * @global array
     * @global ADONewConnection
     * @global integer
     * @global array
     */
    function _createContentPage()
    {
        global $_ARRAYLANG, $objDatabase, $_FRONTEND_LANGID, $_CONFIG;

        Permission::checkAccess(5, 'static');

        $formId = intval($_REQUEST['formId']);
        if ($formId > 0) {
            $objFWUser = FWUser::getFWUserObject();
            $objContactForm = $objDatabase->SelectLimit("SELECT name FROM ".DBPREFIX."module_contact_form WHERE id=".$formId, 1);
            if ($objContactForm !== false) {
                $catname = addslashes($objContactForm->fields['name']);
            }

            $currentTime = time();
            $content = addslashes($this->_getSourceCode($formId));

            $q1 = "INSERT INTO ".DBPREFIX."content_navigation (
                                        catname,
                                        displayorder,
                                        displaystatus,
                                        username,
                                        changelog,
                                        cmd,
                                        lang,
                                        module
                                        ) VALUES(
                                        '".$catname."',
                                        '1',
                                        'on',
                                        '".$objFWUser->objUser->getUsername()."',
                                        '".$currentTime."',
                                        '".$formId."',
                                        '".$_FRONTEND_LANGID."',
                                        '6')";
            $objDatabase->Execute($q1);
            $pageId = $objDatabase->Insert_ID();

            $q2 ="INSERT INTO ".DBPREFIX."content (id,
                                                    content,
                                                    title,
                                                    metatitle,
                                                    metadesc,
                                                    metakeys)
                                            VALUES (".$pageId.",
                                                    '".$content."',
                                                    '".$catname."',
                                                    '".$catname."',
                                                    '".$catname."',
                                                    '".$catname."')";

            if ($objDatabase->Execute($q2) !== false) {
                //create backup for history
                if (!$this->boolHistoryActivate && $this->boolHistoryEnabled) {
                    //user is not allowed to validated, so set if "off"
                    $objDatabase->Execute(' UPDATE  '.DBPREFIX.'content_navigation
                                            SET     is_validated="0",
                                                    activestatus="0"
                                            WHERE   catid='.$pageId.'
                                            LIMIT   1
                                        ');
                }

                if ($this->boolHistoryEnabled) {
                    $objDatabase->Execute('SELECT  protected,
                                                                frontend_access_id,
                                                                backend_access_id
                                                        FROM    '.DBPREFIX.'content_navigation
                                                        WHERE   catid='.$pageId.'
                                                        LIMIT   1
                                                    ');
                    $objDatabase->Execute(' INSERT
                                            INTO    '.DBPREFIX.'content_navigation_history
                                            SET     is_active="1",
                                                    catid='.$pageId.',
                                                    catname="'.$catname.'",
                                                    displayorder=1,
                                                    displaystatus="off",
                                                    username="'.$objFWUser->objUser->getUsername().'",
                                                    changelog="'.$currentTime.'",
                                                    cmd="'.$formId.'",
                                                    lang="'.$_FRONTEND_LANGID.'",
                                                    module="6"');
                    $intHistoryId = $objDatabase->insert_id();
                    $objDatabase->Execute(' INSERT
                                            INTO    '.DBPREFIX.'content_history
                                            SET     id='.$intHistoryId.',
                                                    page_id='.$pageId.',
                                                    content="'.$content.'",
                                                    title="'.$catname.'",
                                                    metatitle="'.$catname.'",
                                                    metadesc="'.$catname.'",
                                                    metakeys="'.$catname.'"');
                    $objDatabase->Execute(' INSERT
                                            INTO    '.DBPREFIX.'content_logfile
                                            SET     action="new",
                                                    history_id='.$intHistoryId.',
                                                    is_validated="'.(($this->boolHistoryActivate) ? 1 : 0).'"
                                        ');
                }

                CSRF::header("Location: ".ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=content&act=edit&pageId=".$pageId);
                exit;
            } else {
                $this->_statusMessageErr = $_ARRAYLANG['TXT_CONTACT_DATABASE_QUERY_ERROR'];
            }
        }
    }

    function _updateContentSite()
    {
        global $objDatabase, $_FRONTEND_LANGID, $_ARRAYLANG;

        Permission::checkAccess(35, 'static');
        $formId = intval($_REQUEST['formId']);
        $pageId = $this->_getContentSiteId($formId);
        $parcat = $this->_getContentSiteParCat($formId);
        if ($pageId > 0) {
            $objFWUser = FWUser::getFWUserObject();
            $objContactForm = $objDatabase->SelectLimit("SELECT name FROM ".DBPREFIX."module_contact_form WHERE id=".$formId, 1);
            if ($objContactForm !== false) {
                $catname = addslashes($objContactForm->fields['name']);
            }
            $content = addslashes($this->_getSourceCode($formId));
            $currentTime = time();

            //make sure the user is allowed to update the content
            if ($this->boolHistoryEnabled) {
                if ($this->boolHistoryActivate) {
                    $boolDirectUpdate = true;
                } else {
                    $boolDirectUpdate = false;
                }
            } else {
                $boolDirectUpdate = true;
            }

            if ($boolDirectUpdate) {
                $objDatabase->Execute("UPDATE   ".DBPREFIX."content
                                       SET      content='".$content."'
                                        WHERE   id=".$pageId);
            }

            if ($parcat!=$pageId) {
                //create copy of parcat (for history)
                $intHistoryParcat = $parcat;
                if ($boolDirectUpdate) {
                    $objDatabase->Execute(" UPDATE  ".DBPREFIX."content_navigation
                                            SET     username='".$objFWUser->objUser->getUsername()."',
                                                    changelog='".$currentTime."'
                                            WHERE catid=".$pageId);
                }
            } else {
                //create copy of parcat (for history)
                $intHistoryParcat = 0;
                if ($boolDirectUpdate) {
                    $objDatabase->Execute(" UPDATE  ".DBPREFIX."content_navigation
                                            SET     username='".$objFWUser->objUser->getUsername()."',
                                                    changelog='".$currentTime."'
                                            WHERE   catid=".$pageId);
                }
            }

            if ($boolDirectUpdate) {
                $this->_statusMessageOk .= $_ARRAYLANG['TXT_CONTACT_CONTENT_PAGE_SUCCESSFULLY_UPDATED']."<br />";
            } else {
                $this->_statusMessageOk .= $_ARRAYLANG['TXT_CONTACT_DATA_RECORD_UPDATED_SUCCESSFUL_VALIDATE']."<br />";
            }

            //create backup for history
            if ($this->boolHistoryEnabled) {
                $objDatabase->Execute('SELECT  displayorder,
                                                            protected,
                                                            frontend_access_id,
                                                            backend_access_id
                                                    FROM    '.DBPREFIX.'content_navigation
                                                    WHERE   catid='.$pageId.'
                                                    LIMIT   1
                                                ');
                if ($boolDirectUpdate) {
                    $objDatabase->Execute(' UPDATE  '.DBPREFIX.'content_navigation_history
                                            SET     is_active="0"
                                            WHERE   catid='.$pageId);
                }

                $objDatabase->Execute(' INSERT
                                        INTO    '.DBPREFIX.'content_navigation_history
                                        SET     is_active="'.(($boolDirectUpdate) ? 1 : 0).'",
                                                catid='.$pageId.',
                                                parcat="'.$intHistoryParcat.'",
                                                catname="'.$catname.'",
                                                username="'.$objFWUser->objUser->getUsername().'",
                                                changelog="'.$currentTime.'",
                                                lang="'.$_FRONTEND_LANGID.'",
                                                cmd="'.$formId.'",
                                                module="6"
                                           ');
                $intHistoryId = $objDatabase->insert_id();
                $objDatabase->Execute(' INSERT
                                        INTO    '.DBPREFIX.'content_history
                                        SET     id='.$intHistoryId.',
                                                page_id='.$pageId.',
                                                content="'.$content.'",
                                                title="'.$catname.'",
                                                metatitle="'.$catname.'",
                                                metadesc="'.$catname.'",
                                                metakeys="'.$catname.'"'
                                        );
                $objDatabase->Execute(' INSERT
                                        INTO    '.DBPREFIX.'content_logfile
                                        SET     action="update",
                                                history_id='.$intHistoryId.',
                                                is_validated="'.(($boolDirectUpdate) ? 1 : 0).'"
                                    ');
            }
        }
    }

    function _createContactFeedbackSite()
    {
        global $objDatabase;

        // Check if the thanks page is already active
        $thxQuery = "SELECT catid FROM ".DBPREFIX."content_navigation
                     WHERE module=6 AND lang=".FRONTEND_LANG_ID;
        $objResult = $objDatabase->SelectLimit($thxQuery, 1);
        if ($objResult !== false) {
            if ($objResult->RecordCount() == 0) {
                // The thanks page doesn't exist, let's change that
                $thxQuery = "SELECT `content`,
                                    `title`, `cmd`, `expertmode`, `parid`,
                                    `displaystatus`, `displayorder`, `username`,
                                    `displayorder`
                                  FROM ".DBPREFIX."module_repository
                             WHERE `moduleid` = 6 AND `lang`=".FRONTEND_LANG_ID;
                $objResult = $objDatabase->Execute($thxQuery);
                if ($objResult !== false) {
                    $content = $objResult->fields['content'];
                    $title = $objResult->fields['title'];
                    $cmd = $objResult->fields['cmd'];
                    $expertmode = $objResult->fields['expertmode'];
                    $displaystatus = $objResult->fields['displaystatus'];
// TODO: Never used
//                    $displayorder = $objResult->fields['displayorder'];
                    $username = $objResult->fields['username'];
                    $changelog = time();

                    $thxQuery = "INSERT INTO ".DBPREFIX."content_navigation
                                 (catname, username, changelog, cmd, displaystatus,
                                  module, lang)
                                 VALUES (
                                 '".$title."',
                                 '".$username."',
                                 '".$changelog."',
                                 '".$cmd."',
                                 '".$displaystatus."',
                                 '6',
                                 '".FRONTEND_LANG_ID."')";
                    $objDatabase->Execute($thxQuery);
                    $thxId = $objDatabase->Insert_ID();

                    $thxQuery = "INSERT INTO ".DBPREFIX."content
                                 (id, content, title, metatitle, metadesc, metakeys, expertmode)
                                 VALUES
                                 (".$thxId.", '".$content."', '".$title."', '".$title."', '".$title."', '".$title."',
                                  '".$expertmode."')";

                    $objDatabase->Execute($thxQuery);
                }
            }
        }
    }

    /**
     * Get Javascript Source
     *
     * Makes the sourcecode for the javascript based
     * field checking
     */
    function _getJsSourceCode($id, $formFields, $preview = false, $show = false)
    {
        $code = "<script src=\"lib/datepickercontrol/datepickercontrol.js\" type=\"text/javascript\"></script>\n";
        $code .= "<script type=\"text/javascript\">\n";
        $code .= "/* <![CDATA[ */\n";

        $code .= "fields = new Array();\n";

        foreach ($formFields as $key => $field) {
            $modifiers = isset($this->arrCheckTypes[$field['check_type']]['modifiers']) ? $this->arrCheckTypes[$field['check_type']]['modifiers'] : '';
           
            $code .= "fields[$key] = Array(\n";
            $code .= "\t'".addslashes($field['name'])."',\n";
            $code .= "\t{$field['is_required']},\n";
            $code .= "\t/". ($this->arrCheckTypes[$field['check_type']]['regex']) ."/".$modifiers.",\n";
            $code .= "\t'".$field['type']."');\n";
        }

        $code .= <<<JS_checkAllFields
function checkAllFields() {
    var isOk = true;

    for (var field in fields) {
        var type = fields[field][3];
        if (type == 'text' || type == 'password' || type == 'textarea') {
            value = document.getElementsByName('contactFormField_' + field)[0].value;
            if (value == "" && isRequiredNorm(fields[field][1], value)) {
                isOk = false;
                document.getElementsByName('contactFormField_' + field)[0].style.border = "red 1px solid";
            } else if (value != "" && !matchType(fields[field][2], value)) {
                isOk = false;
                document.getElementsByName('contactFormField_' + field)[0].style.border = "red 1px solid";
            } else {
                document.getElementsByName('contactFormField_' + field)[0].style.borderColor = '';
            }
        } else if (type == 'checkbox') {
            if (!isRequiredCheckbox(fields[field][1], field)) {
                isOk = false;
            }
        } else if (type == 'checkboxGroup') {
            if (!isRequiredCheckBoxGroup(fields[field][1], field)) {
                isOk = false;
            }
        } else if (type == 'radio') {
            if (!isRequiredRadio(fields[field][1], field)) {
                isOk = false;
            }
        }
    }

    if (!isOk) {
        document.getElementById('contactFormError').style.display = "block";
    }
    return isOk;
}

JS_checkAllFields;

        // This is for checking normal text input field if they are required.
        // If yes, it also checks if the field is set. If it is not set, it returns true.
        $code .= <<<JS_isRequiredNorm
function isRequiredNorm(required, value) {
    if (required == 1) {
        if (value == "") {
            return true;
        }
    }
    return false;
}

JS_isRequiredNorm;

        // Matches the type of the value and pattern. Returns true if it matched, false if not.
        $code .= <<<JS_matchType
function matchType(pattern, value) {
    return value.match(pattern) != null;
}

JS_matchType;

        // Checks if a checkbox is required but not set. Returns false when finding an error.
        $code .= <<<JS_isRequiredCheckbox
function isRequiredCheckbox(required, field) {
    if (required == 1) {
        if (!document.getElementsByName('contactFormField_' + field)[0].checked) {
            document.getElementsByName('contactFormField_' + field)[0].style.border = "red 1px solid";
            return false;
        }
    }
    document.getElementsByName('contactFormField_' + field)[0].style.borderColor = '';

    return true;
}

JS_isRequiredCheckbox;

        // Checks if a multile checkbox is required but not set. Returns false when finding an error.
        $code .= <<<JS_isRequiredCheckBoxGroup
function isRequiredCheckBoxGroup(required, field) {
    if (required == true) {
        var boxes = document.getElementsByName('contactFormField_' + field + '[]');
        var checked = false;
        for (var i = 0; i < boxes.length; i++) {
            if (boxes[i].checked) {
                checked = true;
            }
        }
        if (checked) {
            setListBorder('contactFormField_' + field + '[]', false);
            return true;
        } else {
            setListBorder('contactFormField_' + field + '[]', '1px red solid');
            return false;
        }
    } else {
        return true;
    }
}

JS_isRequiredCheckBoxGroup;

        // Checks if some radio button need to be checked. Returns false if it finds an error
        $code .= <<<JS_isRequiredRadio
function isRequiredRadio(required, field) {
    if (required == 1) {
        var buttons = document.getElementsByName('contactFormField_' + field);
        var checked = false;
        for (var i = 0; i < buttons.length; i++) {
            if (buttons[i].checked) {
                checked = true;
            }
        }
        if (checked) {
            setListBorder('contactFormField_' + field, false);
            return true;
        } else {
            setListBorder('contactFormField_' + field, '1px red solid');
            return false;
        }
    } else {
        return true;
    }
}

JS_isRequiredRadio;

        // Sets the border attribute of a group of checkboxes or radiobuttons
        $code .= <<<JS_setListBorder
function setListBorder(field, borderColor) {
    var boxes = document.getElementsByName(field);
    for (var i = 0; i < boxes.length; i++) {
        if (borderColor) {
            boxes[i].style.border = borderColor;
        } else {
            boxes[i].style.borderColor = '';
        }
    }
}

JS_setListBorder;
      
        $code .= <<<JS_misc
/* ]]> */
</script>

JS_misc;
        return $code;
    }

    protected function getUploaderSourceCode() {
        $source .= <<<EOS
{UPLOAD_WIDGET_CODE}
{UPLOADER_CODE}
<script>
    cx.include(
        [
            'core_modules/contact/js/extendedFileInput.js'
        ],
        function() {
            var ef = new ExtendedFileInput({
               field:  \$J('#contactFormField_upload')
            });            
        }
    );
</script>
EOS;

        return $source;
    }
}
?>
