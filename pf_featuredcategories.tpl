{*
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2020 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{assign var='totalcats' value=$categories|count}

<section id="featured_categories" class="featured-categories clearfix">
  <h3 class="h3 section-title text-uppercase">{l s='Nos Categories' mod='pf_featuredcategories'}</h3>
  </br>
  </br>
    <a class="category-carousel-nav prev"><i class="material-icons">navigate_before</i></a>
	<style>
	h3{
		text-align: center;
	}
table, th, td {
}
th, td,tr {
  padding: 45px;
text-align: center;
padding-top: 0px;

}
</style>
	<table>
<tbody>


<tr>
<td><a href="http://localhost/prestashoptest/4-hommes"><img src="https://i.ibb.co/z5MtGjd/accessoires-1.png" alt="accessoires-1"   width="100" height="100"></a></td>
<td><a href="http://localhost/prestashoptest/5-femmes"><img src="https://i.ibb.co/7WnRZRj/accessoire-1.png" alt="accessoires-1" border="0"   width="100" height="100"></a></td>
<td><a href="http://localhost/prestashoptest/6-accessoires"><img src="https://i.ibb.co/yysKfLc/accessoire.png" alt="accessoires-1" border="0"   width="100" height="100"></a></td>
<td><a href="http://localhost/prestashoptest/7-papeterie"><img src="https://i.ibb.co/WH2YT23/vetements-propres.png" alt="accessoires-1" border="0"   width="100" height="100"></a></td>
<td><a href="http://localhost/prestashoptest/8-accessoires-de-maison"><img src="https://i.ibb.co/J53V9mM/t-shirt.png" alt="accessoires-1" border="0"   width="100" height="100"></a></td>
<td><a href="http://localhost/prestashoptest/9-art"><img src="https://i.ibb.co/z5MtGjd/accessoires-1.png" alt="accessoires-1" border="0"   width="100" height="100"></a></td>

</tr>
<tr>
<td>Homme</td>
<td>Femme</td>
<td>Accessoire</td>
<td>Papeterie</td>
<td>Accessoire de maison</td>
<td>Art</td>
</tr>
</tbody>
</table>
<!-- DivTable.com -->
    <ul class="clearfix">
	    {foreach from=$categories item=category}
	        <li>
	            <a href="{$link->getCategoryLink($category)|escape:'html':'UTF-8'}" title="{$category->name|escape:'htmlall':'UTF-8'}"><img src="{$link->getCatImageLink($category->link_rewrite, $category->id, 'category_default')|escape:'html':'UTF-8'}" /></a>
				<h3 class="h3"><a href="{$link->getCategoryLink($category)|escape:'html':'UTF-8'}" title="{$category->name|escape:'htmlall':'UTF-8'}">{$category->name|escape:'htmlall':'UTF-8'}</a></h3>
	            {if $display_description == '1'}<div class="category-description">{$category->description|strip_tags:'UTF-8'|truncate:120:'...'|escape:'html':'UTF-8'}</div>{/if}
	        </li>
	    {/foreach}
    </ul>
    <a class="category-carousel-nav next"><i class="material-icons">navigate_next</i></a>
</section>