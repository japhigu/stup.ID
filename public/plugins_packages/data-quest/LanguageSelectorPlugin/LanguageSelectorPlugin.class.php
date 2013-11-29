<?php

class LanguageSelectorPlugin extends StudipPlugin implements SystemPlugin
{
    function __construct()
    {
        global $_language;
        parent::__construct();
        if(array_key_exists(Request::get('set_language'), $GLOBALS['INSTALLED_LANGUAGES'])) {
            $_language = $_SESSION['_language'] = Request::get('set_language');
            init_i18n($_language);
            if (is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody') {
                $st = DbManager::get()->prepare("UPDATE user_info SET preferred_language=? WHERE user_id=? LIMIT 1");
                $st->execute(array($_language, $GLOBALS['user']->id));
                page_close();
                while (ob_get_level()) ob_end_clean();
                header("Location: " . UrlHelper::getUrl($_SERVER["REQUEST_URI"], array('set_language' => null)));
                die();
            }
        }
        $languages = $GLOBALS['INSTALLED_LANGUAGES'];
        unset($languages[$_language]);
        reset($languages);
        $change_language_key = key($languages);
        $change_language_name = $languages[$change_language_key]['name'];
        if (is_object($GLOBALS['user']) && $GLOBALS['user']->id != 'nobody') {
            Navigation::insertItem('/links/test', new Navigation($change_language_name, $_SERVER["REQUEST_URI"], array('set_language' => $change_language_key)), 'logout');
        } else {
            Navigation::insertItem('/links/test', new Navigation($change_language_name, $_SERVER["REQUEST_URI"], array('set_language' => $change_language_key)), 'login');
        }
    }
}
