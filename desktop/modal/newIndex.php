<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$id = init('id');
$eqLogic = MesIndexCompteur::byId($id);
if (!is_object($eqLogic)) {
    throw new Exception(__('EqLogic non trouvé: ', __FILE__) . $id);
}

sendVarToJS('eqLogicId', $id);

?>

<div style="position: absolute;  left: 50%;  top: 50%;  -webkit-transform: translate(-50%, -50%);  transform: translate(-50%, -50%);">
    <div>
        <label for="txtNewIndex">New Index</label>
        <input type="text" id="txtNewIndex"/>
    </div>
    <div>
        <label for="ckbxSetNewReferenceValue">Index de référence</label>
        <input type="checkbox" id="ckbxSetNewReferenceValue"/>
    </div>
    <div style="text-align:right;">
        <button type="button" class="btn btn-xs btn-default action" id="newIndexSave">Save</button>
    </div>
</div>

<?php

$enterNewIndex = $eqLogic->getCmd(null, 'NewIndex');
$content = '<script type="text/javascript">
$("#newIndexSave").on("click", function () {
    var newIndex= $("#txtNewIndex").val();
    var isNewReference = $("#ckbxSetNewReferenceValue").is(":checked");
    jeedom.cmd.execute({id:'.$enterNewIndex->getId().', value: { val: newIndex, referenceIndex: isNewReference } });
    $("#txtNewIndex").val(\'\');
    $("#ckbxSetNewReferenceValue"). prop("checked", false);
    $("#md_modal").dialog(\'close\');
});
</script>';
echo $content;
?>