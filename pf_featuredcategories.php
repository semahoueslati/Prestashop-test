<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class Pf_Featuredcategories extends Module
{
    /**
     * @var array
     **/
    private $hooks_list = array(
        'displayHome',
        'header'
    );

    /**
     * @var string
     **/
    private $cp = '';

    /**
     * @var string
     **/
    private $output = '';

    public function __construct()
    {
        $this->name = 'pf_featuredcategories';
        $this->tab = 'front_office_features';
        
        $this->author = 'Presta FABRIQUE';
        $this->version = '3.0.0';
        $this->bootstrap = true;
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->module_key = '002fc538b36af69270571438c32b18f4';
        parent::__construct();
        $this->confirmUninstall = $this->l('Are you sure?');
        $this->displayName = $this->l('Featured Categories Slider');
        $this->description = $this->l('Display a list of selected categories on your homepage');

        $this->cp = Tools::strtoupper($this->name).'_';
    }

    public function install()
    {
        $res = parent::install() && $this->installHooks() && $this->installSql();

        if ($res) {
            if (Shop::isFeatureActive()) {
                $shoplist = Db::getInstance()->executeS("SELECT id_shop, id_shop_group FROM "._DB_PREFIX_."shop");
            } else {
                $shoplist = array(array('id_shop' => null, 'id_shop_group' => null));
            }
            
            foreach ($shoplist as $shop) {
                $is_theme_def = 0;
                $themes_arr = ['classic-rocket'];
                $shop_obj = new Shop($shop['id_shop']);
                if (Shop::isFeatureActive()) {
                    if (in_array($shop_obj->theme->getName(), $themes_arr) ||
                        in_array($shop_obj->theme->get('parent'), $themes_arr)) {
                        $is_theme_def = 1;
                    }
                } else {
                    if (in_array($this->context->shop->theme->getName(), $themes_arr) ||
                        in_array($this->context->shop->theme->get('parent'), $themes_arr)) {
                        $is_theme_def = 1;
                    }
                }
                $this->setConfig('DISPLAY_DESCRIPTION', 0, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('ENABLE_SCROLL', 1, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('XL_SHOW_ITEMS', 4, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('LG_SHOW_ITEMS', 4, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('MD_SHOW_ITEMS', 3, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('SM_SHOW_ITEMS', 2, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('XS_SHOW_ITEMS', 1, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('SCROLL_ITEMS', 1, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('AUTO_SCROLL', 0, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('AUTO_PAUSE', 1, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('SCROLL_SPEED', 200, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('TIMEOUT', 4000, false, $shop['id_shop_group'], $shop['id_shop']);
                $this->setConfig('DEFAULT_SLIDER', $is_theme_def, false, $shop['id_shop_group'], $shop['id_shop']);
            }
        }

        return $res;
    }

    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallHooks() &&  $this->uninstallSql();
    }

    private function sortCategories($categories)
    {
        $id_shop = (int)Context::getContext()->shop->id;
        $res = Db::getInstance()->executeS(
            "SELECT * FROM "._DB_PREFIX_."featuredcategories_order WHERE id_shop=".$id_shop
        );
        $orderedC = array();
        foreach ($res as $r) {
            $orderedC[$r['id_category']] = $r['sort_order'];
        }
        /* if the category doesn't have a sort order, add it to the back of the list */
        $newC = array();
        foreach ($categories as $k => $v) {
            if (array_key_exists($k, $orderedC)) {
                $newC[$orderedC[$k]] = array("id_category"=>$k,"name"=> $v);
            } else {
                $newC[$k+5000] = array("id_category"=>$k,"name"=> $v);
            }
        }
        ksort($newC);
        return $newC;
    }

    public function getContent()
    {
        if ($this->context->shop->theme->getName() !== 'classic-rocket'){
            $this->setConfig('DEFAULT_SLIDER', 0);
        }
        $this->postProcess();

        $classic_rocket = false;
        $themes_arr = ['classic-rocket'];
        if (in_array($this->context->shop->theme->getName(), $themes_arr) ||
            in_array($this->context->shop->theme->get('parent'), $themes_arr)) {
            $custom_text = $this->l('Classic rocket theme was detected as active and option set to YES. Library used: Slick carousel');
            $classic_rocket = true;
        } else {
            $custom_text = $this->l('Using own JS library: jCarouselLite');
        }

        $fields_form1 = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Use theme\'s default slider JS'),
                        'name' => $this->cp.'DEFAULT_SLIDER',
                        'values' => array(
                            array(
                                'id' => 'DEFAULT_SLIDER_ON',
                                'value' => 1,
                                'label' => $this->l('Yes') ),
                            array(
                                'id' => 'DEFAULT_SLIDER_OFF',
                                'value' => 0,
                                'label' => $this->l('No') ),
                        ),
                        'desc' => $custom_text
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable horizontal scroller?'),
                        'name' => $this->cp.'ENABLE_SCROLL',
                        'values' => array(
                            array(
                                'id' => 'ENABLE_SCROLL_ON',
                                'value' => 1,
                                'label' => $this->l('Yes') ),
                            array(
                                'id' => 'ENABLE_SCROLL_OFF',
                                'value' => 0,
                                'label' => $this->l('No') ),
                        ),
                        'desc' => $this->l('Check if you would like to show the categories in a scroller and not as grid')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Display category description?'),
                        'name' => $this->cp.'DISPLAY_DESCRIPTION',
                        'values' => array(
                            array(
                                'id' => 'DISPLAY_DESCRIPTION_ON',
                                'value' => 1,
                                'label' => $this->l('Yes') ),
                            array(
                                'id' => 'DISPLAY_DESCRIPTION_OFF',
                                'value' => 0,
                                'label' => $this->l('No') ),
                        ),
                        'desc' => $this->l('Display the category description below the image and title?')
                    ),
                    array(
                        'type' => 'html',
                        'label' => $this->l('Number of items to show'),
                        'col' => 6,
                        'name' => $this->cp.'XL_SHOW_ITEMS',
                        'html_content' => 
                        '<div class="col-md-2">
                            <label for="' . $this->cp.'XL_SHOW_ITEMS' . '">' . $this->l('Extra large devices') . '</label>
                            <input id="' . $this->cp.'XL_SHOW_ITEMS' . '" type="text" name="' . $this->cp.'XL_SHOW_ITEMS' . '" value="' . $this->getConfig('XL_SHOW_ITEMS') . '">
							<span>(large desktops, 1200px and up)</span>
                        </div>
                        <div class="col-md-2">
                            <label for="' . $this->cp.'LG_SHOW_ITEMS' . '">' . $this->l('Large devices') . '</label>
                            <input id="' . $this->cp.'LG_SHOW_ITEMS' . '" type="text" name="' . $this->cp.'LG_SHOW_ITEMS' . '" value="' . $this->getConfig('LG_SHOW_ITEMS') . '">
							<span>(desktops, 992px and up)</span>
                        </div>
                        <div class="col-md-2">
                            <label for="' . $this->cp.'MD_SHOW_ITEMS' . '">' . $this->l('Medium devices') . '</label>
                            <input id="' . $this->cp.'MD_SHOW_ITEMS' . '" type="text" name="' . $this->cp.'MD_SHOW_ITEMS' . '" value="' . $this->getConfig('MD_SHOW_ITEMS') . '">
							<span>(tablets, 768px and up)</span>
                        </div>
                        <div class="col-md-2">
                            <label for="' . $this->cp.'SM_SHOW_ITEMS' . '">' . $this->l('Small devices') . '</label>
                            <input id="' . $this->cp.'SM_SHOW_ITEMS' . '" type="text" name="' . $this->cp.'SM_SHOW_ITEMS' . '" value="' . $this->getConfig('SM_SHOW_ITEMS') . '">
							<span>(landscape phones, 576px and up)</span>
                        </div>
                        <div class="col-md-2">
                            <label for="' . $this->cp.'XS_SHOW_ITEMS' . '">' . $this->l('Extra small devices') . '</label>
                            <input id="' . $this->cp.'XS_SHOW_ITEMS' . '" type="text" name="' . $this->cp.'XS_SHOW_ITEMS' . '" value="' . $this->getConfig('XS_SHOW_ITEMS') . '">
							<span>(portrait phones, less than 576px)</span>
                        </div>
                        <p class="help-block col-xs-12">'.$this->l('Enter the number of items to show at one time in the slider depending on the resolution').'</p>
                        '
                        ,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Number of items to scroll'),
                        'name' => $this->cp.'SCROLL_ITEMS',
                        'col' => 6,
                        'desc' => $this->l('Enter the number of items you want to scroll at one time (recommended 1 or the number of showed items)')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Auto scroll'),
                        'name' => $this->cp.'AUTO_SCROLL',
                        'values' => array(
                            array(
                                'id' => 'AUTO_SCROLL_ON',
                                'value' => 1,
                                'label' => $this->l('Yes') ),
                            array(
                                'id' => 'AUTO_SCROLL_OFF',
                                'value' => 0,
                                'label' => $this->l('No') ),
                        ),
                        'desc' => $this->l('Select true to automatically scroll the logos.')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Pause on hover'),
                        'name' => $this->cp.'AUTO_PAUSE',
                        'values' => array(
                            array(
                                'id' => 'AUTO_PAUSE_ON',
                                'value' => 1,
                                'label' => $this->l('Yes') ),
                            array(
                                'id' => 'AUTO_PAUSE_OFF',
                                'value' => 0,
                                'label' => $this->l('No') ),
                        ),
                        'desc' => $this->l('Select true to automatically pause the scrolling on mouse over.')
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Speed / Duration (ms)'),
                        'id' => $this->cp.'SCROLL_SPEED',
                        'name' => $this->cp.'SCROLL_SPEED',
                        'desc' => $this->l('Select the scrolling / transition speed (ms)'),
                        'options' => array(
                            'query' => array(
                                array('id' => 100, 'name' => 100),
                                array('id' => 200, 'name' => 200),
                                array('id' => 500, 'name' => 500),
                                array('id' => 700, 'name' => 700),
                                array('id' => 1000, 'name' => 1000)
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        ),
                        'identifier' => 'id',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Timeout (ms)'),
                        'id' => $this->cp.'TIMEOUT',
                        'name' => $this->cp.'TIMEOUT',
                        'desc' => $this->l('Select the time period between scrolls (ms)'),
                        'options' => array(
                            'query' => array(
                                array('id' => 2000, 'name' => 2000),
                                array('id' => 4000, 'name' => 4000),
                                array('id' => 5000, 'name' => 5000),
                                array('id' => 7000, 'name' => 7000),
                                array('id' => 10000, 'name' => 10000)
                            ),
                            'id' => 'id',
                            'name' => 'name'
                        ),
                        'identifier' => 'id',
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'name' => 'submitSliderSettings'
                )
            )
        );
        if (!$classic_rocket) {
            unset($fields_form1['form']['input'][0]);
        }
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?
                Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMainSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
                '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $this->output . $this->renderList() . $helper->generateForm(array($fields_form1));
    }

    public function getConfigFieldsValues()
    {
        $configs = array(
            $this->cp.'DISPLAY_DESCRIPTION',
            $this->cp.'ENABLE_SCROLL',
            $this->cp.'XL_SHOW_ITEMS',
            $this->cp.'LG_SHOW_ITEMS',
            $this->cp.'MD_SHOW_ITEMS',
            $this->cp.'SM_SHOW_ITEMS',
            $this->cp.'XS_SHOW_ITEMS',
            $this->cp.'SCROLL_ITEMS',
            $this->cp.'AUTO_SCROLL',
            $this->cp.'SCROLL_SPEED',
            $this->cp.'TIMEOUT',
            $this->cp.'AUTO_PAUSE',
            $this->cp.'DEFAULT_SLIDER',
        );
        return Configuration::getMultiple($configs);
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submitSliderSettings')) {
            foreach (Tools::getAllValues() as $key => $value) {
                if (strpos($key, $this->cp) === 0) {
                    $this->setConfig(str_replace($this->cp, '', $key), $value);
                }
            }
            $this->output .= $this->displayConfirmation($this->l('Settings successfully saved.'));
        } else {
            $id_shop = (int)Context::getContext()->shop->id;

            if (Tools::isSubmit('submitFeaturedCategories')) {
                if (!empty(Tools::getValue('fcBox', array()))) {
                    Db::getInstance()->execute(
                        "DELETE FROM "._DB_PREFIX_."featuredcategories WHERE id_shop=" . $id_shop
                    );
                    foreach (Tools::getValue('fcBox', array()) as $check) {
                        Db::getInstance()->execute(
                            'INSERT INTO '._DB_PREFIX_.'featuredcategories (id_shop, id_category) '.
                            'VALUES ('.$id_shop.', '.$check.')'
                        );
                    }
                }
            }
        }
    }

    public function renderList()
    {
        $this->context->controller->addJS(_PS_ROOT_DIR_ . 'js/jquery/plugins/jquery.tablednd.js');
        $this->context->controller->addJS($this->_path . 'views/js/back.js', false);

        $tpl = $this->context->smarty->createTemplate(
            'module:' . $this->name . '/views/templates/admin/_configure/categories_list.tpl',
            $this->context->smarty
        );

        $categories = Category::getCategories($this->context->language->id, false);

        /* get the selected (active) categories for this module */
        $categ_res = Db::getInstance()->executeS(
            "SELECT id_category FROM " . _DB_PREFIX_ . "featuredcategories 
            WHERE id_shop=" . (int)$this->context->shop->id
        );
        $active_categories = array();
        foreach ($categ_res as $cr) {
            $active_categories[] = $cr["id_category"];
        }

        /* create a simpler categories array */
        $newCategories = array();
        foreach ($categories as $ca) {
            foreach ($ca as $c) {
                $id_category = $c['infos']['id_category'];
                $newCategories[$id_category] = $c['infos']['name'];
            }
        }

        /* get the order of the selected categories.  */
        /* newly added categories, that were not yet sorted, will be displayed last. */
        $orderedCategories = $this->sortCategories($newCategories);

        /* get the selected (active) categories for this module */
        $categ_res = Db::getInstance()->executeS(
            "SELECT id_category FROM "._DB_PREFIX_."featuredcategories WHERE id_shop=" . $this->context->shop->id
        );
        $active_categories = array();
        foreach ($categ_res as $cr) {
            $active_categories[] = $cr["id_category"];
        }

        $tpl->assign(array(
            'currentShop' => $this->context->shop,
            'orderedCategories' => $orderedCategories,
            'active_categories' => $active_categories
        ));
        return $tpl->fetch();
    }

    public function hookDisplayHome($params)
    {
        /* if there is no order set, order by id. */
        $categ_res = (Db::getInstance()->executeS("SELECT id_category FROM "._DB_PREFIX_."featuredcategories_order"))
            ? Db::getInstance()->executeS(
                "SELECT
                    f.id_category
                FROM
                    "._DB_PREFIX_."featuredcategories f
                LEFT JOIN "._DB_PREFIX_."featuredcategories_order fo ON (f.id_category = fo.id_category)
                WHERE  f.id_category = fo.id_category ORDER BY fo.sort_order"
            )
            : Db::getInstance()->executeS("SELECT id_category FROM "._DB_PREFIX_."featuredcategories");
                              
        $active_categories = array();
        foreach ($categ_res as $cr) {
            $active_categories[] = $cr["id_category"];
        }

        $categories = array();

        foreach ($active_categories as $cat_id) {
            $categories[] = new Category($cat_id, $this->context->language->id);
        }

        $this->context->smarty->assign(array(
            'categories' => $categories,
            'xl_show_items' => (int)$this->getConfig('XL_SHOW_ITEMS'),
            'lg_show_items' => (int)$this->getConfig('LG_SHOW_ITEMS'),
            'md_show_items' => (int)$this->getConfig('MD_SHOW_ITEMS'),
            'sm_show_items' => (int)$this->getConfig('SM_SHOW_ITEMS'),
            'xs_show_items' => (int)$this->getConfig('XS_SHOW_ITEMS'),
            'enable_scroll' => $this->getConfig('ENABLE_SCROLL'),
            'display_description' => $this->getConfig('DISPLAY_DESCRIPTION'),
        ));

        return $this->fetch('module:' . $this->name . '/views/templates/hook/pf_featuredcategories.tpl');
    }

    public function hookHeader()
    {
        if (!$this->getConfig('DEFAULT_SLIDER') && $this->getConfig('ENABLE_SCROLL')) {
            $this->context->controller->addJS(array(
                $this->_path . 'views/js/slick.min.js'
            ));
            $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        }

        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        Media::addJsDef(array($this->cp . 'SLIDER_NAME' => $this->getSeliderName()));

        Media::addJsDef(array_map(
            function ($var) {
                return is_numeric($var) ? (int)$var : $var;
            },
            $this->getConfigFieldsValues()
        ));
    }

    /**
     * @return string
     */
    private function getSeliderName()
    {
        $themes_arr = ['classic-rocket'];
        if (in_array($this->context->shop->theme->getName(), $themes_arr) ||
            in_array($this->context->shop->theme->get('parent'), $themes_arr)) {
            return 'Slick';
        }

        return 'default';
    }

    /**
     *  @return bool
     */
    private function installSql()
    {
        if (!class_exists('SqlInstaller')) {
            require_once dirname(__FILE__).'/sql/install-sql.php';
        }
        $sql = new SqlInstaller;
        if (!$sql->install()) {
            $this->_errors = $sql->getErrors();
            return false;
        }
        return true;
    }
    /**
     *  @return bool
     */
    private function uninstallSql()
    {
        // return true; // for debug only
        if (!class_exists('SqlInstaller')) {
            require_once dirname(__FILE__).'/sql/install-sql.php';
        }
        $sql = new SqlInstaller;
        if (!$sql->uninstall()) {
            $this->_errors = $sql->getErrors();
            return false;
        }
        return true;
    }
    /**
     *  @return bool
     */
    private function installHooks()
    {
        $return = true;
        foreach ($this->hooks_list as $hook) {
            $return &= $this->registerHook($hook);
        }
        if (!$return) {
            $this->_errors[] = $this->l('Hooks can not be installed');
        }
        return (bool)$return;
    }
    /**
     *  @return bool
     */
    private function uninstallHooks()
    {
        $return = true;
        foreach ($this->hooks_list as $hook) {
            $return &= $this->unregisterHook($hook);
        }
        if (!$return) {
            $this->_errors[] = $this->l('Hooks can not be uninstalled');
        }
        return (bool)$return;
    }

    /**
     * Alias for Configuration::get() with prefix
     * @param string key
     * @param int id_lang
     * @param int id_shop_group
     * @param int id_shop
     * @param string html
     * @return mixed
     */
    public function getConfig($key, $id_lang = null, $id_shop_group = null, $id_shop = null, $default = false)
    {
        return Configuration::get($this->cp.$key, $id_lang, $id_shop_group, $id_shop, $default);
    }

    public function getConfigGlobal($key, $idLang = null)
    {
        return Configuration::getGlobalValue($this->cp.$key, $idLang);
    }

    /**
     * Alias for Configuration::updateValue() with prefix
     * @param string key
     * @param mixed values
     * @param bool html
     * @param int id_shop_group
     * @param int id_shop
     * @return bool
     */
    public function setConfig($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    {
        return Configuration::updateValue($this->cp.$key, $values, $html, $id_shop_group, $id_shop);
    }

    public function setConfigGlobal($name, $value, $html = false)
    {
        return Configuration::updateGlobalValue($this->cp.$name, $value, $html);
    }

    /**
     * Alias for Configuration::deleteByName() with prefix
     * @param string key
     * @return bool
     */
    public function deleteConfig($key)
    {
        return Configuration::deleteByName($this->cp.$key);
    }

    /**
    * Alias for Tools::getValue with Prefix
    * @param string key
    * @param string default_value
    * @return bool
    */
    public function getValue($key, $default_value = false)
    {
        return Tools::getValue($this->cp.$key, $default_value);
    }

    public function getCp()
    {
        return $this->cp;
    }
}
