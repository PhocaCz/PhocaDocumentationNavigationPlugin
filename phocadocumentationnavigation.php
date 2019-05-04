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
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );


class plgContentPhocaDocumentationNavigation extends JPlugin
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

		if (!JComponentHelper::isEnabled('com_phocadocumentation', true)) {
			echo '<div class="alert alert-danger">'.JText::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_COMPONENT_NOT_INSTALLED').'</div>';
			return;
		}

		require_once( JPATH_ROOT.'/components/com_phocadocumentation/helpers/route.php' );
		require_once( JPATH_ADMINISTRATOR.'/components/com_phocadocumentation/helpers/phocadocumentationnavigation.php' );

		$document				= JFactory::getDocument();
		$component 				= 'com_phocadocumentation';
		$t						= array();
		$pC	 					= JComponentHelper::getParams($component);
		$t['article_itemid']	= $pC->get( 'article_itemid', '' );
		$css					= $pC->get( 'theme', 'phocadocumentation-grey' );
		JPlugin::loadLanguage( 'plg_content_phocadocumentationnavigation' );

		if ($css == 'phocadocumentation-bootstrap') {$bts = 1;} else {$bts = 0;}
		JHTML::stylesheet('media/com_phocadocumentation/css/'.$css.'.css' );

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
			$output .= '<div class="phocadocumentation-navigation">' . "\n";


			// -------------------------
			// NAVIGATION
			// -------------------------
			if ($view == 'navigation') {

				// -------------------------------------------
				// PARAMS
				$oL['fgcolor']		= '#fafafa';
				$oL['bgcolor']		= '#fafafa';
				$oL['textcolor']	= '#000000';
				$oL['capcolor']		= '#000000';
				$oL['closecolor']	= '#000000';

				$document->addCustomTag('<script type="text/javascript" src="'.JURI::root().'components/com_phocadocumentation/assets/overlib/overlib_mini.js"></script>');

				$d = PhocaDocumentationNavigation::getDocuments();
				$prevOutput = PhocaDocumentationNavigation::getPrevOutput($d['prev'], $oL, $t['article_itemid'], $bts);
				$nextOutput = PhocaDocumentationNavigation::getNextOutput($d['next'], $oL, $t['article_itemid'], $bts);
				$topOutput 	= PhocaDocumentationNavigation::getTopOutput($d['doc'], $oL, $topid, $t['article_itemid'], $bts);
				$listOutput = PhocaDocumentationNavigation::getListOutput($d['list'], $d['doc'], $oL, $t['article_itemid'], $bts);

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
				$sep		= ' <b style="color:#ccc;">&bull;</b> ';
				$sepPrev	= 0;

				if ($main) {
					if ($bts == 1) {
						$output .= '<div class="navigation-text" id="pdoc-top"><h5>'.JText::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_NAVIGATION') . '</h5>'."\n";
					} else {
						$output .= '<div class="navigation-text" id="pdoc-top"><div><div><div><h5>'.JText::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_NAVIGATION') . '</h5>'."\n";
						$output .= '<div>';
					}
				} else {
					if ($bts == 1) {
						$output .= '<div class="navigation-text" >'."\n";
					} else {
						$output .= '<div class="navigation-text" ><div><div><div>'."\n";
						$output .= '<div>';
					}
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
			if ($bts == 1) {
				$output .= '</div></div>';
			} else {
				$output .= '</div></div></div></div></div></div>';
			}

			$article->text = preg_replace($regex_all, $output, $article->text, 1);
		}
		return true;
	}
}
?>
