// JavaScript Document
function getQueryVariable(variable)
{
       var query = window.location.search.substring(1);
       var vars = query.split("&");
       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == variable){return pair[1];}
       }
       return(false);
}

$( document ).ready(function() {
	
	$( "#datepicker" ).datepicker({dateFormat: "mm/dd"});
	
	function formAlert(data){
		var width = $(window).width();
		var height = $(window).height();
		var left = (width/2) - 85;
		var exists = $('#formalert').length;
		if(exists ==0){
			$('body').prepend('<div id="coverup" style="position:fixed;background:black;z-index:900;width:100%;height:'+height+'px;"></div>');
			$('body').prepend('<div id="formalert" class="success" style="top:250px;left:'+left+'px;"></div>');
		}
	
		$('#coverup').fadeTo(200,.6);
		$('#formalert').fadeIn(200).text(data).delay(3000).fadeOut(200,function(){
			$('#coverup').fadeOut(200);
		});
		
	}
	
	if ($('#pageURL').length) {
		$('#pageURL').val(document.URL);
	}	
	
  $("#et-newsletter").submit(function(event){
	event.preventDefault();
    formData = $(this).serialize();	
	
    $.ajax({
      type: "POST",
      dataType: "text",
      url: "handle-et-data.php", //Relative or absolute path to handle-et-data.php file
      data: formData
	  }).done(function(data) {
		
		switch($.trim(data)){
			case "success-redirect": location.href = "http://www.yoursite.com/thank-you.php";break;
			default:formAlert(data); break;
			
		}
		  
      });
	
    return false;
  });
 
});
