<?php

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class YmAttribute extends Module
{
    public function __construct()
    {
        $this->name = 'ymattribute';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Yaniv Mirel';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('YM Attribute');
        $this->description = $this->l(
            'Disable out of stock attribute & allow empty attribute for product'
        );
        $this->tb_versions_compliancy = '>= 1.0.0';
    }

    public function install()
    {
        Configuration::updateValue(
            'YMATTRIBUTE_ATTRIBUTE_CLASS',
            '.attribute_list'
        );
        Configuration::updateValue('YMATTRIBUTE_REMOVE_DEFAULT', false);

        return parent::install() && $this->registerHook('header')
            && $this->registerHook('displayFooterProduct');
    }

    public function uninstall()
    {
        Configuration::deleteByName('YMATTRIBUTE_ATTRIBUTE_CLASS');
        Configuration::deleteByName('YMATTRIBUTE_REMOVE_DEFAULT');

        return parent::uninstall();
    }

    public function hookDisplayHeader()
    {
        if (isset($this->context->controller->php_self)
            && $this->context->controller->php_self == 'product'
        ) {
            $this->context->controller->addCSS(
                $this->_path.'/views/css/ymattribute.css',
                'all'
            );
            $this->context->controller->addJS(
                $this->_path.'/views/js/ymattribute.js'
            );
        }
    }

    public function getContent()
    {
        if (Tools::isSubmit('submitYmattributeModule')) {
            $this->postProcess();
        }

        return $this->renderForm();
    }

    public function postProcess()
    {

        Configuration::updateValue(
            'YMATTRIBUTE_ATTRIBUTE_CLASS',
            Tools::getValue('YMATTRIBUTE_ATTRIBUTE_CLASS')
        );
        Configuration::updateValue(
            'YMATTRIBUTE_REMOVE_DEFAULT',
            Tools::getValue('YMATTRIBUTE_REMOVE_DEFAULT')
        );

    }

    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Attribute list class'),
                        'name'  => 'YMATTRIBUTE_ATTRIBUTE_CLASS',
                        'desc'  => $this->l(
                            'Enter the class for attribute_list.'
                        ),
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Remove default'),
                        'name'    => 'YMATTRIBUTE_REMOVE_DEFAULT',
                        'is_bool' => true,
                        'desc'    => $this->l(
                            'Remove default combination at page load'
                        ),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language(
            (int)Configuration::get('PS_LANG_DEFAULT')
        );
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get(
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG'
        ) ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitYmattributeModule';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ).'&configure='.$this->name.'&tab_module='.$this->tab
            .'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite(
            'AdminModules'
        );
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    public function getConfigFieldsValues()
    {
        $fields = [];

        $fields['YMATTRIBUTE_ATTRIBUTE_CLASS'] = Tools::getValue(
            'YMATTRIBUTE_ATTRIBUTE_CLASS',
            Configuration::get('YMATTRIBUTE_ATTRIBUTE_CLASS')
        );
        $fields['YMATTRIBUTE_REMOVE_DEFAULT'] = Tools::getValue(
            'YMATTRIBUTE_REMOVE_DEFAULT',
            Configuration::get('YMATTRIBUTE_REMOVE_DEFAULT')
        );

        return $fields;
    }

    public function hookDisplayFooterProduct()
    {

    }

}
