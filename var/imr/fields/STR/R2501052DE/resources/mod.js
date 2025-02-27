

function unisciCodici() {
    // Disabilita inizialmente il bottone bnNext
    $('#bnNext').prop('disabled', true);
    // Inserisce un messaggio in rosso subito accanto al bottone
    if ($('#nextHint').length === 0) {
        $('<span id="nextHint" style="color: red; margin-left: 10px;">Klicke auf ein Element, um fortzufahren.</span>')
            .insertAfter('#bnNext');
    }
    
    // Aggiorna il testo in #bnClearSel traducendolo in polacco ("Żaden z nich" = "Nessuno di questi")
    $('#bnClearSel a').text('Keiner von ihnen.');
        
       // Converte gli argomenti (che saranno gli array passati) in un array vero e proprio.
       var groups = Array.prototype.slice.call(arguments);
    
       // Per ogni gruppo passato...
       groups.forEach(function(group) {
           // Per ogni codice (ID) presente nel gruppo...
           group.forEach(function(code) {
               // Associa un event handler al click sull'elemento con ID specificato
               $('#' + code).on('click.unisciCodici', function(e) {
                   // Se l'evento è stato generato in maniera programmatica (da .trigger) esce senza fare nulla.
                   if (e.isTrigger) return;
    
                   // Per ogni altro codice presente nello stesso gruppo...
                   group.forEach(function(otherCode) {
                       if (otherCode !== code) {
                           // Simula un click sull'elemento corrispondente
                           $('#' + otherCode).trigger('click');
                       }
                   });
                    // Sblocca il bottone bnNext al primo click "reale"
                    $('#bnNext').prop('disabled', false);
                    $('#nextHint').remove();
               });
           });
       });
    
        // Aggiunge un handler anche sul div bnClearSel per sbloccare il bottone bnNext
        $('div#bnClearSel a').on('click.unlock', function(e) {
            $('#bnNext').prop('disabled', false);
            $('#nextHint').remove();
        });
    }

    function unisciCodici2() {
        // Aggiorna il testo in #bnClearSel traducendolo in polacco ("Żaden z nich" = "Nessuno di questi")
        $('#bnClearSel a').text('Keiner von ihnen.');
            
           // Converte gli argomenti (che saranno gli array passati) in un array vero e proprio.
           var groups = Array.prototype.slice.call(arguments);
        
           // Per ogni gruppo passato...
           groups.forEach(function(group) {
               // Per ogni codice (ID) presente nel gruppo...
               group.forEach(function(code) {
                   // Associa un event handler al click sull'elemento con ID specificato
                   $('#' + code).on('click.unisciCodici', function(e) {
                       // Se l'evento è stato generato in maniera programmatica (da .trigger) esce senza fare nulla.
                       if (e.isTrigger) return;
        
                       // Per ogni altro codice presente nello stesso gruppo...
                       group.forEach(function(otherCode) {
                           if (otherCode !== code) {
                               // Simula un click sull'elemento corrispondente
                               $('#' + otherCode).trigger('click');
                           }
                       });
          
                   });
               });
           });
        
    
        }


/*
var gruppiCoerenza = {};

function unisciCodici(...gruppi) {
    console.log("Definendo gruppi di coerenza:", gruppi);
    gruppi.forEach(gruppo => {
        gruppo.forEach(id => {
            gruppiCoerenza[id] = gruppo;
        });
    });
    console.log("Struttura gruppiCoerenza:", gruppiCoerenza);
}

$(document).ready(function () {
    $(".comp").on("click", function () {
        let id = $(this).attr("id");
        console.log("Cliccato elemento:", id);
        
        if (gruppiCoerenza[id]) {
            console.log("Attivando coerenza per il gruppo:", gruppiCoerenza[id]);
            gruppiCoerenza[id].forEach(codice => {
                if (codice !== id) {
                    console.log("Simulando clic per:", codice);
                    $("#" + codice)[0].click();
                }
            });
        } else {
            console.log("Nessun gruppo associato a:", id);
        }
    });
});

*/

function mostraOpen() {

    $("#cmp310, #cmp350, #cmp390, #cmp430").hide();


    $("#cmp300 , #cmp340, #cmp380, #cmp420").keyup(function() {
        let contiene;
        let parole;

        contiene = $("#q300_ans, #q340_ans, #q380_ans, #q420_ans").val();
        parole = contiene.length;

        if (parole > 0) { $("#cmp310, #cmp350, #cmp390, #cmp430").fadeIn(1000); } else {
            $("#cmp310, #cmp350, #cmp390, #cmp430").val("");
            $("#cmp310, #cmp350, #cmp390, #cmp430").fadeOut(1000);
        }

    });


}

function leftImg() {
    $("div.conc").insertBefore(".col-xs-12:first");
    $("div.conc").addClass("col-xs-6");
    $(".col-xs-12:first").addClass('col-xs-6').removeClass('col-xs-12 col-md-12');
    $(".scale").css("font-size", "13px");
    $("div.conc").css("text-align", "center");
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
                $this.attr("disabled", false);
                $(".cdown").hide();
            } else {
                $this.attr("disabled", true);
                secs = event.strftime("%S");

                if (secs == 1) { label = "Sekunde" } else { label = "Sekunden" }

                $(".cdown").html(event.strftime('In <span>%S</span> ' + label + ' können Sie weitermachen.'));
                
            }
        });

}


function imgCarosello(link, alto) {
    let itemVisto;
    let imgStamp;

    console.log("l" + link);
    itemVisto = $(".carintesta").attr('data-row');

    imgStamp = "<img height='" + alto + "px' src='" + link + itemVisto + ".png'/>";
    $("div.conc").html(imgStamp);
    console.log("Img" + imgStamp);

    $("table, input").on("click", function() {

        itemVisto = $(".carintesta").attr('data-row');

        imgStamp = "<img height='" + alto + "px' src='" + link + itemVisto + ".png'/>";
        $("div.conc").html(imgStamp);
        console.log("Img" + imgStamp);


    });

}

function coloraCarosello(){
    $("table, input").on("click", function() {

        
        itemVisto = $(".carintesta").attr('data-row');
        if (itemVisto==1) { $("div.carintesta").css("color:red!important"); console.log("entrato");}
        console.log(itemVisto);

    });

}
