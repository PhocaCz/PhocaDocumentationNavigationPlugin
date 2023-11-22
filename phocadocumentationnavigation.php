<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @extension Phoca Extension
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * {phocadocumentation view=navigation|type=mpcn}
 * {phocadocumentation view=navigation|type=ptn|top=site}
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );


class plgContentPhocaDocumentationNavigation extends CMSPlugin
{

	public function __construct(& $subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onContentPrepare($context, &$article, &$params, $page = 0) {

		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}

		$document				= Factory::getDocument();
		$component 				= 'com_phocadocumentation';

		CMSPlugin::loadLanguage( 'plg_content_phocadocumentationnavigation' );
		require_once( JPATH_ROOT.'/plugins/content/phocadocumentationnavigation/helpers/phocadocumentationnavigation.php' );


		//HTMLHelper::_('bootstrap.tooltip', '.hasTooltip', array('placement' => 'bottom'));
		HTMLHelper::_('bootstrap.popover', '.hasPopover', array('placement' => 'top'));

		$pdoc       	= new stdClass();
		$pdoc->svg_path = HTMLHelper::image('com_phocadocumentation/svg-definitions.svg', '', [], true, 1);
		$wa         	= $document->getWebAssetManager();
		$wa->registerAndUseStyle('com_phocadocumentation.main', 'media/com_phocadocumentation/css/main.css', array('version' => 'auto'));
		$wa->registerAndUseScript('plg_phocadocumentationnavigation.main', 'media/plg_content_phocadocumentationnavigation/js/main.js', array('version' => 'auto'));



		// Start Plugin
		$regex_one		= '/({phocadocumentation\s*)(.*?)(})/si';
		$regex_all		= '/{phocadocumentation\s*.*?}/si';
		$matches 		= array();
		$count_matches	= preg_match_all($regex_all,$article->text,$matches,PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
		$customCSS		= '';
		$customCSS2		= '';

		for($i = 0; $i < $count_matches; $i++) {

			// Plugin variables
			$view 		= '';
			$type		= '';
			$topid		= '';

			// Get plugin parameters
			$phocadocumentation	= $matches[0][$i][0];
			preg_match($regex_one,$phocadocumentation,$phocadocumentation_parts);
			$parts			= explode("|", $phocadocumentation_parts[2]);
			$values_replace = array ("/^'/", "/'$/", "/^&#39;/", "/&#39;$/", "/<br \/>/");

			foreach($parts as $key => $value) {
				$values = explode("=", $value, 2);

				foreach ($values_replace as $key2 => $values2) {
					$values = preg_replace($values2, '', $values);
				}

				// Get plugin parameters from article
				if($values[0]=='view') 	{ $view = $values[1];}
				if($values[0]=='type') 	{ $type = $values[1];}
				if($values[0]=='top') 	{ $topid = $values[1];}

			}

			$output  = '';
			$output .= '<div class="plg-phocadocumentationnavigation pdoc-nav-box">' . "\n";


			// -------------------------
			// NAVIGATION
			// -------------------------
			if ($view == 'navigation') {


				$d = PhocaDocumentationNavigation::getDocuments();
				$paramsNav = [];
				$paramsNav['svg_path'] = HTMLHelper::image('com_phocadocumentation/svg-definitions.svg', '', [], true, 1);
				$prevOutput = PhocaDocumentationNavigation::getPrevOutput($d['prev'], $paramsNav);
				$nextOutput = PhocaDocumentationNavigation::getNextOutput($d['next'], $paramsNav);
				$topOutput 	= PhocaDocumentationNavigation::getTopOutput($d['doc'], $paramsNav, $topid);
				$listOutput = PhocaDocumentationNavigation::getListOutput($d['list'], $d['doc'], $paramsNav);

				//Possible Hardcode
				//$header		= JText::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_NAVIGATION');
				//$rTop 		= PhocaDocumentationNavigation::renderTop($prevOutput, $nextOutput, $listOutput, $header);
				//$rBottom		= PHocadocumentationNavigation::renderBottom($prevOutput, $nextOutput, $topOutput);
				// -------------------------------------------

				$main 		= false;
				$prev 		= false;
				$next 		= false;
				$top 		= false;
				$content 	= false;
				$main 		= preg_match("/m/i", $type);
				$prev 		= preg_match("/p/i", $type);
				$next 		= preg_match("/n/i", $type);
				$top 		= preg_match("/t/i", $type);
				$content	= preg_match("/c/i", $type);
				$sep		= ' <div class="pdoc-nav-sep">&bull;</div> ';
				$sepPrev	= 0;

				if ($main) {
					$output .= '<div class="navigation-text" id="pdoc-top"><h5>'.Text::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_NAVIGATION') . '</h5>'."\n";

				} else {
					$output .= '<div class="navigation-text" >'."\n";
				}
				if ($prev) {
					$output .= $prevOutput;
					$sepPrev = 1;
				}
				if ($content) {
					if ($sepPrev == 1) {
						$output .= $sep;
					}
					$output .= $listOutput;
					$sepPrev = 1;
				}
				if ($top) {
					if ($sepPrev == 1) {
						$output .= $sep;
					}
					$output .= $topOutput;
					$sepPrev = 1;
				}
				if ($next) {
					if ($sepPrev == 1) {
						$output .= $sep;
					}
					$output .= $nextOutput;
					$sepPrev = 1;
				}

			}

			$output .= '</div></div>';


			$article->text = preg_replace($regex_all, $output, $article->text, 1);
		}
		return true;
	}
}
?>
