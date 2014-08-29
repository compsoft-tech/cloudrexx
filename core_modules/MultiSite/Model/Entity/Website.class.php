<?php 
namespace Cx\Core_Modules\MultiSite\Model\Entity;

class WebsiteException extends \Exception {}

class Website extends \Cx\Model\Base\EntityBase {
    
    /**
     * Status online
     */
    const STATE_ONLINE = 'online';
    
    /**
     * Status offline
     */
    const STATE_OFFLINE = 'offline';
    
    /**
     * Status init
     */
    const STATE_INIT = 'init';
    
    /**
     * Status setup
     */
    const STATE_SETUP =  'setup';
        
    protected $basepath = null;
  
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    public $name;

    /**
     * @var string $codeBase
     */
    public $codeBase;

    /**
     * @var string $language
     */
// TODO: do we still need this??
    public $language;

    /**
     * @var string $status
     */
    public $status;
    
    /**
     * @var integer $websiteServiceServerId
     */
    public $websiteServiceServerId;
    
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer
     */
    protected $websiteServiceServer;
    
    protected $owner;
    
    private $websiteController;
   
    /**
     * @var string $ipAddress
     */
    private $ipAddress;

    /**
     * @var integer $ownerId
     */
    private $ownerId;
    
    /**
     * @var string $secretKey
     */
    public $secretKey;
    
    /**
     * @var string $installationId
     */
    private $installationId;

    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    private $fqdn;
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    private $baseDn;

    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    private $domains;
    
    /*
     * Constructor
     * */
    public function __construct($basepath, $name, $websiteServiceServer = null, \User $userObj=null, $lazyLoad = true) {
        $this->basepath = $basepath;
        $this->name = $name;

        if ($lazyLoad) {
            return true;
        }

        $this->domains = new \Doctrine\Common\Collections\ArrayCollection();      
        $this->language = $userObj->getFrontendLanguage();
        $this->status = self::STATE_INIT;
        $this->websiteServiceServerId = 0;
        $this->owner = $userObj;
        $this->ownerId = $userObj->getId();
        $this->installationId = $this->generateInstalationId();
        $this->ipAddress = \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteIp');

        if ($websiteServiceServer) {
            $this->setWebsiteServiceServer($websiteServiceServer);
        }
        $this->secretKey = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSite::generateSecretKey();
        $this->validate();
        $this->codeBase = \Cx\Core\Setting\Controller\Setting::getValue('defaultCodeBase');
        $this->setFqdn();
        $this->setBaseDn();
    }

    public static function loadFromFileSystem($basepath, $name)
    {
        if (!file_exists($basepath.'/'.$name)) {
            throw new WebsiteException('No website found on path ' . $basepath . '/' . $name);
        }

        return new Website($basepath, $name);
    }
    
     /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
   /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set codeBase
     *
     * @param string $codeBase
     */
    public function setCodeBase($codeBase)
    {
        $this->codeBase = $codeBase;
    }

    /**
     * Get codeBase
     *
     * @return string $codeBase
     */
    public function getCodeBase()
    {
        return $this->codeBase;
    }

    /**
     * Set language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get language
     *
     * @return string $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set status
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return integer $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set websiteServiceServerId
     *
     * @param integer $websiteServiceServerId
     */
    public function setWebsiteServiceServerId($websiteServiceServerId)
    {
        $this->websiteServiceServerId = $websiteServiceServerId;
    }

    /**
     * Get websiteServiceServerId
     *
     * @return integer $websiteServiceServerId
     */
    public function getWebsiteServiceServerId()
    {
        return $this->websiteServiceServerId;
    }
    
    /**
     * Set websiteServiceServer
     *
     * @param Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer
     */
    public function setWebsiteServiceServer(\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer)
    {
        $this->websiteServiceServer = $websiteServiceServer;
        $this->setWebsiteServiceServerId($websiteServiceServer->getId());
    }

    /**
     * Get websiteServiceServer
     *
     * @return Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer
     */
    public function getWebsiteServiceServer()
    {
        return $this->websiteServiceServer;
    }

