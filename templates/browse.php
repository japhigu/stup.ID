<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<!-- SEARCHBOX -->
<form action="<?= URLHelper::getLink() ?>" method="post">
<?= CSRFProtection::tokenTag() ?>
<div class="topic"><b><?=_("Suche nach Personen")?></b></div>

<? if($sms_msg):?>
<? parse_msg($sms_msg); ?>
<? endif; ?>

<!-- form zur wahl der institute -->
<div style="width: 100%;">
<table width="100%" cellpadding="2" cellspacing="0">
    <? if (count($institutes)): ?>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td style="white-space: nowrap;">
            <b><?=_("in Einrichtungen:")?></b>
        </td>
        <td width="90%">
        <select name="inst_id" style="min-width: 400px;">
            <option value="0">- - -</option>
            <? foreach ($institutes as $institute): ?>
            <option value="<?=$institute['id']?>" <?= $institute['id'] == $inst_id ? 'selected="selected"' : '' ?>><?= htmlReady($institute['name']) ?></option>
            <? endforeach;?>
        </select>
        </td>
    </tr>
    <? endif ?>
    <!-- form zur wahl der seminare -->
    <? if (count($courses)): ?>
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td style="white-space: nowrap;">
            <b><?=_("in Veranstaltungen:")?></b>
        </td>
        <td width="90%">
        <select name="sem_id" style="min-width: 400px;">
            <option value="0">- - -</option>
            <? foreach ($courses as $course): ?>
            <option value="<?=$course['id']?>" <?= $course['id'] == $sem_id ? 'selected="selected"' : '' ?>><?= htmlReady($course['name']) ?></option>
            <? endforeach;?>
        </select>
        </td>
    </tr>
    <? endif ?>
    <!-- form zur freien Suche -->
    <tr class="<?= TextHelper::cycle('steel1', 'steelgraulight') ?>">
        <td>
            <b><?=_("Name:")?></b>
        </td>
        <td width="90%">
            <?= QuickSearch::get('name', $search_object)
                    ->setInputStyle('width: 400px')
                    ->defaultValue('', $name)
                    ->fireJSFunctionOnSelect('STUDIP.Browse.selectUser')
                    ->noSelectbox()
                    ->render() ?>
        </td>
    </tr>
    <tr class="steel2">
        <td colspan="2" align="center">
            <?= Button::create(_('Suchen'), 'send')?>
            <?= Button::create(_('Zur�cksetzen'), 'reset')?>
        </td>
    </tr>
</table>
</div>
</form>
<br>

<!-- RESULTS -->
<? if (isset($users)):?>
<div class="topic"><b><?=_("Ergebnisse:")?></b></div>

<div style="width: 100%;">
<table width="100%" cellpadding="2" cellspacing="0">
    <tr>
        <th align="left">
            <a href="<?= URLHelper::getLink('', compact('name', 'sem_id', 'inst_id')) ?>"><?=_("Name")?></a>
        </th>
        <th align="left">
            <? if ($inst_id): ?>
            <?= _("Funktion an der Einrichtung") ?>
            <? elseif ($sem_id): ?>
            <a href="<?= URLHelper::getLink('', compact('name', 'sem_id') + array('sortby' => 'status')) ?>"><?= _("Status in der Veranstaltung") ?></a>
            <? else: ?>
            <a href="<?= URLHelper::getLink('', compact('name') + array('sortby' => 'perms')) ?>"><?= _("globaler Status") ?></a>
            <? endif; ?>
        </th>
        <th align="right">
            <?=_("Nachricht verschicken")?>
        </th>
    </tr>
    <? foreach ($users as $user): ?>
    <tr class="<?=TextHelper::cycle('cycle_odd', 'cycle_even')?>">
        <td>
            <a href="<?= URLHelper::getLink('about.php', array('username' => $user['username'])) ?>">
                <?= Avatar::getAvatar($user['user_id'], $user['username'])->getImageTag(Avatar::SMALL) ?>
                <?= htmlReady($user['fullname']) ?>
            </a>
        </td>
        <td>
            <?= htmlReady($user['status']) ?>
        </td>
        <td align="right">
            <?= $user['chat'] ?>
            <a href="<?= URLHelper::getLink('sms_send.php', array('sms_source_page' => 'browse.php', 'rec_uname' => $user['username'])) ?>">
                <?= Assets::img('icons/16/blue/mail.png', array('class' => 'text-top', 'title' =>_('Nachricht an Benutzer verschicken')))?>
            </a>
        </td>
    </tr>
    <? endforeach; ?>
</table>
</div>
<? elseif ($name != ''): ?>
    <?= MessageBox::info(_('Es wurde niemand gefunden.')) ?>
<? elseif (isset($name)): ?>
    <?= MessageBox::error(_('Bitte einen Vor- oder Nachnamen eingeben.')) ?>
<? endif; ?>

<?
$infobox = array(
    'picture' => 'infobox/board2.jpg',
    'content' => array(
        array("kategorie" => _("Information:"),
            "eintrag" => array(
                array(
                    "icon" => 'icons/16/black/person.png',
                    "text" => _("Hier k�nnen Sie die Profile aller NutzerInnen abrufen, die im System registriert sind.")
                ),
                array(
                    "icon" => 'icons/16/black/institute.png',
                    "text" => _("Sie erhalten auf der Profilseite von MitarbeiternInnen an Einrichtungen auch weiterf&uuml;hrende Informationen, wie Sprechstunden und Raumangaben.")
                ),
                array(
                    "icon" => 'icons/16/black/info.png',
                    "text" => _("W�hlen Sie den gew�nschten Bereich aus oder suchen Sie nach einem Namen!")
                )
            )
        ),
        array("kategorie" => _("Ansichten:"),
            "eintrag" => array(
                array(
                    "icon" => 'icons/16/black/crown.png',
                    "text" => '<a href="'.URLHelper::getLink('score.php').'">'._("Zur Stud.IP-Rangliste").'</a>'
                )
            )
        )
    )
);
?>
