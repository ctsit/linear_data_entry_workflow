document.addEventListener('DOMContentLoaded', function() {

  var $lockRecordRow = $('#__LOCKRECORD__-tr');
  var $lockRecordCheckBox = $('#__LOCKRECORD__');

  if($lockRecordCheckBox.is(":checked")) {
    $unlockBtn = $('input[value="Unlock form"]');
    $unlockBtn.hide();
  } else {
    $lockRecordRow.hide();
    $lockRecordCheckBox.prop('checked', true);
  }
});
