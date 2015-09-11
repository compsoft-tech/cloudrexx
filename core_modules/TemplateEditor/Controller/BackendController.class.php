<?php

/**
 * Class BackendController
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */

namespace Cx\Core_Modules\TemplateEditor\Controller;


use Cx\Core\Html\Sigma;
use Cx\Core\View\Model\Entity\Theme;
use Cx\Core_Modules\TemplateEditor\Model\Entity\OptionSet;
use Cx\Core_Modules\TemplateEditor\Model\OptionSetFileStorage;
use Cx\Core_Modules\TemplateEditor\Model\PresetFileStorage;
use Cx\Core_Modules\TemplateEditor\Model\PresetRepositoryException;
use Cx\Core_Modules\TemplateEditor\Model\Repository\OptionSetRepository;
use Cx\Core\Core\Model\Entity\SystemComponentBackendController;
use Cx\Core\Routing\Url;
use Cx\Core\View\Model\Repository\ThemeRepository;
use Cx\Core_Modules\TemplateEditor\Model\Repository\PresetRepository;

class BackendController extends SystemComponentBackendController
{
    /**
     * @var ThemeRepository
     */
    protected $themeRepository;
    /**
     * @var OptionSetRepository
     */
    protected $themeOptionRepository;

    /**
     * @var OptionSet
     */
    protected $themeOptions;

    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @var PresetRepository
     */
    protected $presetRepository;

    /**
     * Returns a list of available commands (?act=XY)
     *
     * @return array List of acts
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     *
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array               $cmd      CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd)
    {
        \Permission::checkAccess(47, 'static');
        $fileStorage                 = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository       = new OptionSetRepository($fileStorage);
        $this->themeOptionRepository = $themeOptionRepository;
        $this->themeRepository       = new ThemeRepository();
        $themeID                     = isset($_GET['tid']) ? $_GET['tid'] : 1;
        $this->theme                 = $this->themeRepository->findById(
            $themeID
        );

        if (!$_SESSION['TemplateEditor']) {
            $_SESSION['TemplateEditor'] = array();
        }
        if (!$_SESSION['TemplateEditor'][$this->theme->getId()]) {
            $_SESSION['TemplateEditor'][$this->theme->getId()] = array();
        }
        if (isset($_GET['preset'])) {
            if ($_SESSION['TemplateEditor'][$this->theme->getId(
                )]['activePreset'] != filter_var(
                    $_GET['preset'], FILTER_SANITIZE_STRING
                )
            ) {
                $_SESSION['TemplateEditor'][$this->theme->getId()] = array();
            }
            $_SESSION['TemplateEditor'][$this->theme->getId()]['activePreset']
                = isset($_GET['preset']) ? filter_var(
                $_GET['preset'], FILTER_SANITIZE_STRING
            ) : 'Default';
        }


        $this->presetRepository = new PresetRepository(
            new PresetFileStorage(
                $this->cx->getWebsiteThemesPath() . '/'
                . $this->theme->getFoldername()
            )
        );
        $this->themeOptions     = $this->themeOptionRepository->get(
            $this->theme
        );
        try {
            $this->themeOptions->applyPreset(
                $this->presetRepository->getByName(
                    $_SESSION['TemplateEditor']
                    [$this->theme->getId()]
                    ['activePreset']
                )
            );
        } catch (PresetRepositoryException $e) {
            $_SESSION['TemplateEditor'][$this->theme->getId()]['activePreset']
                = 'Default';
            $this->themeOptions->applyPreset(
                $this->presetRepository->getByName(
                    'Default'
                )
            );
        }

        $this->showOverview($template);
    }

    /**
     * Creates the main overview for this component.
     *
     * @param $template
     *
     * @throws \Cx\Core\Routing\UrlException
     */
    public function showOverview(Sigma $template)
    {
        global $_ARRAYLANG, $_CONFIG;
        \JS::registerJS('core_modules/TemplateEditor/View/Script/spectrum.js');
        $template->loadTemplateFile(
            $this->cx->getCodeBaseCoreModulePath()
            . '/TemplateEditor/View/Template/Backend/Default.html'
        );
        /**
         * @var $themes Theme[]
         */
        $themes = $this->themeRepository->findAll();
        foreach ($themes as $theme) {
            $template->setVariable(
                array(
                    'TEMPLATEEDITOR_LAYOUT_NAME' => $theme->getThemesname(),
                    'TEMPLATEEDITOR_LAYOUT_ID' => $theme->getId()
                )
            );
            if ($this->theme->getId() == $theme->getId()) {
                $template->setVariable(
                    array(
                        'TEMPLATEEDITOR_LAYOUT_ACTIVE' => 'selected'
                    )
                );
            }
            $template->parse('layouts');
        }

        $presets = $this->presetRepository->findAll();
        foreach ($presets as $preset) {
            $template->setVariable(
                array(
                    'TEMPLATEEDITOR_PRESET_NAME' => $this->themeOptions->getActivePreset(
                    )->getName() == $preset ? $preset . ' ('
                        . $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_PRESET_ACTIVE']
                        . ')' : $preset,
                    'TEMPLATEEDITOR_PRESET_ID' => $preset
                )
            );
            if ($_SESSION['TemplateEditor'][$this->theme->getId()]
                ['activePreset'] == $preset
            ) {
                $template->setVariable(
                    array(
                        'TEMPLATEEDITOR_PRESET_ACTIVE' => 'selected'
                    )
                );
            }
            $template->parse('presets');
        }
        if ($_SESSION['TemplateEditor'][$this->theme->getId()]['activePreset']
            == $this->themeOptions->getActivePreset()->getName()
        ) {
            $template->setVariable(
                array(
                    'TEMPLATEDITOR_PRESET_IS_ALREADY_ACTIVE' => 'disabled'
                )
            );

            $template->setVariable(
                array(
                    'TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT_ACTIVE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT_IS_ACTIVE']
                )
            );
        }
        if ($_SESSION['TemplateEditor'][$this->theme->getId()]['activePreset']
            == 'Default'
        ) {
            $template->setVariable(
                array(
                    'TEMPLATEDITOR_PRESET_IS_DEFAULT' => 'disabled'
                )
            );

        }
        foreach ($presets as $preset) {
            $template->setVariable(
                array(
                    'TEMPLATEEDITOR_PRESET_FOR_PRESETS_NAME' => $preset,
                    'TEMPLATEEDITOR_PRESET_FOR_PRESETS_ID' => $preset
                )
            );
            $template->parse('presetsForPresets');
        }

