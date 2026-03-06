
var validazione=0;

function oscuraDrag(parametri) {
    var idImmagini = parametri.split(','); // Dividi i parametri in un array di id
  
    idImmagini.forEach(function(id) {
      var imgId = 'i' + id.trim(); // Costruisci l'id completo dell'immagine
      var catId = 'cat_' + id.trim(); // Costruisci l'id completo dell'immagine
  
      // Seleziona il tag <li> che contiene l'immagine con l'id corrispondente e nascondilo
      $('li.ui-draggable').has('img#' + imgId).hide();
      $('td').has('#' + catId).hide();
    });
  }

function limitDrag()
{

    $( "#categories" ).on( "mousemove", function( ) 
    {

    $(".alertDrag").hide(); 

    var elemento1 = $("#cat_0");
    var elemento2 = $("#cat_1");
    var elemento3 = $("#cat_2");

    // Conta i div all'interno dell'elemento genitore
    var numeroDiDiv1 = elemento1.find("div").length;
    var numeroDiDiv2 = elemento2.find("div").length;
    var numeroDiDiv3 = elemento3.find("div").length;

    if (numeroDiDiv1==4 && numeroDiDiv2==3  && numeroDiDiv3==3) { validazione=1;}
    else { validazione=2;}

    if (validazione==2) { $("#bnNext").hide(); $(".classifier-container").after("<div class='alertDrag'><b>ATTENZIONE RISPETTARE IL NUMERO DI AGGETTIVI DA INSERIRE PER OGNI CASELLA PER PROSEGUIRE!</b> </div>"); }
    if (validazione==1) { $("#bnNext").show(); $(".alertDrag").hide();  }


    console.log ("Elemento 1:"+numeroDiDiv1);
    console.log ("Elemento 2:"+numeroDiDiv2);
    console.log ("Elemento 3:"+numeroDiDiv3);
    console.log ("validazione:"+validazione);

    });

}




function barColor()
{

    $(".slider-header").hide();
    $(".slider-header").fadeIn(1400);

    $(".ui-widget-content") .css("background","#fff");
    $("#console") .css("margin-left","40%");
    
    let vleft;
    var position;
    let position1;


    $('#bnNext').attr('disabled', 'disabled').css('opacity', 0.5); $('#erroreMsg').show().insertBefore('#console');

    $( ".label-min" ).html( $( ".sad" ) );
    $( ".label-max" ).html( $( ".happy" ) );


    $( '.slider' ).slider({change: function( event, ui ) {
        poistion="";
        position=$('.ui-slider .ui-slider-handle').text();
        position1=position-1;
        console.log("posizione:"+position);
        $('#bnNext').removeAttr('disabled').css('opacity', 1);  $('#erroreMsg').hide();


        $(".ui-widget-content") .css("background","#fff");


     if(position<=20) { $('.ui-widget-content').css({ background: "linear-gradient(to right, #cc0000 0%, #fff "+position1+"%)" }); }
      
    if(position>=21 && position<=40) { $('.ui-widget-content').css({ background: "linear-gradient(to right, #cc0000 0%, #ff9e3d "+position+"%,  #fff "+position1+"%)" }); }
    if(position>=41 && position<=60) { $('.ui-widget-content').css({ background: "linear-gradient(to right, #cc0000 0%, #ff9e3d 40%, #f4fc00 "+position+"%,#fff "+position1+"%)" }); }
    if(position>=61 && position<=80) { $('.ui-widget-content').css({ background: "linear-gradient(to right,#cc0000 0%, #ff9e3d 40%, #f4fc00 60%,#90ff19 "+position+"% ,#fff "+position1+"%)" }); }
    if(position>80) { $('.ui-widget-content').css({ background: "linear-gradient(to right, #cc0000 0%,  #ff9e3d 40%, #f4fc00 60%,#90ff19 80%,#007515 "+position+"% , #fff "+position1+"%)" }); }
       /*
       //smile color
       if(position<=50){ $('.sad').css({color:"#be0101"});  }
       else { $('.sad').css({color:"#adadad"}); }

       if(position>50){ $('.happy').css({color:"#029016"});  }
       else { $('.happy').css({color:"#adadad"}); }
    */

    }
    });

}

function barMinus()
{
    let punteggio;
    let valPoint;
    let stampPoint;
    $('.ui-slider .ui-slider-handle').css("color", "#f6f6f6 !important");

    $( '.slider' ).slider({change: function( event, ui ) 
    {
        $('.ui-slider .ui-slider-handle').removeClass("changed");
        valPoint=$('#group_slider_0').val()-10;

        console.log(valPoint);
        $('.ui-slider .ui-slider-handle').text(valPoint);
        $('.ui-slider .ui-slider-handle').css("color", "");


        
    } });

}


function contorovescia(tempo) {
    $('#console').after("<div class='cdown'>&nbsp;</div>");

    let label;
    let secs;

    let fiveSeconds = new Date().getTime() + tempo;
    $('#bnNext').countdown(fiveSeconds, { elapse: true })
        .on('update.countdown', function(event) {
            let $this = $(this);
           
            if (event.elapsed) {
                $(".cdown").hide(); 
                $this.attr("disabled", false);
    
            } else {
                $this.attr("disabled", true);
                secs = event.strftime("%S");

                if (secs == 1) { label = "secondo" } else { label = "secondi" }
            

                $(".cdown").html(event.strftime('Tra <span>%S</span> ' + label + ' potrà proseguire con il sondaggio.'));
            }
        });

} 	


function nonumeri3() 
{
    let numvalid;
    let numslide=$( ".ui-slider .ui-slider-handle" ).length;
   
    $('#bnNext').attr('disabled', 'disabled').css('opacity', 0.5); $('#erroreMsg').show().insertBefore('#console');
    jQuery(".slider").slider({
       
     
        min: 0,
        max: 22 - 1,
        slide: function (event, ui) {
            var id = jQuery(this).attr("id"),
                val = ui.value;
            jQuery(ui.handle).text(val);
            jQuery(ui.focus).remove();
            jQuery("#group_"+id).val(val);
            
        }
       
    });

    $( ".ui-slider .ui-slider-handle" ).each(function() 
    {
        $(this).css("left","49.5%");
        $(this).filter("a").text("-");
    });

    
    let originalPos=$(".ui-slider .ui-slider-handle").position().left;

    $( '.slider' ).slider({change: function( event, ui ) {
  
    //$( ".container" ).mousemove(function()
    //{
        numvalid=0;

    $( ".ui-slider .ui-slider-handle" ).each(function() 
        {
        
        let pleft=$(this).position().left;
        if (pleft!= originalPos) {numvalid++;}
        console.log("pleft "+pleft);
        });

        console.log("validi "+numvalid);
        console.log("slide "+numvalid);
       

        if (numvalid != numslide) { $('#bnNext').attr('disabled', 'disabled').css('opacity', 0.5); $('#erroreMsg').show().insertBefore('#console');}
        else { $('#bnNext').removeAttr('disabled').css('opacity', 1);  $('#erroreMsg').hide();}
    }
    });
}