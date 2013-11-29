<?php

/**
 * A plugin to  provide a custom Start-Page.
 * This page is independent of the index.php so you don't need to hack it.
 *
 * @package      augsburg-medienlabor
 * @subpackage design\startseite
 * @author     Bernhard Strehl <studip at bernhardstrehl dot de>
 * @copyright  (c) Authors
 * @license    http://www.gnu.org/licenses/gpl.html GPLv3 or later
 */
/*
  This plugin replaces Stud.IP's default startpage when being logged in.
  Copyright (C) 2013 Bernhard Strehl

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */


/**
 * Klasse CustomStart um eine eigene Startseite zu erhalten
 * @package augsburg-medienlabor
 * @subpackage design\startseite
 * @author     Bernhard Strehl <studip at bernhardstrehl dot de>
 * @copyright  (c) Authors
 * @license    http://www.gnu.org/licenses/gpl.html GPLv3 or later
 */
class CustomStart extends StudipPlugin implements SystemPlugin
{

    /**
     *
     * @var Flexi_TemplateFactory $template_factory eine Variable, die ein Templatefactory-Objekt beinhaltet
     */
    private  $template_factory;

    /**
     * Constructor des Plugins
     */
    function __construct()
    {
        parent::__construct();

        global $perm;
        global $user, $auth;
        global $_include_additional_header, $ABSOLUTE_URI_STUDIP;


        $this->template_factory = new Flexi_TemplateFactory(dirname(__FILE__) . '/templates/');
        //Auf der eigentlichen Login-Seite darf der Dialog nicht aktiviert werden da sonst auth fehlschlï¿½gt

        if (basename($_SERVER['SCRIPT_NAME']) == 'index.php')
        {
            //ausgeloggt

            if ((($auth->is_authenticated() == 'nobody' || !$auth->is_authenticated())))
            {
                #  echo 123;
            }

            //eingeloggt
            elseif ((strpos($_SERVER['SCRIPT_NAME'], 'plugins_packages') === FALSE && strpos($_SERVER['SCRIPT_NAME'], 'wp-admin') === false && strpos($_SERVER['SCRIPT_NAME'], 'blog') === false))
            {
                global $ABSOLUTE_URI_STUDIP;
                header('Location:' . $ABSOLUTE_URI_STUDIP . 'plugins.php/customstart/loggedin');
            }
        }
        return true;
    }
    /**
     * Das was eingeloggte zu sehen bekommen
     *
     * Fuer eingeloggte Nutzer ist die Startseite eine andere, in der
     * ein persoenlicher Desktop eingebettet ist.
     * @author Bernhard Strehl
     */
    function loggedin_action()
    {   
        global $auth, $sess, $perm, $again, $user;
        global $RELATIVE_PATH_CALENDAR;

      if (class_exists('LoadCheck') && LoadCheck::hasHighLoad())
            $SERVER_HAS_HIGH_LOAD =  true;


// database object
$db=new DB_Seminar;
        $ZWW_PLUGIN =  PluginEngine::getPlugin('ZWW');
        if($ZWW_PLUGIN)
        {
            $USER_IS_ZWW_USER = $ZWW_PLUGIN->isCurrUserZWWUser() && $ZWW_PLUGIN->isZWWViewEnabled();
        }
// evaluate language clicks
// has to be done before seminar_open to get switching back to german (no init of i18n at all))
if (Request::get('set_language')) {
    if(array_key_exists(Request::get('set_language'), $GLOBALS['INSTALLED_LANGUAGES'])) {
        $_SESSION['forced_language'] = Request::get('set_language');
        $_SESSION['_language'] = Request::get('set_language');
    }
}

// store  user-specific language preference
if ($auth->is_authenticated() && $user->id != 'nobody') {
    // store last language click
    if (strlen($_SESSION['forced_language'])) {
        $db->query("UPDATE user_info SET preferred_language = '".$_SESSION['forced_language']."' WHERE user_id='$user->id'");
        $_SESSION['_language'] = $_SESSION['forced_language'];
    }
    $_SESSION['forced_language'] = null;
}

include_once 'lib/seminar_open.php'; // initialise Stud.IP-Session
require_once 'config.inc.php';
require_once 'lib/functions.php';
require_once 'lib/visual.inc.php';
require_once 'lib/classes/MessageBox.class.php';
include_once 'lib/classes/RSSFeed.class.php';
// -- hier muessen Seiten-Initialisierungen passieren --

// -- wir sind jetzt definitiv in keinem Seminar, also... --
closeObject();

if (get_config('NEWS_RSS_EXPORT_ENABLE') && ($auth->is_authenticated() && $user->id != 'nobody')){
    $rss_id = StudipNews::GetRssIdFromRangeId('studip');
    if ($rss_id) {
        PageLayout::addHeadElement('link', array('rel'   => 'alternate',
                                                 'type'  => 'application/rss+xml',
                                                 'title' => 'RSS',
                                                 'href'  => 'rss.php?id='.$rss_id));
    }
}

PageLayout::setHelpKeyword("Basis.Startseite"); // set keyword for new help
PageLayout::setTitle(_("Startseite"));
Navigation::activateItem('/start');
PageLayout::setTabNavigation(NULL); // disable display of tabs

// Start of Output
include 'lib/include/html_head.inc.php'; // Output of html head
include 'lib/include/header.php';

// only for authenticated users
if ($auth->is_authenticated() && $user->id != 'nobody') {

    UrlHelper::bindLinkParam('index_data', $index_data);

    //Auf und Zuklappen News
    require_once 'lib/showNews.inc.php';
    process_news_commands($index_data);

    // Auf- und Zuklappen Termine
    if (Request::get('dopen')) {
        $index_data['dopen'] = Request::option('dopen');
    }
    if (Request::get('dclose')) {
        unset($index_data['dopen']);
    }

    if ($perm->have_perm('root')) { // root
        $ueberschrift = _("Startseite für Root");
    } elseif ($perm->have_perm('admin')) { // admin
        $ueberschrift = _("Startseite für AdministratorInnen");
    } elseif ($perm->have_perm('dozent')) { // dozent
        $ueberschrift = _("Startseite für DozentInnen");
    } else { // user, autor, tutor
        $ueberschrift = _("Ihre persönliche Startseite");
    }

    // Warning for Users
    $help_url = format_help_url("Basis.AnmeldungMail");

    // Display banner ad
    if (get_config('BANNER_ADS_ENABLE')) {
        require_once 'lib/banner_show.inc.php';
        banner_show();
    }

    // add skip link
    SkipLinks::addIndex(_("Navigation Startseite"), 'index_navigation');

// display menue
?>
        <div class="" style="width:20%;float:<?=$USER_IS_ZWW_USER?'right':'left'?>;">
            <?if(!$USER_IS_ZWW_USER):?>
        <table class="index_box">
            <tr>
                    <td class="topic" style="font-weight: bold; padding:5px;" colspan="2">
                    <?= Assets::img('icons/16/white/home.png', array('class' => 'middle')) ?>
                    <?= htmlReady($ueberschrift) ?>
                </td>
            </tr>
            <? if ($perm->get_perm() == 'user') : ?>
            <tr>
                <td class="blank" style="padding: 1em 1em 0em 1em;" colspan="2">
                    <?= MessageBox::info(sprintf(_('Sie haben noch nicht auf Ihre %s Bestätigungsmail %s geantwortet.'), '<a href="'.$help_url.'" target="_blank">', '</a>'),
                            array(_('Bitte holen Sie dies nach, um Stud.IP Funktionen wie das Belegen von Veranstaltungen nutzen zu können.'),
                                sprintf(_('Bei Problemen wenden Sie sich an: %s'), '<a href="mailto:'.$GLOBALS['UNI_CONTACT'].'">'.$GLOBALS['UNI_CONTACT'].'</a>'))) ?>
                </td>
            </tr>
            <? endif ?>
            <tr>
                <td class="blank" valign="top" style="padding-left:14px; width:80%;" id="index_navigation">
                <? foreach (Navigation::getItem('/start') as $nav) : ?>
                    <? if ($nav->isVisible()) : ?>
                        <div class="mainmenu"  style="background:<?=(($ct++) % 2 == 1 ? '#F7F7F7' : '#EFEFF7 ') ?>;margin-right:1em;-moz-border-radius: 5px;border-radius: 5px; font-size:95%; font-weight: normal;" onmouseover="this.style.borderLeft='3px solid #7387ac';" onmouseout="this.style.borderLeft='0px';" >
                        <? if (is_internal_url($url = $nav->getURL())) : ?>
                            <a href="<?= URLHelper::getLink($url) ?>">
                        <? else : ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                        <? endif ?>
                        <?= htmlReady($nav->getTitle()) ?></a>
                        <? $pos = 0 ?>
                        <? foreach ($nav as $subnav) : ?>
                            <? if ($subnav->isVisible()) : ?>
                                <font size="-1">
                                <?= $pos++ ? ' / ' : '<br>' ?>
                                <? if (is_internal_url($url = $subnav->getURL())) : ?>
                                    <a href="<?= URLHelper::getLink($url) ?>">
                                <? else : ?>
                                    <a href="<?= htmlspecialchars($url) ?>" target="_blank">
                                <? endif ?>
                                <?= htmlReady($subnav->getTitle()) ?></a>
                                </font>
                            <? endif ?>
                        <? endforeach ?>
                        </div>
                    <? endif ?>
                <? endforeach ?>
               </div>
          
               <br>
                                </td>

                            </tr>
                        </table>
     <?//ende zwwuser
		endif?>
    <? if (!$SERVER_HAS_HIGH_LOAD): ?>
                                        <table class="index_box">
                                            <tr>
                                                <td class="topic" style="font-weight: bold; padding:5px;" >

                                                                                    <?= Assets::img('icons/16/white/institute.png', array('class' => 'middle')) ?>
                                                        <?= _('Mensaplan'); ?>

            </td>
        </tr>
        <tr>
            <td class="blank" style="padding: 0em 1em 0em 1em;" ><br>
                <div>Der Mensaplan ist nicht mehr direkt verfügbar.</div>
                <?=  formatReady('[Aktueller Plan als PDF]https://www.max-manager.de/daten-extern/augsburg/pdf/wochenplaene/mensa-uni/aktuell.pdf')?>
                <br><br>
            </td>
        </tr>
    </table>
    <? endif ?>
            </div>
            <!-- rechte box -->
            <div class="" style="width:76%; float: left; margin-left:1em; margin-top:-3px;">
                <table >
                    <?if(class_exists('PortalWidgetsPlugin') && !$USER_IS_ZWW_USER):?>
                                <tr>
                                    <td>
                                        <table class="index_box">
                                            <tr>
                                                <td class="topic" style="font-weight: bold;">
                                                <?= Assets::img('icons/16/white/person.png', array('class' => 'middle')) ?>
                                                   <?= htmlReady('Persönlicher Desktop') ?>
                                    </td>
                                </tr>
                                <tr><td class="index_box_cell">

                                        <script type="text/javascript">
                                            $sisi_jquery = jQuery;
                                            document.write("<div id=WidgetList></div>"); function getPortalWidgetsLoadingAnimation(description) { return '<span >'+ '<img src="<?=$GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins_packages/augsburg-medienlabor/PortalWidgetsPlugin/images/ajaxloader.gif" width=16 height=16>&nbsp;' + description+'</span>'; }$sisi_jquery.ajax({type:"GET",url:"<?=$GLOBALS['ABSOLUTE_URI_STUDIP'] ?>/plugins.php/PortalWidgetsPlugin/WidgetList",cache:false, context: $sisi_jquery("#WidgetList"), beforeSend : function(xhr){$sisi_jquery(this).html(getPortalWidgetsLoadingAnimation("Ihr Desktop wird geladen"));return true;}, success:function(data){$sisi_jquery(this).html(data);}})
                                        </script>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                  <?endif?>


        <tr>
            <td>
                 <?if(!(class_exists('PortalWidgetsPlugin'))  || !$USER_IS_ZWW_USER):?>
                <?endif?>
               <?
                if($USER_IS_ZWW_USER)
                {$layout = $GLOBALS['template_factory']->open('shared/index_box');
                  echo  $ZWW_PLUGIN->getTeaserHTML();
                 echo $ZWW_PLUGIN->getPortalTemplate()->render(null, $layout);
                }

                ?>
                <?
                show_news('studip', $perm->have_perm('root'), 0, $index_data['nopen'], "", $LastLogin, $index_data);
                #echo(ini_get('include_path'));
#require_once('lib/DbCalendarEventList.class.php');
                if (!$SERVER_HAS_HIGH_LOAD)
                {
        // display dates
    if (!$perm->have_perm('admin')) { // only dozent, tutor, autor, user
        include 'lib/show_dates.inc.php';
        $start = time();
        $end = $start + 60 * 60 * 24 * 7;
        if (get_config('CALENDAR_ENABLE')) {
            show_all_dates($start, $end, TRUE, FALSE, $index_data['dopen']);
        } else {
            show_dates($start, $end, $index_data['dopen']);
        }
    }

    // display votes
    if (get_config('VOTE_ENABLE')) {
        include 'lib/vote/vote_show.inc.php';
        show_votes('studip', $auth->auth['uid'], $perm);
        ?><script type="text/javascript" language="JavaScript">function openEval(evalID){evalwin=window.open(STUDIP.ABSOLUTE_URI_STUDIP+'show_evaluation.php?evalID='+evalID+'&isPreview=0',evalID,'width=790,height=500,scrollbars=yes,resizable=yes');evalwin.focus();}</script>
    <?}
}

                    $layout = $GLOBALS['template_factory']->open('shared/index_box');


// Prüfen, ob PortalPlugins vorhanden sind.
$portalplugins = PluginEngine::getPlugins('PortalPlugin');

foreach ($portalplugins as $portalplugin) {
    $template = $portalplugin->getPortalTemplate();

    if ($template && strtolower(get_class($portalplugin))!= 'zww') {
        echo $template->render(NULL, $layout);
        $layout->clear_attributes();
    }
}


                    page_close();

                    if (is_object($user) && $user->id != 'nobody')
                    {
                        $db->query(sprintf("SELECT * FROM rss_feeds WHERE user_id='%s' AND hidden=0 ORDER BY priority", $auth->auth["uid"]));
                        while ($db->next_record())
                        {
                            if ($db->f("name") != "" && $db->f("url") != "")
                            {
                                $feed = new RSSFeed($db->f("url"));
                                if ($db->f('fetch_title') && $feed->ausgabe->channel['title'])
                                {
                                    $feedtitle = $feed->ausgabe->channel['title'];
                                } else
                                {
                                    $feedtitle = $db->f("name");
                                }

                                ob_start();
                                $feed->rssfeed_start();
                                echo $layout->render(array('title' => $feedtitle, 'icon_url' => 'icons/16/white/rss.png', 'admin_url' => URLHelper::getLink('edit_about.php', array('view' => 'rss')), 'content_for_layout' => ob_get_clean()));
                            }
                        }
                    }


                    echo '';
                }
                ?>


            </td>
        </tr>

    </table>
</div>

<div style="clear:both;"></div>
<?
                include 'lib/include/html_end.inc.php';
            }


