$(document).ready(function() {

var err="<span style='color:red' class='errM'>Attenzione inserire per ogni marca tutte le informazioni!";
var contEr=0;
$('.container').mouseover(function() 
{

	var mostra=1;
	var br1=$("#comp0").val();
	var br12=$("#comp3").val();
	var br13=$("#comp6").val();
	var br14=$("#comp9").val();
	var br15=$("#comp12").val();
	var br16=$("#comp15").val();
	var br17=$("#comp18").val();
	var br18=$("#comp21").val();	
	var br2=$("#comp1").val();
	var br22=$("#comp4").val();
	var br23=$("#comp7").val();
	var br24=$("#comp10").val();
	var br25=$("#comp13").val();
	var br26=$("#comp16").val();
	var br27=$("#comp19").val();
	var br28=$("#comp22").val();	
	var br3=$("#comp2").val();
	var br32=$("#comp5").val();
	var br33=$("#comp8").val();
	var br34=$("#comp11").val();
	var br35=$("#comp14").val();
	var br36=$("#comp17").val();
	var br37=$("#comp20").val();
	var br38=$("#comp23").val();

	
	if (br1==""){ $("#comp3,#comp6,#comp9,#comp12,#comp15,#comp18,#comp21").prop('disabled', true); }
		else {  $("#comp3,#comp6,#comp9,#comp12,#comp15,#comp18,#comp21").prop('disabled', false); }
	if (br2==""){ $("#comp4,#comp7,#comp10,#comp13,#comp16,#comp19,#comp22").prop('disabled', true); }
		else {  $("#comp4,#comp7,#comp10,#comp13,#comp16,#comp19,#comp22").prop('disabled', false); }	
	if (br3==""){ $("#comp5,#comp8,#comp11,#comp14,#comp17,#comp20,#comp23").prop('disabled', true); }
		else {  $("#comp5,#comp8,#comp11,#comp14,#comp17,#comp20,#comp23").prop('disabled', false); }		
	
	if (br1=="" && ( br12!="" || br13!="" || br14!="" || br15!="" || br16!="" || br17!="" || br18!=""))  { mostra=0;} 
	if (br2=="" && ( br22!="" || br23!="" || br24!="" || br25!="" || br26!="" || br27!="" || br28!=""))  { mostra=0;} 
	if (br3=="" && ( br32!="" || br33!="" || br34!="" || br35!="" || br36!="" || br37!="" || br38!=""))  { mostra=0;} 
	if (br1!="" && ( br12=="" || br13=="" || br14=="" || br15=="" || br16=="" || br17=="" || br18==""))  { mostra=0;}	
	if (br2!="" && ( br22=="" || br23=="" || br24=="" || br25=="" || br26=="" || br27=="" || br28==""))  { mostra=0;}	
	if (br3!="" && ( br32=="" || br33=="" || br34=="" || br35=="" || br36=="" || br37=="" || br38==""))  { mostra=0;}


if (mostra==1){ $('#console button').prop('disabled', false);  $( ".errM" ).hide(); contEr=0;}
else{  $('#console button').prop('disabled', true);  if (contEr==0) {$(".errM").show().insertBefore( "#title" ); contEr++;}   }
	

});

});