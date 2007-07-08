<?PHP
/**
 * Payment processing manager.
 * @package     contrexx
 * @subpackage  module_shop
 * @copyright   CONTREXX CMS - COMVATION AG
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Saferpay payment handling
 */
require_once ASCMS_MODULE_PATH.'/shop/payments/saferpay/Saferpay.class.php';
/**
 * Yellowpay payment handling
 */
require_once ASCMS_MODULE_PATH.'/shop/payments/yellowpay/Yellowpay.class.php';
/**
 * PayPal payment handling
 */
require_once ASCMS_MODULE_PATH.'/shop/payments/paypal/Paypal.class.php';
/**
 * Dummy payment handling -- for testing purposes only.
 */
require_once ASCMS_MODULE_PATH.'/shop/payments/dummy/Dummy.class.php';

/**
 * Payment processing manager.
 * @package     contrexx
 * @subpackage  module_shop
 * @copyright   CONTREXX CMS - COMVATION AG
 */
class PaymentProcessing
{
    /**
     * The active currency code (e.g. CHF, EUR, USD)
     * @access  private
     * @var     string
     */
    var $_currencyCode = NULL;

    /**
     * The active language code (e.g. de, en, fr)
     * @access  private
     * @var     string
     */
    var $_languageCode = NULL;

    /**
     * The Shop configuration Array
     * @access  public
     * @var     array
     */
    var $arrConfig = array();

    /**
     * Array of all available payment processors
     * @access  public
     * @var     array
     */
    var $arrPaymentProcessor = array();

    /**
     * The selected processor ID
     * @access  private
     * @var     integer
     */
    var $_processorId = NULL;

    /**
     * Payment logo folder (e.g. /modules/shop/images/payments/)
     * @access  private
     * @var     string
     */
    var $_imagePath;


    /**
     * Constructor (PHP4)
     *
     * Initialize the shipping options as an indexed array
     * @param   array   $arrConfig  Configuration array
     */
    function PaymentProcessing($arrConfig)
    {
        $this->__construct($arrConfig);
    }


    /**
     * Constructor (PHP5)
     *
     * Initialize the shipping options as an indexed array
     * @param  string
     * @return void
     */
    function __construct($arrConfig)
    {
        global $objDatabase;

        $this->arrConfig     = $arrConfig;
        $this->_imagePath    = ASCMS_PATH_OFFSET.'/modules/shop/images/payments/';

        $query = '
            SELECT id, type, name, description,
                   company_url, status, picture, text
              FROM '.DBPREFIX.'module_shop_payment_processors
          ORDER BY id
        ';
        $objResult = $objDatabase->Execute($query);
        while(!$objResult->EOF) {
            $this->arrPaymentProcessor[$objResult->fields['id']] = array(
                'id'          => $objResult->fields['id'],
                'type'        => $objResult->fields['type'],
                'name'        => $objResult->fields['name'],
                'description' => $objResult->fields['description'],
                'company_url' => $objResult->fields['company_url'],
                'status'      => $objResult->fields['status'],
                'picture'     => $objResult->fields['picture'],
                'text'        => $objResult->fields['text']
            );
            $objResult->MoveNext();
        }
    }


    /**
     * Initialize the processor ID
     * @return  void
     * @param   integer $processorId
     * @param   string  $currencyCode
     * @param   string  $languageCode
     */
    function initProcessor($processorId, $currencyCode, $languageCode)
    {
        $this->_processorId  = $processorId;
        $this->_currencyCode = $currencyCode;
        $this->_languageCode = $languageCode;
    }


    /**
     * Returns the name associated with a payment processor ID.
     *
     * If the optional argument is not set and greater than zero, the value
     * _processorId stored in this object is used.  If this is invalid as
     * well, returns the empty string.
     * @param   integer     $processorId    The payment processor ID
     * @return  string                      The payment processors' name,
     *                                      or the empty string on failure.
     * @global  mixed       $objDatabase    Database object
     */
    function getPaymentProcessorName($processorId=0)
    {
        global $objDatabase;

        // either the argument or the object may not be initialized.
        if (!$processorId) {
            if (!$this->_processorId) {
                return '';
            } else {
                $processorId = $this->_processorId;
            }
        }
        $query = "
            SELECT name
              FROM ".DBPREFIX."module_shop_payment_processors
             WHERE id=$processorId
        ";
        $objResult = $objDatabase->Execute($query);
        if ($objResult && !$objResult->EOF) {
            return $objResult->fields['name'];
        }
        return '';
    }


