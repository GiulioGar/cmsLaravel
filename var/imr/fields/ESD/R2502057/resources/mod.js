

function contorovescia(tempo) {
    $('#console').after("<div class='cdown'>&nbsp;</div>");

    let label;
    let secs;

    let fiveSeconds = new Date().getTime() + tempo;
    $('#bnNext').countdown(fiveSeconds, { elapse: true })
        .on('update.countdown', function(event) {
            let $this = $(this);
            if (event.elapsed) {
                $this.attr("disabled", false);
                $(".cdown").hide();
                //$("#bnNext").click();
            } else {
                $this.attr("disabled", true);
                secs = event.strftime("%S");

                if (secs == 1) { label = "second" } else { label = "seconds" }

                $(".cdown").html(event.strftime('In <span>%S</span> ' + label + ' you can continue.'));
            }
        });

}

function sliderQuality()
{

    $('#bnNext').attr('disabled', 'disabled').css('opacity', 0.5); $('#erroreMsg').show().insertBefore('#console');
    jQuery(".slider").slider({
       
     
        min: 1,
        max: 100,
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

    //recupero il numero delle righe della slider
    var RowNumber = $("input[id^=group_slider_").length;
    let numvalid;
    let numslide=$( ".ui-slider .ui-slider-handle" ).length;
    let originalPos=$(".ui-slider .ui-slider-handle").position().left;

    //$('#console').hide();
    $('.err').hide();

    if (RowNumber>=4)
    {
        //$('#console').before('<div class=\"err\" style=\"color:red\"><b>La preghiamo di differenziare il suo giudizio e di non dare la stessa valutazione a tutte le affermazioni</b></div>');
        
        //ogni volta che clicco sulla slider verifico tutti i valori inseriti procedendo per riga per verificare che per almeno ogni riga ci sia un valore differente
        $( '.slider' ).slider({
            change: function( event, ui ) 
            {
                //console.log(event);
                //console.log(ui["target"]);
                //console.log($(this)[0].attributes[0].value);
                //alert($(this)[0].attributes[0].value);
                //recupero l'id dello slider cliccato
                slid=$(this)[0].attributes[0].value;

                //valore=parseInt($("#"+slid+" a").text());
                //valore=valore+1;
                //$("#"+slid+" a").text(valore);

                // codice Giuseppe
                //valore=parseInt( $("#group_"+slid).val());
                //valore=valore-1;

                //codice Giulio prova
                valore=parseInt($("#"+slid+" a").text());
                valore=valore-1;

                $("#group_"+slid).val(valore);
                
                //$('#console').show();
                $('.err').hide();
                
                var contagiro=0;
                var numero;
                var numTemp;
                var differenza=0;

                $("input[id^=group_slider_").each(function(index, td) {
                    //console.log("diff"+differenza);
                        
                        contagiro++; 
                        //prendo l'attributo value formato da riga:valore, prendendo solo il valore con lo split
                        var getValue = $(this).val();
                        
                        if (contagiro==1){numero=getValue;}
                        else{
                            numTemp=getValue;
                            if (numTemp!=numero){differenza=differenza+1;}
                        }


                });

     if (differenza==0){
        //$('#console').hide(); 
        $('.err').show();
    }

        numvalid=0;

        $( ".ui-slider .ui-slider-handle" ).each(function() 
            {
            
            let pleft=$(this).position().left;
            if (pleft!= originalPos) {numvalid++;}
            //console.log("pleft "+pleft);

            let numQuad=$(this).text();

            if(numQuad !="-") { 
            $(this).css('border', 'solid 4px red'); 
           // $(this).text('*');
        }
            console.log(numQuad);
            });
    
            //console.log("validi "+numvalid);
            //console.log("slide "+numvalid);
           
    
            if (numvalid != numslide) { 
                $('#bnNext').attr('disabled', 'disabled').css('opacity', 0.5); 
                $('#erroreMsg').show().insertBefore('#console');}
            else { 
                $('#bnNext').removeAttr('disabled').css('opacity', 1);  
                $('#erroreMsg').hide();
            }
                
            
            }
        });
    }


}

function contaTempo(){
    document.querySelector("textarea").style.display='none';
    let conta=0;
    setInterval(function()
    {
        conta=conta+1;
        document.querySelector("textarea").textContent=conta;
    }, 1000);
}
