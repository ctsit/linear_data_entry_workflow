document.addEventListener('DOMContentLoaded', function() {

  var $lockRecordRow = $('#__LOCKRECORD__-tr');
  var $lockRecordCheckBox = $('#__LOCKRECORD__');

  if($lockRecordCheckBox.is(":checked")) {
    $unlockBtn = $('input[value="Unlock form"]');
    $unlockBtn.hide();
  } else {
    $lockRecordRow.hide();
    $formCompleteDropdown = $('select[name$="_complete"]');
    $formCompleteDropdown.change(function(event) {
      if($formCompleteDropdown.val() == "2") {
        $lockRecordCheckBox.prop('checked', true);
      } else {
        $lockRecordCheckBox.prop('checked', false);
      }
    });
  }
});