        $this->themeOptions->renderOptions($template);

        if ($this->themeOptions->getOptionCount() == 0) {
            $template->setVariable(
                array(
                    'TEMPLATEOPTION_NO_OPTIONS_TEXT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NO_OPTIONS_HELP'],
                    'TEMPLATEOPTION_NO_OPTIONS_LINKNAME' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NO_OPTIONS_LINKNAME']
                )
            );
            $template->parse('no_options');
        } else {
            $template->parse('presetBlock');
        }
        $template->setVariable(
            array(
                'TEMPLATEEDITOR_IFRAME_URL' => Url::fromModuleAndCmd(
                    'home', '', null,
                    array(
                        'preview' => $this->theme->getId(),
                        'templateEditor' => 1
                    )
                ),
                'TEMPLATEEDITOR_BACKURL' => './index.php?cmd=ViewManager'
            )
        );
        $template->setGlobalVariable($_ARRAYLANG);
        \ContrexxJavascript::getInstance()->setVariable(
            array(
                'newPresetTemplate' => '',
                'TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_CONTENT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_CONTENT'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_YES' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_YES'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_NO' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NO'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_ACTIVATE_PRESET_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_ACTIVATE_PRESET_TITLE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET_TITLE'],
                'themeid' => $this->theme->getId(),
                'iframeUrl' => Url::fromModuleAndCmd(
                    'home', '', null,
                    array(
                        'preview' => $this->theme->getId(),
                        'templateEditor' => 1
                    )
                )->toString(),
                'domainUrl' => $_CONFIG['domainUrl']
            ),
            'TemplateEditor'
        );
    }

}