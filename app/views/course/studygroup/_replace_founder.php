<?
# Lifter010: TODO
?>
<tr>
    <td style='text-align:right; vertical-align:top;'><?=_("Gruppengründer:")?></td>
    <td nowrap>
        <div style="width: 50%; float: left; vertical-align:middle;">
            <? if(is_array($founders) && sizeof($founders) > 0) : ?>
                <? foreach($founders as $founder) : ?>
                    <?= htmlReady(get_fullname_from_uname($founder['username'])) ?>
                <? endforeach; ?>
            <? endif; ?>
        </div>
        <? if(!empty($tutors)) :?>
            <div style="width: 50%; float: left; vertiacl-align:middle;">
                <input type="image" name="replace_founder" src="<?= Assets::image_path('icons/16/yellow/arr_2left.png') ?>" title="<?= _("Als GruppengründerIn eintragen") ?>">
                <select name="choose_founder">
                    <? foreach($tutors as $uid => $tutor) : ?>
                        <option value="<?=$uid?>"> <?= htmlReady($tutor['fullname']) ?> </option>
                    <? endforeach ; ?>
                </select>
            </div>
        <? endif; ?>
    </td>
</tr>
