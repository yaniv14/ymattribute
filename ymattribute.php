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
            'YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS',
            '.product_attributes'
        );
        Configuration::updateValue(
            'YMATTRIBUTE_ATTRIBUTE_CLASS',
            '.attribute_list'
        );
        Configuration::updateValue(
            'YMATTRIBUTE_OOS_TEXT',
            $this->l('Out of stock')
        );
        Configuration::updateValue('YMATTRIBUTE_REMOVE_DEFAULT', false);

        return parent::install() && $this->registerHook('header')
            && $this->registerHook('displayFooterProduct');
    }

    public function uninstall()
    {
        Configuration::deleteByName('YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS');
        Configuration::deleteByName('YMATTRIBUTE_ATTRIBUTE_CLASS');
        Configuration::deleteByName('YMATTRIBUTE_OOS_TEXT');
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
            'YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS',
            Tools::getValue('YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS')
        );
        Configuration::updateValue(
            'YMATTRIBUTE_ATTRIBUTE_CLASS',
            Tools::getValue('YMATTRIBUTE_ATTRIBUTE_CLASS')
        );
        Configuration::updateValue(
            'YMATTRIBUTE_OOS_TEXT',
            Tools::getValue('YMATTRIBUTE_OOS_TEXT')
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
                        'label' => $this->l('Product attributes class'),
                        'name'  => 'YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS',
                        'desc'  => $this->l(
                            'Enter the class for product_attributes.'
                        ),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Attribute list class'),
                        'name'  => 'YMATTRIBUTE_ATTRIBUTE_CLASS',
                        'desc'  => $this->l(
                            'Enter the class for attribute_list.'
                        ),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Out of stock text'),
                        'name'  => 'YMATTRIBUTE_OOS_TEXT',
                        'desc'  => $this->l(
                            'Enter the text to display when attribute out of stock.'
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
        $fields['YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS'] = Tools::getValue(
            'YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS',
            Configuration::get('YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS')
        );
        $fields['YMATTRIBUTE_ATTRIBUTE_CLASS'] = Tools::getValue(
            'YMATTRIBUTE_ATTRIBUTE_CLASS',
            Configuration::get('YMATTRIBUTE_ATTRIBUTE_CLASS')
        );
        $fields['YMATTRIBUTE_REMOVE_DEFAULT'] = Tools::getValue(
            'YMATTRIBUTE_REMOVE_DEFAULT',
            Configuration::get('YMATTRIBUTE_REMOVE_DEFAULT')
        );
        $fields['YMATTRIBUTE_OOS_TEXT'] = Tools::getValue(
            'YMATTRIBUTE_OOS_TEXT',
            Configuration::get('YMATTRIBUTE_OOS_TEXT')
        );

        return $fields;
    }

    public function hookDisplayFooterProduct($params)
    {
        $this->smarty->assign(
            [
                'availableCombinations'  => $this->availableCombinations(
                    $params['product']
                ),
                'productAttributesClass' => Configuration::get(
                    'YMATTRIBUTE_PRODUCT_ATTRIBUTES_CLASS'
                ),
                'AttributeListClass'     => Configuration::get(
                    'YMATTRIBUTE_ATTRIBUTE_CLASS'
                ),
                'outOfStockText'         => Configuration::get(
                    'YMATTRIBUTE_OOS_TEXT'
                ),
            ]
        );

        return $this->display(__FILE__, 'views/templates/front/ymattribute.tpl');
    }

    public function availableCombinations($product)
    {
        $groups = [];
        $availableCombinations = [];

        $attributesGroups = $product->getAttributesGroups(
            $this->context->language->id
        );
        if (is_array($attributesGroups) && $attributesGroups) {
            foreach ($attributesGroups as $k => $row) {
                if ( ! isset($groups[$row['id_attribute_group']])) {
                    $groups[$row['id_attribute_group']] = [
                        'group_name' => $row['group_name'],
                        'name'       => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default'    => -1,
                    ];
                }

                $groups[$row['id_attribute_group']]['attributes'][$row['id_attribute']]
                    = $row['attribute_name'];
                if ($row['default_on']
                    && $groups[$row['id_attribute_group']]['default'] == -1
                ) {
                    $groups[$row['id_attribute_group']]['default']
                        = (int)$row['id_attribute'];
                }
                if ( ! isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']])) {
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']]
                        = 0;
                }
                $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] += (int)$row['quantity'];

                $combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']]
                    = $row['attribute_name'];
                $combinations[$row['id_product_attribute']]['attributes'][]
                    = (int)$row['id_attribute'];
                $combinations[$row['id_product_attribute']]['price']
                    = (float)Tools::convertPriceFull(
                    $row['price'],
                    null,
                    $this->context->currency,
                    false
                );
                $combinations[$row['id_product_attribute']]['quantity']
                    = (int)$row['quantity'];

            }

            if ( ! Product::isAvailableWhenOutOfStock($product->out_of_stock)
                && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0
            ) {
                foreach ($groups as &$group) {
                    foreach (
                        $group['attributes_quantity'] as $key => &$quantity
                    ) {
                        if ($quantity <= 0) {
                            unset($group['attributes'][$key]);
                        }
                    }
                }
            }
            if (isset($combinations)) {
                foreach ($combinations as $idProductAttribute => $comb) {
                    if ($combinations[$idProductAttribute]['quantity'] > 0) {
                        $availableCombinations[$idProductAttribute]
                            = array_combine(
                            array_keys(
                                $combinations[$idProductAttribute]['attributes_values']
                            ),
                            array_values(
                                array_map(
                                    'strval',
                                    $combinations[$idProductAttribute]['attributes']
                                )
                            )
                        );
                    }
                }
            }
        }

        return $availableCombinations;
    }

}
