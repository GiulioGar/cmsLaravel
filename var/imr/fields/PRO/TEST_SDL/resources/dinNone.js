$(window).load(function(){
var col;
var conta=0;
var opa;

$(".comp").click(function() {
col=$("font", this).css("color");
opa=$("img", this).css('opacity');
if (col == "rgb(255, 0, 0)" || opa!=1) {conta++;} 
else  {conta--;}
alert(opa+"chitemmuort"+conta);
if (conta>0) { $("#bnNoSel").hide(); }
else { $("#bnNoSel").show(); }

});

});