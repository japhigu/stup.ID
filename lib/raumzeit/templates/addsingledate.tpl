<?
# Lifter010: TODO
use Studip\Button,
    Studip\LinkButton;
?>
<TR>
    <TD colspan="9" class="steelgraulight">
        <FONT size="-1">
            &nbsp;<B><?=_("Neuer Termin:")?></B>
        </FONT>
    </TD>
</TR>
<TR>
    <TD class="steelgraulight" colspan="9">
        <a name="newSingleDate"></a>
        <FORM action="<?= URLHelper::getLink() ?>" method="post">
        <?= CSRFProtection::tokenTag() ?>
        <TABLE cellpadding="2" cellspacing="0" border="0" width="100%">
            <TR>
                <TD width="2%" align="right" valign="top" class="<?=$tpl['class']?>">
                    <IMG src="<?=$GLOBALS['ASSETS_URL']?>images/icons/16/blue/arr_1down.png" border="0" align="abstop">
                </TD>
                <TD width="98%" nowrap class="<?=$tpl['class']?>" colspan="8">
                    <FONT size="-1">
                        <INPUT type="text" id="day" name="day" maxlength="2" size="2" value="<?= $_REQUEST['day'] ? $_REQUEST['day'] : _("dd")?>">.
                        <INPUT type="text" id="month" name="month" maxlength="2" size="2" value="<?= $_REQUEST['month'] ? $_REQUEST['month'] : _("mm")?>">.
                        <INPUT type="text" id="year" name="year" maxlength="4" size="4" value="<?= $_REQUEST['year'] ? $_REQUEST['year'] : ("jjjj")?>">&nbsp;,&nbsp;
                        <INPUT type="text" id="start_stunde" name="start_stunde" maxlength="2" size="2" value="<?= $_REQUEST['start_stunde'] ? $_REQUEST['start_stunde'] : _("hh")?>">:
                        <INPUT type="text" id="start_minute" name="start_minute" maxlength="2" size="2" value="<?= $_REQUEST['start_minute'] ? $_REQUEST['start_minute'] : _("mm")?>">
                        <?=_("bis")?>
                        <INPUT type="text" id="end_stunde" name="end_stunde" maxlength="2" size="2" value="<?= $_REQUEST['end_stunde'] ? $_REQUEST['end_stunde'] : _("hh")?>">:
                        <INPUT type="text" id="end_minute" name="end_minute" maxlength="2" size="2" value="<?= $_REQUEST['end_minute'] ? $_REQUEST['end_minute'] : _("mm")?>">
                        <?=_("Uhr")?>
                    </FONT>
                    <?=Termin_Eingabe_javascript(1);?>
                </TD>
            </TR>
            <TR>
                <TD class="steelgraulight">&nbsp;</TD>
                <TD class="steelgraulight" colspan="2" valign="top">
                    <FONT size="-1">
                    <? if ($GLOBALS['RESOURCES_ENABLE']) { ?>
                    <?=_("Raum:")?>
                    <?= Assets::img('icons/16/blue/room_clear.png', array('class' => 'bookable_rooms_action', 'title' => _("Nur buchbare R�ume anzeigen"))) ?>
                    <SELECT name="room">
                        <OPTION value="nothing"><?=_("KEINEN Raum buchen")?></option>
                        <?
                        $resList->reset();
                        if ($resList->numberOfRooms()) {
                            while ($res = $resList->next()) {
                                echo '<OPTION value="'.$res['resource_id'].'">'.my_substr(htmlReady($res["name"]), 0, 30)."</OPTION>\n";
                            }
                        }
                        ?>
                    </SELECT>
                    <BR/>
                    <? } ?>
                    <?=_("freie Ortsangabe:")?>
                    <input name="freeRoomText" type="text" size="10" maxlength="255">
                    <?=$GLOBALS['RESOURCES_ENABLE']? _("(f&uuml;hrt <em>nicht</em> zu einer Raumbuchung)") : ''?>
                    </FONT>
                </TD>
                <TD class="steelgraulight" colspan="2" valign="top" nowrap>
                    <FONT size="-1">
                    <?=_("Art:");?>
                    <SELECT name="dateType">
                    <?
                    foreach ($TERMIN_TYP as $key => $val) {
                        echo '<OPTION value="'.$key.'"';
                        if ($key == 1) {
                            echo ' selected';
                        }
                        echo '>'.$val['name']."</OPTION>\n";
                    }
                    ?>
                    </FONT>
                </TD>
            </TR>
            <TR>
                <TD class="steelgraulight">&nbsp;</TD>
                <TD class="steelgraulight">
                    <label><?= _("Durchf�hrende Dozenten:") ?>
                    <SELECT name="related_teachers[]" multiple>
                    <? foreach ($sem->getMembers('dozent') as $dozent) : ?>
                        <OPTION value="<?= $dozent['user_id'] ?>"><?= htmlReady($dozent['fullname']) ?></OPTION>
                    <? endforeach ?>
                    </SELECT>
                    </label>
                </TD>
                <TD class="steelgraulight">&nbsp;</TD>
            </TR>
            <TR>
                <TD colspan="9" class="steelgraulight" align="center">                    
                    <INPUT type="hidden" name="cmd" value="doAddSingleDate">
                    <?= Button::createAccept(_('�bernehmen')) ?>
                    <?= LinkButton::createCancel(_('Abbrechen'), URLHelper::getURL()) ?>                    
                </TD>
            </TR>
        </TABLE>
        </FORM>
    </TD>
</TR>
<TR>
    <TD colspan="9" class="steel1" height="10"></TD>
</TR>
<?
unset($tpl)
?>