    /**
     * Returns the processor type associated with a payment processor ID.
     *
     * If the optional argument is not set and greater than zero, the value
     * _processorId stored in this object is used.  If this is invalid as
     * well, returns the empty string.
     * Note: Currently supported types are 'internal' and 'external'.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @param   integer     $processorId    The payment processor ID
     * @return  string                      The payment processor type,
     *                                      or the empty string on failure.
     * @global  mixed       $objDatabase    Database object
     */
    function getCurrentPaymentProcessorType($processorId=0)
    {
        global $objDatabase;

        // either the argument or the object may not be initialized.
        if (!$processorId) {
            if (!$this->_processorId) {
                return '';
            } else {
                $processorId = $this->_processorId;
            }
        }

        $query = "
            SELECT type
              FROM ".DBPREFIX."module_shop_payment_processors
             WHERE id=$processorId
        ";
        $objResult = $objDatabase->Execute($query);
        if ($objResult && !$objResult->EOF) {
            return $objResult->fields['type'];
        }
        return '';
    }


    /**
     * Returns the picture file name associated with a payment processor ID.
     *
     * If the optional argument is not set and greater than zero, the value
     * _processorId stored in this object is used.  If this is invalid as
     * well, returns the empty string.
     * @param   integer     $processorId    The payment processor ID
     * @return  string                      The payment processors' picture
     *                                      file name, or the empty string
     *                                      on failure.
     * @global  mixed       $objDatabase    Database object
     */
    function getPaymentProcessorPicture($processorId=0)
    {
        global $objDatabase;

        // either the argument or the object may not be initialized.
        if (!$processorId) {
            if (!$this->_processorId) {
                return '';
            } else {
                $processorId = $this->_processorId;
            }
        }
        $query = "
            SELECT picture
              FROM ".DBPREFIX."module_shop_payment_processors
             WHERE id=$processorId
        ";
        $objResult = $objDatabase->Execute($query);
        if ($objResult && !$objResult->EOF) {
            return $objResult->fields['picture'];
        }
        return '';
    }


    /**
     * Check out the payment processor associated with the payment processor
     * selected by {@link initProcessor()}.
     *
     * If the page is redirected, or has already been handled, returns the empty
     * string.
     * In the other cases, returns HTML code for the payment form and to insert
     * a picture representing the payment method.
     * @return  string      Empty string, or HTML code
     */
    function checkOut()
    {
        switch ($this->getPaymentProcessorName()) {
            case 'Internal':
                /* Redirect browser */
                header('location: index.php?section=shop&cmd=success&handler=Internal');
                exit;
                break;
            case 'Internal_LSV':
                /* Redirect browser */
                header('location: index.php?section=shop&cmd=success&handler=Internal');
                exit;
                break;
            case 'Internal_CreditCard':
                $return = $this->_Internal_CreditCardProcessor();
                break;
            case 'Internal_Debit':
                $return = $this->_Internal_DebitProcessor();
                break;
            case 'Saferpay_All_Cards':
                $return = $this->_SaferpayProcessor();
                break;
            case 'Saferpay_Mastercard_Multipay_CAR':
                $return = $this->_SaferpayProcessor(array('Mastercard Multipay CAR'));
                break;
            case 'Saferpay_Visa_Multipay_CAR':
                $return = $this->_SaferpayProcessor(array('Visa Multipay CAR'));
                break;
            case 'PostFinance_DebitDirect':
                $return = $this->_YellowpayProcessor();
                break;
            case 'Paypal':
                $return = $this->_PayPalProcessor();
                break;
            case 'Dummy':
                $return = Dummy::getForm();
                break;
        }
        // shows the payment picture
        $return .= $this->_getPictureCode();
        return $return;
    }


