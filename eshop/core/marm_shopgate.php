<?php
/**
 * Shopgate Connector
 *
 * Copyright (c) 2011 Joscha Krug | marmalade.de
 * E-mail: mail@marmalade.de
 * http://www.marmalade.de
 *
 * Developed for
 * Shopgate GmbH
 * www.shopgate.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

class marm_shopgate
{
    /**
     * information about how shopgate config variables
     * are editable in oxid admin GUI
     * @var array
     */
    protected $_aConfig = array(
        'shop_is_active' => array('type' => 'checkbox', 'group' => 'general'),
        'customer_number' => array('type'=>'input', 'group' => 'general'),
        'shop_number' => array('type' => 'input', 'group' => 'general'),
        'apikey' => array('type' => 'input', 'group' => 'general'),
        'generate_items_csv_on_the_fly' => array('type' => 'checkbox', 'group' => 'general'),
        'enable_ping' => array('type' => 'checkbox', 'group' => 'permissions'),
        'enable_get_shop_info' => array('type' => 'checkbox', 'group' => 'permissions'),
        'enable_http_alert' => array('type' => 'checkbox', 'group' => 'permissions'),
        'enable_connect' => array('type' => 'checkbox', 'group' => 'permissions'),
        'enable_get_items_csv' => array('type' => 'checkbox', 'group' => 'permissions'),
        'enable_mobile_website' => array('type' => 'checkbox', 'group' => 'mobileweb'),
        'server' => array(
            'type' => 'select',
            'options' => array (
                'live',
                'pg',
                'custom'
            ),
            'group' => 'debug'
        ),
        'server_custom_url' => array('type' => 'input', 'group' => 'debug'),
//        'enable_get_reviews_csv' => array('type' => 'checkbox'),
//        'enable_get_pages_csv' => array('type' => 'checkbox'),
//        'enable_get_log_file' => array('type' => 'checkbox'),
//        'max_attributes' => array('type' => 'input'),
//        'use_custom_error_handler' => array('type' => 'checkbox'),
//        'use_stock' => array('type' => 'checkbox'),
//        'background_color' => array('type' => 'input'),
//        'foreground_color' => array('type' => 'input'),
        'plugin' => array('type' => false),
    );

    /**
     * contains array of files which will be included from library
     * to get framework working
     * @var array
     */
    protected $_aFilesToInclude = array(
        'framework.php',
        'connect_api.php',
        'core_api.php',
        'order_api.php'
    );
    /**
     * defines where shopgate framework placed.
     */
    const FRAMEWORK_DIR = 'shopgate';

    /**
     * stores created instance of framework object.
     * @var ShopgateFramework
     */
    protected $_oShopgateFramework = null;

    /**
     * marm_shopgate class instance.
     *
     * @var marm_shopgate
     */
    private static $_instance = null;

    /**
     * returns marm_shopgate object
     *
     * @return marm_shopgate
     */
    public static function getInstance()
    {
        if ( !(self::$_instance instanceof marm_shopgate) ) {
            self::$_instance = oxNew( 'marm_shopgate' );
        }
        return self::$_instance;
    }

    /**
     * replace given object to the class instance.
     * USED ONLY FOR PHPUNIT
     * @param marm_shopgate $oNewInstance
     * @return marm_shopgate
     */
    public static function replaceInstance(marm_shopgate $oNewInstance)
    {
        $oOldInstance = self::$_instance;
        self::$_instance = $oNewInstance;
        return $oOldInstance;
    }

    /**
     * returns full path, where framework is placed.
     * @return string
     */
    protected function _getFrameworkDir()
    {
        $sDir = oxConfig::getInstance()->getConfigParam( 'sShopDir' )
                . DIRECTORY_SEPARATOR
                . self::FRAMEWORK_DIR
                . DIRECTORY_SEPARATOR
        ;
        return $sDir;
    }

    /**
     * returns path of framework library
     * @return string
     */
    protected function _getLibraryDir()
    {
        return $this->_getFrameworkDir() . 'lib' . DIRECTORY_SEPARATOR;
    }

    /**
     * returns array of file names from shopgate framework library
     * that has to be inlcuded
     * @return array
     */
    protected function _getFilesToInclude()
    {
        return $this->_aFilesToInclude;
    }

    /**
     * function loads framework by including it
     * @return void
     */
    public function init()
    {
        $sLibraryDir = $this->_getLibraryDir();
        foreach ($this->_getFilesToInclude() as $sFile) {
            $sFile = $sLibraryDir . $sFile;
            if (file_exists($sFile)) {
                require_once $sFile;
            }
        }
        $this->initConfig();
    }

    /**
     * sends config to Shopgate instance only then required params are set:
     * apikey, customer_number and shop_number
     * @return void
     */
    public function initConfig()
    {
        $aConfig = $this->_getConfigForFramework();
        if (isset($aConfig['apikey']) && isset($aConfig['customer_number']) && isset($aConfig['shop_number']) ) {
            ShopgateConfig::setConfig($aConfig);
        }
    }

    /**
     * returns ShopgateFramework object,
     * saves it internally,
     * resets instance if $blReset = true
     *
     * @param bool $blReset
     * @return ShopgateFramework
     */
    public function getFramework($blReset = false)
    {
        if ($this->_oShopgateFramework !== null && !$blReset) {
            return $this->_oShopgateFramework;
        }
        $this->init();

        $this->_oShopgateFramework = oxNew('ShopgateFramework');
        return $this->_oShopgateFramework;
    }

    /**
     * returns array shopgate config name and edit type in oxid (checkbox, input)
     * @return array
     */
    protected function _getConfig()
    {
        return $this->_aConfig;
    }

    /**
     * returns array for framework filled with values from oxid configuration.
     * @return array
     */
    protected function _getConfigForFramework()
    {
        $aConfig = array();
        $oConfig = oxConfig::getInstance();
        foreach ($this->_getConfig() as $sConfigKey => $aOptions) {
            $sValue = $oConfig->getConfigParam($this->getOxidConfigKey($sConfigKey));
            if ($sValue !== null) {
                $aConfig[$sConfigKey] = $sValue;
            }
        }
        $aConfig['plugin'] = 'oxid';
        return $aConfig;
    }

    /**
     * returns shopgate config array with information
     * how to display it in format:
     * array(
     *   [oxid_name] => marm_shopgate_customer_number
     *   [shopgate_name] => customer_number
     *   [type] => checkbox|input|select
     *   [value] => 1234567890
     * )
     * @return array
     */
    public function getConfigForAdminGui()
    {
        $aConfig = array();
        $oOxidConfig = oxConfig::getInstance();
        $this->init();
        $aShopgateConfig = ShopgateConfig::getConfig();
        foreach ($this->_getConfig() as $sConfigKey => $aOptions) {

            if ($sConfigKey == 'plugin')  continue;
            $sOxidConfigKey = $this->getOxidConfigKey($sConfigKey);
            $sValue = $oOxidConfig->getConfigParam($sOxidConfigKey);
            if ($sValue === null) {
                $sValue = $aShopgateConfig[$sConfigKey];
            }
            $aOptions['oxid_name'] = $sOxidConfigKey;
            $aOptions['shopgate_name'] = $sConfigKey;
            $aOptions['value'] = $sValue;
            $aConfig[$aOptions['group']][$sConfigKey] = $aOptions;
        }
        return $aConfig;
    }

    /**
     * will generate key name on which oxid will 
     * @param $sShopgateConfigKey
     * @return string
     */
    public function getOxidConfigKey($sShopgateConfigKey)
    {
        $sShopgateConfigKey = strtolower($sShopgateConfigKey);
        $sHash = md5($sShopgateConfigKey);
        $sStart = substr($sHash, 0, 3);
        $sEnd = substr($sHash, -3);
        return 'marm_shopgate_'.$sStart.$sEnd;
    }

    /**
     * outputs HTML SCRIPT tag for shopgate mobile javascript redirect
     * @return string
     */
    public function getMobileSnippet()
    {
        $sSnippet = '';
        $oOxidConfig = oxConfig::getInstance();
        if ( $oOxidConfig->getConfigParam($this->getOxidConfigKey('enable_mobile_website')) && $oOxidConfig->getConfigParam($this->getOxidConfigKey('shop_is_active')) ) {
            $iShopNumber = $oOxidConfig->getConfigParam($this->getOxidConfigKey('shop_number'));
            $sSnippet  = '<script type="text/javascript">'."\n";
            $sSnippet .= 'var _shopgate = {}; '."\n";
            $sSnippet .= '_shopgate.shop_number = "'.$iShopNumber.'"; '."\n";
            $sSnippet .= $this->_getDetailsMobileSnippet();
            $sSnippet .= '_shopgate.host = (("https:" == document.location.protocol) ? "';
            $sSnippet .= 'https://static-ssl.shopgate.com" : "http://static.shopgate.com"); '."\n";
            $sSnippet .= 'document.write(unescape("%3Cscript src=\'" + _shopgate.host + ';
            $sSnippet .= '"/mobile_header/" + _shopgate.shop_number + ".js\' ';
            $sSnippet .= 'type=\'text/javascript\' %3E%3C/script%3E")); '."\n";
            $sSnippet .= '</script>';
        }
        return $sSnippet;
    }

    /**
     * if current page is details, returns redirect values for this article
     * @return string
     */
    protected function _getDetailsMobileSnippet()
    {
        $sReturn = '';
        $oActiveView = oxConfig::getInstance()->getActiveView();
        if ($oActiveView->getClassName() == 'details') {
            $sReturn .= '_shopgate.redirect = "item";'."\n";
            $oArticle = $oActiveView->getProduct();
            $sReturn .= '_shopgate.item_number = "'.$oArticle->oxarticles__oxartnum->value.'";'."\n";
        }
        return $sReturn;
    }

    /**
     * executes ShopgateOrderApi::setShippingComplete()
     * @param int $sShopgateOrderId
     * @return void
     */
    public function setOrderShippingCompleted($sShopgateOrderId)
    {
        $this->init();
        $oShopgateOrderApi = oxNew('ShopgateOrderApi');
        try {
            $oShopgateOrderApi->setShippingComplete($sShopgateOrderId);
        }
        catch(ShopgateFrameworkException $oEx) {

            /** @var $oOxidEx  oxException*/
            $oOxidEx = oxNew('oxException');
            $oOxidEx->setMessage($oEx->getMessage());

            if (!isset($oEx->lastResponse)) {
                throw $oOxidEx;
            }
            $aLastResponse = $oEx->lastResponse;
            if (!is_array($aLastResponse)) {
                throw $oOxidEx;
            }
            if (!isset($aLastResponse['error'])) {
                throw $oOxidEx;
            }
            if ($aLastResponse['error'] != 204 && $aLastResponse['error'] != 203 ) {
                throw $oOxidEx;
            }
        }
    }
}
