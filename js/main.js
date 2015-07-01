var formHasErrors = false;
var sendStr="";

$(document).ready(function(){
  $('form[method=post]').updatePost();
  $('.sort').addSortable('/biedri');
  $('#headerList').setWidth("#listItems");
  $('.filters.export .filter_header, .filters.picture .filter_header').addSlideTrigger();
  $('.leftFilters .filter_header').addSlideTriggerLeft();
  $('.orderTable').setMaxSel();
  $('ul.itemLists li').addEdit();
  $('.assignM').assignButton();
  $('.pagination').alignCenter('horizontal');
  $().updateInput();
  $('[data-ask]').click(function(e){
    if(!confirm($(this).attr('data-ask'))) {
      return false;
      e.preventDefault();
    }
  });
  $('#logout').readInput();
});


function displayCurtains(closeTrigger) {
  var windowWidth = $(window).width();
  var windowHeight = $(window).height();  
  $('#curtains').css({height: windowHeight, width: windowWidth, zIndex: "1000"}).animate({opacity: 0.5},'fast').addCloseTrigger(closeTrigger);
  $('#curtainsContainer').animate({opacity: 1},'fast').children('button').addCloseTrigger(closeTrigger);
}

function displayMessage(message,type,closeTrigger) {
  $('#curtainsContainer').removeClass('success error').html(message).addClass(type).addCloseButton().alignCenter();
  displayCurtains(closeTrigger);
}

$(function() {
  $( '.pickDate' ).datepicker({
    changeMonth: true,
    changeYear: true,
    format: 'dd.mm.yyyy'
  });
});

var selectedInput = null;
$(function() {
    $('input, textarea, select').focus(function() {
        selectedInput = $(this);
    }).blur(function(){
        selectedInput = null;
    });
});

function checkPK(form) {
  if($('input[name=personalCode][data-submit]').val().match(/\d\d\d\d\d\d-[12]\d\d\d\d/)) {
    $('input[name=personalCode][data-submit]').removeAttr("data-error");
    return true;
  } else {
    alert("Personas Kods nav pareizs");
    $('input[name=personalCode][data-submit]').attr("data-error","1");
    return false;
  }
}
