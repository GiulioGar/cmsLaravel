function allinea(colonne, margine, margine_larghezza){
    $('div#container').css('overflow', 'hidden');
	var elemento;
	var soloitem=0;
	var altezza;
	var altezzaitm=0;
	var contaitm=1;
	var larghezza;
	var imposta_colonne=colonne;
	var count=0;
	var conta_elementi_riga=0;
	var riga=0;
	var mar=margine;
	var mar_l=margine_larghezza;
	elemento_img=$('.ranking-option:nth-child(1) img');
	if (!elemento_img.length){soloitem=1; altezza=300;}
    for (i=0;i<50;i++) {
	elemento=$('.ranking-option:nth-child('+i+')');
	elemento_img=$('.ranking-option:nth-child('+i+') img');
	if ((elemento.length)&&(elemento_img.length)) {altezza=elemento.height();}
	if ((elemento.length)&&(!elemento_img.length)&&(soloitem==0)) {if (contaitm==1){altezzaitm=elemento.height(); if (conta_elementi_riga==0){riga--;}} if (contaitm>1){altezzaitm=elemento.height();if (conta_elementi_riga==0){riga--;}}contaitm++;}
	if (elemento_img.length) {larghezza=elemento_img.width()+mar_l;}else{larghezza=mar_l; if ((elemento.length)&&(soloitem==0)){larghezza=0; riga++;}}
	$('.ranking-option:nth-child('+i+')').css('min-height','+altezza+px');
	if ($('.ranking-option:nth-child('+i+')').length){count++; conta_elementi_riga++;}
	$('.ranking-option:nth-child('+i+')').css({left: larghezza*(conta_elementi_riga-1),top:-(altezza*(count-1))+riga*(altezza+mar)-riga*(altezzaitm)-10, position:'relative'});
	if (conta_elementi_riga==imposta_colonne){conta_elementi_riga=0; riga++}
    }
    if (conta_elementi_riga==0){riga--;}
    $('div#console').css({top:-(altezza*(count-1))+riga*(altezza+mar)-riga*(altezzaitm), position:'relative'});
	var console=$('div#console');
	var position = console.position();
	var altezzacontainer = (position.top)+150;
	$('div#container').height( altezzacontainer );
	}