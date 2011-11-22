<?php
/*
* 2007-2011 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 7040 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class BlockStore extends Module
{
	function __construct()
	{
		$this->name = 'blockstore';
		$this->tab = 'front_office_features';
		$this->version = 1.0;
		$this->author = 'PrestaShop';
		$this->need_instance = 0;

		parent::__construct();

		$this->displayName = $this->l('Stores block');
		$this->description = $this->l('Displays a block with a link to the store locator.');
	}

	function install()
	{
		Configuration::updateValue('BLOCKSTORE_IMG', 'store.jpg');
		return (parent::install() AND $this->registerHook('rightColumn') AND $this->registerHook('header'));
	}

	public function uninstall()
	{
		Configuration::deleteByName('BLOCKSTORE_IMG');
		return parent::uninstall();
	}

	function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	function hookRightColumn($params)
	{
		$this->context->smarty->assign('store_img', Configuration::get('BLOCKSTORE_IMG'));
		return $this->display(__FILE__, 'blockstore.tpl');
	}

	function hookHeader($params)
	{
		$this->context->controller->addCSS($this->_path.'blockstore.css', 'all');
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitStoreConf'))
		{
			if (isset($_FILES['store_img']) AND isset($_FILES['store_img']['tmp_name']) AND !empty($_FILES['store_img']['tmp_name']))
			{
				if ($error = checkImage($_FILES['store_img'], 4000000))
					return $this->displayError($this->l('invalid image'));
				else
				{
					if (!move_uploaded_file($_FILES['store_img']['tmp_name'], dirname(__FILE__).'/'.$_FILES['store_img']['name']))
						return $this->displayError($this->l('an error occurred on uploading file'));
					else
					{						
						if (Configuration::get('BLOCKSTORE_IMG') != $_FILES['store_img']['name'])
							if ($this->context->shop->getContextType() != Shop::CONTEXT_SHOP || Configuration::hasContext('BLOCKSTORE_IMG', null, Shop::CONTEXT_SHOP))
								@unlink(dirname(__FILE__).'/'.Configuration::get('BLOCKSTORE_IMG'));
						Configuration::updateValue('BLOCKSTORE_IMG', $_FILES['store_img']['name']);
						return $this->displayConfirmation($this->l('Settings are updated'));
					}
				}
			}
		}
		return '';
	}

	public function getContent()
	{
		$img = false;
		if ($this->context->shop->getContextType() == Shop::CONTEXT_SHOP && file_exists(dirname(__FILE__).'/'.Configuration::get('BLOCKSTORE_IMG')))
			$img = Configuration::get('BLOCKSTORE_IMG');
		if (!$img || ($this->context->shop->getContextType() != Shop::CONTEXT_SHOP && file_exists(dirname(__FILE__).'/'.Configuration::getGlobalValue('BLOCKSTORE_IMG'))));
			$img = Configuration::getGlobalValue('BLOCKSTORE_IMG');
		
		$img_src = ($img ? Tools::getProtocol().Tools::getMediaServer($this->name)._MODULE_DIR_.$this->name.'/'.$img : false);
			
		
		$output = $this->postProcess().'
		<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post" enctype="multipart/form-data">
			<fieldset>
				<legend>'.$this->l('Store block configuration').'</legend>';
		if ($img_src)
			$output .= '<div class="margin-form"><img src="'.$img_src.'" alt="'.$this->l('Store image').'" style="height:115px;margin-left: 100px;width:174px"/></div>';
		$output .= '
				<label for="store_img">'.$this->l('Change image').'</label>
				<div class="margin-form">
					<input id="store_img" type="file" name="store_img" /> ( '.$this->l('image will be displayed as 174x115').' )
				</div>

				<p class="center">
					<input class="button" type="submit" name="submitStoreConf" value="'.$this->l('Save').'"/>
				</p>
			</fieldset>
		</form>
		';
		return $output;
	}
}

