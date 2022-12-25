<?php
/* @package Joomla
 * @copyright Copyright (C) Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @form Phoca form
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;

defined('_JEXEC') or die();
class PhocaDocumentationNavigation
{
	private static $doc = false;

	private function __construct(){}

	public static function getDocuments() {

		if(self::$doc === false) {

			$app		= Factory::getApplication();
			$id			= $app->input->get('id', 0, 'int');
			$user 		= Factory::getUser();
			$userLevels	= implode (',', $user->getAuthorisedViewLevels());
			$db 		= Factory::getDBO();


			// CURRENT DOC (Information about current doc - ordering, category)
			$wheres		= array();
			$wheres[]	= " c.id= ".(int)$id;
			$wheres[]	= " c.catid= cc.id";

			$wheres[] = ' c.state = 1';
			$wheres[] = ' cc.published = 1';
			$wheres[] = " cc.access IN (".$userLevels.")";

			// Active
			$jnow		= Factory::getDate();
			$now		= $jnow->toSQL();
			$nullDate	= $db->getNullDate();
			//$wheres[] = ' ( c.publish_up = '.$db->Quote($nullDate).' OR c.publish_up <= '.$db->Quote($now).' )';
			//$wheres[] = ' ( c.publish_down = '.$db->Quote($nullDate).' OR c.publish_down >= '.$db->Quote($now).' )';

			$wheres[] = ' (' . $db->quoteName('publish_up') . ' IS NULL OR ' . $db->quoteName('publish_up') . ' <= '.$db->Quote($now).')';

            $wheres[] = ' (' . $db->quoteName('publish_down') . ' IS NULL OR ' . $db->quoteName('publish_down') . ' >= '.$db->Quote($now).')';

			$query = ' SELECT c.id, c.title, c.alias, c.catid, c.ordering, cc.id AS categoryid, cc.title AS categorytitle, cc.alias AS categoryalias, cc.access as cataccess, c.language'
				//.' n.id AS nextid, n.title AS nexttitle, n.alias AS nextalias,'
				//.' p.id AS previd, p.title AS prevtitle, p.alias AS prevalias'
				.' FROM #__content AS c'
				.' LEFT JOIN #__categories AS cc ON cc.id = c.catid'
				//.' LEFT JOIN #__content AS n ON cc.id = n.catid AND n.ordering > c.ordering'
				//.' LEFT JOIN #__content AS p ON cc.id = p.catid AND p.ordering < c.ordering'
				. ' WHERE ' . implode( ' AND ', $wheres )
				//. ' GROUP BY c.id, c.title, c.alias, c.catid, c.ordering, cc.id, cc.title, cc.alias, cc.access'
				. ' ORDER BY c.ordering';


			$db->setQuery($query, 0, 1);

			$cDoc = $db->loadObject();

			$d['next']	= array();
			$d['prev']	= array();
			$d['list']	= array();
			$d['doc']	= array();

			if (!empty($cDoc)) {

				$d['doc']['id']		= (int)$cDoc->id;
				$d['doc']['title']	= $cDoc->title;
				$d['doc']['alias']	= $cDoc->alias;
				$d['doc']['cid']	= (int)$cDoc->catid;
				$d['doc']['calias']	= $cDoc->categoryalias;
				$d['doc']['language']	= $cDoc->language;

			/*	if (isset($cDoc->nextid) && (int)$cDoc->nextid > 0) {
					$d['next']['id']	= (int)$cDoc->nextid;
					$d['next']['title']	= $cDoc->nexttitle;
					$d['next']['alias']	= $cDoc->nextalias;
					$d['next']['cid']	= (int)$cDoc->catid;
					$d['next']['calias']= $cDoc->categoryalias;
				} else {
					$d['next'] = array();
				}
				if (isset($cDoc->previd) && (int)$cDoc->previd > 0) {
					$d['prev']['id']	= (int)$cDoc->previd;
					$d['prev']['title']	= $cDoc->prevtitle;
					$d['prev']['alias']	= $cDoc->prevalias;
					$d['prev']['cid']	= (int)$cDoc->catid;
					$d['prev']['calias']= $cDoc->categoryalias;
				} else {
					$d['prev'] = array();
				} */

				// Query LIST
				$wheres		= array();
				$wheres[]	= " c.catid= ".(int)$cDoc->catid;
				$wheres[]	= " c.catid= cc.id";
				$wheres[] 	= " cc.access IN (".$userLevels.")";
				$wheres[] 	= " c.state = 1";
				$wheres[] 	= " cc.published = 1";
				$query = " SELECT c.id, c.title, c.alias, c.ordering, c.language, cc.id AS cid, cc.title AS ctitle, cc.alias AS calias"
				." FROM #__content AS c, #__categories AS cc"
				." WHERE " . implode( " AND ", $wheres )
                .' ORDER BY c.ordering';
				$db->setQuery($query);
				$lDoc = $db->loadAssocList();

				$currentArrayId = 0;
				if (!empty($lDoc)) {
					$d['list'] = $lDoc;
					foreach($lDoc as $k => $v) {

						if (isset($v['id']) && (int)$v['id'] == (int)$id) {
							$currentArrayId = $k;
							break;
						}
					}

					// We don't search for ordering or id but for array key
					// the array key starts from 0 ++ and it is not ordering or id so we can get prev and next
					// in case the key will be ordering, there can be missing the number: 1 2 4 6 so then nothing will be found
					$next = $currentArrayId + 1;
					$prev = $currentArrayId - 1;// It can be even minus, there will be check for this

					if (isset($lDoc[$next]) && !empty($lDoc[$next]) && isset($lDoc[$next]['id'])) {
						$d['next']['id']	= (int)$lDoc[$next]['id'];
						$d['next']['title']	= $lDoc[$next]['title'];
						$d['next']['alias']	= $lDoc[$next]['alias'];
						$d['next']['cid']	= (int)$lDoc[$next]['cid'];
						$d['next']['ctitle']= $lDoc[$next]['ctitle'];
						$d['next']['calias']= $lDoc[$next]['calias'];
						$d['next']['language']= $lDoc[$next]['language'];
					}
					if (isset($lDoc[$prev]) && !empty($lDoc[$prev]) && isset($lDoc[$prev]['id'])) {
						$d['prev']['id']	= (int)$lDoc[$prev]['id'];
						$d['prev']['title']	= $lDoc[$prev]['title'];
						$d['prev']['alias']	= $lDoc[$prev]['alias'];
						$d['prev']['cid']	= (int)$lDoc[$prev]['cid'];
						$d['prev']['ctitle']= $lDoc[$prev]['ctitle'];
						$d['prev']['calias']= $lDoc[$prev]['calias'];
						$d['prev']['language']= $lDoc[$prev]['language'];
					}
				}

			}


			self::$doc = $d;
		}
		return self::$doc;
	}



	public final function __clone() {
		throw new Exception('Function Error: Cannot clone instance of Singleton pattern', 500);
		return false;
	}

	public static function getPrevOutput($d, $paramsNav) {

		$o = '';
		if (!empty($d)) {

			$slug 		= $d['alias'] ? ($d['id'] . ':' . $d['alias']) : $d['id'];
        	$link 		= RouteHelper::getArticleRoute($slug, $d['cid'], $d['language']);
			$title 		= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8');
			$attributes = 'class="pdoc-nav-prev" data-bs-toggle="tooltip" data-bs-placement="top" title="'.$title.'"';

			$o .=  '<a '.$attributes.' href="'. Route::_($link).'">';
			$o .= '<svg class="pdoc-si pdoc-si-prev"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-prev"></use></svg>';
			$o .= '</a>';
		} else {
			$o =  '<div class="pdoc-link-inactive" title="'.Text::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_PREVIOUS').'">';
			$o .= '<svg class="pdoc-si pdoc-si-prev"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-prev"></use></svg>';
			$o .= '</div>';
		}
		return $o;
	}

	public static function getNextOutput($d, $paramsNav) {

		$o = '';
		if (!empty($d)) {

			$slug 		= $d['alias'] ? ($d['id'] . ':' . $d['alias']) : $d['id'];
        	$link 		= RouteHelper::getArticleRoute($slug, $d['cid'], $d['language']);
			$title 		= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8');
			$attributes = 'class="pdoc-nav-prev" data-bs-toggle="tooltip" data-bs-placement="top" title="'.$title.'"';

			$o .=  '<a '.$attributes.' href="'. Route::_($link).'">';
			$o .= '<svg class="pdoc-si pdoc-si-next"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-next"></use></svg>';
			$o .= '</a>';
		} else {
			$o =  '<div class="pdoc-link-inactive" title="'.Text::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_NEXT').'">';
			$o .= '<svg class="pdoc-si pdoc-si-next"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-next"></use></svg>';
			$o .= '</div>';
		}
		return $o;
	}

	public static function getTopOutput($d, $paramsNav, $topId) {

		$o = '';
		if (!empty($d)) {

			$slug 		= $d['alias'] ? ($d['id'] . ':' . $d['alias']) : $d['id'];
			$link 		= RouteHelper::getArticleRoute($slug, $d['cid'], $d['language']) . '#'.$topId;
			//$title 		= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8');
			$attributes = 'class="pdoc-nav-prev" data-bs-toggle="tooltip" data-bs-placement="top" title="'.Text::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_TO_TOP').'"';

			$o .=  '<a '.$attributes.' href="'. Route::_($link).'">';
			$o .= '<svg class="pdoc-si pdoc-si-top"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-top"></use></svg>';
			$o .= '</a>';
		} else {
			$o =  '<div class="pdoc-link-inactive" title="'.Text::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_TOP').'">';
			$o .= '<svg class="pdoc-si pdoc-si-top"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-top"></use></svg>';
			$o .= '</div>';
		}
		return $o;

	}

	public static function getListOutput($d, $dd, $paramsNav) {

		$o = '';
		$oBox = '';
		if (!empty($d)) {

			$oBox .= '<ul>';
			foreach ($d as $k => $v) {

				$slug = $v['alias'] ? ($v['id'] . ':' . $v['alias']) : $v['id'];
        		$link = RouteHelper::getArticleRoute($slug, $v['cid'], $v['language']);
				$attributes = 'title="'.$v['title'].'"';

				$oBox .= '<li>';
				//$oBox .= '<svg class="pdoc-si pdoc-si-article"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-article"></use></svg>';
				$oBox .= '<a '.$attributes.' href="'. Route::_($link).'">';
				$oBox .= $v['title'];
				$oBox .= '</a></li>';

			}
			$oBox = '<div class="pdoc-popover-box">'.$oBox.'</div>';
		}

		if (!empty($dd)) {

			$title		= Text::_('PLG_CONTENT_PHOCADOCUMENTATIONNAVIGATION_TABLE_OF_CONTENTS');

			//$slug = $d['alias'] ? ($d['id'] . ':' . $d['alias']) : $d['id'];
			$link = RouteHelper::getCategoryRoute($dd['id'], $dd['language']);

			$content = htmlspecialchars($oBox, ENT_QUOTES, 'UTF-8');
			// !!! we cannot use data-bs-trigger="focus" because it makes the links in popover inactive
			$attributes = 'class="pdoc-nav-prev" data-bs-toggle="popover" data-bs-placement="top" title="'.$title.'" data-bs-content="'.$content.'" data-bs-html="true"';

			$o .=  '<a '.$attributes.' href="javascript:void(0);" data-href="'. Route::_($link).'">';
			$o .= '<svg class="pdoc-si pdoc-si-category"><use xlink:href="'.$paramsNav['svg_path'].'#pdoc-si-category"></use></svg>';
			$o .= '</a>';

		}
		return $o;
	}

	/* Possible Hardcode */
	/*
	public static function renderTop($p, $n, $l, $h) {

		$sep = ' <b style="color:#ccc;">&bull;</b> ';
		$o = '';
		$o .= '<div class="navigation-text" id="pdoc-top"><h5>'.$h . '</h5>'."\n";
		$o .= $p . $sep . $l . $sep. $n;
		$o .= '</div>';
		return $o;
	}

	public static function renderBottom($p, $n, $t) {

		$sep = ' <b style="color:#ccc;">&bull;</b> ';
		$o = '';
		$o .= '<div class="navigation-text" id="pdoc-top">'."\n";
		$o .= $p . $sep . $t . $sep. $n;
		$o .= '</div>';
		return $o;
	}

	*/
}
?>
