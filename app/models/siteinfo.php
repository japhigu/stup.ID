<?php
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
/*
 * siteinfo - display information about Stud.IP
 *
 * Copyright (c) 2008  Ansgar Bockstiegel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once('lib/visual.inc.php');
require_once('lib/user_visible.inc.php');

class Siteinfo {
    private $sme; //SiteinfoMarkupEngine
    private $rubrics_empty; //boolean; true if there is no rubric
    private $db; //DBManager

    function __construct() {
        $this->sme = new SiteinfoMarkupEngine();
        $this->db = DBManager::get();
    }
    function get_detail_content($id) {
        global $perm;
        //first we define some fallbacks
        if ($id == 0) {
            //users with root priveleges get a hint whether what to do...
            if ($perm->have_perm('root')) {
                if ($this->rubrics_empty) {
                    return _("Benutzen Sie den Link �neue Rubrik anlegen� in der Infobox, um eine Rubrik anzulegen.");
                } else {
                    return _("Benutzen Sie den Link �neue Seite anlegen� in der Infobox, um eine Seite in dieser Rubrik anzulegen.");
                }
            //...while unauthorized users just get informed that there's something missing und who might be the person to fix this
            } else {
                return _("Der f�r diese Stud.IP-Installation verantwortliche Administrator muss hier noch Inhalte einf�gen.\n(:rootlist:)");
            }
        } else {
            $sql = "SELECT content
                    FROM siteinfo_details
                    WHERE detail_id = ".$this->db->quote($id,PDO::PARAM_INT);
            $result = $this->db->query($sql);
            $rows = $result->fetch();
            return $rows[0];
        }
    }

    function get_detail_name($id) {
        $sql = "SELECT name
                FROM siteinfo_details
                WHERE detail_id = ".$this->db->quote($id,PDO::PARAM_INT);
        $result = $this->db->query($sql);
        $rows = $result->fetch();
        return $rows[0];
    }

    function get_detail_content_processed($id) {
        //applying Schnellformatierungen and Siteinfo-specific markup to the content
        $content = $this->get_detail_content($id);
        $output = $this->sme->siteinfoDirectives(formatReady(language_filter($content)));
        return $output;
    }

    function get_all_details() {
        $sql = "SELECT detail_id, rubric_id, name
                FROM siteinfo_details
                ORDER BY position, detail_id ASC";
        $result = $this->db->query($sql);
        return $result->fetchAll();
    }

    function first_detail_id($rubric = NULL) {
        $rubric_id = $rubric ? $rubric : $this->first_rubric_id();
        $sql = "SELECT detail_id
                FROM siteinfo_details ";
        if($rubric_id) {
            $sql .= "WHERE rubric_id = ".$this->db->quote($rubric_id,PDO::PARAM_INT);
        }
        $sql .= " ORDER BY position, detail_id ASC
                 LIMIT 1";
        $result = $this->db->query($sql);
        $rows = $result->fetch();
        if (count($rows) > 0) {
            return $rows[0];
        } else {
            return 0;
        }
    }

    function get_all_rubrics() {
        $sql = "SELECT rubric_id, name
                FROM siteinfo_rubrics
                ORDER BY position, rubric_id ASC";
        $result = $this->db->query($sql);
        return $result->fetchAll();
    }

    function first_rubric_id() {
        $sql = "SELECT rubric_id
                FROM siteinfo_rubrics
                ORDER BY position, rubric_id ASC
                LIMIT 1";
        $result = $this->db->query($sql);
        $rows = $result->fetch();
        if ($result->rowCount() > 0) {
            return $rows[0];
        } else {
            $this->rubrics_empty = TRUE;
            return NULL;
        }
    }

    function rubric_for_detail($id) {
        $sql = "SELECT rubric_id
                FROM siteinfo_details
                WHERE detail_id = ".$this->db->quote($id,PDO::PARAM_INT);
        $result = $this->db->query($sql);
        $rows = $result->fetch();
        return $rows[0];
    }

    function rubric_name($id) {
        $sql = "SELECT name
                FROM siteinfo_rubrics
                WHERE rubric_id = ".$this->db->quote($id,PDO::PARAM_INT);
        $result = $this->db->query($sql);
        $rows = $result->fetch();
        return $rows[0];
    }

    function save($type, $input) {
        //distinguish the subject and the action (modification/insertion)
        switch ($type) {
            case "update_detail":
                $this->db->exec("UPDATE siteinfo_details
                                 SET rubric_id = ".$this->db->quote($input['rubric_id'],PDO::PARAM_INT).",
                                     name = ".$this->db->quote($input['detail_name']).",
                                     content = ".$this->db->quote($input['content'])."
                                 WHERE detail_id=".$this->db->quote($input['detail_id'],PDO::PARAM_INT));
                $rubric = $input['rubric_id'];
                $detail = $input['detail_id'];
                break;
            case "insert_detail":
                $this->db->exec("INSERT
                           INTO siteinfo_details
                           (rubric_id,
                            name,
                            content)
                           VALUES (".$this->db->quote($input['rubric_id'],PDO::PARAM_INT).",
                                   ".$this->db->quote($input['detail_name']).",
                                   ".$this->db->quote($input['content']).");");
                $rubric = $input['rubric_id'];
                $detail = $this->db->lastInsertId();
                break;
            case "update_rubric":
                $this->db->exec("UPDATE siteinfo_rubrics
                           SET name = ".$this->db->quote($input['rubric_name'])."
                           WHERE rubric_id = ".$this->db->quote($input['rubric_id'],PDO::PARAM_INT).";");
                $rubric = $input['rubric_id'];
                $detail = $this->first_detail_id($rubric);
                break;
            case "insert_rubric":
                $this->db->exec("INSERT
                           INTO siteinfo_rubrics
                           (name)
                           VALUES (".$this->db->quote($input['rubric_name']).");");
                $rubric = $this->db->lastInsertId();
                $detail = 0;
        }
        return array($rubric, $detail);
    }

    function delete($type,$id) {
        if($type=="rubric") {
            $this->db->exec("DELETE FROM siteinfo_details WHERE rubric_id = ".$this->db->quote($id).";");
            $this->db->exec("DELETE FROM siteinfo_rubrics WHERE rubric_id = ".$this->db->quote($id).";");
        } else {
            $this->db->exec("DELETE FROM siteinfo_details WHERE detail_id = ".$this->db->quote($id).";");
        }
    }
}

class SiteinfoMarkupEngine {
    private $db;
    private $template_factory;
    private $siteinfo_directives;
    //a copy of wiki-engine to support specialized markup in order
    //to preserve (parts?) of the old impressum.php-functionality
    //and add new markup as needed

    function __construct() {
        $this->db = DBManager::get();
        $this->template_factory = new Flexi_TemplateFactory($GLOBALS['STUDIP_BASE_PATH'].'/app/views/siteinfo/markup/');
        $this->siteinfoMarkup("/\(:version:\)/e",'$this->version()');
        $this->siteinfoMarkup("/\(:uniname:\)/e",'$this->uniName()');
        $this->siteinfoMarkup("/\(:unicontact:\)/e",'$this->uniContact()');
        $this->siteinfoMarkup("/\(:userinfo ([a-z_@\-]*):\)/e",'$this->userinfo(\'$1\')');
        $this->siteinfoMarkup("/\(:userlink ([a-z_@\-]*):\)/e",'$this->userlink(\'$1\')');
        $this->siteinfoMarkup("/\(:rootlist:\)/e",'$this->rootlist()');
        $this->siteinfoMarkup("/\(:adminlist:\)/e",'$this->adminlist()');
        $this->siteinfoMarkup("/\(:coregroup:\)/e",'$this->coregroup()');
        $this->siteinfoMarkup("/\(:toplist ([a-z]*):\)/ei",'$this->toplist(\'$1\')');
        $this->siteinfoMarkup("/\(:indicator ([a-z_\-]*):\)/ei",'$this->indicator(\'$1\')');
        $this->siteinfoMarkup("/\(:history:\)/e",'$this->history()');
        $this->siteinfoMarkup("'\[style=(&quot;)?(.*?)(&quot;)?\]\s*(.*?)\s*\[/style\]'es",'$this->style(\'$2\', \'$4\')');
    }

    function siteinfoMarkup($pattern, $replace) {
        //function to register markup for later processing
        $this->siteinfo_directives[] = array($pattern, $replace);
    }

    function siteinfoDirectives($str) {
        //function to process registered markup
        if (is_array($this->siteinfo_directives)) {
            foreach ($this->siteinfo_directives as $direct) {
                $str = preg_replace($direct[0],$direct[1],$str);
            }
        }
        return $str;
    }

    function version() {
        return htmlentities($GLOBALS['SOFTWARE_VERSION']);
    }

    function uniName() {
        return htmlentities($GLOBALS['UNI_NAME_CLEAN']);
    }

    function uniContact() {
        $template = $this->template_factory->open('uniContact');
        $template->contact = $GLOBALS['UNI_CONTACT'];
        return $template->render();
    }

    function userinfo($input) {
        $template = $this->template_factory->open('userinfo');
        $sql = "SELECT ".$GLOBALS['_fullname_sql']['full'] ." AS fullname,
                       Email,
                       username
                FROM auth_user_md5
                LEFT JOIN user_info USING (user_id)
                WHERE username=".$this->db->quote($input)."
                AND ".get_vis_query();
        $result = $this->db->query($sql);
        if ($result->rowCount() == 1) {
            $user = $result->fetch(PDO::FETCH_ASSOC);
            $template->username = $user['username'];
            $template->fullname = $user['fullname'];
            $template->email = $user['Email'];
        } else {
            $template->error = TRUE;
        }
        return $template->render();
    }

    function userlink($input) {
        $template = $this->template_factory->open('userlink');
        $sql = "SELECT ".$GLOBALS['_fullname_sql']['full'] ." AS fullname,
                       username
                FROM auth_user_md5
                LEFT JOIN user_info USING (user_id)
                WHERE username=".$this->db->quote($input)."
                AND ".get_vis_query();
        $result = $this->db->query($sql);
        if ($result->rowCount() == 1) {
            $user = $result->fetch(PDO::FETCH_ASSOC);
            $template->username = $user['username'];
            $template->fullname = $user['fullname'];
        } else {
            $template->error = TRUE;
        }
        return $template->render();
    }


    function rootlist() {
        $template = $this->template_factory->open('rootlist');
        $sql = "SELECT ".$GLOBALS['_fullname_sql']['full'] ." AS fullname,
                       Email,
                       username
                FROM auth_user_md5
                LEFT JOIN user_info USING (user_id)
                WHERE perms='root'
                AND ".get_vis_query()."
                ORDER BY Nachname";
        $result = $this->db->query($sql);
        if ($result->rowCount() > 0) {
            $template->users = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $template->error = TRUE;
        }
        return $template->render();
    }

    function adminList() {
        $template = $this->template_factory->open('adminList');
        $sql = "SELECT Institute.Name AS institute,
                ".$GLOBALS['_fullname_sql']['full'] ." AS fullname,
                auth_user_md5.Email,
                auth_user_md5.username
                FROM user_inst
                LEFT JOIN Institute ON (user_inst.institut_id = Institute.Institut_id)
                LEFT JOIN auth_user_md5 USING (user_id)
                LEFT JOIN user_info USING (user_id)
                WHERE inst_perms='admin'
                AND ".get_vis_query()."
                ORDER BY Institute.Name, auth_user_md5.Nachname, auth_user_md5.Vorname";
        $result = $this->db->query($sql);
        if ($result->rowCount() > 0) {
            $template->admins = $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $template->error = TRUE;
        }
        return $template->render();
    }

    function coregroup() {
        $cache = StudipCacheFactory::getCache();
        if (!($remotefile = $cache->read('coregroup'))) {
            $remotefile = file_get_contents('http://develop.studip.de/studip/extern.php?module=Persons&config_id=8d1dafc3afca2bce6125d57d4119b631&range_id=4498a5bc62d7974d0a0ac3e97aca5296');
            $cache->write('coregroup', $remotefile);
        }
        $out = str_replace(array('class="normal"','align="left"'), array("",""), $remotefile);
        return $out;
    }

    function toplist($item) {
        $template = $this->template_factory->open('toplist');
        switch ($item) {
            case "mostparticipants":
                $template->heading = _("die meisten Teilnehmer");
                $sql = "SELECT seminar_user.seminar_id,
                               seminare.name AS display,
                               count(seminar_user.seminar_id) AS count
                        FROM seminar_user
                        INNER JOIN seminare USING(seminar_id)
                        WHERE seminare.visible = 1
                        GROUP BY seminar_user.seminar_id
                        ORDER BY count DESC
                        LIMIT 10";
                $template->type = "seminar";
                break;
            case "recentlycreated":
                $template->heading = _("zuletzt angelegt");
                $sql = "SELECT seminare.seminar_id,
                               seminare.name AS display,
                               FROM_UNIXTIME(mkdate, '%d.%m.%Y %h:%i:%s') AS count
                        FROM seminare
                        WHERE visible = 1
                        ORDER BY mkdate DESC
                        LIMIT 10";
                $template->type = "seminar";
                break;
            case "mostdocuments":
                $template->heading = _("die meisten Materialien (Dokumente)");
                $sql = "SELECT a.seminar_id,
                               b.name AS display,
                               count(a.seminar_id) AS count
                        FROM seminare b
                        INNER JOIN dokumente a USING(seminar_id)
                        WHERE b.visible=1
                        GROUP BY a.seminar_id
                        ORDER BY count DESC
                        LIMIT 10";
                $template->type = "seminar";
                break;
            case "mostpostings":
                $template->heading = _("die aktivsten Veranstaltungen (Postings der letzten zwei Wochen)");
                $sql = " SELECT a.seminar_id,
                                b.name AS display,
                                count( a.seminar_id ) AS count
                         FROM px_topics a
                         INNER JOIN seminare b USING ( seminar_id )
                         WHERE b.visible = 1
                         AND a.mkdate > UNIX_TIMESTAMP( NOW( ) - INTERVAL 2 WEEK )
                         GROUP BY a.seminar_id
                         ORDER BY count DESC
                         LIMIT 10 ";
                $template->type = "seminar";
                break;
            case "mostvisitedhomepages":
                $template->heading = _("die beliebtesten Profile (Besucher)");
                $sql = "SELECT auth_user_md5.user_id,
                               username,
                               views AS count,
                             ".$GLOBALS['_fullname_sql']['full'] . " AS display
                        FROM object_views
                        LEFT JOIN auth_user_md5 ON(object_id=auth_user_md5.user_id)
                        LEFT JOIN user_info USING (user_id)
                        WHERE auth_user_md5.user_id IS NOT NULL
                        ORDER BY count DESC
                        LIMIT 10";
                $template->type = "user";
                break;
        }
        if($sql) {
            $result = $this->db->query($sql);
            if  ($result->rowCount() > 0) {
                $template->lines = $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return "";
            }
        } else {
            return "";
        }
        return $template->render();
    }

    function indicator($key) {
        $template = $this->template_factory->open('indicator');
        $indicator['seminar_all'] = array("query" => "SELECT count(*) FROM seminare",
                                          "title" => _("Aktive Veranstaltungen"),
                                          "detail" => _("alle Veranstaltungen, die nicht archiviert wurden"));
        $indicator['seminar_archived'] = array("query" => "SELECT count(*) FROM archiv",
                                               "title" => _("Archivierte Veranstaltungen"),
                                               "detail" => _("alle Veranstaltungen, die archiviert wurden"));
        $indicator['institute_secondlevel_all'] = array("query" => "SELECT count(*) FROM Institute WHERE Institut_id != fakultaets_id",
                                                        "title" => _("beteiligte Einrichtungen"),
                                                        "detail" => _("alle Einrichtungen au�er den Fakult�ten"));
        $indicator['institute_firstlevel_all'] = array("query" => "SELECT count(*) FROM Institute WHERE Institut_id = fakultaets_id",
                                                       "title" => _("beteiligte Fakult�ten"),
                                                       "detail" => _("alle Fakult�ten"));
        $indicator['user_admin'] = array("query" => "SELECT count(*) FROM auth_user_md5 WHERE perms='admin'",
                                         "title" => _("registrierte Administratoren"),
                                         "detail" => "");
        $indicator['user_dozent'] = array("query" => "SELECT count(*) FROM auth_user_md5 WHERE perms='dozent'",
                                          "title" => _("registrierte Dozenten"),
                                          "detail" => "");
        $indicator['user_tutor'] = array("query" => "SELECT count(*) FROM auth_user_md5 WHERE perms='tutor'",
                                         "title" => _("registrierte Tutoren"),
                                         "detail" => "");
        $indicator['user_autor'] = array("query" => "SELECT count(*) FROM auth_user_md5 WHERE perms='autor'",
                                         "title" => _("registrierte Autoren"),
                                         "detail" => "");
        $indicator['posting'] = array("query" => "SELECT count(*) FROM px_topics",
                                      "title" => _("Forenbeitr�ge"),
                                      "detail" => "");
        $indicator['document'] = array("query" => "SELECT count(*) FROM dokumente WHERE url = ''",
                                       "title" => _("Dokumente"),
                                       "detail" => "");
        $indicator['link'] = array("query" => "SELECT count(*) FROM dokumente WHERE url != ''",
                                   "title" => _("verlinkte Dateien"),
                                   "detail" => "");
        $indicator['litlist'] = array("query" => "SELECT count(*) FROM lit_list",
                                      "title" => _("Literaturlisten"),
                                      "detail" => "");
        $indicator['termin'] = array("query" => "SELECT count(*) FROM termine",
                                     "title" => _("Termine"),
                                     "detail" => "");
        $indicator['news'] = array("query" => "SELECT count(*) FROM news",
                                   "title" => _("Ank�ndigungen"),
                                   "detail" => "");
        $indicator['guestbook'] = array("query" => "SELECT count(*) FROM user_info WHERE guestbook='1'",
                                        "title" => _("G�steb�cher"),
                                        "detail" => "");
        $indicator['vote'] = array("query" => "SELECT count(*) FROM vote WHERE type='vote'",
                                   "title" => _("Umfragen"),
                                   "detail" => "",
                                   "constraint" => get_config('VOTE_ENABLE'));
        $indicator['test'] = array("query" => "SELECT count(*) FROM vote WHERE type='test'",
                                   "title" => _("Tests"),
                                   "detail" => "",
                                   "constraint" => get_config('VOTE_ENABLE'));
        $indicator['evaluation'] = array("query" => "SELECT count(*) FROM eval",
                                         "title" => _("Evaluationen"),
                                         "detail" => "",
                                         "constraint" => get_config('VOTE_ENABLE'));
        $indicator['wiki_pages'] = array("query" => "SELECT COUNT(DISTINCT keyword) AS count FROM wiki",
                                         "title" => _("Wiki-Seiten"),
                                         "detail" => "",
                                         "constraint" => get_config('WIKI_ENABLE'));
        $indicator['resource'] = array("query" => "SELECT COUNT(*) FROM resources_objects",
                                       "title" => _("Ressourcen-Objekte"),
                                       "detail" => _("von Stud.IP verwaltete Ressourcen wie R�ume oder Ger�te"),
                                       "constraint" => $RESOURCES_ENABLE);
        if (in_array($key,array_keys($indicator))) {
            if (!isset($indicator[$key]['constraint']) || $indicator[$key]['constraint']) {
                $result = $this->db->query($indicator[$key]['query']);
                $rows = $result->fetch(PDO::FETCH_NUM);
                $template->title = $indicator[$key]['title'];
                if ($indicator[$key]['detail']) {
                    $template->detail = $indicator[$key]['detail'];
                }
                $template->count = $rows[0];
            } else {
                return "";
            }
        } else {
            return "";
        }
        return $template->render();
    }

    function history() {
        return formatReady(file_get_contents($ABSOLUTE_PATH_STUDIP.'history.txt'));
    }
    function style($style, $styled) {
        $style = str_replace('\"', '"', $style);
        $styled = str_replace('\"', '"', $styled);
        return '<div style="'.$style.'">'.$styled.'</div>';
    }
}

//functions for language filtering; used both in page-content and detail- and rubric-names

function language_filter($input) {
    return preg_replace("'\[lang=(\w*)\]\s*(.*?)\s*\[/lang\]'es",
                        'stripforeignlanguage(\'$1\', \'$2\')',
                        $input);
}

function stripforeignlanguage($language, $text) {
    global $_language;
    list($primary, $sub) = explode('_',$_language);
    if (($language==$primary) || ($language==$_language)) {
        return str_replace('\"', '"', $text);
    } else {
        return '';
    }
}

?>
