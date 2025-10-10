// Helper: restituisce una stringa che contiene SOLO testo e <br/> dal nodo dato
function onlyTextAndBr($el) {
  return $el.contents().map(function () {
    if (this.nodeType === 3) { // text node
      return this.nodeValue;
    }
    if (this.nodeType === 1) { // element
      const tag = this.tagName && this.tagName.toLowerCase();
      if (tag === 'br') return '<br/>';
      // ricorsione: estrae testo e <br> anche da eventuali figli
      return onlyTextAndBr($(this));
    }
    return '';
  }).get().join('');
}

// Clicka il radio della colonna indicata (accetta "col0" o 0)
function selectColumn(col) {
  var colClass = (typeof col === 'number') ? ('col' + col) : col; // "col0", "col1", ...
  var $radio = $(".table-header .table-cell." + colClass).find("input[type='radio']");
  if ($radio.length) {
    // click nativo: scatena anche l'onclick inline (optionClicked('...')) e gli eventuali listener
    $radio.get(0).click();
  }
}


function buildTableFromOptions() {
  var $container = $(".col-xs-12.col-md-12").first();
  var $choices   = $container.find(".choice-option");

  var data = [];
  $choices.each(function () {
    var $choice = $(this);
    var $label  = $choice.find("label").first();

    // Manteniamo solo testo e <br/> dal label
    var htmlWithBr = onlyTextAndBr($label).trim();
    var parts = htmlWithBr.split("|").map(function (s) { return s.trim(); });

    // Price pulito a 2 decimali se presente e numerico
    var priceNum = (parts[5] !== undefined && parts[5] !== "") ? parseFloat(parts[5]) : NaN;

    data.push({
      nameHtml: parts[0] || "",
      img:      parts[1] || "",
      att1:     parts[2] || "",
      att2:     parts[3] || "",
      att3:     parts[4] || "",
      price:    isNaN(priceNum) ? "" : priceNum.toFixed(2),
      time:     parts[6] || "",
      elem:     $choice
    });
  });

  // Pulisci i label: rimuovi solo i text node e ri-applica il contenuto con <br/>
  data.forEach(function (item) {
    var $label = item.elem.find("label").first();
    $label.contents().filter(function () { return this.nodeType === 3; }).remove();
    $label.append(" " + item.nameHtml);
  });

  // Stacca le choice dal DOM per reinserirle nell’header
  $choices.detach();

  // (Opzionale) se ricostruisci spesso, rimuovi eventuale tabella esistente
  $container.find(".table-container").remove();

  // --- Costruzione tabella dinamica in base al numero di opzioni ---
  var headerCellsHtml = data.map(function (_item, i) {
    return '<div class="table-cell cell-amp' + i + ' col' + i + '"></div>';
  }).join("");

  var imgRowHtml = '<div class="table-row">' +
                     '<div class="table-cell"><b></b></div>' +
                     data.map(function (item, i) {
                       var imgHtml = item.img
                         ? '<img src="https://www.primisoft.com/fields/UNB/R2501044W2/resources/' + item.img + '.png" alt="">'
                         : '';
                       return '<div class="table-cell col' + i + '">' + imgHtml + '</div>';
                     }).join("") +
                   '</div>';

  function makeAttrRow(label, getter) {
    return '<div class="table-row">' +
             '<div class="table-cell"><b>' + label + '</b></div>' +
             data.map(function (item, i) {
               return '<div class="table-cell col' + i + '">' + (getter(item) || "") + '</div>';
             }).join("") +
           '</div>';
  }

  var rowsHtml = ""
    + makeAttrRow("⏱️Tempo di viaggio", function (item) { return item.att1 ? (item.att1 + " min.") : ""; })
    + makeAttrRow("🚶🏼‍♂️Distanza dalla fermata (inizio viaggio)", function (item) { return item.att2; })
    + makeAttrRow("📍Destinazione", function (item) { return item.att3; })
    + makeAttrRow("💶 Costo di viaggio", function (item) { return item.price !== "" ? (item.price + "€") : ""; })
    + makeAttrRow("⏱️ Tempo di attesa per viaggio di rientro", function (item) { return item.time; });

  var tableHtml =
    '<div class="table-container">' +
      '<div class="table-row table-header">' +
        '<div class="table-cell">&nbsp;</div>' +
        headerCellsHtml +
      '</div>' +
      imgRowHtml +
      rowsHtml +
    '</div>';

  $container.append(tableHtml);

  // Inserisci ciascuna choice nell'header della sua colonna
  data.forEach(function (item, i) {
    $(".cell-amp" + i).append(item.elem);
  });

  // --- Interazioni: click delegato, tastiera e hover evidenziato ---
  var $table = $container.find(".table-container");

  // Clic su QUALSIASI cella di colonna -> seleziona il radio nell'header di quella colonna
  $table.on("click", ".table-cell[class*='col']", function (e) {
    // Evita doppi trigger quando clicchi direttamente input/label/link/button
    if ($(e.target).is("input, label, a, button")) return;

    var colClass = Array.from(this.classList).find(function (c) { return /^col\d+$/.test(c); });
    if (!colClass) return;

    var $radio = $table.find(".table-header .table-cell." + colClass + " input[type='radio']").first();
    if ($radio.length) $radio.get(0).click(); // click nativo -> scatena anche onclick inline
  });

  // Accessibilità: selezione anche con Invio/Spazio
  $table.on("keydown", ".table-cell[class*='col']", function (e) {
    if (e.key === " " || e.key === "Enter") {
      e.preventDefault();
      var colClass = Array.from(this.classList).find(function (c) { return /^col\d+$/.test(c); });
      if (!colClass) return;
      var $radio = $table.find(".table-header .table-cell." + colClass + " input[type='radio']").first();
      if ($radio.length) $radio.get(0).click();
    }
  });

  // Hover evidenziando l'intera colonna (classe CSS .highlight-column)
  $table
    .on("mouseenter", ".table-cell[class*='col']", function () {
      var colClass = Array.from(this.classList).find(function (c) { return /^col\d+$/.test(c); });
      if (colClass) $table.find("." + colClass).addClass("highlight-column");
    })
    .on("mouseleave", ".table-cell[class*='col']", function () {
      var colClass = Array.from(this.classList).find(function (c) { return /^col\d+$/.test(c); });
      if (colClass) $table.find("." + colClass).removeClass("highlight-column");
    });

  // Ruolo e focus per UX/a11y
  $table.find(".table-cell[class*='col']").attr({ role: "button", tabindex: "0" });
}


// textbox in time

function transformTextareaToTime() {
  let el = document.getElementById("ans");

  if (!el) return; // niente da fare

  const newInput = document.createElement("input");
  newInput.type = "time";
  newInput.id = el.id;
  newInput.name = el.name;
  newInput.className = el.className;

  // Range completo e step 5 minuti
  newInput.min = "00:00";
  newInput.max = "23:55";
  newInput.step = "300";

  // Se nel textarea c'è un testo tipo HH:MM lo preserva
  const match = el.value.match(/\b([01]\d|2[0-3]):([0-5]\d)\b/);
  if (match) {
    newInput.value = match[0];
  }

  // sostituisce nel DOM
  el.parentNode.replaceChild(newInput, el);
}



