function setupRowCheckboxSync() {

    // Questa funzione forza col3 a rimanere checked se col2 è checked.
    // Se col2 NON è checked, col3 è libera di essere checked o unchecked.
    function syncCol3WithCol2($col2, $col3) {
      if ($col2.is(":checked")) {
        // col2 = checked => forziamo col3 a checked
        $col3.prop("checked", true).parent().addClass("selected");
      }
      // Se col2 è unchecked, non facciamo niente: col3 rimane libera
    }
  
    // 1) Catturiamo i click sul td.cell (quando si clicca “fuori” dalla checkbox)
    jQuery("table.scale").on("click", "td.cell", function() {
      var $row = jQuery(this).closest("tr");
      var $inputs = $row.find("td.cell input[type='checkbox'], td.cell input[type='radio']");
  
      // Ci aspettiamo almeno 2 input: col2 e col3
      if ($inputs.length >= 2) {
        var $col2 = $inputs.eq(0);
        var $col3 = $inputs.eq(1);
  
        // Lasciamo agire prima la logica di cellClicked(...)
        setTimeout(function() {
          syncCol3WithCol2($col2, $col3);
        }, 0);
      }
    });
  
    // 2) Catturiamo l’evento "change" quando si clicca direttamente sul checkbox
    jQuery("table.scale").on("change", "td.cell input[type='checkbox'], td.cell input[type='radio']", function() {
      var $row = jQuery(this).closest("tr");
      var $inputs = $row.find("td.cell input[type='checkbox'], td.cell input[type='radio']");
  
      if ($inputs.length >= 2) {
        var $col2 = $inputs.eq(0);
        var $col3 = $inputs.eq(1);
  
        setTimeout(function() {
          syncCol3WithCol2($col2, $col3);
        }, 0);
      }
    });
  }




function alignComposedOptions() {
    $('.container-component').each(function () {
        const $block = $(this); // Individua il blocco corrente
        const $choiceOptions = $block.find('.choice-option'); // Trova tutte le opzioni

        let $rowContainer = $('<div class="image-row-container"></div>'); // Contenitore per allineare elementi
        let count = 0;

        $choiceOptions.each(function () {
            const $option = $(this); // Opzione corrente
            const hasImage = $option.find('img').length > 0; // Controlla se contiene un'immagine

            if (hasImage) {
                // Stile per allineare
                $option.css({
                    display: 'inline-block',
                    width: '30%',
                    margin: '0.5% 1%',
                    verticalAlign: 'top',
                });

                $rowContainer.append($option); // Aggiunge al contenitore
                count++;

                // Se raggiunti 3 elementi con immagine, forza una nuova riga
                if (count % 3 === 0) {
                    $block.append($rowContainer); // Aggiunge la riga al blocco
                    $rowContainer = $('<div class="image-row-container"></div>'); // Crea un nuovo contenitore
                }
            } else {
                // Quando si trova un elemento senza immagine:
                if ($rowContainer.children().length > 0) {
                    $block.append($rowContainer); // Aggiunge la riga con immagini
                    $rowContainer = $('<div class="image-row-container"></div>'); // Resetta il contenitore
                }

                $option.css({
                    display: 'block', // Forza il blocco per andare a capo
                    width: '100%', // Occupa tutta la larghezza
                    textAlign: 'left', // Testo allineato a sinistra
                    margin: '10px 0', // Spazio sopra e sotto
                });

                $block.append($option); // Aggiunge l'elemento senza immagine
                count = 0; // Resetta il contatore per evitare disallineamenti
            }
        });

        // Aggiunge eventuali elementi rimasti
        if ($rowContainer.children().length > 0) {
            $block.append($rowContainer);
        }
    });
}


function checkInputs() {
    let allValid = true; // Variabile per verificare se tutti i valori sono > 0

    // Controlla ogni input di tipo text
    $("input[type='text']").each(function () {
        let value = parseFloat($(this).val());
        if (isNaN(value) || value <= 0) {
            allValid = false;
            return false; // Esci dal ciclo se trovi un valore non valido
        }
    });

    // Mostra o nasconde il messaggio di errore e abilita/disabilita il pulsante
    if (!allValid) {
        $("#errorMessage").show(); // Mostra il messaggio
        $("#bnNext").prop("disabled", true); // Disabilita il pulsante
    } else {
        $("#errorMessage").hide(); // Nascondi il messaggio
        $("#bnNext").prop("disabled", false); // Abilita il pulsante
    }
}

// Funzione per aggiornare il testo dello span
function updateStatusLabel() {
    $(".fixed-sum-status-label").text("Totale");
}

    // Funzione per inibire i numeri decimali
    function blockDecimals() {
        $("input[type='text']").on("keypress", function (e) {
            // Consenti solo numeri interi (blocca il punto, la virgola e i caratteri non numerici)
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });

        $("input[type='text']").on("input", function () {
            // Rimuovi eventuali caratteri non numerici (ad esempio incollati)
            $(this).val($(this).val().replace(/[^0-9]/g, ""));
        });
    }


// Funzione per inizializzare gli eventi
function initializeValidation() {
    // Crea il messaggio di errore accanto al pulsante, inizialmente nascosto
    if ($("#errorMessage").length === 0) {
        $("<span id='errorMessage' style='color: red; display: none;'>Inserire almeno un valore maggiore di 0 per ogni elemento</span>")
            .insertAfter("#bnNext");
    }

    // Controlla gli input al caricamento della pagina
    checkInputs();

    // Aggiorna il testo dello span
    updateStatusLabel();

    // Inibisci l'inserimento di decimali
     blockDecimals();

    // Associa l'evento di input agli elementi da validare
    $("input[type='text']").on("input", function () {
        checkInputs();
    });
}







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


function autoSelectOption(values) {
    // Itera su tutti i valori forniti
    values.forEach(function(value) {
        // Trova la checkbox con il valore specificato
        var $checkbox = $('input[type="checkbox"][value="' + value + '"]');

        if ($checkbox.length) {
            // Seleziona la checkbox
            $checkbox.prop('checked', true).trigger('change');

            // Aggiorna l'input nascosto 'topOfMind'
            var $topOfMind = $('#topOfMind');
            var currentValue = $topOfMind.val();
            //$topOfMind.val(currentValue ? currentValue + ',' + value : value);

            // Evita modifiche manuali alla checkbox
            $checkbox.off('click').on('click', function(e) {
                e.preventDefault();
            });
        }
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
});