    /**
     * Returns HTML code for showing the logo associated with this
     * payment processor, if any, or an empty string otherwise.
     *
     * @return  string      HTML code, or the empty string
     */
    function _getPictureCode()
    {
        $imageName = $this->getPaymentProcessorPicture();
        return (!empty($imageName)
            ?   '<br /><br /><img src="'.
                $this->_imagePath.$imageName.
                '" alt="" title="" /><br /><br />'
            :   ''
        );
    }


    /**
     * Returns the HTML code for the Saferpay payment method.
     * @return  string  HTML code
     */
    function _SaferpayProcessor($arrCards = array())
    {
        global $_ARRAYLANG;

        $objSaferpay = new Saferpay();
        if ($this->arrConfig['saferpay_use_test_account']['status'] == 1) {
            $objSaferpay->isTest = true;
        }

        $arrShopOrder = array(
            'AMOUNT'      => str_replace('.', '', $_SESSION['shop']['grand_total_price']),
            'CURRENCY'    => $this->_currencyCode,
            'ORDERID'     => $_SESSION['shop']['orderid'],
            'ACCOUNTID'   => $this->arrConfig['saferpay_id']['value'],
            'SUCCESSLINK' => urlencode('http://'.$_SERVER['SERVER_NAME'].'/index.php?section=shop&cmd=success&handler=saferpay'),
            'FAILLINK'    => urlencode('http://'.$_SERVER['SERVER_NAME'].'/index.php?section=shop&cmd=cart'),
            'BACKLINK'    => urlencode('http://'.$_SERVER['SERVER_NAME'].'/index.php?section=shop&cmd=cart'),
            'DESCRIPTION' => urlencode('"'.$_ARRAYLANG['TXT_ORDER_NR'].' '.$_SESSION['shop']['orderid'].'"'),
            'LANGID'      => $this->_languageCode,
            'PROVIDERSET' => $arrCards
        );

        $payInitUrl = $objSaferpay->payInit($arrShopOrder);
        $return = '';
        // Fixed: Added check for empty return string,
        // i.e. on connection problems
        if (   !$payInitUrl
            || strtoupper(substr($payInitUrl, 0, 5)) == 'ERROR') {
            $return .=
                "<font color='red'><b>".
                "The Saferpay Payment processor couldn't be initialized!".
                "<br />$payInitUrl</b></font>";
        } else {
            $return .= "<script src='http://www.saferpay.com/OpenSaferpayScript.js'></script>\n";
            switch ($this->arrConfig['saferpay_window_option']['value']){
                case 0: // iframe
                    $return .=
                        $_ARRAYLANG['TXT_ORDER_PREPARED']."<br/><br/>\n".
                        "<iframe src='$payInitUrl' width='580' height='400' scrolling='no' marginheight='0' marginwidth='0' frameborder='0' name='saferpay'></iframe>\n";
                    break;
                case 1: // popup
                    $return .=
                        $_ARRAYLANG['TXT_ORDER_LINK_PREPARED']."<br/><br/>\n".
                        "<script language='javascript' type='text/javascript'>
                         function openSaferpay()
                         {
                             strUrl = '$payInitUrl';
                             if (strUrl.indexOf(\"WINDOWMODE=Standalone\") == -1){
                                 strUrl += \"&WINDOWMODE=Standalone\";
                             }
                             oWin = window.open(
                                                 strUrl,
                                                 'SaferpayTerminal',
                                                 'scrollbars=1,resizable=0,toolbar=0,location=0,directories=0,status=1,menubar=0,width=580,height=400'
                             );
                             if (oWin==null || typeof(oWin)==\"undefined\") {
                                 alert(\"The payment couldn't be initialized, because it seems that you are using a popup blocker!\");
                             }
                         }
                         </script>\n".
                        "<input type='button' name='order_now' value='".
                        $_ARRAYLANG['TXT_ORDER_NOW'].
                        "' onclick='openSaferpay()' />\n";
                    break;
                case 2: // new window
                    $return .=
                        $_ARRAYLANG['TXT_ORDER_LINK_PREPARED']."<br/><br/>\n".
                        "<form method='post' action='$payInitUrl'>\n<input type='Submit' value='".
                        $_ARRAYLANG['TXT_ORDER_NOW'].
                        "'>\n</form>\n";
                    break;
            }
        }
        return $return;
    }


    /**
     * Returns the HTML code for the Yellowpay payment method.
     * @return  string  HTML code
     */
    function _YellowpayProcessor()
    {
        global $_ARRAYLANG;

        $arrShopOrder = array(
            'txtShopId'           => $this->arrConfig['yellowpay_id']['value'],
            'txtOrderTotal'       => $_SESSION['shop']['grand_total_price'],
            'ShopId'              => $this->arrConfig['yellowpay_shop_id'],
            'Hash_seed'           => $this->arrConfig['yellowpay_hash_seed'],
            'txtLangVersion'      => strtoupper($this->_languageCode),
            'txtArtCurrency'      => $this->_currencyCode,
            'txtOrderIDShop'      => $_SESSION['shop']['orderid'],
            'PaymentType'         => 'DebitDirect',
            'DeliveryPaymentType' => $this->arrConfig['yellowpay_delivery_payment_type']['value'],
// huh?  this isn't set anywhere in the shop, and not even used anywhere in yellowpay.class.php
            'SessionId'           => $_SESSION['shop']['PHPSESSID']
        );

        $objYellowpay = new Yellowpay();
        $yellowpayForm = $objYellowpay->getForm(
            $arrShopOrder,
            $_ARRAYLANG['TXT_ORDER_NOW']
        );
        if (count($objYellowpay->arrError) > 0) {
            return "<font color='red'><b>Yellowpay couldn't be initialized!</b></font>";
        }
        return $yellowpayForm;
    }


    /**
     * Returns the HTML code for the PayPal payment method.
     * @return  string  HTML code
     */
    function  _PayPalProcessor()
    {
        $objPayPal = new PayPal();
        return $objPayPal->getForm();
    }


    /**
     * Check in the payment processor after the payment is complete.
     * @return  mixed   For external payment types:
     *                  The integer order ID, if known, upon success,
     *                  the 'NULL' string value upon failure.  This may
     *                  be used in successive queries in place of the order
     *                  ID and will yield no result, and thus the
     *                  confirmation will fail as a consequence.
     *                  For internal payment types:
     *                  Boolean true, because these all skip the order
     *                  confirmation after this, as this has already been
     *                  done.
     */
    function checkIn()
    {
        $transaction = false;
        if (isset($_GET['handler']) && !empty($_GET['handler'])) {
            switch ($_GET['handler']) {
                case 'saferpay':
                    $objSaferpay  = new Saferpay();
                    if ($this->arrConfig['saferpay_use_test_account']['status'] == 1) {
                        $objSaferpay->isTest = true;
                    } else {
                        $arrShopOrder['ACCOUNTID'] = $this->arrConfig['saferpay_id']['value'];
                    }
                    $transaction = $objSaferpay->payConfirm();
                    if (intval($this->arrConfig['saferpay_finalize_payment']['value']) == 1) {
                        if ($objSaferpay->isTest == true) {
                            $transaction = true;
                        } else {
                            $transaction = $objSaferpay->payComplete($arrShopOrder);
                        }
                    }
                    if ($transaction) {
                        return $objSaferpay->getOrderId();
                    }
                    break;
                case 'paypal':
                    // order ID must be returned when the payment is done.
                    // is this guaranteed to be a GET request?
                    if (isset($_REQUEST['orderid'])) {
                        return intval($_REQUEST['orderid']);
                    }
                    break;
                // Dunno about this one...
                case 'PostFinance_DebitDirect':
                    break;
                // For the remaining types, there's no need to check in, so we
                // return true and jump over the validation of the order ID
                // directly to success!
                case 'Internal':
                case 'Internal_CreditCard':
                case 'Internal_Debit':
                case 'Internal_LSV':
                    return true;
                // Dummy payment.
                // Returns a result similar to PayPal or Saferpay.
                case 'dummy':
                    $result = '';
                    if (isset($_REQUEST['result'])) {
                        $result = $_REQUEST['result'];
                    }
                    // returns the order ID on success, 'NULL' otherwise
                    return Dummy::commit($result);
                default:
                    break;
                // Note: The order ID is kept in a backup in index.class.php
                // for payment methods that do not return it.
            }
        }
        // Anything else is wrong.
        return 'NULL';
    }
}

?>
