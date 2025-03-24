
function alignChoiceOptions(maxDivsPerRow) {
    // Nascondi inizialmente tutti i div
    $('.choice-option').css('display', 'none');
    
    var containerWidth = $('.container').width();
    var currentRowWidth = 0;
    var rowDivs = [];
    var divCount = 0;

    $('.choice-option').each(function() {
        var $this = $(this);
        var elementWidth = $this.outerWidth(true); // Ottiene la larghezza del div inclusi margini

        // Verifica se il div contiene un'immagine
        var containsImage = $this.find('img').length > 0;

        // Se il div non contiene un'immagine, allinealo da solo in una riga
        if (!containsImage) {
            $this.css('display', 'block');
            return;
        }

        // Se il numero massimo di div per riga è raggiunto
        if (divCount >= maxDivsPerRow) {
            // Mostra gli elementi della riga corrente
            $.each(rowDivs, function(_, div) {
                $(div).css('display', 'inline-block');
            });

            // Azzera per il nuovo rigo
            currentRowWidth = 0;
            rowDivs = [];
            divCount = 0;
        }

        // Aggiungi l'elemento corrente alla riga e aggiorna la larghezza corrente
        rowDivs.push($this);
        currentRowWidth += elementWidth;
        divCount++;
    });

    // Mostra gli elementi dell'ultimo rigo se ci sono elementi rimasti
    if (rowDivs.length > 0) {
        $.each(rowDivs, function(_, div) {
            $(div).css('display', 'inline-block');
        });
    }
}


function autoSelectOption(value) {
    // Trova la checkbox con il valore specificato
    var checkbox = $('input[type="checkbox"][value="' + value + '"]');

    // Simula il click sull'opzione
    checkbox.trigger('click');

    // Imposta il valore nell'input hidden con id topOfMind
    $('#topOfMind').val(value);

    // Rimuovi l'evento di click per evitare ulteriori modifiche
    checkbox.off('click');
    checkbox.click(function(e) {
        e.preventDefault();
    });
}


function meseAnno() {

    $('input#comp0').attr('type', 'number');
    $('input#comp1').attr('type', 'number');

    $('input#comp0').attr({
        'min': 13,
        'max': 99,
    });

    $('input#comp1').attr({
        'min': 0,
        'max': 11,
    });


}

function noEsclusive() {
// Funzione per gestire la logica di input
function handleInput($input) {
    var inputType = $input.attr('type');
    var inputName = $input.attr('name');
    var isChecked = $input.is(':checked');
    var value = $input.val().trim(); // Assicura di rimuovere spazi bianchi ai lati

    // Aggiornamento basato sul tipo di input
    if (inputType === 'radio') {
        if (isChecked) {
            // Deseleziona tutti i checkbox e resetta gli input di testo con lo stesso nome
            $('input[type="checkbox"][name="' + inputName + '"], input[type="text"][name="' + inputName + '"]').prop('checked', false).val('').parent().removeClass('selected');
        }
    } else if (inputType === 'checkbox') {
        // Deseleziona tutti i radio con lo stesso nome
        $('input[type="radio"][name="' + inputName + '"]').prop('checked', false).parent().removeClass('selected');
    } else if (inputType === 'text') {
        // Deseleziona tutti i radio con lo stesso nome solo se l'input di testo contiene almeno un carattere
        if (value.length > 0) {
            $('input[type="radio"][name="' + inputName + '"]').prop('checked', false).parent().removeClass('selected');
        }
    }

    // Gestisce l'aggiunta o rimozione della classe 'selected'
    if ((isChecked || value.length > 0) && !$input.parent().hasClass('selected')) {
        $input.parent().addClass('selected');
    } else if (!isChecked && value.length === 0) {
        $input.parent().removeClass('selected');
    }
}

// Gestione del click sugli input
$('table.table').on('click', 'td, td input[type="checkbox"], td input[type="radio"], td input[type="text"]', function(e) {
    e.stopPropagation(); // Previene la propagazione dell'evento a elementi genitori
    var $target = $(e.target);
    
    // Verifica se l'evento è originato da un input o dal td stesso
    if ($target.is('input[type="checkbox"], input[type="radio"], input[type="text"]')) {
        handleInput($target);
    } else {
        var $inputs = $target.find('input[type="checkbox"], input[type="radio"], input[type="text"]');
        $inputs.each(function() {
            handleInput($(this));
        });
    }
});

// Gestisce il cambiamento degli input per aggiornare la classe 'selected'
$('input[type="checkbox"], input[type="radio"], input[type="text"]').change(function() {
    handleInput($(this));
});


}

