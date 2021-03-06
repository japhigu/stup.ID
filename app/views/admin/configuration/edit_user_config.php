<?
# Lifter010: TODO
use Studip\Button, Studip\LinkButton;

?>
<?= (isset($flash['error']))?MessageBox::error($flash['error'], $flash['error_detail']):'' ?>
<? if (empty($via_ajax)): ?>
<h2><?=_("Bearbeiten von Konfigurationsparameter f�r den Nutzer: ")?><?=$search_user['fullname']?></h2>
<? endif; ?>
<form action="<?= $controller->url_for('admin/configuration/user_configuration/update') ?>" method=post>
    <?= CSRFProtection::tokenTag() ?>
    <table class="default">
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?=_("Name:")?>(<em>field</em>) </td>
            <td>
                <input type="hidden" name = "field" value = "<?= htmlReady($search_user['field'])?>">
                <input type="hidden" name = "user_id" value = "<?= htmlReady($user_id)?>">
                <?= htmlReady($search_user['field']) ?>
            </td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?=_("Inhalt:")?>(<em>value</em>) </td>
            <td><textarea cols="55" rows="4" name="value"><?= htmlReady($search_user['value'])?></textarea></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td><?=_("Beschreibung:")?>(<em>description</em>) </td>
            <td><?= htmlReady($search_user['description'])?></td>
        </tr>
        <tr class="<?= TextHelper::cycle('cycle_odd', 'cycle_even') ?>">
            <td>&nbsp;</td>
            <td>
                <?= Button::createAccept(_('�bernehmen'),'uebernehmen', array('title' => _('�nderungen �bernehmen')))?>
                <?= LinkButton::createCancel(_('Abbrechen'), $controller->url_for('admin/configuration/configuration'),array('title' => _('Zur�ck zur �bersicht')))?>
            </td>
        </tr>
    </table>
</form>