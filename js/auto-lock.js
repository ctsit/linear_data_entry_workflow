document.addEventListener('DOMContentLoaded', function() {

  //get UI elements of interest
  var $lockRecordRow = $('#__LOCKRECORD__-tr');
  var $lockRecordCheckBox = $('#__LOCKRECORD__');

  //if form is already locked
  if($lockRecordCheckBox.is(":checked")) {
    //hide unlock button
    $unlockBtn = $('input[value="Unlock form"]');
    $unlockBtn.hide();
  } else {
    //hide row with the lock button
    $lockRecordRow.hide();

    //when the form_complete dropdown is changed
    $formCompleteDropdown = $('select[name$="_complete"]');
    $formCompleteDropdown.change(function(event) {
      //if the form_complete value is set to "complete" then lock the form
      if($formCompleteDropdown.val() == "2") {
        $lockRecordCheckBox.prop('checked', true);
      } else {
        $lockRecordCheckBox.prop('checked', false);
      }
    });
  }
});
