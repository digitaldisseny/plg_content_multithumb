<?php
#error_reporting(E_ALL);
/**
 * @version $Id: multithumb.php, v 2.0 alpha 3 for Joomla 1.5 2008/8/27 15:08:21 marlar Exp $
 * @package Joomla
 * @copyright (C) 2007-2008 Martin Larsen; with modifications from Erich N. Pekarek and René-C. Kerner
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

//import library dependencies
jimport('joomla.event.plugin');
jimport('joomla.document.document');

require_once (JPATH_SITE . DS . 'components' . DS . 'com_content' . DS . 'helpers' . DS . 'route.php');

// BK-Thumb plugin implementation
class plgContentMultithumb extends JPlugin {

	function plgContentMultithumb ( &$subject, $config ) {
	// public function __construct(& $subject, $config) {
		// Iniatialize parent class
		parent::__construct($subject, $config);
	
		// Current version for infornation message
		$this->botmtversion = 'Multithumb 3.1.0';
		
		$version = new JVersion();
		$this->jVersion = (int) substr(str_replace('.','', $version->getShortVersion()),0,2);
		if ( $this->jVersion >= 16 ) {
			$this->loadLanguage();
		}
		

		
		// Don't initaialize anymore if plugin is disabled
		$this->published	= JPluginHelper::isEnabled('content','multithumb');
		if (!$this->published) {
			return;
		}
		
		$this->_live_site = JURI::base( true );

		
		// Initialize paramters
		$plugin = JPluginHelper::getPlugin( 'content', 'multithumb' );
		if ( $this->jVersion >= 16 ) {
			$plugin = JPluginHelper::getPlugin( 'content', 'multithumb' );
			$params = new JRegistry();
			$params->loadJSON($plugin->params);
			$this->init_params($params);
		} else {
			$this->init_params(new JParameter( $plugin->params ));
		}
		
		$this->cont_num = 0;
		// echo "DEBUG:$this->cont_num<br>";
		
 		if ( $this->_params->get('slimbox_headers') == 2 ) {
			$this->botAddMultiThumbHeader('slimbox');
		}
		if ( $this->_params->get('prettyphoto_headers') == 2 ) {
			$this->botAddMultiThumbHeader('prettyPhoto');
		}

		if ( $this->_params->get('shadowbox_headers') == 2 ) {
			$this->botAddMultiThumbHeader('shadowbox');
		}
		
		if ( $this->_params->get('jquery_headers') == 2 ) {
			$this->botAddMultiThumbHeader('jquery');
		}	

		if ( $this->_params->get('iload_headers') == 2 ) {
			$this->botAddMultiThumbHeader('iLoad');
		}
		
	}

	// Initializes plugin parameters
	function init_params (&$params) {
		$this->_params = $params;
		$this->_params->def('thumb_size_type', 0);
		$this->_params->def('shadowbox_headers', 1);
		$this->_params->def('slimbox_headers', 1);
		$this->_params->def('prettyphoto_headers', 1);
		
		$this->_params->def('memory_limit', 'default');
		$this->_params->def('time_limit', '');

		// $this->_params->def('ignore_cats', '');
		$this->_params->def('only_classes', '');		
		$this->_params->def('only_tagged', 0);
		$this->_params->def('exclude_tagged', 0);
		$this->_params->def('enable_thumbs', 1);
		
		// $this->_params->def('thumb_width',150);
		// $this->_params->def('thumb_height',100);
		$this->_params->def('popup_type', 'slimbox');
		$this->_params->def('thumb_proportions','bestfit');
		$this->_params->def('thumb_bg', '#FFFFFF');
		$this->_params->def('border_size', '2px');
		$this->_params->def('border_color', '#000000');
		$this->_params->def('border_style', 'none');
		$this->_params->def('thumbclass', 'multithumb');
		
		$this->_params->def('resize',0);
		$this->_params->def('full_width',800);
		$this->_params->def('full_height',600);
		$this->_params->def('image_proportions','bestfit');
		$this->_params->def('image_bg', '#000000');
		
		$this->_params->def('blog_mode', 'link');
		$this->_params->def('enable_blogs', 1);
		$this->_params->def('max_thumbnails', 0);
		$this->_params->def('num_cols', 3);
		$this->_params->def('allow_img_toolbar',0);
		$this->_params->def('scramble', 'off');
		$this->_params->def('quality', 80);
		$this->_params->def('watermark', 0);
		$this->_params->def('watermark_type', 0);
		if ( !$this->_params->get('watermark_type') ) {
			$this->_params->set('watermark', 0);
		}
		$this->_params->def('watermark_file', '');
		if ( !$this->_params->get('watermark_file') ) {
			$this->_params->set('watermark', 0);
		}
		
		if(!$this->_params->get('watermark')) { // No watermark
			$this->_params->set('watermark_file', '');
		}
		// if(strpos($this->_params->get('watermark_file'), '://')) { // It's a url
			// $this->_params->set( 'watermark_file', 
					// str_replace( "/", DS, 
						// str_replace($this->_live_site, JPATH_SITE, 
							// $this->_params->get('watermark_file'))) );
		// }
		$this->_params->def('watermark_left', '');
		$this->_params->def('watermark_top', '');
		
		$this->watermark_cats = $this->_params->get('watermark_cats');
		if ($this->watermark_cats) {
			if ( !is_array($this->watermark_cats) ) {
				$this->watermark_cats = (array)$this->watermark_cats;
			}
		} else {
			$this->watermark_cats = (array)null;
		}
		
		$this->_params->def('transparency_type', 'alpha');
		$this->_params->set('transparent_color', hexdec($this->_params->get('transparent_color', '#000000')) );
		$this->_params->def('transparency', '25');
		
		$this->_params->def('error_msg', 'text');
 		$this->_params->def('css', ".multithumb {
    margin: 5px; 
    float: left; 
 }");

	$this->_params->def('caption_css', ".mtCapStyle {
 font-weight: bold;
 color: black;
 background-color: #ddd;
 margin: 0px 4px;
 text-align: center;
 white-space: pre-wrap;
}");

	$this->_params->def('gallery_css', ".mtGallery {
   margin: 5px;
   align: center;
   float: none;
}");

		$IS_ARTICLE_RULE=$this->_params->get('IS_ARTICLE_RULE', "option=com_content&view=article,option=com_flexicontent&view=items");
		
		$IS_ARTICLE_RULE=str_replace ("\n", "", $IS_ARTICLE_RULE);
		$IS_ARTICLE_RULE=str_replace ("\r", "", $IS_ARTICLE_RULE);
		$IS_ARTICLE_RULE=str_replace (" ", "", $IS_ARTICLE_RULE);
		$P1 = explode(",", $IS_ARTICLE_RULE);
		$this->is_article_rule = "(";
		foreach ($P1 as $val1) {
			$P2 = explode("&", $val1);
			$this->is_article_rule .= "(";
			foreach ($P2 as $val2) {
				list($cmd, $val) = explode("=", $val2);
				$this->is_article_rule .= "( JRequest::getCmd('$cmd') == '$val' ) AND ";
			}
			$this->is_article_rule .= "TRUE ) OR ";
		}
		$this->is_article_rule .= "FALSE )";
		
		// echo "DEBUG: $this->is_article_rule <br/>";
		// echo "DEBUG:".JRequest::getCmd('layout')."<br>";
		
		
		$IS_BLOG_RULE=$this->_params->get('IS_BLOG_RULE', "option=com_content&view=featured,option=com_content&layout=blog");
		$IS_BLOG_RULE=str_replace ("\n", "", $IS_BLOG_RULE);
		$IS_BLOG_RULE=str_replace ("\r", "", $IS_BLOG_RULE);
		$IS_BLOG_RULE=str_replace (" ", "", $IS_BLOG_RULE);		
		$P1 = explode(",", $IS_BLOG_RULE);
		$is_blog_rule = "(";
		foreach ($P1 as $val1) {
			$P2 = explode("&", $val1);
			$is_blog_rule .= "(";
			foreach ($P2 as $val2) {
				list($cmd, $val) = explode("=", $val2);
				$is_blog_rule .= "( JRequest::getCmd('$cmd') == '$val' ) AND ";
			}
			$is_blog_rule .= "TRUE ) OR ";
		}
		$is_blog_rule .= "FALSE )";
		
		eval( "\$is_blog = $is_blog_rule;" ) ;			
		$this->is_blog = $is_blog;
//		echo "DEBUG:".(int)$is_blog." - $is_blog_rule<br>";
//		echo "DEBUG:".(int)$is_article." - $this->is_article_rule<br>";

		eval( "\$is_article = $this->is_article_rule;" ) ;			
		$this->is_article = $is_article;
		
		// $this->is_blog = ( /* ( JRequest::getCmd('option') == 'com_content') && */
						// ((JRequest::getCmd('view') == 'featured') || (JRequest::getCmd('layout') == 'blog')) );

		// echo "DEBUG:".$this->is_blog."<br>";
		
		if ($this->is_blog) {

			$item_id = JRequest::getInt('Itemid');
			// <param name="blog_mode" type="list"			
			// <option value="link">Link to article</option>
			// <option value="popup">Popup</option>
			// <option value="thumb">Thumbnails only</option>
			// <option value="disable">Disable</option>
			// $item_id = JRequest::getInt('Itemid');

			$blog_ids = $this->_params->get('blog_ids');
			if ($blog_ids) {
				if ( !is_array($blog_ids) ) {
					$blog_ids = (array)$blog_ids;
				}
			} else {
				$blog_ids = (array)null;
			}
			
			if ( ( $this->_params->get('enable_blogs') == 0 ) ||
			     // ( $this->_params->get('blog_mode') == "disable" ) ||
				( $this->_params->get('enable_blogs') == 2 && /* $blog_ids && */ !in_array($item_id, $blog_ids) ) ) {
				
				$this->_params->set('enable_thumbs', 0);
				$this->_params->set('blog_mode', 'disable');
				
				// $this->_params->set('thumb_size_type', 1);
				$this->_params->set('blog_mode', 'thumb');

				$this->_params->set('popup_type', 'nothumb' );
			} else {
				$this->_params->set('enable_thumbs', 1);
				if( ( $this->_params->get('blog_mode')=='thumb' || $this->_params->get('blog_mode')=='link' ) ) {
					$this->_params->set('popup_type', 'none');
				}
			}
			
			// echo "DEBUG:".$this->_params->get('enable_blogs')."<br>";

			
			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('blog_size', "200x150" ));

			$this->_params->set('thumbclass', $this->_params->get('thumbclass_blog') );
			$this->_params->set('css_blog',   $this->_params->get('css_blog') );
			
			if ( $this->_params->get('caption') ==1 ) {
				$this->_params->set('caption', 0);
			}
			
			$this->_params->set('thumb_size_first', '');

			// $this->_params->set('thumb_size_type', 0);
		} else {
			$this->_params->set('blog_mode', 'disable');

			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('thumb_size', "150x100"));

			if ( $this->_params->get('caption') == 3 ) {
				$this->_params->set('caption', 0);
			}
		}
		
		
		$this->_params->set('thumb_width', $thumb_width);
		$this->_params->set('thumb_height', $thumb_height);
		
		if ( !$this->_params->get('caption') ) {
			$this->_params->set('caption_pos', 'disabled');
		}
		$this->_params->def('caption_pos', 'disabled');
		if ( $this->_params->get('caption_pos') ==  'disabled') {
			$this->_params->set('caption_pos', '');
		}
		$this->_params->def('caption_type', 'title');
				//$alt = trim( $alt);
		// $title= trim( $title);
    	switch ($this->_params->get('caption_type')) {
    		case "alt":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',0);
				$this->_params->set('caption_type_alt',1);
    			break;
    		case "title":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',1);
				$this->_params->set('caption_type_alt',0);
    			break;
			case "iptc_caption":
				$this->_params->set('caption_type_iptc',1);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',0);
				$this->_params->set('caption_type_alt',0);
				break;
    		case "alt_or_title":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',2);
				$this->_params->set('caption_type_alt',1);
    			break;
    		case "title_or_alt":
				$this->_params->set('caption_type_iptc',0);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',1);
				$this->_params->set('caption_type_alt',2);
    			break;
    		case "iptc_caption_or_alt_or_title":
				$this->_params->set('caption_type_iptc',1);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',3);
				$this->_params->set('caption_type_alt',2);
    			break;
    		case "iptc_caption_or_title_or_alt":
				$this->_params->set('caption_type_iptc',1);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',2);
				$this->_params->set('caption_type_alt',3);
    			break;
    		case "alt_or_title_or_iptc_caption":
				$this->_params->set('caption_type_iptc',3);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',2);
				$this->_params->set('caption_type_alt',1);
    			break;
    		case "title_or_alt_or_iptc_caption":
				$this->_params->set('caption_type_iptc',3);
				$this->_params->set('caption_type_filename',0);
				$this->_params->set('caption_type_title',1);
				$this->_params->set('caption_type_alt',2);
    			break;
    	}

		// Defines regular expression for img tag searching
		/* if( !$is_blog and $this->_params->get('enable_thumbs') == 4 and $this->_params->get('only_classes') ) {
			$this->regex = '#<img(?=[^>]*class=["\'](?:'.($this->_params->get('only_classes')).')["\'])[^>]*src=(["\'])([^"\']*)\1[^>]*>';
			// echo "DEBUG:".htmlspecialchars($this->regex)."<br>";
		} else */ {
			$this->regex = '#<img[^>]*src=(["\'])([^"\']*)\1[^>]*>';
		}
		$this->regex .= '|{multithumb([^}]*)}#is';
		
		$this->is_gallery = false;	
		

		// Preserve parameters set

		// if (version_compare(PHP_VERSION, '5.0.0', '<')) {
			// $this->_paramsDef = $this->_params;
		// } else {
			$this->_paramsDef = clone($this->_params);
		// }
		
		// echo "DEBUG: ".$this->_paramsDef->get('thumb_width' )."-".$this->_paramsDef->get('thumb_height' )."<br />";
		
		// echo "DEBUG:".$is_blog." ".$this->_params->get('blog_mode')." ".JRequest::getCmd('view') ." ".JRequest::getCmd('layout'). "<br>";

	

	}
	
	function parse_size($size, $dimention = "" ) {
		$pieces = explode("x", $size);
		if ( isset($pieces[0]) && !isset($pieces[1]) ) {
			$height = $width = $pieces[0];
		} elseif ( isset($pieces[0]) && isset($pieces[1]) ) {
			$width = $pieces[0];
			$height = $pieces[1];
		} else {
			$width = $height = 0;
		}
		
		if ($dimention == "width") {
			return $width;
		} elseif ($dimention == "height") {
			return $height;
		} else {
			return array($width, $height);
		}
	}
	
	public function onContentPrepare($context, &$article, &$params, $limitstart = 0) {
		// echo "DEBUG1 onContentPrepare: ".print_r((int)$this->is_article,true)."<br>";		
		// echo "DEBUG2 onContentPrepare: ".print_r($params,true)."<br>";		
		if ( !$this->is_article ) {
			return;		
		}

		
		// $this->is_article = true;
		$this->onPrepareContent ( $article, $params, $limitstart);
		
	}
	
	public function onContentBeforeDisplay($context, &$article, &$params, $limitstart = 0) {
		// return;
		// echo "DEBUG2:onContentBeforeDisplay: ".print_r((int)$this->is_blog,true)."<br><br>";
		if ( !$this->is_blog ) {
			return;		
		}		

		// $this->is_blog = true;
		// $this->is_article = false;		
		if ( isset( $article->text ) ) {
			$text_save = $article->text;
		}
		$article->text = $article->introtext ;
		$this->onPrepareContent ( $article, $params, $limitstart);
		$article->introtext = $article->text ;
		if ( isset( $text_save ) ) {
			$article->text = $text_save;
		}
		
	}
	
	// Process one article
	function onPrepareContent ( &$row, &$params, $page=0 ) {
	


		/* $aparams = new JRegistry();
		$aparams->loadObject($row->attribs); */
		// echo "DEBUG3: aparams ".print_r($aparams, true)."<br>";		
		
		// If plugin disabled or row parameters is not post remove plugin tags and stop processing
		if ( !$this->published /*|| !$params */) {
			$row->text = preg_replace('#{(no)?multithumb([^}]*)}#i', '', $row->text);

			return true;
		}
		
		static $rowid = 0;
		
		if ( isset( $row->id ) ) {
			$this->rowid = $row->id;
		} else {
			$this->rowid = ++$rowid;
		}
		
		if ( $this->rowid ) {
			$this->cont_num++;
		}
		


		// Restore plugin params
		// $this->_params = $this->_paramsDef;

		// Decide if thumbs should be generated depend on current category 
		// $is_article = ( JRequest::getCmd('option') == 'com_content') && 
							// (JRequest::getCmd('view') == 'article');

			
			// $this->is_article = (int)((JRequest::getCmd('option') == 'com_content' && JRequest::getCmd('view') == 'article') ||
						// (JRequest::getCmd('option') == 'com_flexicontent' && JRequest::getCmd('view') == 'items')) ;

						
		// echo "DEBUG: ".(int)$this->is_blog." <br>";
		// echo "DEBUG: ".(int)$this->is_article." <br>";
		if ($this->is_article && $this->_params->get('enable_thumbs') < 4) {
			if ( preg_match('/{multithumb}/is', $row->text)==0 ) {
				if ($this->_params->get('enable_thumbs') ) {
					// <param name="enable_thumbs" type="list" default="1" label="Thumbnails for articles"
					// <option value="1">Enable for allcategories</option>
					// <option value="2">Enable for following categories only</option>
					// <option value="3">Enable for all except following categories</option>
					// <option value="0">Disable</option>

					$only_cats = $this->_params->get('only_cats');
					if ($only_cats) {
						if ( !is_array($only_cats) ) {
							$only_cats = (array)$only_cats;
						}
					} else {
						$only_cats = (array)null;
					}

					if ( /* $only_cats &&  $this->_params->get('enable_thumbs')!=1 &&*/(
						 ( $this->_params->get('enable_thumbs')==2  && !in_array($row->catid, $only_cats ) ) ||
						 ( $this->_params->get('enable_thumbs')==3  && in_array($row->catid, $only_cats ) ) )) {
						// $this->_params->set('enable_thumbs', 0);
						$row->text = preg_replace('/{(no)?multithumb([^}]*)}/i', '', $row->text);
						
						// $this->_params->set('thumb_size_type', 1);
						$this->_params->set('popup_type', 'nothumb');				
						// return true;
					}
					// echo "DEBUG:1<br>";
				} else { 
					// $this->_params->set('thumb_size_type', 1);
					$this->_params->set('popup_type', 'nothumb');	

				}
			}
		}
		
		// 
		if(	($this->_params->get('exclude_tagged' ) && stristr($row->text, '{nomultithumb}')!==false) ||
			($this->_params->get('only_tagged' )    && preg_match('/{multithumb([^}]*)}/is', $row->text )==0 )) {
			// $this->_params->set('thumb_size_type', 1);
			$this->_params->set('blog_mode', 'thumb'); // TBD ???
//			$this->_params->set('popup_type', 'nothumb');
			$row->text = preg_replace('/{(no)?multithumb([^}]*)}/i', '', $row->text);
			return true;
		}
		
		// Cleanup NOmultithumb if it is ignored
		if (stristr($row->text, '{nomultithumb}')!==false) { // BK
			$row->text = preg_replace('/{nomultithumb}/i', '', $row->text);
		}
		
		// PROCESS ROW
		
		$this->mt_thumbnail_count = array();
		$this->mt_gallery_count = 0;
		

		if ( $this->_params->get('blog_mode')=='link' ) {
		

			// Read more text
			// $this->botMtLinkText = "";
			// if ($params) {
				// $this->botMtLinkText = ( $aparams->get('readmore') ? $aparams->get('readmore') : JText::_('Read more...') ); // BK
				
				
		// <?php $attribs = json_decode($this->item->attribs);  
		//
		// if ($attribs->alternative_readmore == null) :
			// echo JText::_('COM_CONTENT_REGISTER_TO_READ_MORE');
		// elseif ($readmore = $this->item->alternative_readmore) :
			// echo $readmore;
			// if ($params->get('show_readmore_title', 0) != 0) :
			    // echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
			// endif;
		// elseif ($params->get('show_readmore_title', 0) == 0) :
			// echo JText::sprintf('COM_CONTENT_READ_MORE_TITLE');
		// else :
			// echo JText::_('COM_CONTENT_READ_MORE');
			// echo JHtml::_('string.truncate', ($this->item->title), $params->get('readmore_limit'));
		// endif; 
				
				

				// echo "DEBUG3: show_readmore_title ".$row->params->get('show_readmore_title', 0)."<br>";
				if ($row->params == null) {
					$this->botMtLinkText = JText::_('COM_CONTENT_REGISTER_TO_READ_MORE');
				} elseif ($row->alternative_readmore) {
					$this->botMtLinkText = $row->alternative_readmore;
					if ($row->params->get('show_readmore_title', 0) != 0) {
						$this->botMtLinkText .= JHTML::_('string.truncate', $row->title, $row->params->get('readmore_limit'));
					}
				} elseif ($row->params->get('show_readmore_title')) {
					$this->botMtLinkText = JText::_('COM_CONTENT_READ_MORE').
						JHTML::_('string.truncate', $row->title, $row->params->get('readmore_limit'));
				} else {
					$this->botMtLinkText = JText::sprintf('COM_CONTENT_READ_MORE_TITLE');	
				}

				// echo "DEBUG:".JText::_('COM_CONTENT_READ_MORE_TITLE')."<br/>";
				
				
				
				// article link 
				$this->botMtLinkOn = JRoute::_(ContentHelperRoute::getArticleRoute($row->slug, $row->catid )); // BK
			// } 

			
		}
			

		
		$this->watermark = $this->_params->get('watermark');
		if ( /* $this->watermark_cats && */ $this->watermark != 1 &&(
			 ( $this->_params->get('watermark')==2  && !in_array($row->catid, $this->watermark_cats ) ) ||
			 ( $this->_params->get('watermark')==3  && in_array($row->catid, $this->watermark_cats ) ) )) {
			// $this->_params->set('watermark', 0);
			$this->watermark = 0;
		}
		if ( $this->watermark > 0 ) {
			$this->watermark = 1;
		}
			
		// initialize error message
		$this->multithumb_msg = '';
		
		$this->imgnum = 0;

				
		// PROCESS IMAGES OR INLINE PARAMETERS IN THE TEXT
		$row->text = preg_replace_callback($this->regex, array($this,'bot_mt_replace_handler'), $row->text);
			
		// Print error messages
		if($this->multithumb_msg)
		switch($this->_params->get('error_msg')) {
			case 'popup':
				$row->text .= "<script type='text/javascript' language='javascript'>alert('Multithumb found errors on this page:\\n\\n".$this->multithumb_msg."')</script>";
				break;
			case 'text':
				$this->multithumb_msg = str_replace('\\n', "\n", $this->multithumb_msg);
				$row->text = "<div style='border:2px solid black; padding: 10px; background-color: white; font-weight: bold; color: red;'>Multithumb found errors on this page:<br /><br />\n\n".$this->multithumb_msg."</div>" . $row->text;
		}

		// Update headers
		$this->botAddMultiThumbHeader('style');

		return true;
	}
	
	// Is called for each image or inline parameters in the text
    function bot_mt_replace_handler(&$matches) {

	
		// echo "DEBUG: $row->id". $matches[0].  "<br />";	
	  
		//
    	// inline parameters processing
    	//
    	if(strtolower(substr($matches[0], 0, 11))=='{multithumb') {
    		// Just for remding: '|{multithumb([^}]*)}#is';
    		$this->inline_parms($matches[3]);

    		// go to the next match
    		return '';

    	}

    	// it's a normal image
    	return $this->image_replacer($matches);
    }


	// Process images in the text
    function image_replacer(&$matches) {

	
 		static $call_counter = 0;
		$call_counter++;
		
		if ($call_counter > 5) {
			echo "Multhumb infinite loop error: <br /><pre>".htmlspecialchars(print_r($matches, true))."</pre>";
			exit(1);
		}

		// echo "DEBUG:".$this->_params->get('popup_type')." ".$this->popup_type." ".$this->_params->get('thumb_size_type' )."<br>";

		
    	// Current image tag
    	$imgraw = $imgrawOrg = $matches[0];

    	// Original path of current image
    	$imgloc = rawurldecode($matches[2]);

		// Captions parameters
    	$this->caption_pos = $this->_params->get('caption_pos');
    	$this->caption_type = $this->_params->get('caption_type');
    	 
    	$style=$alt=$title=$align=$class=$onclick=$img=$hspace=$vspace=$border=$inline_height=$inline_width='';
		// style, height, width, longdesc  

		// class
    	if(preg_match('#class=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$class = $temp[2];
			$imgraw = preg_replace('#class=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		
		// style
    	if(preg_match('#style=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$style = $temp[2];
			$imgraw = preg_replace('#style=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		
		// echo "DEBUG1: ".htmlspecialchars($style)." <br>";				
		
		// border
    	if(preg_match('#\bborder\b=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$border = $temp[2];
			$imgraw = preg_replace('#\bborder\b=(["\'])(.*?)\\1#i', "", $imgraw);
    	} elseif (preg_match('#\bborder\b:\s*([0-9]*)#i', $style, $temp)) {
			$border = $temp[1];
			$style = preg_replace('#\bborder\b:\s*([0-9]*)#i', "", $style);
		}
		// echo "DEBUG: ".htmlspecialchars($border)."<br/>";
		// echo "DEBUG2: ".htmlspecialchars($style)." <br>";				

		
		// margin
    	// $margin = preg_match('#(\s?margin[^:"]*\s*:\s*[^;"]*[;]?)#i', $imgraw, $temp) ?	$temp[1] : '';

/*     	if(preg_match('#float\s*:\s*(\w+)#i', $imgraw, $temp)) {
    		$align = $temp[1];
    	}
 */


    	// align
    	if(preg_match('#align=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$align = $temp[2];
			$imgraw = preg_replace('#align=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		
    	// alt 
    	if(preg_match('#alt=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$alt = $temp[2];
			$imgraw = preg_replace('#alt=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		$orgAlt=$alt;
		
		// title
    	if(preg_match('#title=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$title = $temp[2];
			$imgraw = preg_replace('#title=(["\'])(.*?)\\1#i', "", $imgraw);
    	}
		$orgTitle = $title;
		
		// $imgid = '';
    	// if(preg_match('#id=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		// $imgid = $temp;
			// $imgraw = preg_replace('#id=(["\'])(.*?)\\1#i', "", $imgraw);
    	// }

		// hspace
		if (preg_match('#hspace=(["\'])(.*?)\\1#i', $imgraw, $temp) ) {
			$hspace = $temp[2];
			$imgraw = preg_replace('#hspace=(["\'])(.*?)\\1#i', "", $imgraw);
		}

		// vspace
		if (preg_match('#vspace=(["\'])(.*?)\\1#i', $imgraw, $temp) ) {
			$vspace = $temp[2];
			$imgraw = preg_replace('#vspace=(["\'])(.*?)\\1#i', "", $imgraw);
		}

		// height
		if(preg_match('#\bheight\b=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$inline_height = $temp[2];
			$imgraw = preg_replace('#\bheight\b=(["\'])(.*?)\\1#i', "", $imgraw);
    	} elseif (preg_match('#(^|;)\s*\bheight\b:\s*([0-9]*)px;#i', $style, $temp)) {
			$inline_height = $temp[2];
			$style = preg_replace('#(^|;)\s*\bheight\b:\s*[^;]*;#i', '\1', $style);
		}
		// 

		// width
		if(preg_match('#\bwidth\b=(["\'])(.*?)\\1#i', $imgraw, $temp)) {
    		$inline_width = $temp[2];
			$imgraw = preg_replace('#\bwidth\b=(["\'])(.*?)\\1#i', "", $imgraw);
    	} elseif (preg_match('#(^|;)\s*\bwidth\b:\s*([0-9]*)px;#i', $style, $temp)) {
			$inline_width = $temp[2];
			$style = preg_replace('#(^|;)\s*\bwidth\b:\s*[^;]*;#i', '\1', $style);
		}
		
		// echo "DEBUG: $inline_width $inline_height $style <br>";
		

    	$this->set_sys_limits();

    	// Process alt parameter of image that is used for multithumb instructions as prefix separated by ":"
    	$this->set_popup_type($title, $alt);

    	// echo "<b>DEBUG: $this->popup_type</b><br />";

		// Ignore images where mt_ignore specified in the alt text
		if ( $this->popup_type=='ignore' ) {
			$call_counter--;
			return  preg_replace('/mt_ignore\s*:\s*/', '', $imgraw);
		}

    	// Change image path from relative to full
    	$this->fix_path($imgloc, $imgurl);

    	// It's a gallery
    	if($this->popup_type=='gallery'){
			$call_counter--;
    		$res = $this->gallery($imgloc, $imgurl, $alt, $title, "center", $class  );
			if ( $res === false ) {
				return $imgrawOrg;
			} else {
				return $res;
			}
    	}
		
		$this->imgnum++;

		// echo "DEBUG:$imgurl $this->is_article $class<br />";
		
		if( ( ( $this->is_article and /* preg_match('/{multithumb([^}]*)}/is', $row->text)==0 and */ $this->_params->get('enable_thumbs') == 4 ) and  
			( !$this->_params->get('only_classes') or !preg_match  ( '/\b'.$this->_params->get('only_classes').'\b/', $class ) ) ) 
			or
			( ( !$this->is_blog and $this->_params->get('enable_blogs') == 4 ) and  
			( !$this->_params->get('only_classes_blog') or !preg_match  ( '/\b'.$this->_params->get('only_classes_blog').'\b/', $class ) ) ) 
			
			
			) {
				$this->popup_type = 'nothumb';
		}
		
		// Resize or watermark full image
		$imgurlOrg = $imgurl;
			
		// Full image size
    	$full_width  = $this->_params->get('full_width');
    	$full_height = $this->_params->get('full_height');

    	if( !$this->_params->get('resize')) {
    		$full_width = $full_height = 0;
    	}
		
    	// Resize image and/or set watermark
    	$imgtemp = $this->botmt_thumbnail($imgurl, $full_width, $full_height, $this->_params->get('image_proportions'), hexdec($this->_params->get('image_bg')), (int)($this->_params->get('watermark_type') >= 1), 'images', 0 , /* $size, */ $this->_params->get('img_type', "") );

    	// If image resized or watermarked use it instead of the original one
    	if($imgtemp) {
    		$imgurl = $imgtemp;
    		$real_width = $full_width;
    		$real_height = $full_height;
			preg_match('/(.*src=["\'])[^"\']+(["\'].*)/i', $imgraw, $parts);
			$imgraw = $parts[1].$imgurl.$parts[2];
    	} else {
    		$real_width = $full_width;
    		$real_height = $full_height;
		}

		// Ignore too small images
		list ($min_img_width, $min_img_height) = $this->parse_size($this->_params->get('min_img_size', '20x20'));		
		if( !( ( $real_width==0 || $real_height==0 ||
				 ( $min_img_width  && ( $min_img_width  < $real_width ) ) ||
				 ( $min_img_height && ( $min_img_height < $real_height ) ) )  ) ) {
			$call_counter--;
			return $imgrawOrg;  // just show the full image
		}			
			
		$imgraw = preg_replace('/(src=["\'])[^"\']+(["\'])/i', '', $imgraw);
		$imgraw = preg_replace('/^<img/', '', $imgraw);
		$imgraw = preg_replace('#/*>$#', '', $imgraw);
		
		// echo "DEBUG".htmlspecialchars($imgraw)."<br/>";
		 
		// Don't continue processing of nothumb images
		// if ( $this->popup_type=='nothumb' ) {
			// $call_counter--;
			// return  preg_replace('/mt_nothumb*\s*:\s*/', '', $imgraw);
		// }

		$thumb_width = $this->_params->get('thumb_width');
		$thumb_height = $this->_params->get('thumb_height');

		if ( $this->_params->get('thumb_size_first') && $this->imgnum <= $this->_params->get('leading_num', 1) ) {
			list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('thumb_size_first'));
		}

		if ( $this->is_blog ) {
			if ( $this->_params->get('blog_size_leading') && 
				$this->cont_num && 
				($this->cont_num <= $this->_params->get('blog_leading_num', 1) ) ) {
				list ($thumb_width, $thumb_height) = $this->parse_size($this->_params->get('blog_size_leading'));
			}
		}

		if ( (( $this->is_article and $this->_params->get('use_image_size')) or 
		      ($this->is_blog and $this->_params->get('use_image_size_blog')) ) and ($inline_width or $inline_height ) ) {
			$thumb_width  = 0;
			$thumb_height = 0;
			if ($inline_width) {
				$thumb_width = $inline_width;
			}
			if ( $inline_height ) {
				$thumb_height = $inline_height;
			}
			// $this->popup_type = 'none';
		}

		
		// $real_width && $real_height && 
	    // No thumbing for images smaller than the thumbnails
		
		// echo "DEBUG0: ($thumb_width  && ($thumb_width  < $real_width)  ($thumb_height && ($thumb_height < $real_height)  $this->popup_type ".$this->_params->get('force_popup')."<br/>";
    	if( !( ( $real_width==0 || $real_height==0 ||
				 ($thumb_width  && ($thumb_width  < $real_width) ) ||
				 ($thumb_height && ($thumb_height < $real_height) )  ) /* &&
			   $this->_params->get('enable_thumbs') */ ) ) {
				
			   
				if ($this->_params->get('force_popup') /* || ($thumb_width==0 && $thumb_height ==0 ) */ ) {
					$thumb_width = $real_width;
					$thumb_height = $real_height;
				} else {
					$this->popup_type = 'none';
				}
    		// $img = $imgraw;  // just show the full image
			// 
			// 
    	} /* else */ 

		
		{
    		// Process the varius popup methods

    		// Create thumb
			if ( $this->popup_type == 'nothumb' ) {
				$thumb_file = $imgurl;
				
				if ( $inline_width or $inline_height ) {
					$thumb_size = '';
					
					if ($inline_width) {
						$thumb_width = $inline_width;
						$thumb_size .= ' width="'. $thumb_width .'"';
					}
					if ( $inline_height ) {
						$thumb_height = $inline_height;
						$thumb_size .= ' height="'. $thumb_height .'"';
					}
				} elseif ( $real_width and $real_height ) {
					$thumb_width = $real_width;
					$thumb_height = $real_height;
					$thumb_size = ' width="'. $thumb_width .'" height="'. $thumb_height .'"';
				} else {
					$thumb_size = '';
				}

			} else {
				// echo "DEBUG:".$imgloc." ".$this->popup_type."<br />";
				// if ( !$this->create_thumb($imgloc, $thumb_file, $thumb_size, $thumb_width, $thumb_height, /* size_only*/ $this->popup_type == "expando", $size) ) {
				// echo "DEBUG0:$imgloc<br/>";

				$zoomin = ( $this->_params->get('magnify_type') == 2 or $this->_params->get('magnify_type') == 3 ) && 
					( $this->popup_type != "nothumb" ) && 
					( $this->popup_type != "none" ) && 
					( $this->popup_type != "expando" );
				

				$iptc_caption = '';
				
				$temp_file = $this->botmt_thumbnail($imgurlOrg, $thumb_width, $thumb_height, $this->_params->get('thumb_proportions'), hexdec($this->_params->get('thumb_bg')), (int)($this->_params->get('watermark_type') == 2), 'thumbs', $this->popup_type == "expando" , /* $size, */ $this->_params->get('img_type', ""), $zoomin, $iptc_caption );

				// Define caption text
				$this->caption = $this->set_caption($alt, $title, $iptc_caption, 
					substr(basename($imgurl), 0, strlen(basename($imgurl))-4) );
				
				/* // Ignore too small images
				list ($min_img_width, $min_img_height) = $this->parse_size($this->_params->get('min_img_size', '20x20'));		
				if( !( ( $thumb_width==0 || $thumb_height==0 ||
						 ( $min_img_width  && ( $min_img_width  < $thumb_width ) ) ||
						 ( $min_img_height && ( $min_img_height < $thumb_height ) ) )  ) ) {
					$call_counter--;
					return $imgrawOrg;  // just show the full image
				} */	
			
				if ( $temp_file ) {
					$thumb_file = $temp_file;
					$thumb_size = ' width="'. $thumb_width .'" height="'. $thumb_height .'"';
					// echo "DEBUG1:$thumb_file<br/>";
					// return true;
				} else {
					$thumb_file = $imgurl;
					$thumb_size = '';
					if ( $thumb_width ) {
						$thumb_size .= ' width="'. $thumb_width .'" ';
					}
					if ( $thumb_height ) {
						$thumb_size .= ' height="'. $thumb_height .'" ';
					}
					
					// $thumb_size = ' width="'. $thumb_width .'" height="'. $thumb_height .'"';
					// echo "DEBUG2:$thumb_file<br/>";
					// return $imgraw;
				}
			}

			// echo "DEBUG1:$this->popup_type $imgurl<br />";

			// Include java scripts
    		$this->botAddMultiThumbHeader($this->popup_type);

			//
			// Build popup tag
			//

			if ( $this->_params->get('magnify_type') == 1 or $this->_params->get('magnify_type') == 3 /* and 
					$this->popup_type != 'nothumb' and 
					$this->popup_type != 'none' */ ) {
				$cursor_style = 'style="cursor: url(\''.$this->_live_site.'/plugins/content/multithumb/magnify.cur\'), auto;"';
			} else {
				$cursor_style = "";
			}
			
			switch ( $this->_params->get('group_images', 0) ) {
				case 1:
/* 					if ( $this->is_gallery ) {
						$rel = $alt;
					}
 */					$rel = $this->rowid;
					break;
				case 2:
					$rel = 'page';
					break;
				default:
					static $i = 0;
					$rel = $i++;
			}

			if ( $this->is_gallery and $this->_params->get('group_images_gal')) {
				$rel = $alt;
			}
			
			// echo "DEBUG:$imgurl<br>";
			// Start popup link
    		switch($this->popup_type) {
    			case 'normal': // Normal popup
    				/* $imgtemp  */ $img = "<a target=\"_blank\" href=\"$imgurl\" onclick=\"this.target='_self';this.href='javascript:void(0)';thumbWindow( '".JURI::base(false)."' , '$imgurl','$alt',$real_width,$real_height,0,".$this->_params->get('allow_img_toolbar').");\" ".$cursor_style." >";
    				break;
				case 'iLoad': 
					if ( $this->_params->get('group_images', 0) or $this->is_gallery) {
						$rel = $this->_params->get('iload_splitSign', '|').$rel;
					} else {
						$rel = '';
					}
					$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.$rel.'" title="'.$this->caption.'" '.$cursor_style.' >';
					break;
				case 'thumbnail':
					$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.'" title="'.$this->caption.'" '.$cursor_style.' >';
					break;
				case 'modal':
					if ( !($this->caption_pos && $this->caption) ) {
						$img = '<a target="_blank" href="'.$imgurl.'" class="'.$this->popup_type.'" title="'.$this->caption.'" '.$cursor_style.' >';
					} else { 
						$img = '<a target="_blank" href="'.$imgurl.'" title="'.$this->caption.'" '.$cursor_style.' >';
						$class .= " $this->popup_type ";
					}
					break;
    			case 'slimbox': // Slimbox
					$this->popup_type = 'lightbox';
    			case 'lightbox': // Lightbox
    			case 'shadowbox': 
    			case 'prettyPhoto': 
    				$img = '<a target="_blank" href="'.$imgurl.'" rel="'.$this->popup_type.'['.$rel.']" title="'.$this->caption.'" '.$cursor_style.' >';
    				break;
    			case 'greybox': // Greybox
    				$img = '<a target="_blank" href="'.$imgurl.'" rel="gb_imageset['.$rel.']" title="'.$this->caption.'" '.$cursor_style.' >';
    				break;
    			case 'expansion': // Thumbnail expansion
    				$thumb_size = ''; // No size attr for thumbnail expansion!
    				$img = '<a href="javascript:void(0);">';
    				$onclick = "onclick=\"return multithumber_expand(this)\"";
    				$onclick .= "lowsrc=\"$imgurl\" ".$cursor_style." ";
    				// $thumb_file = '';
    				// $class .= " expando";
    				break;
    			case 'expando': // Thumbnail expansion
    				// $size = ''; // No size attr for thumbnail expansion!
    				// $onclick = "onclick=\"return multithumber_expand(this)\"";
    				// $onclick .= "lowsrc=\"${imgurl}\"";
    				$thumb_file = '';
    				$class .= " expando";
    				// $onclick = "rel=\"expando\"";
    				break;
    			// case 'thickbox': // Thickbox
    				// /* $imgtemp  */ $img = '<a target="_blank" href="'.$imgurl.'" rel="'.$alt.'" alt="'.$this->caption.'" title="'.$this->caption.'" class="thickbox">';
    				// if($this->_params->get('max_thumbnails')) {
    					// if(!isset($this->mt_thumbnail_count[$alt])) {
    						// $this->mt_thumbnail_count[$alt] = 0;
    					// }
    					// $this->mt_thumbnail_count[$alt]+=1;
    					// if($this->mt_thumbnail_count[$alt]>$this->_params->get('max_thumbnails')) {
							// $call_counter--;
    						// return /* $imgtemp  */ $img."</a>\n";
    					// }
    				// }
    				// break;
				case 'nothumb':
    			case 'none': // No popup, just thumbnail
    			default:
    				/* $imgtemp  */ $img = '';
			}
			
    		switch($this->popup_type) {
    			case 'iLoad': // Normal popup
     			case 'lightbox': // Lightbox
    			case 'shadowbox': 
    			case 'prettyPhoto':
				case 'greybox':				
					if($this->_params->get('max_thumbnails')) {
						if(!isset($this->mt_thumbnail_count[$rel])) {
    						$this->mt_thumbnail_count[$rel] = 0;
    					}
    					$this->mt_thumbnail_count[$rel]+=1;
    					if($this->mt_thumbnail_count[$rel]>$this->_params->get('max_thumbnails')) {
							if ( $this->_params->get('more_images', 0) and 
								 $this->mt_thumbnail_count[$rel]-1 == $this->_params->get('max_thumbnails') ) {
								$img .= $this->_params->get('more_images_text', JText::_("More images..."));
							}
							$call_counter--;
    						return /* $imgtemp  */ $img."</a>";
    					}
    				}
					break;
			}

			//Img class
			if ( $this->popup_type != 'nothumb' /* and 
					$this->popup_type != 'none' */ ) {
				if ( $this->is_gallery ) {
					$class .= " ".$this->_params->get('gallery_class');
				} else {
					$class .= " ".$this->_params->get('thumbclass');
				}
				
			}
			
			$class = trim($class);
			$imgclass = '';
			if( $class ) {
				$imgclass = 'class="'.$class.'" ';
			}
			
			if($thumb_file) { // If thumb generated show it
				$img .= '<img src="'.$thumb_file.'" '.$imgraw." ";
			} else { // If thumb is not generated row image
				$img .= '<img src="'.$imgurl.    '" '.$imgraw." ";
			}
			
			
			/* if($thumb_file) { // If thumb generated show it
				$img .= '<img style="background:url('.$imgurl.') no-repeat"  src="http://www.phytozome.net/images/magnifyGlass3.gif"  ';
			} else { // If thumb is not generated row image
				$img .= '<img style="background:url('.$imgurl.') no-repeat"  src="http://www.phytozome.net/images/magnifyGlass3.gif" ';
			} */
			
			
			
			$img .= $thumb_size.'  '.$onclick.' ';
			
			if ( !($this->caption_pos && $this->caption) ) {
				$img .= $imgclass.' ';
			}
			
			// $img .= $imgid." ";

			// Border 
			$bordercss = '';
			
			if( $this->_params->get('border_style')!='none'  ) {

				$style = preg_replace('#border\s*:\s*[^;]*;#i', '', $style);
				$border = '';
				if( $this->_params->get('border_style')!='remove' ) {
					$bordercss = 'border: '.$this->_params->get('border_size').' '.$this->_params->get('border_style').' '.$this->_params->get('border_color').';';
					$style .= $bordercss;
			
				}
			}
			
			// if ( $this->_params->get('magnify_cursor', 1) and 
					// $this->popup_type != 'nothumb' and 
					// $this->popup_type != 'none' ) {
				// $style .= 'cursor: url(\''.$this->_live_site.'/plugins/content/multithumb/magnify.cur\'), -moz-zoom-in; ';
			// }

			// margin
/* 			if (!$margin && ($hspace || $vspace) ) {
				$margin = "margin:";
				if ($vspace) {
					$margin .= $vspace."px ";
				} else {
					$margin .= "0px ";
				}
				
				if ($hspace) {
					$margin .= $hspace."px ;";
				} else {
					$margin .= "0px ;";
				}
			} */
			
			// style
			// $style = "";
			// $style .= $align? "float:".$align.";" : ''; 
			// $style .= $margin ? "$margin" : ''; 
			if ( $style ) {
				$style='style="'.$style.'"';
			}
			
			if ( $align ) {
				$align='align="'.$align.'"';
			}
			
			if ( $hspace ) {
				$hspace='hspace="'.$hspace.'"';
			}
			
			if ( $vspace ) {
				$vspace='vspace="'.$vspace.'"';
			}
			
			if ( !$alt ) {
				$alt=$title;
			}
		
			if ( !$alt ) {
				$alt=basename($thumb_file);
			}
						
			if ( $alt ) {
				$alt='alt="'.$alt.'"';
			}
			

			if ( $title ) {
				$title='title="'.$title.'"';
			}
		
			if ( $border != "" ) {
				$border='border="'.$border.'"';
			}
		
			// if no caption image will have border propertiese
			if ( !($this->caption_pos && $this->caption) ) {
				$img .= " $style $align $hspace $vspace $border";
			}

			// finishing image
			$img .= " $alt $title />";
			
			// finishing popup link
			switch($this->popup_type) {
				case 'expando':
				case 'nothumb';
    			case 'none': // No popup, just thumbnail
					break;
				default:
					$img .= '</a>';
			}
			// if ( $this->popup_type!='prettyPhoto' ) {
				// $img .= '</span>';
			// }


			// caption processing
			if ($this->caption_pos && $this->caption) {

				// W/A 
				$style = preg_replace('#display\s*:\s*block\s*;#i', '', $style);

				$caption_style = ' style=" ';
				if ( $this->caption_pos == "left" || $this->caption_pos == "right" ) {
					$caption_style .= "height:".$thumb_height."px; width:0px;"; 
				} else {
					$caption_style .= "width:".$thumb_width."px; height:0px;"; 
				}
				$caption_style .= '"';
				
				
				switch ($this->caption_pos) {
					case "left":
					// display:inline; border: 0; 
$img = '<table '.$imgclass.' cellspacing="0" cellpadding="0" '.$style.' '.$align.' '.$hspace.' '.$vspace.'  '.$border.' ><tr><td class="'.$this->_params->get('caption_class').'" '.$caption_style.' >'.$this->caption.'</td><td >'.$img.'</td></tr></table>';
						break;
					case "right":
$img = '<table '.$imgclass.' cellspacing="0" cellpadding="0" '.$style.' '.$align.' '.$hspace.' '.$vspace.'  '.$border.' ><tr><td >'.$img.'</td><td class="'.$this->_params->get('caption_class').'" '.$caption_style.' >'.$this->caption.'</td></tr></table>';
						break;
					case "top":
$img = '<table '.$imgclass.' cellspacing="0" cellpadding="0" '.$style.' '.$align.' '.$hspace.' '.$vspace.'  '.$border.' ><tr><td class="'.$this->_params->get('caption_class').'" '.$caption_style.' >'.$this->caption.'</td></tr><tr><td >'.$img.'</td></tr></table>';
						break;
					case "bottom":
$img = '<table '.$imgclass.' cellspacing="0" cellpadding="0" '.$style.' '.$align.' '.$hspace.' '.$vspace.'  '.$border.' ><tr><td >'.$img.'</td></tr><tr><td class="'.$this->_params->get('caption_class').'" '.$caption_style.' >'.$this->caption.'</td></tr></table>';
						break;
				}

			}

		}

		// article link for blog
		// echo "DEBUG:".$this->_params->get('blog_mode')."<br>";
		if( $this->_params->get('blog_mode')=='link' ) {
			$img = $this->bot_mt_makeFullArticleLink($img, $orgAlt, $orgTitle );
		}
		
		$call_counter--;
		return $img;
	}
	
	
	function create_watermark($sourcefile_id ,  $watermarkfile, 
								$horiz_position, $horiz_shift, 
								$vert_position, $vert_shift, 
								$transparency_type = 'alpha', $transcolor = false, $transparency = 100 ) {
		static $disable_wm_ext_warning, $disable_wm_load_warning, $disable_alpha_warning;
		
		// $watermarkfile = $this->_params->get('watermark_file');
		// $horiz_shift = $this->_params->get('watermark_left'); 
		// $vert_shift = $this->_params->get('watermark_top');
		
		if($transparency_type == 'alpha') {
			$transcolor = FALSE;
		} // else {
			// $transcolor = $this->_params->get('transparent_color');
		// }
		// $transparency = $this->_params->get('transparency');
		

		//Get the resource ids of the pictures
		$fileType = strtolower(substr($watermarkfile, strlen($watermarkfile)-3));
		switch($fileType) {
			case 'png':
				$watermarkfile_id = @imagecreatefrompng($watermarkfile);
				break;
			case 'gif':
				$watermarkfile_id = @imagecreatefromgif($watermarkfile);
				break;
			case 'jpg':
				$watermarkfile_id = @imagecreatefromjpeg($watermarkfile);
				break;
			default:
				$watermarkfile = basename($watermarkfile);
				if(!$disable_wm_ext_warning) $this->multithumb_msg .= "You cannot use a .$fileType file ($watermarkfile) as a watermark<br />\\n";
				$disable_wm_ext_warning = true;
				return false;
		}
		if(!$watermarkfile_id) {
			if(!$disable_wm_load_warning) $this->multithumb_msg .= "There was a problem loading the watermark $watermarkfile<br />\\n";
			$disable_wm_load_warning = true;
			return false;
		}

		@imageAlphaBlending($watermarkfile_id, false);
		$result = @imageSaveAlpha($watermarkfile_id, true);
		if(!$result) {
			if(!$disable_alpha_warning) $this->multithumb_msg .= "Watermark problem: your server does not support alpha blending (requires GD 2.0.1+)<br />\\n";
			$disable_alpha_warning = true;
			imagedestroy($watermarkfile_id);
			return false;
		}

		//Get the sizes of both pix
		$sourcefile_width=imageSX($sourcefile_id);
		$sourcefile_height=imageSY($sourcefile_id);
		$watermarkfile_width=imageSX($watermarkfile_id);
		$watermarkfile_height=imageSY($watermarkfile_id);

		switch ($horiz_position) {
		case 'center':
			$dest_x = ( $sourcefile_width / 2 ) - ( $watermarkfile_width / 2 );
			break;
		case 'left':
			$dest_x = $horiz_shift;
			break;
		case 'right':
			$dest_x = $sourcefile_width - $watermarkfile_width + $horiz_shift;
		}

		switch ($vert_position) {
		case 'middle':
			$dest_y = ( $sourcefile_height / 2 ) - ( $watermarkfile_height / 2 );
			break;
		case 'top':
			$dest_y = $vert_shift;
			break;
		case 'bottom':
			$dest_y = $sourcefile_height - $watermarkfile_height + $vert_shift;
			break;
		}
		// echo "DEBUG:  $horiz_position $horiz_shift $dest_x $vert_position $vert_shift $dest_y<br>";
			
		// if a gif, we have to upsample it to a truecolor image
		if($fileType == 'gif') {
			// create an empty truecolor container
			$tempimage = imagecreatetruecolor($sourcefile_width, $sourcefile_height);

			// copy the 8-bit gif into the truecolor image
			imagecopy($tempimage, $sourcefile_id, 0, 0, 0, 0, $sourcefile_width, $sourcefile_height);

			// copy the source_id int
			$sourcefile_id = $tempimage;
		}

		if($transcolor!==false) {
			imagecolortransparent($watermarkfile_id, $transcolor); // use transparent color
			imagecopymerge($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height, $transparency);
		}
		else
		imagecopy($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height); // True alphablend

		imagedestroy($watermarkfile_id);
			
	}
	
	function calc_size($origw, $origh, &$width, &$height, &$proportion, &$newwidth, &$newheight, &$dst_x, &$dst_y, &$src_x, &$src_y, &$src_w, &$src_h) {
		
		/* if(!($width || $height)) { // Both sides zero
			$proportion='bestfit';
		} elseif(!($width && $height)){ // One of the sides zero
			$proportion = 'bestfit';
		} */

		if(!$width ) {
			$width = $origw;
			// $newwidth = $width;
		}

		if(!$height ) {
			$height = $origh;
			// $newheight = $height;
		}

		$dst_x = $dst_y = $src_x = $src_y = 0;

		if (  $proportion == 'stretch' ) {
			$src_w = $origw;
			$src_h = $origh;
			$newwidth = $width;
			$newheight = $height;
			return;
		}
				
		if ( $height > $origh ) {
			$newheight = $origh;
			$height = $origh;
		} else {
			$newheight = $height;
		}
		
		if ( $width > $origw ) {
			$newwidth = $origw;
			$width = $origw;
		} else {
			$newwidth = $width;
		}
		
		
		// echo "DEBUG $proportion<br />";

		switch($proportion) {
			case 'fill':
			case 'transparent':
				$xscale=$origw/$width;
				$yscale=$origh/$height;

				// Recalculate new size with default ratio
				if ($yscale<$xscale){
					$newheight =  round($origh/$origw*$width);
					$dst_y = round(($height - $newheight)/2);
				} else {
					$newwidth = round($origw/$origh*$height);
					$dst_x = round(($width - $newwidth)/2);

				}

				$src_w = $origw;
				$src_h = $origh;
				break;

			case 'crop':

				$ratio_orig = $origw/$origh;
				$ratio = $width/$height;
				if ( $ratio > $ratio_orig) {
					$newheight = round($width/$ratio_orig);
					$newwidth = $width;
				} else {
					$newwidth = round($height*$ratio_orig);
					$newheight = $height;
				}
					
				$src_x = ($newwidth-$width)/2;
				$src_y = ($newheight-$height)/2;
				$src_w = $origw;
				$src_h = $origh;				
				break;
				
 			case 'only_cut':
				// }
				$src_x = round(($origw-$newwidth)/2);
				$src_y = round(($origh-$newheight)/2);
				$src_w = $newwidth;
				$src_h = $newheight;
				
				// echo "DEBUG: $origw-$newheight  $newwidth-$origh  $src_x $src_y $src_w $src_h <br/>";
				break; 
				
			case 'bestfit':
				$xscale=$origw/$width;
				$yscale=$origh/$height;

				// Recalculate new size with default ratio
				if ($yscale<$xscale){
					$newheight = $height = round($width / ($origw / $origh));
				}
				else {
					$newwidth = $width = round($height * ($origw / $origh));
				}
				$src_w = $origw;
				$src_h = $origh;	
				
				break;
			}

		// echo "DEBUG:ORG: $origw x $origh  NEW: $newwidth x $newheight<br>";	
	}
	
	function botmt_thumbnail($filename, &$width, &$height, $proportion='bestfit', $bgcolor = 0xFFFFFF, 
							$watermark = 0, $dest_folder = 'thumbs', $size_only = 0, /* $size = 0, */ $img_type = "", $zoomin = 0, 
							&$iptc_caption = NULL ) {
							
		static $disablegifwarning, $disablepngwarning, $disablejpgwarning, $disablepermissionwarning;
		$ext = pathinfo($filename , PATHINFO_EXTENSION ); // BK
		// $base = pathinfo($filename , PATHINFO_BASENAME ); // BK
		// $ext = $temp['extension']; //todo: check filename here
		
		$prefix = '';
		// if($width || $height) {
			$prefix = substr($proportion,0,1) . "_".$width."_".$height."_".$bgcolor."_";
		// }
		
		$prefix .= $watermark.(int)$zoomin."_";
		
		if ( $img_type ) {
			$thumb_ext = $img_type;
		} else {
			$thumb_ext = $ext;
		}
		
		$alt_filename = '';
		// echo "DEBUG: Oops1 $filename <br />";		
		if($dest_folder=='thumbs') {
			$alt_filename = substr($filename, 0, -(strlen($ext)+1)) . '.thumb.' . $ext;
			if(file_exists($alt_filename)) {
				$filename = $alt_filename;
				// $size = 0;
			} /* // Removed because collision with mod_roknewspager
				else { 
				$alt_filename = substr($filename, 0, -(strlen($ext)+1)) . '_thumb.' . $ext;

				if(file_exists($alt_filename)) {
					$filename = $alt_filename;
					$size = 0;
				} 				
			} */
		}
		$dest_folder='thumbs';
		
		$thumb_file = $prefix . str_replace(array( JPATH_ROOT, ':', '/', '\\', '?', '&', '%20', ' '),  '_' ,substr($filename, 0, -(strlen($ext)+1)).'.'.$thumb_ext); // BK
		// echo "DEBUG:$thumb_file<br />";
		if ( isset( $this ) ) {
			switch($this->_params->get('scramble')) {
				case 'md5': $thumb_file = md5($thumb_file) . '.' . $thumb_ext; break;
				case 'crc32': $thumb_file = sprintf("%u", crc32($thumb_file)) . '.' . $thumb_ext;
			}
		}
		
		$thumbname = JPATH_CACHE. DS ."multithumb_$dest_folder".DS."$thumb_file"; // BK
		// echo "DEBUG:$thumbname<br>";
		if(file_exists($thumbname))	{
			$size = @getimagesize($thumbname);
			if($size) {
				$width = $size[0];
				$height = $size[1];
			}
			if ( isset( $this ) and ( $this->_params->get('caption_type_iptc') or $this->_params->get('caption_type_gallery_iptc') ) ) {
				$iptc_caption = file_get_contents($thumbname.".iptc_caption.txt");
			}
			return JURI::base(false)."cache". "/" ."multithumb_$dest_folder". "/" . basename($thumbname); // BK
		}		

		/*if (  strpos($filename, JPATH_CACHE. "/multithumb_") ) {
			return false;
		}*/
		
		
	
		
		// if ( !$size ) {
			$info = NULL;
			
			$size = @getimagesize($filename, $info);
			if(!$size) {
				if ( isset( $this ) ) {
					$this->multithumb_msg .= "There was a problem loading image $filename<br/>\\n";
				}
				return false;
			}


		// }

		$origw = $size[0];
		$origh = $size[1];
		// if( /* $alt_filename && */ ($origw<$width && $origh<$height)) { // BK
			// $width = $origw;
			// $height = $origh;
			// if ( !$watermark ) {
				// return false; 
			// }
		// }

		{
	
			plgContentMultithumb::calc_size($origw, $origh, $width, $height, $proportion, $newwidth, $newheight, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
			// echo "DEBUG:origw:$origw, origh:$origh, width:$width, height:$height, proportion:$proportion, newwidth:$newwidth, newheight:$newheight, dst_x:$dst_x, dst_y:$dst_y, src_x:$src_x, src_y:$src_y, src_w:$src_w, src_h:$src_h <br >";

			// echo "DEBUG: Oops2 $filename <br />";		
			if ( $size_only ) { // TODO. Avoid access to thumb for $size_only
				return true;
			}

			switch(strtolower($size['mime'])) {
				case 'image/png':
					$imagecreatefrom = "imagecreatefrompng";
					break;
				case 'image/gif':
					$imagecreatefrom = "imagecreatefromgif";
					break;
				case 'image/jpeg':
					$imagecreatefrom = "imagecreatefromjpeg";
					break;
				default:
					if ( isset( $this ) ) {
						$this->multithumb_msg .= "Unsupported image type $filename ".$size['mime']."<br />\\n";
					}
					return false;
			}

			
			if ( !function_exists ( $imagecreatefrom ) ) {
				if ( isset( $this ) ) {
					$this->multithumb_msg .= "Failed to process $filename. Function $imagecreatefrom doesn't exist.<br />\\n";
				}
				return false;
			}
			$src_img = $imagecreatefrom($filename);
			if (!$src_img) {
				if ( isset( $this ) ) {
					$this->multithumb_msg .= "There was a problem to process image $filename ".$size['mime']."<br />\\n";
				}
				return false;
			}
			
			$dst_img = ImageCreateTrueColor($width, $height);
			
			// $bgcolor = imagecolorallocatealpha($image, 200, 200, 200, 127);
			
			imagefill( $dst_img, 0,0, $bgcolor);
			if ( $proportion == 'transparent' ) {
				imagecolortransparent($dst_img, $bgcolor);
			}
			
			imagecopyresampled($dst_img,$src_img, $dst_x, $dst_y, $src_x, $src_y, $newwidth, $newheight, $src_w, $src_h);
			// echo "DEBUG: dst_x:$dst_x, dst_y:$dst_y, src_x:$src_x, src_y:$src_y, dst_w:$newwidth,dst_h:$newheight,src_w:$origw, src_h:$src_h<br>";

			// watermark image
			if(isset( $this ) && $watermark) {
				// $this->watermark($dst_img);watermark_vert_type
				$this->create_watermark($dst_img, $this->_params->get('watermark_file'), 
					$this->_params->get('watermark_horiz_type', 'center'), $this->_params->get('watermark_left', 0), 
					$this->_params->get('watermark_vert_type', 'middle'),  $this->_params->get('watermark_top', 0), 
					$this->_params->get('transparency_type'), $this->_params->get('transparent_color'), $this->_params->get('transparency'));

			}
			
			if ( isset( $this ) && $zoomin ) {
				$this->create_watermark($dst_img, $this->_params->get('zoomin_file', 'plugins/content/multithumb/zoomin.png'), 
					'right', 0,
					'bottom', 0
					// $this->_params->get('watermark_horiz_type', 'center'), $this->_params->get('watermark_left', 0), 
					// $this->_params->get('watermark_vert_type', 'middle'),  $this->_params->get('watermark_top', 0), 
					// $this->_params->get('transparency_type'), $this->_params->get('transparent_color'), $this->_params->get('transparency')
					);
			}
			
		// Make sure the folder exists
			
			
			switch(strtolower($thumb_ext)) {
				case 'png':
					$imagefunction = "imagepng";
					break;
				case 'gif':
					$imagefunction = "imagegif";
					break;
				default:
					$imagefunction = "imagejpeg";
			}
			

			if($imagefunction=='imagejpeg') {
				$result = $imagefunction($dst_img, $thumbname, isset($this) ? $this->_params->get('quality') : 80 );
			} else {
				$result = $imagefunction($dst_img, $thumbname);
			}

			if(!$result) {
				// BK This code proceses the case when cache storage is not created yet.
				// It means that first time a first image will not be creted.
				// If the folder doesn't exist try to create it
				$dir = JPATH_CACHE.DS."multithumb_".$dest_folder;
		        // echo "DEBUG: $dir<br>";
				if (!is_dir($dir)) {

					// Make sure the index file is there
					$indexFile      = $dir . DS . 'index.html';
					$mkdir_rc = mkdir($dir) && file_put_contents($indexFile, '<html><body bgcolor="#FFFFFF"></body></html>');

					
					if( !$mkdir_rc && !$disablepermissionwarning) {
						isset( $this ) && $this->multithumb_msg .= "Could not create image storage: ".$dir."<br />\\n"; // BK
					}
					
					if($imagefunction=='imagejpeg') {
						$result = $imagefunction($dst_img, $thumbname, $this ? $this->_params->get('quality') : 80 );
					} else {
						$result = $imagefunction($dst_img, $thumbname);
					}
					
				}
				
				if ( !$result ) {
					if(!$disablepermissionwarning) {
						isset( $this ) && $this->multithumb_msg .= "Could not create image:\\n$thumbname.\\nCheck if you have write permissions in ".JPATH_CACHE.DS."multihumb_$dest_folder".DS."<br />\\n"; // BK
					}
					$disablepermissionwarning = true;
				}
			
			} else {
				imagedestroy($dst_img);
			}
			imagedestroy($src_img);
			
			$iptc_caption = '';
			if ( $info && isset($info["APP13"])) { 
				$iptc = iptcparse($info["APP13"]); 
				if (is_array($iptc)) { 
					$iptc_caption = @utf8_encode($iptc["2#120"][0]); 
				}
			}	
			if ( isset( $this ) and ( $this->_params->get('caption_type_iptc') or $this->_params->get('caption_type_gallery_iptc') )) {
				file_put_contents($thumbname.".iptc_caption.txt", $iptc_caption);
			}			

		}
		// echo "DEBUG: <br />".JURI::base(false)."cache/multithumb_$dest_folder/" . basename($thumbname)."<br /><br />";
		
		return JURI::base(false)."cache/multithumb_$dest_folder/" . basename($thumbname); // BK
    }
	
    function botAddMultiThumbHeader($headertype) {
    	// global $cur_template;
    	$document 	= &JFactory::getDocument();
    	// static $libs;


		static $headers;

    	if($headertype=='style' && !isset($headers['style'])) {
    		$headers['style'] = 1;
    		$document->addStyleDeclaration( "/* ".$this->botmtversion." */\n" . str_replace(array('<br />', '\[', '\]', '&nbsp;'), array("\n", '{', '}', ' '), 
										$this->_params->get('css', '')."\n".
										$this->_params->get('css_blog', '')."\n".
										$this->_params->get('gallery_css', '')."\n".
										$this->_params->get('caption_css', '')));
    	}
    	// if($this->_params->get('add_headers')=='never') return; // Don't add headers
    	$header = '';
    	// if(!is_array($libs)) {
    		// $libs = array();
    		// if($this->_params->get('add_headers')=='auto') { // Handle headers automatically
    			// $indexphp = @file_get_contents( JPATH_SITE."/templates/$cur_template/index.php");
    			// if(preg_match_all('/<script [^>]*(prototype|mootools|scriptaculous|lightbox|AJS|AJS_fx|gb_scripts|multithumb|slimbox|prettyPhoto|shadowbox)\.js[^>]*>/', $indexphp, $libs))
    			// $libs = $libs[1];
    		// }
    	// }

    	switch($headertype) {
			case 'jquery':
				if ( $this->_params->get('jquery_headers', 1) && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;			
					// jquery core, and no conflict directly after
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/jquery/jquery-'.$this->_params->get('jquery_version', '1.4.4').'.min.js' );
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/jquery/jquery.no.conflict.js' );
				}
				break;
    		case 'slimbox':
				$this->botAddMultiThumbHeader('jquery');
    			if ( $this->_params->get('slimbox_headers', 1) && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/slimbox/css/slimbox.css', "text/css", "screen" );
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/slimbox/js/slimbox2.js' );
					$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("head"); 				
var body = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "jQuery(document).ready(function(){ jQuery(\"a[rel^=\'lightbox\']\").slimbox({/* Put custom options here */  /* BEGIN */ loop: '.$this->_params->get('slimbox_loop', '0').' , overlayOpacity: '.$this->_params->get('slimbox_overlayOpacity', '0.8').',	overlayFadeDuration: '.$this->_params->get('slimbox_overlayFadeDuration', '400').',resizeDuration: '.$this->_params->get('slimbox_resizeDuration', '400').', initialWidth: '.$this->_params->get('slimbox_initialWidth','250').', initialHeight: '.$this->_params->get('slimbox_initialHeight', '250').' , imageFadeDuration: '.$this->_params->get('slimbox_imageFadeDuration', '400').' , captionAnimationDuration: '.$this->_params->get('slimbox_captionAnimationDuration', '400').' , closeKeys: '.$this->_params->get('slimbox_closeKeys', '[27, 88, 67]').' , previousKeys: '.$this->_params->get('slimbox_previousKeys', '[37, 80]').' , nextKeys: '.$this->_params->get('slimbox_nextKeys', '[39, 78]').' , counterText: \"'.$this->_params->get('slimbox_counterText', 'Image {x} of {y}').'\" /* END */ }, null, function(el) {			return (this == el) || ((this.rel.length > 8) && (this.rel == el.rel));		}); });"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  
}' );
    			}
    			break;
     		case 'iLoad':
				if ( $this->_params->get('iload_headers', 1) && !isset($headers[$headertype]) ) {
					$headers[$headertype] = 1;
				$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("body"); 				
var body = b[b.length-1] ;  

script2 = document.createElement("script");   
script2.type = "text/javascript";   
var tt = " function config_iLoad() {L.path=\"'.JURI::base( false ).'plugins/content/multithumb/iLoad/\"\n\
L.showInfo='.$this->_params->get('iload_info', 'true').'\n\
L.showName='.$this->_params->get('iload_showName', 'true').'\n\
L.showDesc='.$this->_params->get('iload_showDesc', 'true').'\n\
L.showSet='.$this->_params->get('iload_showSet', 'true').'\n\
L.fileInfoText=\"'.$this->_params->get('iload_fileInfoText', 'File format <b> \[F\] <b> size<b> \[W\] x \[H\] </b> pixels').'\"\n\
L.imageSetText='.$this->_params->get('iload_imageSetText','[\'<b>[N] </b> from <b> [T] </b>\', \'in the group [S]\']').'\n\
L.fontCss=\"'.$this->_params->get('iload_fontCss', 'font:11pxTahoma,Arial,Helvetica,sans-serif;color:#aaa;').'\"\n\
L.imageDescCss=\"'.$this->_params->get('iload_imageDescCss', 'display: block;').'\"\n\
L.imageNameCss=\"'.$this->_params->get('iload_imageNameCss', 'display: block; font-weight: 700; color: # 999;').'\"\n\
L.imageSetCss=\"'.$this->_params->get('iload_imageSetCss', 'display: block;').'\"\n\
L.imageInfoCss=\"'.$this->_params->get('iload_imageInfoCss', 'display: block;').'\"\n\
L.zIndex=\"'.$this->_params->get('iload_zIndex', '9999').'\"\n\
L.splitSign=\"'.$this->_params->get('iload_splitSign', '|').'\"\n\
L.bigButtonsDisabledOpacity='.$this->_params->get('iload_bigButtonsDisabledOpacity', '30').'\n\
L.bigButtonsPassiveOpacity='.$this->_params->get('iload_bigButtonsPassiveOpacity', '100').'\n\
L.bigButtonsActiveOpacity='.$this->_params->get('iload_bigButtonsActiveOpacity', '70').'\n\
L.minButtonsPassiveOpacity='.$this->_params->get('iload_minButtonsPassiveOpacity', '50').'\n\
L.minButtonsActiveOpacity='.$this->_params->get('iload_minButtonsActiveOpacity', '100').'\n\
L.overlayAppearTime='.$this->_params->get('iload_overlayAppearTime', '200').'\n\
L.overlayDisappearTime='.$this->_params->get('iload_overlayDisappearTime', '200').'\n\
L.containerAppearTime='.$this->_params->get('iload_containerAppearTime', '300').'\n\
L.containerDisappearTime='.$this->_params->get('iload_containerDisappearTime', '300').'\n\
L.containerResizeTime='.$this->_params->get('iload_containerResizeTime', '300').'\n\
L.contentAppearTime='.$this->_params->get('iload_contentAppearTime', '350').'\n\
L.contentDisappearTime='.$this->_params->get('iload_contentDisappearTime', '200').'\n\
L.loaderAppearTime='.$this->_params->get('iload_loaderAppearTime', '200').'\n\
L.loaderDisappearTime='.$this->_params->get('iload_loaderDisappearTime', '200').'\n\
L.containerCenterTime='.$this->_params->get('iload_containerCenterTime', '300').'\n\
L.panelAppearTime='.$this->_params->get('iload_panelAppearTime', '300').'\n\
L.panelDisappearTime='.$this->_params->get('iload_panelDisappearTime', '300').'\n\
L.arrowsTime='.$this->_params->get('iload_arrowsTime', '230').'\n\
L.paddingFromScreenEdge='.$this->_params->get('iload_paddingFromScreenEdge', '35').'\n\
L.contentPadding='.$this->_params->get('iload_contentPadding', '0').'\n\
L.cornersSize='.$this->_params->get('iload_cornersSize', '18').'\n\
L.overlayOpacity='.$this->_params->get('iload_overlayOpacity', '95').'\n\
L.overlayBackground=\"'.$this->_params->get('iload_overlayBackground', '#000000').'\"\n\
L.containerColor=\"'.$this->_params->get('iload_containerColor', '#ffffff').'\"\n\
L.panelType='.$this->_params->get('iload_panelType', '1').'\n\
L.hidePanelWhenScale='.$this->_params->get('iload_hidePanelWhenScale', 'true').'\n\
L.forceCloseButton='.$this->_params->get('iload_forceCloseButton', 'true').'\n\
L.arrows='.$this->_params->get('iload_arrows', 'true').'\n\
L.imageNav='.$this->_params->get('iload_imageNav', 'true').'\n\
L.showSize='.$this->_params->get('iload_showSize', 'true').'\n\
L.forceFullSize='.$this->_params->get('iload_forceFullSize', 'false').'\n\
L.keyboard='.$this->_params->get('iload_keyboard', 'true').'\n\
L.dragAndDrop='.$this->_params->get('iload_dragAndDrop', 'true').'\n\
L.preloadNeighbours='.$this->_params->get('iload_preloadNeighbours', 'true').'\n\
L.slideshowTime='.$this->_params->get('iload_slideshowTime', '3000').'\n\
L.slideshowRound='.$this->_params->get('iload_slideshowRound', 'true').'\n\
L.slideshowClose='.$this->_params->get('iload_slideshowClose', 'false').'\n\
L.tips=['.$this->_params->get('iload_tips', '\'Previous\', \'Next\', \'Close\', \'Slideshow\', \'Pause\', \'Original\',\'Fit to window\'').']\n\
L.errorWidth='.$this->_params->get('iload_errorWidth', '240').'\n\
L.errorName='.$this->_params->get('iload_errorName', 'Error.').'\n\
L.closeOnClickWhenSingle='.$this->_params->get('iload_closeOnClickWhenSingle', 'true').'\n\
L.errorDescCss=\"'.$this->_params->get('iload_errorDescCss', 'display: block; padding-bottom: 4px;').'\"\n\
L.errorNameCss=\"'.$this->_params->get('iload_errorNameCss', 'display: block; font-weight: 700; color: # 999; padding-bottom: 4px;').'\"\n\
L.errorText=\"'.$this->_params->get('iload_errorText', 'Could not load image. Perhaps the address specified is not valid or the server is temporarily unavailable.').'\"\n\
}";
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  

script = document.createElement("script");  
script.type = "text/javascript";
script.onload=config_iLoad;
script.src = "'.JURI::base( false ).'/plugins/content/multithumb/iLoad/iLoad.js";  
body.appendChild(script);  
}' );

/*

*/
				}
				break;
    		case 'prettyPhoto':
    			$this->botAddMultiThumbHeader('jquery', 1);
    			if ( $this->_params->get('prettyphoto_headers') && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/prettyPhoto/css/prettyPhoto.css', "text/css", "screen" );
    				$document->addScript( $this->_live_site.'/plugins/content/multithumb/prettyPhoto/js/jquery.prettyPhoto.js' );
					$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("body"); 				
var body = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "jQuery(document).ready(function(){ jQuery(\"a[rel^=\'prettyPhoto\']\").prettyPhoto({\n\
animation_speed: \''.$this->_params->get('prettyphoto_animationSpeed', 'normal').'\',  \n\
opacity: '.$this->_params->get('prettyphoto_opacity', '0.80').', 	\n\
show_title: '.$this->_params->get('prettyphoto_showTitle', 'true').',  \n\
allow_resize: '.$this->_params->get('prettyphoto_allowresize', 'true').'  ,			\n\
default_width: 500,			\n\
default_height: 344	,			\n\
counter_separator_label: \''.$this->_params->get('prettyphoto_counter_separator_label', '/').'\', 			\n\
theme: \''.$this->_params->get('prettyphoto_theme', 'light_rounded').'\', 			\n\
opacity: '.$this->_params->get('prettyphoto_opacity', '0.80').', 	\n\
horizontal_padding: '.$this->_params->get('prettyphoto_horizontal_padding', '20').', 	\n\
wmode: \'opaque\',		\n\
autoplay: true, 			\n\
modal: '.$this->_params->get('prettyphoto_modal', 'false').', 	\n\
deeplinking: true, \n\
slideshow:  '.$this->_params->get('prettyphoto_slideshow', 'false').', 	\n\
autoplay_slideshow: '.$this->_params->get('prettyphoto_autoplay_slideshow', 'false').', 	\n\
overlay_gallery: '.$this->_params->get('prettyphoto_overlay_gallery', 'false').', 	\n\
keyboard_shortcuts: true, \n\
social_tools: false, \n\
}); \n\
});"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  
}' );

/*

overlay_gallery: '.$this->_params->get('prettyphoto_overlay_gallery', 'false').', 	\n\

*/
    			}
    			break;
    		case 'shadowbox':
    			// $this->botAddMultiThumbHeader('jquery');
    			if ( $this->_params->get('shadowbox_headers', 1) && !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				$document->addScript( $this->_live_site.'/plugins/content/multithumb/shadowbox/shadowbox.js' );
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/shadowbox/shadowbox.css', "text/css", "screen" );
					$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("head"); 				
var body = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "Shadowbox.init( {  animate:	           '.$this->_params->get('shadowbox_animate', '1').' ,animateFade:           '.$this->_params->get('shadowbox_animateFade', '1').' ,animSequence:        \"'.$this->_params->get('shadowbox_animSequence', 'sync').'\"  ,autoplayMovies:	       '.$this->_params->get('shadowbox_autoplayMovies', '1').'  ,continuous:	           '.$this->_params->get('shadowbox_continuous', '0').'  ,counterLimit:	      '.$this->_params->get('shadowbox_counterLimit', '10').' ,counterType:	      \"'.$this->_params->get('shadowbox_counterType', 'default').'\"    ,displayCounter:	       '.$this->_params->get('shadowbox_displayCounter', '1').'  ,displayNav:	          '.$this->_params->get('shadowbox_displayNav', '1').' ,enableKeys:	           '.$this->_params->get('shadowbox_enableKeys', '1').'  ,fadeDuration:          '.$this->_params->get('shadowbox_fadeDuration', '0.35').' ,flashVersion:	      \"'.$this->_params->get('shadowbox_flashVersion', '9.0.0').'\"  ,handleOversize:	      \"'.$this->_params->get('shadowbox_handleOversize', 'resize').'\"  ,handleUnsupported:	 \"'.$this->_params->get('shadowbox_handleUnsupported', 'link').'\"  ,initialHeight:	       '.$this->_params->get('shadowbox_initialHeight','160').' ,initialWidth:	       '.$this->_params->get('shadowbox_initialWidth', '320').' ,modal:	               '.$this->_params->get('shadowbox_modal', '0').'  ,overlayColor:	      \"'.$this->_params->get('shadowbox_overlayColor','#000').'\"  ,overlayOpacity:	       '.$this->_params->get('shadowbox_overlayOpacity', '0.5').'  ,resizeDuration:	       '.$this->_params->get('shadowbox_resizeDuration', '0.35').'  ,showOverlay:	      '.$this->_params->get('shadowbox_showOverlay', '1').' ,showMovieControls:	   '.$this->_params->get('shadowbox_showMovieControls', '1').' ,slideshowDelay:	      '.$this->_params->get('shadowbox_slideshowDelay', '0').' ,viewportPadding:	   '.$this->_params->get('shadowbox_viewportPadding', '20').' ,flashVars: {'.$this->_params->get('shadowbox_flashVars','').'}    } );"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
body.appendChild(script2);  
}' );

/*
	, flashParams: {'.$this->_params->get('shadowbox_flashParams','bgcolor:#000000').'}    
*/
    			}
    			break;
			case 'thumbnail':
				if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
					$document->addScript( $this->_live_site.'/plugins/content/multithumb/thumbnailviewer/thumbnailviewer.js' );
					//$document->addScriptDeclaration( 'document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/thumbnailviewer/thumbnailviewer.js">/* Image Thumbnail Viewer Script- © Dynamic Drive (www.dynamicdrive.com) * This notice must stay intact for legal use. * Visit http://www.dynamicdrive.com/ for full source code */</scr\'+\'ipt>\'); document.write(\'<scr\'+\'ipt type="text/javascript" >thumbnailviewer.defineLoading="<img src='.$this->_live_site.'/plugins/content/multithumb/thumbnailviewer/loading.gif /> Loading Image...";</scr\'+\'ipt>\'); ' );
					
				$document->addScriptDeclaration( 'window.onload=function(){
var h = document.getElementsByTagName("head"); 
var head = h[h.length-1] ;  				
script2 = document.createElement("script");   
script2.type = "text/javascript";   
var tt = "/* Image Thumbnail Viewer Script- © Dynamic Drive (www.dynamicdrive.com) * This notice must stay intact for legal use. * Visit http://www.dynamicdrive.com/ for full source code */ \n\
thumbnailviewer.definefooter=\'<div class=\"footerbar\">CLOSE x</div>\' \n\
thumbnailviewer.enableAnimation=true ; \n thumbnailviewer.enableTitle=true;  \n\
thumbnailviewer.defineLoading=\"<img src='.$this->_live_site.'/plugins/content/multithumb/thumbnailviewer/loading.gif /> Loading Image...\"\n\
";
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
head.appendChild(script2);  
}' );
					
	//		
 
 		
					
    				$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/thumbnailviewer/thumbnailviewer.css', "text/css", "screen" );
    			}
				break;
				
    		case 'modal':
				if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
					JHTML::_('behavior.modal');
				}
				break;
				
    		case 'greybox':
    			if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    
			// $document->addCustomTag('<script type="text/javascript">var GB_ROOT_DIR = "'.$this->_live_site.'/plugins/content/multithumb/greybox/";</script>'."\n".
			// '<script type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS.js"></script>'."\n".
			// '<script type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS_fx.js"></script>'."\n".
			// '<script type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_scripts.js"></script>'."\n".
			// '<link rel="stylesheet" href="'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_styles.css" type="text/css" media="screen" />'."\n");
					
				
$document->addScriptDeclaration( 'document.write(\'<scr\'+\'ipt type="text/javascript">var GB_ROOT_DIR = "'.$this->_live_site.'/plugins/content/multithumb/greybox/";</scr\'+\'ipt>\');
document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS.js"></scr\'+\'ipt>\');
document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS_fx.js"></scr\'+\'ipt>\');
document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_scripts.js"></scr\'+\'ipt>\');
document.write(\'<link rel="stylesheet" href="'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_styles.css" type="text/css" media="screen"  />\'); ');
			


					
	/*			
			
			$document->addScriptDeclaration( 'window.onload=function(){
var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script2 = document.createElement("script");   
script2.type = "text/javascript";
script2.charset="utf-8";
var tt = "var GB_ROOT_DIR = \"'.$this->_live_site.'/plugins/content/multithumb/greybox/\";"
if (navigator.appName == "Microsoft Internet Explorer") {
	script2.text = tt;
} else {
	script2.appendChild( document.createTextNode(tt) );
}
head.appendChild(script2);  

var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script3 = document.createElement("script");  
script3.type = "text/javascript";   
script3.src = "'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS.js";  
head.appendChild(script3);  

var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script4 = document.createElement("script");  
script4.type = "text/javascript";   
script4.src = "'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS_fx.js";  
head.appendChild(script4);  

var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script5 = document.createElement("script");  
script5.type = "text/javascript";   
script5.src = "'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_scripts.js";  
head.appendChild(script5);  

var b = document.getElementsByTagName("head"); 				
var head = b[b.length-1] ;  
script6 = document.createElement("link");  
script6.type = "text/css";   
script6.rel="stylesheet"
script6.href = "'.$this->_live_site.'/plugins/content/multithumb/greybox/gb_styles.css";  
head.appendChild(script6);  
}' );
			
			*/





			
			

			
			// $document->addScript( $this->_live_site.'/plugins/content/multithumb/shadowbox/shadowbox.js' );
			
			  
   				//$document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/greybox/gb_styles.css', "text/css", "screen" );
    		}
				
				// document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/greybox/AJS_fx.js"></scr\'+\'ipt>\');
			  
    			break;

    		// case 'thickbox':
    			// if ( !$bot_mt_thickbox_header_added ) {
    				// $bot_mt_thickbox_header_added=1;
    				// $document->addScriptDeclaration( 'var tb_pathToImage = "'.JURI::base( true ).'/plugins/content/multithumb/thickbox/loadingAnimation.gif";
			  // document.write(\'<scr\'+\'ipt type="text/javascript" src="'.$this->_live_site.'/plugins/content/multithumb/thickbox/thickbox.js"></scr\'+\'ipt>\');' );
    					
    				// $document->addStyleSheet( $this->_live_site.'/plugins/content/multithumb/thickbox/thickbox.css', "text/css", "screen" );
    			// }
    			// break;

    		case 'normal':
    		case 'expansion':
    			if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				// if(!in_array('multithumb', $libs)) {
    					$document->addScript( $this->_live_site.'/plugins/content/multithumb/multithumb.js' );
    				// }
    			}
    			break;
    		case 'expando':
    			if ( !isset($headers[$headertype]) ) {
    				$headers[$headertype]=1;
    				// if(!in_array('expando', $libs)) {
    					$document->addScript( $this->_live_site.'/plugins/content/multithumb/expando.js' );
    				// }
    			}
    			break;
    	}
		
    }

    function bot_mt_makeFullArticleLink($img, $altOrg, $titleOrg) {

		// echo "DEBUG3: ".$this->_params->get('blog_img_txt')."<br>";
		$botMtLinkText = $this->botMtLinkText;

		if ($this->_params->get('blog_img_txt')==1) {
			if ($altOrg!="") {
				$botMtLinkText = JText::_('COM_CONTENT_READ_MORE').$altOrg;
			// } else {
				// $botMtLinkText = JText::_('COM_CONTENT_READ_MORE_TITLE');
			}
		} elseif ($this->_params->get('blog_img_txt')==2) {					
			if ($titleOrg!="") {
				$botMtLinkText = JText::_('COM_CONTENT_READ_MORE').$titleOrg;
			// } else {
				// $botMtLinkText = JText::_('COM_CONTENT_READ_MORE_TITLE');	
			}
		}
		
		if ( $botMtLinkText ) {
			
			$img = preg_replace(array('/((?:title|alt)=(["\']))(.*?)(\\2)/', '/<img[^>]*>/'), array("$1".$botMtLinkText."$4", "<a href=\"".$this->botMtLinkOn."\" title=\"".$botMtLinkText."\">$0</a>"), $img);
		}
     // $img = preg_replace(array('/((?:title|alt)=(["\']))(.*?)(\\2)/', '/<img[^>]*>/'), array("$1".$this->botMtLinkText."$4", "<a href=\"".$this->botMtLinkOn."\" title=\"".$this->botMtLinkText."\">$0</a>"), $img);
    	return $img;
    }


    // processes inline parameters
    function inline_parms(&$parms) {

	
		 
    	if(isset($parms)) { // Now handle manual param settings ...
    		$botMtParamStr = str_replace(array('<br />', '&nbsp'), '', $parms);
    		// parse parameters
    		if(preg_match_all('/\bdefault\b|[^=\s]+ *=[^=]*(?:\s+|$)(?!=)/is', $botMtParamStr, $botParams)) {
    			foreach($botParams[0] as $param) {
				
						
					$param = trim(trim($param, ';'));
    				// restore default value of all parameters
    				if($param == 'default') {
						// if (version_compare(PHP_VERSION, '5.0.0', '<')) {
							// $this->_params = $this->_paramsDef;
						// } else {
						$this->_params = clone($this->_paramsDef);
						// }
    				} else {
    					// set specific parameter
    					$param = explode('=', $param);
    					$varname = trim($param[0]);
    					$value = trim($param[1]);
    					// restore default value of specific parameter
						// echo "DEBUG:".$this->_paramsDef->get( 'thumb_width' )."<br>";
						
    					// if(strtolower($value)=='default') {
    						// $value = $this->_paramsDef->get( $varname );
    					// }

    					// update only exist paramters
    					if( null != $this->_params->get( $varname, null )) {
							$this->_params->set( $varname,  $value );
    					}
    				}
    			}
    		}
    	}

    }

    function set_sys_limits () {

    	// set system parametrs once per session
    	if(is_numeric($this->_params->get('time_limit'))) {
    		set_time_limit($this->_params->get('time_limit'));
    		// avoid next execution
    		// @TODO Change to static variable
    		$this->_params->set('time_limit', '');
    		$this->_paramsDef->set('time_limit', '');
    	}

    	if($this->_params->get('memory_limit') != 'default') {
    		ini_set("memory_limit", $this->_params->get('memory_limit'));
    		$this->_params->set('memory_limit', 'default');
    		$this->_paramsDef->set('memory_limit', 'default' );
    	}

    }

    function set_caption($alt, $title, $iptc_caption, $filename) {
    	// BK if(!$title) $title = $alt;
    	// if(!isset($caption)) {
		
		// array(6 => 5, 13 => 9, "a" => 42)
		$values = array( $this->_params->get('caption_type_iptc') 	=> $iptc_caption,
						$this->_params->get('caption_type_filename') 		=> $filename,
						$this->_params->get('caption_type_title') 			=> $title,
						$this->_params->get('caption_type_alt') 			=> $alt );
				
		ksort($values);
		$caption = '';
		foreach ($values as $key =>  $val) {
		    if ( $key and $val ) {
				$caption = $val;
				break;
			}
		}
		

    	return $caption;
    	// }
    	// if(!isset($caption)) $caption = $caption_type=='title' ? $title : $alt;
    }


    //
    // Process resized or/and watermarked images
    //
    function resize_image(&$imgraw, /*$imgloc, */ &$imgurl, &$real_width, &$real_height /* ,  $size , $watermark */ )
    {

		// Full image size
    	$full_width  = $this->_params->get('full_width');
    	$full_height = $this->_params->get('full_height');

    	if( !$this->_params->get('resize')) {
    		$full_width = $full_height = 0;
    	}

    	// Resize image and/or set watermark
    	$imgtemp = $this->botmt_thumbnail($imgurl, $full_width, $full_height, $this->_params->get('image_proportions'), hexdec($this->_params->get('image_bg')), (int)($this->_params->get('watermark_type') >= 1), 'images', 0 , /* $size, */ $this->_params->get('img_type', "") );

    	// If image resized or watermarked use it instead of the original one
    	if($imgtemp) {
    		$imgurl = $imgtemp;
    		$real_width = $full_width;
    		$real_height = $full_height;
			preg_match('/(.*src=["\'])[^"\']+(["\'].*)/i', $imgraw, $parts);
			$imgraw = $parts[1].$imgurl.$parts[2];
    	} else {
    		$real_width = $full_width;
    		$real_height = $full_height;
		}

    }
	
	function gallery($imgloc, $imgurl, $alt, $title, $align, $class  ) {
    	// It should be list of files

		static $mt_gallery_count = 0;
		
		if ( $mt_gallery_count ) {
			return false;
		}
		
		$mt_gallery_count++;
		
		if(!@fopen($imgloc, 'r')) {
    		// can't open file. Ignore it.
    		return false;
    	}
		
  		$old_caption_type_iptc     = $this->_params->get('caption_type_iptc');
		$old_caption_type_filename = $this->_params->get('caption_type_filename');
		$old_caption_type_title    = $this->_params->get('caption_type_title');
		$old_caption_type_alt      = $this->_params->get('caption_type_alt');  

		$this->_params->set('caption_type_iptc', $this->_params->get('caption_type_gallery_iptc'));
		$this->_params->set('caption_type_filename', $this->_params->get('caption_type_gallery_filename'));
		$this->_params->set('caption_type_title', $this->_params->get('caption_type_gallery_title'));
		$this->_params->set('caption_type_alt', $this->_params->get('caption_type_gallery_alt'));  		
		
    	$pathinfo = pathinfo($imgloc);
    	$filepatt = "$pathinfo[dirname]/{*.gif,*.jpg,*.png,*.GIF,*.JPG,*.PNG}";

    	// Preserve caption caption type (position)
/*     	$old_caption_pos = $this->_params->get('caption_pos');
    	$old_caption_type = $this->caption_type; */

    	// $this->_params->set('caption_type', 'title' ); // Make sure we get the titles for the gallery
    	// $style = in_array($align, array('left', 'right')) ? ' style="float:'.$align.';"' : '';
		$style = $align ? ' align="'.$align.'" ' : '';
    	$gallery = '<table class="'.$this->_params->get('gallery_class').'" >' . "\n";

    	$n = 0; $lblinks = '';
	
		if(file_exists("$imgloc.txt")) {
			$imglist = file_get_contents("$imgloc.txt");
			preg_match_all('/(\S+\.(?:jpg|png|gif))\s(.*)/i', $imglist, $files, PREG_SET_ORDER);
			$dir = dirname($imgurl);
			// $alt = basename($imgurl);
		} else {
			$files = glob($filepatt, GLOB_BRACE);
			sort($files);
			$imgpos = array_search($imgloc, $files);
			$files = array_merge(array_slice($files, $imgpos), array_slice($files, 0, $imgpos));
			$dir = dirname($imgurl);
			// $alt = basename($imgurl);
		}
    	 
//     	if($alt=='mt_gallery') {
    		$this->mt_gallery_count++;
			
    		if ( !$alt ) {
				$alt = $title;
			}
			
    		if ( !$alt ) {
				$alt = "gallery".$this->mt_gallery_count;
			}
			
			
//    	}
     	// if($title=='mt_gallery') {
		//	$title = ' ';
		// }
		
		$mt_gallery_more = 0;
		
		// echo "DEBUG:$dir<br>";
    	foreach($files as $file) {
    		if(is_array($file)) {
    			$fn = $dir.'/'.$file[1];
    			$title = str_replace("'", '&#39;', $file[2]);
    		} else {
				$fn = $dir.'/'.basename($file);
			}
			$this->is_gallery = true;
    		$galimg = preg_replace_callback($this->regex, 
									        array( &$this,'image_replacer'), 
											'<img '.$style.' class="'.$class.'" alt="'.$alt.'" title="'.$title.'" src="'.$fn.'" />' . "\n");
			// echo "DEBUG1:".htmlspecialchars($galimg)."<br>";
			$this->is_gallery = false;
    		if(!(strpos($galimg, '<img') === false)){

    			if($n % $this->_params->get('num_cols') == 0) { 
					$gallery .= '<tr class="'.$this->_params->get('gallery_class').'" >';
				}
    			$gallery .= '<td class="'.$this->_params->get('gallery_class').'" valign="bottom" nowrap="nowrap" '.$style.'>'.$galimg.'</td>
';
    			$n++;
    			if($n % $this->_params->get('num_cols') == 0) {
					$gallery .= "</tr>\n";
				}
    		} else if(substr($galimg,0,2)=='<a') {
				/* if (!$mt_gallery_more and $this->_params->get('more_images', 0) ) {
					$mt_gallery_more = 1;
					$mt_gallery_more_link = $galimg;
				} else */ {

				// echo "DEBUG:".htmlspecialchars(substr($mt_gallery_more_link,0,strlen($mt_gallery_more_link)-5))."<br/>";
					$lblinks .= $galimg;
				}
				
    		}
    	}

    	$gallery .= str_repeat('<td>&nbsp;</td>', $this->_params->get('num_cols')-1 - ($n-1) % $this->_params->get('num_cols')) . "</tr>";
		/* if ( $mt_gallery_more and $this->_params->get('more_images', 0) ) {
			$gallery .= '<tr><td rowspan="0">'.substr($mt_gallery_more_link,0,strlen($mt_gallery_more_link)-5).$this->_params->get('more_images_text', JText::_("More images...")).'</a></td></tr>';
			// $gallery .= '<tr><td rowspan="0">AAAA'.$mt_gallery_more_link.'</td></tr>';
		} */
		$gallery .= "</table>\n";
		

		
/*     	$this->_params->set('caption_pos', $old_caption_pos );
    	$this->_params->set('caption_type', $old_caption_type );
 */
		$this->_params->set('caption_type_iptc', $old_caption_type_iptc);
		$this->_params->set('caption_type_filename', $old_caption_type_filename);
		$this->_params->set('caption_type_title', $old_caption_type_title);
		$this->_params->set('caption_type_alt', $old_caption_type_alt);  		
		
		
		$mt_gallery_count--;
    	return $gallery . $lblinks;

    }

	// Change image path from relative to full
    function fix_path(&$imgloc, &$imgurl) {
		// echo "DEBUG: <br>$imgloc<br>";
    	if(!(false === strpos($imgloc, '://'))) { // It's a url
			// 
			$pos = strpos($imgloc, JURI::base( false ));
			if ( $pos !== false && $pos == 0 ) { // It's internal full url
				$imgloc = substr($imgloc, strlen (  JURI::base( false ) ) );
				$imgurl = $imgloc;
				$imgloc = JPATH_SITE.DS.str_replace( "/", DS, $imgloc );
				// echo "DEBUG:$imgloc<br />";
			} else { // external url
				$imgurl = $imgloc;
				$imgloc = str_replace(" ", "%20", $imgloc );
			}
			
    		
			// $imgloc = str_replace( , JPATH_SITE.DS, $imgloc);
    		#if(strpos($imgloc, '://')) return $imgraw; // Still http:// => it must be an external image
    	} else { // it's a relative path
    		/* if(!file_exists($imgloc)) { // it might be a malformed image location, try removing leading ../
    			$imgloc = preg_replace('#^(\.\./)+#', '', $imgloc);
    		} */

    		if (substr($imgloc, 0, 1) == "/") {
    			// It's full path
    			/* $imgurl = substr_replace( JURI::base( false ),
    			"",
    			-strlen( JURI::base( true ) ) ).$imgloc; */
    			
				// $imgurl = JURI::base( true ).$imgloc;
				
				$imgloc = substr_replace( $imgloc, "", 0, strlen(JURI::base( true )) );
				$imgurl = $imgloc;
    			/* $imgloc = substr_replace( JPATH_SITE,
    			 "",
    			 -strlen( str_replace( "/", DS, JURI::base( true ) ) ) ).DS.str_replace( "/", DS, $imgloc );*/
    			$imgloc = JPATH_SITE.str_replace( "/", DS, $imgloc );
    			// echo "DEBUG: $imgurl<br />$imgloc<br /><br />";

    		} else {
    			$imgurl = /* JURI::base( true ).'/'.*/ $imgloc;
    			$imgloc = JPATH_SITE.DS.$imgloc;
    		}
    	}
    	// echo "DEBUG: <br />imgurl=$imgurl <br /> imgloc=$imgloc<br /><br />";
		$imgurl = str_replace(" ", "%20", $imgurl );
		// echo "$imgloc<br>";
    }
	
	


    function set_popup_type( &$title, &$alt ) {

		// echo "DEBUG1:".$this->_params->get('popup_type')."<br>";
    	$this->popup_type = $this->_params->get('popup_type');

    	// Process alt parameter of image that is used for multithumb instructions as prefix separated by ":"
    	$temp = explode(':', $alt, 2);
    	// Parametrs that may be specified in alt field
    	$popupmethods = array(	'none'		=>'mt_none',
								'normal'	=>'mt_popup', 
								'thumbnail'	=>'mt_thumbnail', 
								'lightbox'	=>'mt_slimbox', 
								'prettyPhoto' =>'mt_prettyPhoto', 
								'shadowbox'	=>'mt_shadowbox', 
								'expansion'	=>'mt_expand', 
								'expando'	=>'mt_expando', 
								'gallery'	=>'mt_gallery', 
								'ignore'	=>'mt_ignore', 
								'nothumb'	=>'mt_nothumb', 
								'greybox'	=>'mt_greybox', 
								'slimbox'	=>'mt_slimbox', 
								'thickbox'	=>'mt_slimbox');
    	// Search for any expected instruction
    	$new_popup_style = array_search(strtolower($temp[0]), $popupmethods);
		
		// echo "DEBUG: $temp[0] <br />";

    	// instruction found
    	if($new_popup_style!==false) {
    		// change popup type
    		$this->popup_type = $new_popup_style;

    		// if($this->popup_type=='ignore') { // mt_ignore, don't create thumb
    			// $this->popup_type='none';
    		// }

    		// Remove instruction from alt pararmeter of the image
    		$alt = preg_replace('/^(mt_none|mt_popup|mt_thumbnail|mt_lightbox|mt_expand|mt_gallery|mt_ignore|mt_nothumb|mt_greybox|mt_slimbox|mt_prettyPhoto|mt_shadowbox|mt_thickbox):?/i', '', $alt);

    		// Alt text is specified after instruction
//    		if(count($temp)>1) {
    			// Set alt text of current and following images
    			// $alt = $temp[1];
//    		}
			
/* 			if(!$title) { 
				$title = $alt;
			} */
			// echo "DEBUG: $alt $title<br>";			
    	}
		


    	if ( $this->popup_type == "lightbox"  ) {
    		$this->popup_type = "slimbox";
    	}
		
		if ( $this->popup_type == "thickbox" ) {
    		$this->popup_type = "shadowbox";
		}
    }

/*     function getimagesize( &$imgloc, &$real_width, &$real_height, &$size, &$info )	{
    	$size = @getimagesize( $imgloc, $info );
    	if (is_array( $size )) {
    		$real_width=$size[0];
    		$real_height=$size[1];
    		// $size = $size[3];
			return true;
    	} // END if ( !$this->_params->get('resize')
    	else { // For some reason we can't determine real size, so disable thumbnail generation by setting the size to zero
    		return false;
    	}
    } */

} // Class End
?>
