<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use LouisAuthie\Askdialog\Service\AskDialogClient;
use LouisAuthie\Askdialog\Service\DataGenerator;

class AskDialog extends Module
{
    public function __construct()
    {
        $this->name = 'askdialog';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'AskDialog';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('Ask Dialog', [], 'Modules.AskDialog.Admin');
        $this->description =  $this->trans('Module to provide the AskDialog assistant on your e-shop', [], 'Modules.AskDialog.Admin');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
    }

    public function install()
    {
        return parent::install() && $this->registerHook('displayHeader') && $this->registerHook('displayFooterAfter') && $this->registerHook('displayProductAdditionalInfo');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookDisplayHeader($params)
    {
        if ($this->context->controller->php_self == 'product') {
            $this->context->controller->addCSS($this->_path . 'views/css/cssForProductPage.css', 'all');
        }
        $this->context->controller->addCSS($this->_path . 'views/css/cssForAllPages.css', 'all');
       
    }

    public function hookDisplayProductAdditionalInfo($params)
    {

        if($this->context->controller->php_self != 'product'){
            return;
        }
        $product = $params['product'];

        $this->context->smarty->assign('product', $product);

        $product_id = $product['id_product'];
        $product_title = $product['name'];
        $product_slug = $product['link_rewrite'];
        $selected_variant_id = $product['id_product_attribute'];
        $assistant_name = $this->trans('AskDialog Assistant', [], 'Modules.AskDialog.Admin');
        $assistant_description = $this->trans('How can I help you with this product?', [], 'Modules.AskDialog.Admin');
        $ask_anything_placeholder = $this->trans('Ask anything about this product...', [], 'Modules.AskDialog.Admin');

        $this->context->smarty->assign([
            'product_id' => $product_id,
            'product_title' => $product_title,
            'product_slug' => $product_slug,
            'selected_variant_id' => $selected_variant_id,
            'assistant_name' => $assistant_name,
            'assistant_description' => $assistant_description,
            'ask_anything_placeholder' => $ask_anything_placeholder,
            'enableProductQuestion' => Configuration::get('PS_ENABLE_PRODUCT_QUESTION'),
            'defaultDesign' => Configuration::get('PS_DEFAULT_DESIGN'),
            'my_array' => ['suggestion-0', 'suggestion-1']
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayproduct.tpl');
    }

    public function hookDisplayFooterAfter($params)
    {
        //Include view 
        $this->context->smarty->assign('module_dir', $this->_path);
        $customer = $this->context->customer;
        $customerId = $customer->isLogged() ? $customer->id : 'anonymous';
        $this->context->smarty->assign('customer_id', $customerId);
        $publicApiKey = Configuration::get('ASKDIALOG_API_KEY');
        $countryCode = $this->context->country->iso_code;
        $languageCode = $this->context->language->iso_code;
        $languageName = $this->context->language->name;
        $primaryColor = Configuration::get('PS_COLOR_PRIMARY');
        $backgroundColor = Configuration::get('PS_COLOR_BACKGROUND');
        $ctaTextColor = Configuration::get('PS_COLOR_CTA_TEXT');
        $ctaBorderType = Configuration::get('PS_CTA_BORDER_TYPE');
        $capitalizeCtas = Configuration::get('PS_CAPITALIZE_CTAS');
        $fontFamily = Configuration::get('PS_FONT_FAMILY');
        $highlightProductName = Configuration::get('PS_HIGHLIGHT_PRODUCT_NAME');
        $dataJsSrc = Configuration::get('PS_DATA_JS_SRC');

        $this->context->smarty->assign([
            'public_api_key' => $publicApiKey,
            'country_code' => $countryCode,
            'language_code' => $languageCode,
            'language_name' => $languageName,
            'primary_color' => $primaryColor,
            'background_color' => $backgroundColor,
            'cta_text_color' => $ctaTextColor,
            'cta_border_type' => $ctaBorderType,
            'capitalize_ctas' => $capitalizeCtas,
            'font_family' => $fontFamily,
            'highlight_product_name' => $highlightProductName,
            'data_js_src' => $dataJsSrc,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/displayfooterafter.tpl');

    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $apiKey = strval(Tools::getValue('ASKDIALOG_API_KEY'));
            if (!$apiKey || empty($apiKey)) {
                $output .= $this->displayError($this->trans('Invalid API Key', [], 'Modules.AskDialog.Admin'));
            } else {
                Configuration::updateValue('ASKDIALOG_API_KEY', $apiKey);
                $output .= $this->displayConfirmation($this->trans('Settings updated', [], 'Modules.AskDialog.Admin'));
                $apiClient = new AskDialogClient($apiKey);
                $result = $apiClient->sendDomainHost();
            }
        }

        $dataGenerator = new DataGenerator();
        $dataGenerator->getCatalogData();

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Modules.AskDialog.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('API Key', [], 'Modules.AskDialog.Admin'),
                        'name' => 'ASKDIALOG_API_KEY',
                        'size' => 20,
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    protected function getConfigFormValues()
    {
        return [
            'ASKDIALOG_API_KEY' => Configuration::get('ASKDIALOG_API_KEY', ''),
        ];
    }
}