    public function getOwner()
    {
        if (!isset($this->owner)) {
            $user = new \User();
            $this->owner = $user->getUser($this->ownerId);
        }
        return $this->owner;
    }
    
    /**
     * Set secretKey
     *
     * @param string $secretKey
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Get secretKey
     *
     * @return string $secretKey
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }
    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Get ipAddress
     *
     * @return string $ipAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set ownerId
     *
     * @param integer $ownerId
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * Get ownerId
     *
     * @return integer $ownerId
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }
     /**
     * Set installationId
     *
     * @param string $installationId
     */
    public function setInstallationId($installationId)
    {
        $this->installationId = $installationId;
    }

    /**
     * Get installationId
     *
     * @return string $installationId
     */
    public function getInstallationId()
    {
        return $this->installationId;
    }
    /**
     * Creates a new website
     */
    public function setup() {
        global $_DBCONFIG, $_ARRAYLANG;
        
        \DBG::msg('Website::setup()');
        $this->status = self::STATE_SETUP;
        \Env::get('em')->persist($this);
        \Env::get('em')->flush();
        
        $this->websiteController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();

        $websiteName = $this->getName();
        $websiteMail = $this->owner->getEmail(); 
        $websiteIp = null;

        // language
        $lang = $this->owner->getBackendLanguage();
        $langId = \FWLanguage::getLanguageIdByCode($lang);
        
        if ($langId === false) {
            $langId = \FWLanguage::getDefaultLangId();
        }
        $isServiceServer = true;
        //check if the current server is running as the website manager
        if ($this->websiteServiceServer instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer) {
            \DBG::msg('Website: Forward setup() to Website Service Server');
            $isServiceServer = false;
            //create user account in website service server
            \Cx\Core_Modules\MultiSite\Controller\JsonMultiSite::executeCommandOnServiceServer('createUser', array('userId' => $this->owner->getId(), 'email'  => $this->owner->getEmail()), $this->websiteServiceServer);
            //create website in website service server
            $params = array(
                'userId'      => $this->owner->getId(),
                'websiteName' => $websiteName,
                'websiteId'   => $this->getId(),
                );
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSite::executeCommandOnServiceServer('createWebsite', $params, $this->websiteServiceServer);
            if(!$resp || $resp->status == 'error'){
                $errMsg = isset($resp->message) ? $resp->message : '';
                throw new WebsiteException('Problem in creating website '.$errMsg);    
            }
            $this->ipAddress = $resp->data->websiteIp;
            $this->codeBase  = $resp->data->codeBase;
            $this->status    = $resp->data->state;
        } else {
            \DBG::msg('Website: setup process..');
            $objDb = new \Cx\Core\Model\Model\Entity\Db($_DBCONFIG);
            $objDbUser = new \Cx\Core\Model\Model\Entity\DbUser();
            \DBG::msg('Website: setupDatabase..');
            $this->setupDatabase($langId, $this->owner, $objDb, $objDbUser);
            \DBG::msg('Website: setupDataFolder..');
            $this->setupDataFolder($websiteName);
            \DBG::msg('Website: setupConfiguration..');
            $this->setupConfiguration($websiteName, $objDb, $objDbUser);
            \DBG::msg('Website: setupMultiSiteConfig..');
            $this->setupMultiSiteConfig($websiteName);
            \DBG::msg('Website: setupRobotsFile..');
            $this->setupRobotsFile($websiteName);
            \DBG::msg('Website: createContrexxUser..');
            $this->createContrexxUser($websiteName);

            \DBG::msg('Website: prepare reset password function..');
            $this->owner->setRestoreKey();
            // hard-coded to 1 day
            $this->owner->setRestoreKeyTime(86400);
            $this->owner->store();
            $websitePasswordUrl = \FWUser::getPasswordRestoreLink(false, $this->owner);

            \DBG::msg('Website: setup process.. DONE');
            \DBG::msg('Website: Set state to '.self::STATE_ONLINE);
            $this->status = self::STATE_ONLINE;
            $websiteIp = \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteIp');
        }

        \Env::get('em')->persist($this);
        \Env::get('em')->flush();

        if (\Cx\Core\Setting\Controller\Setting::getValue('mode') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE) {
            throw new \Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException('MultiSite mode was set to Website at the end of setup process. No E-Mail was sent to '.$this->owner->getEmail());
        }
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER
            || \Cx\Core\Setting\Controller\Setting::getValue('mode') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID
        ) {
            $websiteDomain = $websiteName.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain');
            $websiteUrl = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getApiProtocol().$websiteName.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain');
            // write mail
            \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
            // send ADMIN mail
            \Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                'section' => 'MultiSite',
                'lang_id' => $langId,
                'key' => 'notifyAboutNewWebsite',
                //'to' => $websiteMail,
                'search' => array(
                    '[[MULTISITE_DOMAIN]]',
                    '[[WEBSITE_DOMAIN]]',
                    '[[WEBSITE_URL]]',
                    '[[WEBSITE_NAME]]',
                    '[[CUSTOMER_EMAIL]]',
                    '[[CUSTOMER_NAME]]',
                    '[[SUBSCRIPTION_NAME]]'),
                'replace' => array(
                    \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain'),
                    $websiteDomain,
                    $websiteUrl,
                    $websiteName,
                    $websiteMail,
                    '<customer-name>',
                    '<subscription:trial / business>'),
            ));
            // send CUSTOMER mail
            if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                'section' => 'MultiSite',
                'lang_id' => $langId,
                'key' => 'createInstance',
                'to' => $websiteMail,
                'search' => array('[[WEBSITE_DOMAIN]]', '[[WEBSITE_NAME]]', '[[WEBSITE_MAIL]]', '[[WEBSITE_PASSWORD_URL]]'),
                'replace' => array($websiteDomain, $websiteName, $websiteMail, $websitePasswordUrl),
            ))) {
            //  TODO: Implement proper error handler:
            //       removeWebsite() must not be called from within this method.
            //       Instead, in case the setup process fails, a proper exception must be thrown.
            //       Then the object that executed the setup() method must handle the exception
            //       and call the removeWebsite() method if required.
                //$this->removeWebsite($websiteName);
                throw new \Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException(array('object' => 'form', 'type' => 'success', 'message' => "Your website <a href='".ComponentController::getApiProtocol(). $websiteDomain/"'>$websiteDomain</a> has been build successfully. Unfortunately, we were unable to send you a message to the address <strong>$websiteMail</strong> with further instructions on how to proceed. Our helpdesk team will get in touch with you as soon as possible. We apologize for any inconvenience."));
            }
            return array(
                'status' => 'success',
            );
        }

        return array(
            'status' => 'success',
            'websiteIp' => $websiteIp,
            'codeBase' => $this->codeBase,
            'state' => $this->status
        );
    }
    
    /*
    * function validate to validate website name
    * */
    public function validate()
    {
        self::validateName($this->getName());
    }

    public static function validateName($name) {
        global $_ARRAYLANG, $objInit;

        $langData = $objInit->loadLanguageData('MultiSite');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        $websiteName = $name;

        // verify that name is not a blocked word
        $unavailablePrefixesValue = explode(',',\Cx\Core\Setting\Controller\Setting::getValue('unavailablePrefixes'));
        if (in_array($websiteName, $unavailablePrefixesValue)) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'], "<strong>$websiteName</strong>"));
        }

        // verify that name complies with naming scheme
        if (preg_match('/[^a-z0-9]/', $websiteName)) {
            throw new WebsiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_WRONG_CHARS']);
        }
        if (strlen($websiteName) < \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength')) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_TOO_SHORT'], \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength')));
        }
        if (strlen($websiteName) > \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength')) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_TOO_LONG'], \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength')));
        }

        // existing website
        if (\Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('name' => $websiteName))) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'], "<strong>$websiteName</strong>"));
        }
    }
    
    /*
    * function setupDatabase to create database
    * and populate database with basic data
    * @param $langId language ID of the website
    * */
    protected function setupDatabase($langId, $objUser, $objDb, $objDbUser){
        $objDbUser->setPassword(\User::make_password(8, true));
        $objDbUser->setName(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseUserPrefix').$this->id);      

        $objDb->setHost(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseHost'));
        $objDb->setName(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabasePrefix').$this->id);

        $websitedb = $this->initDatabase($objDb, $objDbUser);
        if (!$websitedb) {
            throw new WebsiteException('Database could not be created');
        }
        if (!$this->initDbStructure($objUser, $objDbUser, $langId, $websitedb)) {
            throw new WebsiteException('Database structure could not be initialized');
        }
        if (!$this->initDbData($objUser, $objDbUser, $langId, $websitedb)) {
            throw new WebsiteException('Database data could not be initialized');
        }    
    }
    /*
    * function setupDataFolder to create folders for 
    * website like configurations files
    * @param $websiteName name of the website
    * */
    protected function setupDataFolder($websiteName){
        // website's data repository
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName);
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName);

        // config
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config');

        // tmp
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/tmp');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/tmp');

        // themes
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/themes');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/themes');     

        // media
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive1');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive1');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive2');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive2');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive3');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive3');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive4');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/archive4');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/FileSharing');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/media/FileSharing');    

        // images
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/content');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/content');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/attach');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/attach');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Shop');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Shop');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Gallery');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Gallery');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Access');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Access');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Access/profile');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Access/profile');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/MediaDir');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/MediaDir');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Downloads');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Downloads');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Calender');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Calender');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Podcast');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Podcast');
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Blog');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/images/Blog');

        // feed
        \Cx\Lib\FileSystem\FileSystem::make_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/feed');
        \Cx\Lib\FileSystem\FileSystem::makeWritable(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/feed');
    }    
     /*
    * function setupConfiguration to create configuration
    * files
    * @param $website Name name of the website
    * */
    protected function setupConfiguration($websiteName, $objDb, $objDbUser){
        global $_PATHCONFIG;

        $codeBaseOfWebsite = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository').'/'.$this->codeBase  :  \Env::get('cx')->getCodeBaseDocumentRootPath();

        // setup base configuration (configuration.php)
        try {
            $configuration = new \Cx\Lib\FileSystem\File($codeBaseOfWebsite . \Env::get('cx')->getCoreModuleFolderName() . '/MultiSite/Data/WebsiteSkeleton/config/configuration.php');
            $configuration->copy(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config/configuration.php');

            $newConf = new \Cx\Lib\FileSystem\File(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config/configuration.php');
            $newConfData = $newConf->getData();
            $installationRootPath = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository').'/'.$this->codeBase : $_PATHCONFIG['ascms_installation_root'];

            // set database configuration
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'host\'\\] = \'.*?\';/', '$_DBCONFIG[\'host\'] = \'' .$objDb->getHost() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'tablePrefix\'\\] = \'.*?\';/', '$_DBCONFIG[\'tablePrefix\'] = \'' .$objDb->getTablePrefix() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'dbType\'\\] = \'.*?\';/', '$_DBCONFIG[\'dbType\'] = \'' .$objDb->getdbType() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'charset\'\\] = \'.*?\';/', '$_DBCONFIG[\'charset\'] = \'' .$objDb->getCharset() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'timezone\'\\] = \'.*?\';/', '$_DBCONFIG[\'timezone\'] = \'' .$objDb->getTimezone() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'database\'\\] = \'.*?\';/', '$_DBCONFIG[\'database\'] = \'' .$objDb->getName() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'user\'\\] = \'.*?\';/', '$_DBCONFIG[\'user\'] = \'' . $objDbUser->getName() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'password\'\\] = \'.*?\';/', '$_DBCONFIG[\'password\'] = \'' . $objDbUser->getPassword() . '\';', $newConfData);
            
            // set path configuration
            $newConfData = preg_replace('/\\$_PATHCONFIG\\[\'ascms_root\'\\] = \'.*?\';/', '$_PATHCONFIG[\'ascms_root\'] = \'' . \Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_PATHCONFIG\\[\'ascms_installation_root\'\\] = \'.*?\';/', '$_PATHCONFIG[\'ascms_installation_root\'] = \'' . $installationRootPath . '\';', $newConfData);          
                        
            $newConf->write($newConfData);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to setup configuration file: '.$e->getMessage());
        }

        // setup basic configuration (settings.php)
        try {
            $settings = new \Cx\Lib\FileSystem\File($codeBaseOfWebsite . \Env::get('cx')->getCoreModuleFolderName() . '/MultiSite/Data/WebsiteSkeleton/config/settings.php');
            $settings->copy(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config/settings.php');
            $newSettings = new \Cx\Lib\FileSystem\File(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config/settings.php');
            $settingsData = preg_replace_callback(
                '/(\$_CONFIG\[([\'"])((?:(?!\2).)*)\2\]\s*=\s*([\'"]))(?:(?:(?!\4).)*)(\4;)/',
                function($match) {
                    $originalString = $match[0];
                    $optionString = $match[1];
                    $settingsOption = $match[3];
                    $delimiter = $match[4];
                    $closure = $match[5];
                    $escapedDelimiter = addslashes($delimiter);
                    switch ($settingsOption) {
                        case 'domainUrl':
                            $value = $this->getBaseDn()->getName();
                            break;
                        case 'installationId':
                            $value = $this->installationId;
                            break;
                        default:
                            return $originalString;
                            break;
                    }
                    $escapedValue = str_replace($delimiter, $escapedDelimiter, $value);
                    return  $optionString . $escapedValue . $closure;
                },
                $newSettings->getData()
            );
            $newSettings->write($settingsData);
            
            $websitePath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath');
            $websiteConfigPath = $websitePath . '/' . $websiteName . \Env::get('cx')->getConfigFolderName();
            \Cx\Core\Config\Controller\Config::init($websiteConfigPath);

            // we must re-initialize the original MultiSite settings of the main installation
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            // we must re-initialize the original MultiSite settings of the main installation
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');

            throw new WebsiteException('Unable to setup settings file: '.$e->getMessage());
        }
        
        // setup preInitHooks.yml
        try {
            $preInit = new \Cx\Lib\FileSystem\File($codeBaseOfWebsite . \Env::get('cx')->getConfigFolderName() . '/preInitHooks.yml');
            $preInit->copy(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config/preInitHooks.yml');
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to set up preInitHooks.yml: '.$e->getMessage());
        }

        // setup DomainRepository.yml
        try {
            $domainRepository = new \Cx\Lib\FileSystem\File($codeBaseOfWebsite . \Env::get('cx')->getCoreModuleFolderName() . '/MultiSite/Data/WebsiteSkeleton/config/DomainRepository.yml');
            $domainRepository->copy(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/config/DomainRepository.yml');
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to set up DomainRepository.yml: '.$e->getMessage());
        }
    }

    protected function setupMultiSiteConfig($websiteName)
    {
        $websitePath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath');
        $websiteConfigPath = $websitePath . '/' . $websiteName . \Env::get('cx')->getConfigFolderName();

        $config = \Env::get('config');
        $serviceInstallationId = $config['installationId'];
        $serviceHostname = $config['domainUrl'];
        $websiteHttpAuthMethod   = \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthMethod');
        $websiteHttpAuthUsername = \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthUsername');
        $websiteHttpAuthPassword = \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthPassword');
        
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'config','FileSystem', $websiteConfigPath);
            if (\Cx\Core\Setting\Controller\Setting::getValue('mode') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('mode', \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE, 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE.':'.\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE, 'config')){
                    throw new \Exception("Failed to add Setting entry for MultiSite mode");
            }
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'website','FileSystem', $websiteConfigPath);
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHostname') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHostname', $serviceHostname, 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new \Exception("Failed to add Setting entry for Hostname of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceSecretKey') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceSecretKey', $this->secretKey, 3,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new \Exception("Failed to add Setting entry for SecretKey of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceInstallationId') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceInstallationId', $serviceInstallationId, 4,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new \Exception("Failed to add Setting entry for InstallationId of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteUserId') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteUserId', 0, 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new \Exception("Failed to add Setting entry for InstallationId of Website User Id");
            }
// TODO: HTTP-Authentication details of Website Service Server must be set
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthMethod') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHttpAuthMethod', $websiteHttpAuthMethod, 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'none:none, basic:basic, digest:digest', 'website')){
                    throw new \Exception("Failed to add Setting entry for HTTP Authentication Method of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthUsername') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHttpAuthUsername', $websiteHttpAuthUsername, 6,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new \Exception("Failed to add Setting entry for HTTP Authentication Username of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthPassword') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHttpAuthPassword', $websiteHttpAuthPassword, 7,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new \Exception("Failed to add Setting entry for HTTP Authentication Password of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteState') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteState', $this->status, 8,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, self::STATE_ONLINE.':'.self::STATE_ONLINE.','.self::STATE_OFFLINE.':'.self::STATE_OFFLINE.','.self::STATE_INIT.':'.self::STATE_INIT.','.self::STATE_SETUP.':'.self::STATE_SETUP, 'website')){
                    throw new \Exception("Failed to add website entry for website state");
            }
        } catch (\Exception $e) {
            // we must re-initialize the original MultiSite settings of the main installation
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            throw new WebsiteException('Error in setting up the MultiSite configuration:'. $e->getMessage());
        }

        // we must re-initialize the original MultiSite settings of the main installation
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
    }

    protected function createContrexxUser($websiteName)
    {
        $params = array(
            'email' => $this->owner->getEmail(),
            'active'=> 1,
            'admin' => 1,
        );
        $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSite::executeCommandOnWebsite('createUser', $params, $this);
        if(!$resp || $resp->status == 'error'){
            $errMsg = isset($resp->message) ? $resp->message : '';
            \DBG::dump($resp);
            \DBG::msg($errMsg);
            throw new WebsiteException('Unable to create admin user account.');
        }
    }

    /**
     * Removes non-activated websites that are older than 60 days
    */
    public function cleanup() {
throw new WebsiteException('implement secret-key algorithm first!');
        $instRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $websites = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($instRepo->findAll());
        $someTimeAgo = strtotime('60 days ago');
        foreach ($websites as $website) {
            if (!$website->isActivated() && $website->getCreateDate() < $someTimeAgo) {
                $this->removeWebsite($website->getName());
            }
        }
    }
    
    /**
     * Completely removes an website
     * @param type $websiteName 
     */
    public function removeWebsite($websiteName, $silent = false) {
        if (is_array($websiteName)) {
            if (isset($websiteName['post']) && isset($websiteName['post']['websiteName'])) {
                $websiteName = $websiteName['post']['websiteName'];
            } else {
                $websiteName = '';
            }
        }
        if (empty($websiteName)) {
            $websiteName = current(explode('.', substr($_SERVER['HTTP_ORIGIN'], 8)));
        }
        
        // check if installation exists
        if (!file_exists(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName)) {
            if ($silent) {
                return false;
            }
            throw new MultiSiteException('No website with that name');
        }
        
// TODO: remove database user
        // remove database
        $dbName = \Cx\Core\Setting\Controller\Setting::getValue('websiteDatabasePrefix').$this->id;
        $dbObj = new \Cx\Core\Model\Model\Entity\Db();
        $dbObj->setName($dbName);
        $this->websiteController->removeDb($dbObj);

        // remove files
        \Cx\Lib\FileSystem\FileSystem::delete_folder(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName, true);

        return 'success';
    }
    
    protected function initDatabase($objDb, $objDbUser)
    {
        //call db controller method to create new db
        $this->websiteController->createDb($objDb, $objDbUser);

        //call core db class to create db connection object
        $dbClass = new \Cx\Core\Model\Db($objDb, $objDbUser);
        $websitedb = $dbClass->getAdoDb();       

        return $websitedb;
    }

    protected function initDbStructure($objUser, $objDbUser, $langId, $websitedb) {
        return $this->initDb('structure', $objUser, $objDbUser, $langId, $websitedb);
    }
    
    protected function initDbData($objUser, $objDbUser, $langId, $initDbData) {
        return $this->initDb('data', $objUser, $objDbUser, $langId, $initDbData);
    }
    
    /**
     *
     * @param type $dbPrefix
     * @param type $type
     * @param type $mail
     * @return boolean|string
     * @throws \Exception 
     */
    protected function initDb($type, $objUser, $objDbUser, $langId, $websitedb) {
        $dumpFilePath = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository').'/'.$this->codeBase  :  \Env::get('cx')->getCodeBaseDocumentRootPath();
        $fp = @fopen(\Env::get('ClassLoader')->getFilePath($dumpFilePath.'/installer/data/contrexx_dump_' . $type . '.sql'), "r");
        if ($fp === false) {
            throw new \Exception('File not found');
        }

        $line = 1;
        if (!isset($_SESSION['MultiSite'])) {
            $_SESSION['MultiSite'] = array();
        }
        if (!isset($_SESSION['MultiSite']['sqlqueries'])) {
            $_SESSION['MultiSite']['sqlqueries'] = array();
        }
        if (!isset($_SESSION['MultiSite']['sqlqueries'][$type])) {
            $_SESSION['MultiSite']['sqlqueries'][$type] = 0;
        }
        $sqlQuery = '';
        $statusMsg = '';
        while (!feof($fp)) {
            if ($_SESSION['MultiSite']['sqlqueries'][$type] >= $line) {
                $line++;
                continue;
            }
            $buffer = fgets($fp);
            if ((substr($buffer,0,1) != "#") && (substr($buffer,0,2) != "--")) {
                $sqlQuery .= $buffer;
                if (preg_match("/;[ \t\r\n]*$/", $buffer)) {
                    // Don't have to replace prefix, because it is in a separate db.
                    // This would be required when using single-database-mode.
                    // Single-database-mode has not yet been implemented.
                    //$sqlQuery = preg_replace($dbPrefixRegexp, '`'.$dbsuffix.'$1`', $sqlQuery);
                    $sqlQuery = preg_replace('#CONSTRAINT(\s)*`([0-9a-z_]*)`(\s)*FOREIGN KEY#', 'CONSTRAINT FOREIGN KEY', $sqlQuery);
                    $sqlQuery = preg_replace('/TYPE=/', 'ENGINE=', $sqlQuery);
                    $result = $websitedb->Execute($sqlQuery);
                    if ($result === false) {
                        $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, 'UTF-8')."<br /> (".$websitedb->ErrorMsg().")<br />";
                        return $statusMsg;
/*                    } else {
                        echo $sqlQuery;*/
                    }
                    $sqlQuery = '';
                }
            }
            $_SESSION['MultiSite']['sqlqueries'][$type] = $line;
            $line++;
        }
        
        if ($type == 'data') {
// TODO: create default user
            // set default language for user
            $result = $websitedb->Execute(
                    'UPDATE `contrexx_access_users`
                        SET `frontend_lang_id` = ' . $langId . ',
                            `backend_lang_id`  = ' . $langId . '
                        WHERE `email` = \'' . $objUser->getEmail() . '\''
            );
            if ($result === false) {
                $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, 'UTF-8')."<br /> (".$websitedb->ErrorMsg().")<br />";
                return $statusMsg;
            }

            // set default language for installation
            $result = $websitedb->Execute('
                    UPDATE
                        `contrexx_languages`
                    SET
                        `is_default` =
                            CASE `id`
                                WHEN ' . $langId . '
                                THEN \'true\'
                                ELSE \'false\'
                            END'
            );
            if ($result === false) {
                $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, 'UTF-8')."<br /> (".$websitedb->ErrorMsg().")<br />";
                return $statusMsg;
            }
        }
        
        global $_DBCONFIG;
        unset($_SESSION['MultiSite']['sqlqueries'][$type]);

        if (empty($statusMsg)) {
            return true;
        } else {
            //echo $statusMsg;
            return $statusMsg;
        }
    }

    function generateInstalationId(){
        $randomHash = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSite::generateSecretKey();
        $installationId = $randomHash . str_pad(dechex(crc32($randomHash)), 8, '0', STR_PAD_LEFT);    
        return $installationId;
    }

    /**
     * Set Fqdn
     *
     */    
    function setFqdn(){
        $config = \Env::get('config');
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER) {
            $serviceServerHostname = $this->websiteServiceServer->getHostname();
        } else {
            $serviceServerHostname = $config['domainUrl'];
        }
        $fqdn = new Domain($this->name.'.'.$serviceServerHostname);
        $fqdn->setType(Domain::TYPE_FQDN);
        $this->fqdn = $fqdn;
        $this->mapDomain($this->fqdn);
        \Env::get('em')->persist($this->fqdn);
    }
    
    /**
     * get Fqdn
     *
     */    
    public function getFqdn(){
        if ($this->fqdn) {
            return $this->fqdn;
        }

        // fetch FQDN from Domain repository
        return \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain')->findOneBy(array('type' => Domain::TYPE_FQDN, 'componentId' => $this->id));
    }   
    
    /**
     * Set BaseDn
     *
     */    
    function setBaseDn(){
        $baseDn = new Domain($this->name.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain'));
        $baseDn->setType(Domain::TYPE_BASE_DOMAIN);
        $this->baseDn = $baseDn;
        $this->mapDomain($this->baseDn);
        \Env::get('em')->persist($this->baseDn);
    }
    
    /**
     * Get BaseDn
     *
     */    
    public function getBaseDn(){
        if ($this->baseDn) {
            return $this->baseDn;
        }

        // fetch baseDn from Domain repository
        return \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain')->findOneBy(array('type' => Domain::TYPE_BASE_DOMAIN, 'componentId' => $this->id)); 
    }
    
    /**
     * Get DomainAliases
     *
     */   
    public function getDomainAliases(){
        return \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain')->findBy(array('type' => Domain::TYPE_EXTERNAL_DOMAIN, 'componentId' => $this->id));
    }

    /**
     * Get domains
     *
     * @return Doctrine\Common\Collections\Collection $domains
     */
    public function getDomains() {
        $this->domains = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain')->findBy(array('componentId' => $this->id));

        return $this->domains;
    }
    
    /**
     * mapDomain
     * 
     * @param Cx\Core_Modules\MultiSite\Model\Entity\Domain $domain
     */  
    public function mapDomain(Domain $domain) {
        $domain->setWebsite($this);
        $this->domains[] = $domain;
    }
    
    /**
     * 
     * unmapDomain
     *
     * @param string $name websitename
     */  
    public function unmapDomain($name){
        
        foreach ($this->getDomainAliases() as $domain) {
            if($domain->name == $name) {
                \Env::get('em')->remove($domain);
                break;
            }   
        }
    }
    /**
     * setup Robots File
     * 
     * @param string $websiteName websitename
     * 
     * @throws WebsiteException
     */
    public function setupRobotsFile($websiteName) {
        try {
            $codeBaseOfWebsite = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository').'/'.$this->codeBase  :  \Env::get('cx')->getCodeBaseDocumentRootPath();
            $setupRobotFile = new \Cx\Lib\FileSystem\File($codeBaseOfWebsite . \Env::get('cx')->getCoreModuleFolderName() . '/MultiSite/Data/WebsiteSkeleton/robots.txt');
            $setupRobotFile->copy(\Cx\Core\Setting\Controller\Setting::getValue('websitePath').'/'.$websiteName . '/robots.txt');
        }  catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to setup robot file: '.$e->getMessage());
        }
    }
}