function choicheAltro(){
    // Funzione per controllare duplicati e gestire il comportamento del button e del div di avviso
    function checkDuplicates() {
        let textInputs = $('input[type="text"]');
        let values = [];
        let hasDuplicate = false;

        textInputs.each(function() {
            let currentValue = $(this).val();
            if (currentValue !== '' && values.includes(currentValue)) {
                hasDuplicate = true;
            } else if (currentValue !== '') {
                values.push(currentValue);
            }
        });

        if (hasDuplicate) {
            $('#bnNext').prop('disabled', true);
            if ($('#duplicateWarning').length === 0) {
                $('<div id="duplicateWarning">Attenzione: diversificare le risposte.</div>').insertAfter('#bnNext');
            }
        } else {
            $('#bnNext').prop('disabled', false);
            $('#duplicateWarning').remove();
        }
    }

    // Evento che verifica i duplicati quando il contenuto di un input text cambia
    $('input[type="text"]').on('input', function() {
        if ($('input[type="text"]').length > 1) {
            checkDuplicates();
        }
    });

    // Funzione al click sui radio button
    $('input[type="radio"]').click(function() {
        $('input[type="text"]').val(''); // Azzera tutti i campi di testo
        $('#bnNext').prop('disabled', false); // Abilita il pulsante
        $('#duplicateWarning').remove(); // Rimuove il messaggio di avviso se presente
    });

    // Verifica iniziale per configurare correttamente la UI all'avvio
    if ($('input[type="text"]').length > 1) {
        checkDuplicates();
    }
}


function multiAltro() {
// Funzione per controllare duplicati
function checkDuplicateTextInputs() {
    var inputsByName = {};
    var hasDuplicates = false;

    $("input[type='text']").each(function() {
        var name = $(this).attr('name');
        var value = $(this).val().trim();
        if (!inputsByName[name]) {
            inputsByName[name] = [];
        }
        inputsByName[name].push(value);
    });

    $.each(inputsByName, function(name, values) {
        var valueCounts = {};
        values.forEach(function(value) {
            if (!valueCounts[value]) {
                valueCounts[value] = 0;
            }
            valueCounts[value]++;
        });
        $.each(valueCounts, function(value, count) {
            if (count > 1 && value !== "") {
                hasDuplicates = true;
            }
        });
    });

    if (hasDuplicates) {
        $("#bnNext").prop('disabled', true);
        if ($("#duplicateWarning").length === 0) {
            $("<div id='duplicateWarning'>Attenzione: diversificare le risposte.</div>").insertAfter("#bnNext");
        }
    } else {
        $("#bnNext").prop('disabled', false);
        $("#duplicateWarning").remove();
    }
}

// Controllo iniziale e alla modifica dei campi di testo
$("input[type='text']").on("input", checkDuplicateTextInputs);
checkDuplicateTextInputs();

// Abilita il pulsante e rimuove l'avviso quando si clicca su un input radio o sul suo td
$("input[type='radio'], td.cell").on("click", function(event) {
    if ($(this).is('td.cell')) {
        $(this).find('input[type="radio"]').prop("checked", true);
    }
    $("#bnNext").prop('disabled', false);
    $("#duplicateWarning").remove();
    // Verifica di nuovo la condizione dopo un breve ritardo per garantire l'aggiornamento dell'input
    setTimeout(checkDuplicateTextInputs, 50);
});

}


$(document).ready(function() {
    meseAnno();
    multiAltro();
    choicheAltro();
});