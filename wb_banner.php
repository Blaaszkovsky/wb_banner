<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Wb_Banner extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'wb_banner';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PrestaShop edit. WBSHOP.pl';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('(WB) Banner', [], 'Modules.Banner.Admin');
        $this->description = $this->trans('Add a banner to the homepage of your store to highlight your sales and new products in a visual and friendly way.', [], 'Modules.Banner.Admin');

        $this->ps_versions_compliancy = ['min' => '1.7.1.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:wb_banner/views/templates/hook/wb_banner.tpl';
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('actionObjectLanguageAddAfter') &&
            $this->installFixtures();
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixture((int) $params['object']->id, Configuration::get('WB_BANNER_IMG', (int) Configuration::get('PS_LANG_DEFAULT')));
    }

    protected function installFixtures()
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $this->installFixture((int) $lang['id_lang'], 'sale70.png');
        }

        return true;
    }

    protected function installFixture($id_lang, $image = null)
    {
        $values['WB_BANNER_IMG'][(int) $id_lang] = $image;
        $values['WB_BANNER_MOBILE_IMG'][(int) $id_lang] = $image;
        $values['WB_BANNER_LINK'][(int) $id_lang] = '';
        $values['WB_BANNER_IMG_ALT'][(int) $id_lang] = '';
        $values['WB_BANNER_DESC'][(int) $id_lang] = '';
        $values['WB_BANNER_BUTTON_TEXT'][(int) $id_lang] = '';

        Configuration::updateValue('WB_BANNER_IMG', $values['WB_BANNER_IMG']);
        Configuration::updateValue('WB_BANNER_MOBILE_IMG', $values['WB_BANNER_MOBILE_IMG']);
        Configuration::updateValue('WB_BANNER_LINK', $values['WB_BANNER_LINK']);
        Configuration::updateValue('WB_BANNER_IMG_ALT', $values['WB_BANNER_IMG_ALT']);
        Configuration::updateValue('WB_BANNER_DESC', $values['WB_BANNER_DESC'], true);
        Configuration::updateValue('WB_BANNER_BUTTON_TEXT', $values['WB_BANNER_BUTTON_TEXT']);
    }

    public function uninstall()
    {
        Configuration::deleteByName('WB_BANNER_IMG');
        Configuration::deleteByName('WB_BANNER_MOBILE_IMG');
        Configuration::deleteByName('WB_BANNER_LINK');
        Configuration::deleteByName('WB_BANNER_IMG_ALT');
        Configuration::deleteByName('WB_BANNER_DESC');
        Configuration::deleteByName('WB_BANNER_BUTTON_TEXT');

        return parent::uninstall();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $languages = Language::getLanguages(false);
            $values = [];
            $update_images_values = false;

            foreach ($languages as $lang) {
                if (isset($_FILES['WB_BANNER_IMG_' . $lang['id_lang']])
                    && isset($_FILES['WB_BANNER_IMG_' . $lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['WB_BANNER_IMG_' . $lang['id_lang']]['tmp_name'])) {
                    if ($error = ImageManager::validateUpload($_FILES['WB_BANNER_IMG_' . $lang['id_lang']], 4000000)) {
                        return $this->displayError($error);
                    } else {
                        $ext = substr($_FILES['WB_BANNER_IMG_' . $lang['id_lang']]['name'], strrpos($_FILES['WB_BANNER_IMG_' . $lang['id_lang']]['name'], '.') + 1);
                        $file_name = md5($_FILES['WB_BANNER_IMG_' . $lang['id_lang']]['name']) . '.' . $ext;

                        if (!move_uploaded_file($_FILES['WB_BANNER_IMG_' . $lang['id_lang']]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file_name)) {
                            return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                        } else {
                            if (Configuration::hasContext('WB_BANNER_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('WB_BANNER_IMG', $lang['id_lang']) != $file_name) {
                                @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . Configuration::get('WB_BANNER_IMG', $lang['id_lang']));
                            }

                            $values['WB_BANNER_IMG'][$lang['id_lang']] = $file_name;
                        }
                    }

                    $update_images_values = true;
                }

                if (isset($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']])
                    && isset($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']]['tmp_name'])) {
                    if ($error = ImageManager::validateUpload($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']], 4000000)) {
                        return $this->displayError($error);
                    } else {
                        $ext = substr($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']]['name'], strrpos($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']]['name'], '.') + 1);
                        $file_name = md5($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']]['name']) . '.' . $ext;

                        if (!move_uploaded_file($_FILES['WB_BANNER_MOBILE_IMG_' . $lang['id_lang']]['tmp_name'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $file_name)) {
                            return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                        } else {
                            if (Configuration::hasContext('WB_BANNER_MOBILE_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('WB_BANNER_MOBILE_IMG', $lang['id_lang']) != $file_name) {
                                @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . Configuration::get('WB_BANNER_MOBILE_IMG', $lang['id_lang']));
                            }

                            $values['WB_BANNER_MOBILE_IMG'][$lang['id_lang']] = $file_name;
                        }
                    }

                    $update_images_values = true;
                }

                $values['WB_BANNER_LINK'][$lang['id_lang']] = Tools::getValue('WB_BANNER_LINK_' . $lang['id_lang']);
                $values['WB_BANNER_IMG_ALT'][$lang['id_lang']] = Tools::getValue('WB_BANNER_IMG_ALT_' . $lang['id_lang']);
                $values['WB_BANNER_DESC'][$lang['id_lang']] = Tools::getValue('WB_BANNER_DESC_' . $lang['id_lang']);
                $values['WB_BANNER_BUTTON_TEXT'][$lang['id_lang']] = Tools::getValue('WB_BANNER_BUTTON_TEXT_' . $lang['id_lang']);
            }

            if ($update_images_values && isset($values['WB_BANNER_IMG'])) {
                Configuration::updateValue('WB_BANNER_IMG', $values['WB_BANNER_IMG']);
            }

            if ($update_images_values && isset($values['WB_BANNER_MOBILE_IMG'])) {
                Configuration::updateValue('WB_BANNER_MOBILE_IMG', $values['WB_BANNER_MOBILE_IMG']);
            }

            Configuration::updateValue('WB_BANNER_LINK', $values['WB_BANNER_LINK']);
            Configuration::updateValue('WB_BANNER_IMG_ALT', $values['WB_BANNER_IMG_ALT']);
            Configuration::updateValue('WB_BANNER_DESC', $values['WB_BANNER_DESC'], true);
            Configuration::updateValue('WB_BANNER_BUTTON_TEXT', $values['WB_BANNER_BUTTON_TEXT']);

            $this->_clearCache($this->templateFile);

            return $this->displayConfirmation($this->trans('The settings have been updated.', [], 'Admin.Notifications.Success'));
        }

        return '';
    }

    public function getContent()
    {
        return $this->postProcess() . $this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('Banner image', [], 'Modules.Banner.Admin'),
                        'name' => 'WB_BANNER_IMG',
                        'desc' => $this->trans('Upload an image for your banner. The recommended dimensions are 1110 x 214px if you are using the default theme.', [], 'Modules.Banner.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'file_lang',
                        'label' => $this->trans('Banner mobile image', [], 'Modules.Banner.Admin'),
                        'name' => 'WB_BANNER_MOBILE_IMG',
                        'desc' => $this->trans('Upload an mobile image for your banner.', [], 'Modules.Banner.Admin'),
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Banner Link', [], 'Modules.Banner.Admin'),
                        'name' => 'WB_BANNER_LINK',
                        'desc' => $this->trans('Enter the link associated to your banner. When clicking on the banner, the link opens in the same window. If no link is entered, it redirects to the homepage.', [], 'Modules.Banner.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Banner img alt', [], 'Modules.Banner.Admin'),
                        'name' => 'WB_BANNER_IMG_ALT',
                        'desc' => $this->trans('Enter the image alt text.', [], 'Modules.Banner.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Banner button text', [], 'Modules.Banner.Admin'),
                        'name' => 'WB_BANNER_BUTTON_TEXT',
                        'desc' => $this->trans('Enter the button text.', [], 'Modules.Banner.Admin'),
                    ],
                    [
                        'type' => 'textarea',
                        'lang' => true,
                        'label' => $this->trans('Banner description', [], 'Modules.Banner.Admin'),
                        'name' => 'WB_BANNER_DESC',
                        'desc' => $this->trans('Please enter a short but meaningful description for the banner.', [], 'Modules.Banner.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            $fields['WB_BANNER_IMG'][$lang['id_lang']] = Tools::getValue('WB_BANNER_IMG_' . $lang['id_lang'], Configuration::get('WB_BANNER_IMG', $lang['id_lang']));
            $fields['WB_BANNER_MOBILE_IMG'][$lang['id_lang']] = Tools::getValue('WB_BANNER_MOBILE_IMG_' . $lang['id_lang'], Configuration::get('WB_BANNER_MOBILE_IMG', $lang['id_lang']));
            $fields['WB_BANNER_LINK'][$lang['id_lang']] = Tools::getValue('WB_BANNER_LINK_' . $lang['id_lang'], Configuration::get('WB_BANNER_LINK', $lang['id_lang']));
            $fields['WB_BANNER_IMG_ALT'][$lang['id_lang']] = Tools::getValue('WB_BANNER_IMG_ALT_' . $lang['id_lang'], Configuration::get('WB_BANNER_IMG_ALT', $lang['id_lang']));
            $fields['WB_BANNER_DESC'][$lang['id_lang']] = Tools::getValue('WB_BANNER_DESC_' . $lang['id_lang'], Configuration::get('WB_BANNER_DESC', $lang['id_lang']));
            $fields['WB_BANNER_BUTTON_TEXT'][$lang['id_lang']] = Tools::getValue('WB_BANNER_BUTTON_TEXT_' . $lang['id_lang'], Configuration::get('WB_BANNER_BUTTON_TEXT', $lang['id_lang']));
        }

        return $fields;
    }

    public function renderWidget($hookName, array $params)
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('wb_banner'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $params));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('wb_banner'));
    }

    public function getWidgetVariables($hookName, array $params)
    {
        $imgname = Configuration::get('WB_BANNER_IMG', $this->context->language->id);
        $mobileimgname = Configuration::get('WB_BANNER_MOBILE_IMG', $this->context->language->id);

        if(Context::getContext()->isMobile()) {
            $imgDir = _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $mobileimgname;
            if ($mobileimgname && file_exists($imgDir)) {
                $sizes = getimagesize($imgDir);

                $this->smarty->assign([
                    'banner_img' => $this->context->link->protocol_content . Tools::getMediaServer($mobileimgname) . $this->_path . 'img/' . $mobileimgname,
                    'banner_width' => $sizes[0],
                    'banner_height' => $sizes[1],
                ]);
            }
        } else {
            $imgDir = _PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $imgname;
            if ($imgname && file_exists($imgDir)) {
                $sizes = getimagesize($imgDir);

                $this->smarty->assign([
                    'banner_img' => $this->context->link->protocol_content . Tools::getMediaServer($imgname) . $this->_path . 'img/' . $imgname,
                    'banner_width' => $sizes[0],
                    'banner_height' => $sizes[1],
                ]);
            }
        }
        
        $banner_link = Configuration::get('WB_BANNER_LINK', $this->context->language->id);
        if (!$banner_link) {
            $banner_link = $this->context->link->getPageLink('index');
        }

        return [
            'banner_img_alt' => Configuration::get('WB_BANNER_IMG_ALT', $this->context->language->id),
            'banner_link' => $this->updateUrl($banner_link),
            'banner_desc' => Configuration::get('WB_BANNER_DESC', $this->context->language->id),
            'banner_button_text' => Configuration::get('WB_BANNER_BUTTON_TEXT', $this->context->language->id),
        ];
    }

    private function updateUrl($link)
    {
        if (substr($link, 0, 7) !== 'http://' && substr($link, 0, 8) !== 'https://') {
            $link = 'http://' . $link;
        }

        return $link;
    }
}