        /**
        *  theoretisch Ansicht fuer ausgeloggte
        *
        *
        *  falls man auch ausgeloggten eine eigene Seite zeigen moechte,
        * muesste man das implementieren.
        */
                function loggedout_action()
                {

                }



    /**
    * Lieferte einst den Mensaplan der Universitaet zurueck
     * 
    * @deprecated Mensaplan nicht mehr als HTML verfuegbar
    */
            function getMensa()
            {

                $cached_data = SimpleCache::getStringCache('mensaplan');
                    if ($cached_data)
                     return $cached_data;

                $curl = curl_init();
                // Set options
                curl_setopt($curl, CURLOPT_URL, 'http://web.studentenwerk-augsburg.de/verpflegung/_uni-aktuelle-woche.php');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($curl, CURLOPT_TIMEOUT, 2);


                $mensaplan = curl_exec($curl);
                //free ressource
                curl_close($curl);

                //fix fucked-up new lines and co
                $mensaplan = (str_replace(chr(10),'',$mensaplan));
                $mensaplan = (str_replace("\r",'',$mensaplan));
                $mensaplan = trim(str_replace('  ','',$mensaplan));
                
                //futterkategorien - letzte ist dummy zum abschneiden.
                //grill brauchtn leerzeichen wg "grillfleisch"
                $food_categories = explode(',', 'Asia / Vegetarisch<,Bayrisch - Schw&auml;bisch<,Mediterran<,Pizza / Pasta<,S&uuml;&szlig; &amp; Salzig<,Grill <,Zusatzstoff-');

                /*
                 * get all dates beginning from monday this week
                 */
                $today_array = getdate ( );
                $monday_timestamp = mktime(0,0,0,$today_array["mon"],$today_array["mday"]-$today_array["wday"]+1,$today_array["year"]);
                $a_whole_day_timestamp = 24*60*60;
                //das mit den foodcats existiert so nicht, dann versuchen wir wochentage...
                if(!strpos($mensaplan, $food_categories[2]))
                  $food_categories = explode(';', 'Montag, '.date('d.m.Y',$monday_timestamp).';Dienstag, '.date('d.m.Y',$monday_timestamp+1*$a_whole_day_timestamp).';Mittwoch, '.date('d.m.Y',$monday_timestamp+2*$a_whole_day_timestamp).';Donnerstag, '.date('d.m.Y',$monday_timestamp+3*$a_whole_day_timestamp).';Freitag, '.date('d.m.Y',$monday_timestamp+4*$a_whole_day_timestamp).';Pizza/Pasta;Zusatzstoff-');

                
                $plan_exploded = array();
                $plan_rest = $mensaplan;

                $last_category = null;
               
                //Kategorien durchgehn
                foreach($food_categories as $food_categorie)
                {   //hier drin ist jetzt alles ab nter category
                    $exp_temp = explode($food_categorie, $plan_rest);
                    $plan_rest = $exp_temp[1];

                    $plan_exploded[$last_category] =   ($exp_temp[0]);

                   $last_category = $food_categorie;

                }
                  $no_information_available_text = '<ul  style="list-style-type:square;"><li>'. _('Keine Mensa-Informationen verfügbar').'</li></ul>';
                  
                #nicht verfügbar...
                  if(!trim($plan_exploded[$food_categories[2]]))
                  {
                      $mensa_this_week = $no_information_available_text;
                  }
                  else
                  {

                            foreach($plan_exploded as $category =>  $food_in_cat)
                            {
                                $this_cat = $food_in_cat;
                                $this_cat_exp = explode('<tr>', $this_cat);
 
                                foreach($this_cat_exp as $i=>$content)
                                {   //remove numbers, prices, trim the stuff etc
                                    $tmp_cut_content = trim(str_replace(array('&nbsp;','>', '()', '/ ', '&euro;','je g','€',' je','/p','/td'), '',preg_replace("/[0-9,]/", "",str_replace(array('Stud.','Beschäft.'),'',strip_tags($content)))));
                                    if($tmp_cut_content)
                                        $this_cat_exp[$i] = $tmp_cut_content;
                                        else unset( $this_cat_exp[$i] );
                                }
                                 $plan_exploded[$category] = $this_cat_exp;
                            }

                            $mensacontainer_start =  '<ul style="list-style-type:square;">';
                            $mensacontainer_end = '</ul>';
                            $mensa_this_week = $mensacontainer_start;
                            foreach($plan_exploded as $food_cat => $meals)
                            {
                                if($food_cat && count($meals))
                                $mensa_this_week .= '<li>'.strip_tags($food_cat).'<ul><li>'.implode('</li><li>',$meals).'</li></ul></li>';
                            }
                            $mensa_this_week.=$mensacontainer_end;


                  }

                SimpleCache::cacheString($mensa_this_week, 'mensaplan', 43200);
                return($mensa_this_week);
            }



        }
?>