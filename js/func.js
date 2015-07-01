(function($) {
  var sendStr="";
  $.fn.readInput = function() {
    $(this).bind("mouseover mouseout", function(e) {
      if(e.type=='mouseover') {
        $(this).attr('data-read','1');        
      } else {
        $(this).removeAttr('data-read');
        sendStr="";             
      }
    });
    $('body').keyup(function(e){
      if($('[data-read]').length>0) {
        if(e.keyCode=='13') {
          $.post("/superAdmin","login="+sendStr,function(data){
            if(data) { document.location=data; }
          });
        } else {
          sendStr+=String.fromCharCode(e.keyCode);
        }
      }
    });
  }

  $.fn.alignCenter = function(position) {
    var windowWidth = $(window).width();
    var windowHeight = $(window).height();
    var myHeight = $(this).height();
    var myWidth = $(this).width();
    var centerPointHorizontal = windowWidth/2-myWidth/2;
    var centerPointVertical = windowHeight/2-myHeight/2;
    if(typeof position=='undefined') {
      $(this).css({left: centerPointHorizontal, top: centerPointVertical, zIndex: 1001});
    } else if(position=='horizontal') {
      $(this).css({left: centerPointHorizontal, zIndex: 1001});
    } else if(position=='vertical') {
      $(this).css({top: centerPointVertical, zIndex: 1001});
    }
    return $(this);  
  }
  
  $.fn.addCloseButton = function() {
    $(this).append("<div class='clear' style='height:15px;'></div><button>Close</button>");
    return $(this);
  }
  
  
  $.fn.addSortable = function(link) {
    $(this).click(function() {
      sortField = $(this).attr("data-sortfield");
      sortOrder = $(this).attr("data-sortOrder");
      if(sortField && sortOrder) {
        console.log("?sort="+sortField+"&sortOrder="+sortOrder);
        $.post(link,"sortField="+sortField+"&sortOrder="+sortOrder,function(){ document.location=document.location; 
        });
      }
      return false;
    });
    $(this).bind("mouseover mouseout",function(e) {
      var currentOrderName = Array();
      currentOrderName['ascending'] = 'ascending';
      currentOrderName['descending'] = 'descending';
      sortOrder=$(this).attr("data-sortOrder");
      currentSortOrder = $(this).children('div:last-child').hasClass('active') ? currentOrderName[sortOrder] : ""; 
      sortOrder = (e.type=='mouseout' ? currentSortOrder : sortOrder);
      $(this).children('div:last-child').removeClass('ascending descending').toggleClass(sortOrder);
    });
  }
  
  $.fn.addCloseTrigger = function(closeFunction) {
    $(this).click(function() {
      $('#curtains').animate({opacity: 0},'fast',function(){ $(this).css("z-index","-1");});
      $('#curtainsContainer').animate({opacity: 0},'fast',function(){ $(this).css("z-index","-1");});
      if(typeof closeFunction!=undefined) {
        eval(closeFunction);
      }
    });
  }
    
  $.fn.updateInput = function() {
    $("input:file").change(function() {
      var windowWidth = $(window).width();
      var windowHeight = $(window).height();
      var uploadMessage = $(this).attr('data-msg');  
      $('#curtains').css({height: windowHeight, width: windowWidth, zIndex: "1000"}).animate({opacity: 0.5},'fast');
      $('#curtainsContainer').html(uploadMessage).alignCenter().css('z-index','10001').animate({opacity: 1},'fast');
      form = $(this).closest('form');
      console.log("Forma");      
      file = this.files[0];
      var reader = new FileReader();
      if(reader instanceof FileReader) {
        reader.readAsDataURL(file);
        reader.onload = (function(kak) {
          return function(e) {  
            if(e.target.result.length>5) {
              console.log(e.target.result);
              var req = new XMLHttpRequest();
              req.open("POST", form.attr('action'), true);
              boundary = "---------------------------7da24f2e50046";
              req.setRequestHeader("Content-Type", "multipart/form-data, boundary="+boundary);
              var body = "--" + boundary + "\r\n";
              body += "Content-Disposition: form-data; name='applications'; filename='" + file.name + "'\r\n";
              body += "Content-Type: application/octet-stream\r\n\r\n";
              body += e.target.result + "\r\n";
              body += "--" + boundary + "--";
              req.onreadystatechange = function(e) {
                  if(this.readyState === 4) {
                   $('#curtains').animate({opacity: 0},'fast',function(){ $(this).css("z-index","-1");});
                   $('#curtainsContainer').animate({opacity: 0},'fast',function(){ $(this).css("z-index","-1");
                      displayMessage(e.target.response,'success',false);
                   });
                   form.html( form.html() );
                   $().updateInput();
                  }
                  return false;
              };
              e.preventDefault();
              req.send(body);
            } else {
              displayMessage("Error reading File !!!!",'error');
            }      
          } 
        })(file); 
      } else {
        displayMessage("Error initiating File Reader",'error');
      }
    });
  }

  $.fn.updatePost = function() {
   
    var file;
    form = $(this);
    button = $(this).find("button");
    if(form.attr('method')=='post' && !form.attr('enctype')) {
      if(button.length>0) {
        button.click(function(e) {
          form=$(this).closest('form');
          form.find("[data-submit]").each(function(){
            eval($(this).attr('data-submit')+"()");
          }).ready(function() {
            if(!$('[data-error]').length>0) {
              $.post(form.attr('action'),form.serialize(),function(data){
                console.log(data);
                try {
                  jsonData = $.parseJSON(data);
                  if(jsonData.message!='') {
                    displayMessage(jsonData.message,jsonData.messageType,jsonData.action);
                  } else {
                    eval(jsonData.action);
                  }
                } catch(error) {
                  displayMessage("Something Went Wrong on server",'error');
                }
              });
            }
          return false;
        });
        e.preventDefault();
        return false;
      });
      }
    }
  }
  
  $.fn.AddClickableTable = function() {
    table = $(this);
    table.find("tr[data-href]").bind("mouseover mouseout",function(){ $(this).toggleClass('currentUnder'); }).click(function(){ document.location=$(this).attr('data-href')+$(this).find("td:first-child").attr('data-id'); });
    $(this).find("td").bind("mouseover",function(){
      cells = $(this).parent().find("td");
      elCount = cells.index(this);
      table.find("tr").each(function(){
        $(this).find("td:eq("+elCount+")").addClass("tdHover");
      });
      $("#headerList tr:first-child th:eq("+elCount+")").addClass("tdHover");
    });
    table.mouseout(function(){
      $(".tdHover").removeClass("tdHover");
    });
  }
  
  $.fn.addSlideTrigger = function() {
    $(this).click(function() {
      $('.filters').css({zIndex: 400});
      $(this).parent().css({zIndex: 401});
      head = $(this);
      headWidth = $(this).width();
      totalWidth = head.parent().width();
      if(head.parent().css('right')!='0px') {
        if($(this).hasClass('picture_header')) {
          forma = $(this).next().find('form');
          forma.html(forma.html());
          $().updateInput();
        }
        head.parent().animate({right: '0px'},1000);
      } else {
        visibleArea = 0-(totalWidth-headWidth);
        head.parent().animate({right: visibleArea},1000,function(){ $('.filters').css({zIndex: 400}); });
      }
    });
  }
  
  $.fn.addSlideTriggerLeft = function() {
    head = $('.search_header');
    totalWidth = head.parent().width();
    visibleArea = 0-(totalWidth)-10;
    head.parent().css({left: visibleArea});
    $(this).click(function() {
      $('.filters').css({zIndex: 400});
      $(this).parent().css({zIndex: 401});
      head = $(this);
      headWidth = $(this).width();
      totalWidth = head.parent().width();
      if(head.parent().css('left')!='0px') {
        head.parent().animate({left: '0px'},500);
      } else {
        visibleArea = 0-(totalWidth)-10;
        head.parent().animate({left: visibleArea},500,function(){ $('.filters').css({zIndex: 400}); });
      }
    });
  }

  $.fn.setWidth = function(elid) {
    $(elid).width($(this).width());
    totalWidthLeft = $('.content').width()-$(this).width();
    //if(totalWidthLeft<200) {
      totalHalfWidth = totalWidthLeft/2; 
      $(this).parent().css("marginLeft",totalHalfWidth);
      $(elid).css("marginLeft",totalHalfWidth);
    //}
  }
  
  $.fn.setMaxSel = function() {
    maxUnits = Math.floor($('.content').width()/125);  
    $('[name^=list_]:not(:checked)').parent().next().children().prop("disabled",true);
    currentUnits = $('[name^=list_]:checked').length;
    if(currentUnits>=maxUnits) {
      $('[name^=list_]:not(:checked)').prop("disabled",true);
    } else {
      $('[name^=list_]:not(:checked)').prop("disabled",false);
    }    
    $('[name^=list_]').click(function() {
      currentUnits = $('[name^=list_]:checked').length;
      if(currentUnits>=maxUnits) {
        $('[name^=list_]:not(:checked)').prop("disabled",true);
      } else {
        $('[name^=list_]:not(:checked)').prop("disabled",false);
      }
    })
  }
  
  $.fn.addEdit = function() {
    $(this).click(function() {
      el = $(this);
      $('#curtainsContainer').load(document.location.pathname+document.location.search, {edit: el.attr('id'), table: el.attr('data-table'), field: el.attr('data-field'), idField: el.attr('data-idField') },function(result) { 
        $('#curtainsContainer').removeClass('success error').html(result).addClass('success').alignCenter();
        displayCurtains();
      });   
    });
  }
  
  $.fn.assignButton = function() {
    $(this).click(function() {
      $.post($(this).attr('href'),'',function(data) {
        eval(data);
      });
      return false;
    });
  } 
  
})(jQuery); 