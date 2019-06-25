<?php

namespace Cx\Model\Proxies\__CG__\Cx\Modules\Shop\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Pricelist extends \Cx\Modules\Shop\Model\Entity\Pricelist implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     */
    public function __get($name)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__get', array($name));

        return parent::__get($name);
    }





    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', 'id', 'name', 'langId', 'borderOn', 'headerOn', 'headerLeft', 'headerRight', 'footerOn', 'footerLeft', 'footerRight', 'categories', 'lang', 'allCategories', 'validators', 'virtual');
        }

        return array('__isInitialized__', 'id', 'name', 'langId', 'borderOn', 'headerOn', 'headerLeft', 'headerRight', 'footerOn', 'footerLeft', 'footerRight', 'categories', 'lang', 'allCategories', 'validators', 'virtual');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Pricelist $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', array($name));

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function setLangId($langId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLangId', array($langId));

        return parent::setLangId($langId);
    }

    /**
     * {@inheritDoc}
     */
    public function getLangId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLangId', array());

        return parent::getLangId();
    }

    /**
     * {@inheritDoc}
     */
    public function setBorderOn($borderOn)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBorderOn', array($borderOn));

        return parent::setBorderOn($borderOn);
    }

    /**
     * {@inheritDoc}
     */
    public function getBorderOn()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBorderOn', array());

        return parent::getBorderOn();
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaderOn($headerOn)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setHeaderOn', array($headerOn));

        return parent::setHeaderOn($headerOn);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderOn()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getHeaderOn', array());

        return parent::getHeaderOn();
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaderLeft($headerLeft)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setHeaderLeft', array($headerLeft));

        return parent::setHeaderLeft($headerLeft);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderLeft()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getHeaderLeft', array());

        return parent::getHeaderLeft();
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaderRight($headerRight)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setHeaderRight', array($headerRight));

        return parent::setHeaderRight($headerRight);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderRight()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getHeaderRight', array());

        return parent::getHeaderRight();
    }

    /**
     * {@inheritDoc}
     */
    public function setFooterOn($footerOn)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFooterOn', array($footerOn));

        return parent::setFooterOn($footerOn);
    }

    /**
     * {@inheritDoc}
     */
    public function getFooterOn()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFooterOn', array());

        return parent::getFooterOn();
    }

    /**
     * {@inheritDoc}
     */
    public function setFooterLeft($footerLeft)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFooterLeft', array($footerLeft));

        return parent::setFooterLeft($footerLeft);
    }

    /**
     * {@inheritDoc}
     */
    public function getFooterLeft()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFooterLeft', array());

        return parent::getFooterLeft();
    }

    /**
     * {@inheritDoc}
     */
    public function setFooterRight($footerRight)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFooterRight', array($footerRight));

        return parent::setFooterRight($footerRight);
    }

    /**
     * {@inheritDoc}
     */
    public function getFooterRight()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFooterRight', array());

        return parent::getFooterRight();
    }

    /**
     * {@inheritDoc}
     */
    public function addCategory(\Cx\Modules\Shop\Model\Entity\Category $category)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addCategory', array($category));

        return parent::addCategory($category);
    }

    /**
     * {@inheritDoc}
     */
    public function removeCategory(\Cx\Modules\Shop\Model\Entity\Category $category)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeCategory', array($category));

        return parent::removeCategory($category);
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCategories', array());

        return parent::getCategories();
    }

    /**
     * {@inheritDoc}
     */
    public function setLang(\Cx\Core\Locale\Model\Entity\Locale $lang = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLang', array($lang));

        return parent::setLang($lang);
    }

    /**
     * {@inheritDoc}
     */
    public function getLang()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLang', array());

        return parent::getLang();
    }

    /**
     * {@inheritDoc}
     */
    public function setAllCategories($allCategories)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAllCategories', array($allCategories));

        return parent::setAllCategories($allCategories);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllCategories()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAllCategories', array());

        return parent::getAllCategories();
    }

    /**
     * {@inheritDoc}
     */
    public function getComponentController()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getComponentController', array());

        return parent::getComponentController();
    }

    /**
     * {@inheritDoc}
     */
    public function setVirtual($virtual)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setVirtual', array($virtual));

        return parent::setVirtual($virtual);
    }

    /**
     * {@inheritDoc}
     */
    public function isVirtual()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isVirtual', array());

        return parent::isVirtual();
    }

    /**
     * {@inheritDoc}
     */
    public function initializeValidators()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'initializeValidators', array());

        return parent::initializeValidators();
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'validate', array());

        return parent::validate();
    }

    /**
     * {@inheritDoc}
     */
    public function __call($methodName, $arguments)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__call', array($methodName, $arguments));

        return parent::__call($methodName, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__toString', array());

        return parent::__toString();
    }

}
