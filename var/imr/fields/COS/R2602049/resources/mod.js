
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






function rankSmartInit (cfg) {
  cfg = cfg || {};
  var strictSelection = (cfg.strict_selection === true); // default false
  var forcedLang = cfg.lang;
  var COPY = cfg.copy || {}; // override testi
  var showSlots = (cfg.show_slots !== false); // default true

  // NEW: layout / densità testo
  var columns = parseInt(cfg.columns, 10);
  if (!columns || columns < 1) columns = 2; // default come prima
  var textDensity = cfg.text_density; // "normal" | "compact" | undefined

  // ===== i18n + template =====
  function detectLang() {
    var lang = (forcedLang || (navigator.languages && navigator.languages[0]) || navigator.language || "en").toLowerCase();
    return lang.split("-")[0];
  }
  var LANG = detectLang();

  function getOrdinalSuffix(n) {
    var j = n % 10, k = n % 100;
    if (j === 1 && k !== 11) return "st";
    if (j === 2 && k !== 12) return "nd";
    if (j === 3 && k !== 13) return "rd";
    return "th";
  }

  function template(str, vars) {
    return String(str || "").replace(/\{(\w+)\}/g, function (_, k) {
      return (vars && vars[k] !== undefined) ? String(vars[k]) : "";
    });
  }

  var I18N = {
    it: {
      progress: function (filled, total) { return "Completate: " + filled + "/" + total; },
      undo: "Annulla ultima",
      reset: "Reset",
      note: "Suggerimento: usa la lente per ingrandire.",
      slotLabel: function (n) { return n + "ª scelta"; },
      slotHintDrop: "Trascina qui",
      slotHintFillPrev: "Completa prima le scelte precedenti",
      slotAssignedTitle: "Selezione",
      slotAssignedHint: "Tocca un’altra opzione per cambiare",
      remove: "Rimuovi",
      toastOrder: "Prima completa le scelte in ordine 🙂",
      modalTitle: "Anteprima",
      modalClose: "Chiudi ✕",
      headline_first: "Osserva le opzioni e scegli la tua preferita.",
      headline_next_template: "Ottimo! Ora scegli la tua scelta n. {n}.",
      headline_done: "Perfetto! Hai completato la classifica."
    },
    en: {
      progress: function (filled, total) { return "Completed: " + filled + "/" + total; },
      undo: "Undo last",
      reset: "Reset",
      note: "Tip: use the magnifier to zoom in.",
      slotLabel: function (n) { return n + getOrdinalSuffix(n) + " choice"; },
      slotHintDrop: "Drop here",
      slotHintFillPrev: "Complete previous choices first",
      slotAssignedTitle: "Selected",
      slotAssignedHint: "Pick another option to change",
      remove: "Remove",
      toastOrder: "Please complete choices in order 🙂",
      modalTitle: "Preview",
      modalClose: "Close ✕",
      headline_first: "Look carefully and pick your top choice.",
      headline_next_template: "Nice! Now pick choice #{n}.",
      headline_done: "All set! Ranking completed."
    }
  };

  var T0 = I18N[LANG] || I18N.it;

  var T = Object.assign({}, T0, {
    headline_first: (COPY.headline_first != null) ? COPY.headline_first : T0.headline_first,
    headline_next_template: (COPY.headline_next_template != null) ? COPY.headline_next_template : T0.headline_next_template,
    headline_done: (COPY.headline_done != null) ? COPY.headline_done : T0.headline_done
  });

  function getHeadline(filled, total) {
    if (filled <= 0) return T.headline_first;
    if (filled >= total) return T.headline_done;
    var n = filled + 1;
    return template(T.headline_next_template, { n: n, filled: filled, total: total });
  }

  // ===== DOM helpers =====
  function nearestRankingContainerFromCurrentScript() {
    var cs = document.currentScript;
    if (cs) {
      var el = cs;
      while (el && el !== document.body) {
        var found = el.querySelector ? el.querySelector(".ranking-option-container") : null;
        if (found) return $(found);
        el = el.parentElement;
      }
    }
    return $(".ranking-option-container").first();
  }

  function injectCssOnce() {
    if (document.getElementById("imr-ranksmart-css")) return;

    var css = `
      .imr-rankui{margin:12px 0 16px;padding:12px;border:1px solid #e5e7eb;border-radius:14px;background:#fafafa}
      .imr-rankui__top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;flex-wrap:wrap}

      .imr-headline{
        font-weight:900;font-size:18px;line-height:1.2;color:#0f172a;
        background:linear-gradient(90deg, rgba(255,237,213,.9), rgba(254,215,170,.45));
        border:1px solid rgba(249,115,22,.25);
        padding:10px 12px;border-radius:14px;
        box-shadow:0 10px 22px rgba(0,0,0,.06);
        max-width: 720px;
      }
      .imr-headline.imr-pop{animation: imrPop .22s ease-out;}
      @keyframes imrPop{0%{transform:scale(.98);opacity:.6}100%{transform:scale(1);opacity:1}}

      .imr-rankui__meta{display:flex;align-items:center;gap:12px;margin-top:4px}
      .imr-rankui__progress{font-size:13px;opacity:.8}
      .imr-rankui__actions button{margin-left:8px}

      /* NEW: colonne configurabili con CSS var --imr-cols (default 2) */
      .imr-rankcards{display:grid;grid-template-columns:repeat(var(--imr-cols, 2),minmax(240px,1fr));gap:14px;margin-top:14px}
      @media (max-width:992px){.imr-rankcards{grid-template-columns:1fr}}

      .imr-rankslots{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;margin-top:16px}
      @media (max-width:992px){.imr-rankslots{grid-template-columns:repeat(2,minmax(140px,1fr))}}

      .imr-rankslot{border:2px dashed #cbd5e1;border-radius:14px;min-height:110px;padding:10px;background:#fff;position:relative;display:flex;align-items:center;justify-content:center;text-align:center}
      .imr-rankslot.imr-dragover{border-color:#f97316;box-shadow:0 0 0 4px rgba(249,115,22,.18)}
      .imr-rankslot__label{position:absolute;top:8px;left:10px;font-weight:900;font-size:12px;color:#0f172a;background:#eef2ff;padding:4px 8px;border-radius:999px}
      .imr-rankslot__hint{font-size:12px;opacity:.65;padding:8px}

      .imr-rankslot__thumb{width:100%;display:flex;align-items:center;gap:10px}
      .imr-rankslot__thumb img{width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb}
      .imr-rankslot__thumb .imr-thumbtxt{font-size:12px;opacity:.85;line-height:1.2;text-align:left}
      .imr-rankslot__thumb .imr-thumbtxt b{display:block;font-size:12px;opacity:.95}
      .imr-rankslot__thumb .imr-unassign{margin-top:6px;padding:2px 8px;font-size:12px}

      .imr-rankcard{position:relative;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;background:#fff;cursor:grab;transition:transform .08s ease,box-shadow .08s ease}
      .imr-rankcard:active{cursor:grabbing}
      .imr-rankcard:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(0,0,0,.08)}
      .imr-rankcard button.ordering{position:absolute !important;left:-9999px !important;width:1px !important;height:1px !important;overflow:hidden !important}

      .imr-rankbadge{
        position:absolute;top:10px;right:10px;z-index:4;
        width:36px;height:36px;border-radius:999px;
        background:rgba(249,115,22,.96);
        color:#fff;display:flex;align-items:center;justify-content:center;
        font-weight:1000;font-size:14px;
        box-shadow:0 10px 24px rgba(0,0,0,.22);
      }
      .imr-rankbadge.imr-none{display:none}

      .imr-rankcard__content{position:relative}
      .imr-selected .imr-rankcard__content{opacity:.35;filter:grayscale(0.4)}
      .imr-selected:hover{transform:none;box-shadow:none}

      .imr-rankcard label{cursor:inherit;display:block;margin:0}
      .imr-rankcard img{width:100% !important;height:auto;display:block}

      .imr-textbox{
        padding:16px 14px;
        font-size:16px;
        line-height:1.35;
        font-weight:700;
        color:#0f172a;
        min-height:120px;
        display:flex;
        align-items:center;
      }

      /* NEW: compatto per testo (liste lunghe) */
      .imr-compact .imr-textbox{
        min-height:unset;
        padding:10px 12px;
        font-size:14px;
        line-height:1.25;
      }

      /* 🔽 BADGE PIÙ PICCOLO IN COMPACT */
      .imr-compact .imr-rankbadge{
        width:24px;
        height:24px;
        font-size:12px;
        top:6px;
        right:6px;
      }      

      .imr-rankui__note{font-size:12px;opacity:.75;margin-top:10px}
      .imr-toast{margin-top:8px;font-size:12px;opacity:.85}

      .imr-zoombtn{
        position:absolute;left:10px;top:10px;z-index:5;
        width:34px;height:34px;border-radius:999px;
        background:rgba(255,255,255,.92);
        border:1px solid rgba(0,0,0,.12);
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 6px 18px rgba(0,0,0,.12);
        cursor:pointer;
      }
      .imr-zoombtn:hover{background:#fff}
      .imr-zoombtn svg{width:18px;height:18px;opacity:.85}

      .imr-modal{position:fixed;inset:0;z-index:9999;display:none}
      .imr-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.65)}
      .imr-modal__panel{
        position:relative;
        width:min(92vw, 980px);
        height:min(86vh, 720px);
        margin:6vh auto 0;
        background:#0b0f19;
        border-radius:16px;
        overflow:hidden;
        box-shadow:0 20px 60px rgba(0,0,0,.4);
        display:flex;flex-direction:column;
      }
      .imr-modal__topbar{
        display:flex;align-items:center;justify-content:space-between;
        padding:10px 12px;background:rgba(255,255,255,.06);color:#fff;
        font-size:13px;
      }
      .imr-modal__close{
        border:0;background:rgba(255,255,255,.12);color:#fff;
        border-radius:10px;padding:6px 10px;cursor:pointer;
      }
      .imr-modal__body{flex:1;display:flex;align-items:center;justify-content:center;padding:12px}
      .imr-modal__body img{max-width:100%;max-height:100%;object-fit:contain}
      .imr-modal__body .imr-modal__text{
        width:100%;
        max-width:860px;
        color:#fff;
        font-size:22px;
        line-height:1.35;
        font-weight:800;
        padding:16px;
        border-radius:14px;
        background:rgba(255,255,255,.06);
      }
      .imr-modal__hint{opacity:.8}
    `;

    var style = document.createElement("style");
    style.id = "imr-ranksmart-css";
    style.textContent = css;
    document.head.appendChild(style);
  }

  function lensSvg() {
    return '' +
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
      '<path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>' +
      '<path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
      "</svg>";
  }

  // === IMPORTANT: ricavo l'indice stabile da id/name del button "bnX" ===
  function getStableBnIndex($opt) {
    var $btn = $opt.find("button.ordering").first();
    if (!$btn.length) return null;

    var id = ($btn.attr("id") || "").trim();
    var name = ($btn.attr("name") || "").trim();

    var m = id.match(/^bn(\d+)$/);
    if (!m) m = name.match(/^bn(\d+)$/);
    if (!m) return null;

    return String(parseInt(m[1], 10)); // "1", "2", ...
  }

  function getOptImageSrc($opt) {
    var $img = $opt.find("img").first();
    return $img.length ? $img.attr("src") : null;
  }

  function getOptText($opt) {
    var $label = $opt.find("label").first();
    var txt = $label.length ? $label.text() : $opt.text();
    txt = (txt || "").replace(/\s+/g, " ").trim();
    return txt;
  }

  // ===== Find container =====
  var $optContainer = nearestRankingContainerFromCurrentScript();
  if (!$optContainer.length) return;

  var $root = $optContainer.closest(".row");
  if (!$root.length) $root = $optContainer.parent();

  if ($root.data("imrRankSmartInited")) return;
  $root.data("imrRankSmartInited", true);

  var $options = $optContainer.find(".ranking-option");
  if (!$options.length) return;
  // NEW: nota lente solo se ci sono immagini
  var hasAnyImage = ($options.find("img").length > 0);


  var selCount = parseInt($("#sel_count").val() || String($options.length), 10);
  if (!selCount || selCount < 2) selCount = $options.length;

  var $selIdsEl = $("#selIds");
  var $selIdxsEl = $("#selIdxs");
  var $nextBtn = $("#bnNext");
  // NON tocchiamo #sortorder (come richiesto)
  // var $sortorderEl = $("#sortorder"); // non usato

  injectCssOnce();

  // ===== UI =====
  var $ui = $(
    '<div class="imr-rankui">' +
      '<div class="imr-rankui__top">' +
        '<div class="imr-headline"></div>' +
        '<div class="imr-rankui__meta">' +
          '<div class="imr-rankui__progress"></div>' +
          '<div class="imr-rankui__actions">' +
            '<button type="button" class="btn btn-default imr-undo"></button>' +
            '<button type="button" class="btn btn-default imr-reset"></button>' +
          "</div>" +
        "</div>" +
      "</div>" +
    '<div class="imr-rankcards"></div>' +
    (showSlots ? '<div class="imr-rankslots"></div>' : '') +
      '<div class="imr-rankui__note"></div>' +
      '<div class="imr-toast" style="display:none;"></div>' +
    "</div>"
  );

  $ui.find(".imr-undo").text(T.undo);
  $ui.find(".imr-reset").text(T.reset);
  if (hasAnyImage) {
  $ui.find(".imr-rankui__note").text(T.note);
} else {
  $ui.find(".imr-rankui__note").hide();
}


  // NEW: compact (esplicito o auto se tante opzioni)
  if (textDensity === "compact" || (!textDensity && $options.length > 12)) {
    $ui.addClass("imr-compact");
  }

  $optContainer.before($ui);

  var $headline = $ui.find(".imr-headline");
  var $slots = showSlots ? $ui.find(".imr-rankslots") : $();
  var $cards = $ui.find(".imr-rankcards");
  var $toast = $ui.find(".imr-toast");

  // NEW: applica numero colonne (desktop) via CSS var
  if ($cards.length && $cards[0] && $cards[0].style) {
    $cards[0].style.setProperty("--imr-cols", String(columns));
  }

  function toast(msg) {
    if (!msg) return;
    $toast.text(msg).stop(true, true).fadeIn(80);
    clearTimeout(toast._t);
    toast._t = setTimeout(function () { $toast.fadeOut(200); }, 1200);
  }

  var lastHeadline = "";
  function setHeadline(text) {
    if (text === lastHeadline) return;
    lastHeadline = text;
    $headline.removeClass("imr-pop");
    $headline.text(text);
    setTimeout(function () { $headline.addClass("imr-pop"); }, 10);
  }

  // Modal
  var $modal = $(
    '<div class="imr-modal" role="dialog" aria-modal="true">' +
      '<div class="imr-modal__backdrop"></div>' +
      '<div class="imr-modal__panel">' +
        '<div class="imr-modal__topbar">' +
          '<div class="imr-modal__hint"></div>' +
          '<button type="button" class="imr-modal__close"></button>' +
        "</div>" +
        '<div class="imr-modal__body"></div>' +
      "</div>" +
    "</div>"
  );
  $modal.find(".imr-modal__hint").text(T.modalTitle);
  $modal.find(".imr-modal__close").text(T.modalClose);
  $("body").append($modal);

  function openModalWithImage(src) {
    if (!src) return;
    $modal.find(".imr-modal__body").html('<img alt="">');
    $modal.find("img").attr("src", src);
    $modal.fadeIn(120);
  }
  function openModalWithText(text) {
    $modal.find(".imr-modal__body").html('<div class="imr-modal__text"></div>');
    $modal.find(".imr-modal__text").text(text || "");
    $modal.fadeIn(120);
  }
  function closeModal() {
    $modal.fadeOut(120, function () { $modal.find(".imr-modal__body").empty(); });
  }
  $modal.on("click", ".imr-modal__backdrop, .imr-modal__close", function () { closeModal(); });
  $(document).on("keydown.imrRankSmart", function (e) { if (e.key === "Escape") closeModal(); });

  // ===== Trasforma opzioni in cards =====
  $options.each(function () {
    var $opt = $(this);
    $opt.addClass("imr-rankcard").attr("draggable", "true");

    // indice stabile dal bottone bnX (id o name)
    var bnIndex = getStableBnIndex($opt);
    if (bnIndex == null) {
      // se non lo trovo, fallback: provo value (ultima spiaggia)
      var fallbackVal = $opt.find("button.ordering").first().val();
      bnIndex = (fallbackVal == null) ? "" : String(fallbackVal);
    }
    $opt.attr("data-optid", bnIndex); // <-- QUESTO è ciò che finisce in selIds

    // wrapper per offuscare solo contenuto
    if (!$opt.find(".imr-rankcard__content").length) {
      var $label = $opt.find("label").first();
      if ($label.length) {
        var hasImg = $label.find("img").length > 0;
        if (!hasImg) {
          var txt = getOptText($opt);
          $label.empty().append('<div class="imr-textbox"></div>');
          $label.find(".imr-textbox").text(txt);
        }
        $label.wrap('<div class="imr-rankcard__content"></div>');
      } else {
        $opt.wrapInner('<div class="imr-rankcard__content"></div>');
      }
    }

    if (!$opt.find(".imr-rankbadge").length) $opt.append('<div class="imr-rankbadge imr-none"></div>');

    // NEW: lente SOLO se c’è immagine
    var hasImgForZoom = !!getOptImageSrc($opt);
    if (hasImgForZoom && !$opt.find(".imr-zoombtn").length) {
      $opt.append('<button type="button" class="imr-zoombtn" aria-label="Zoom">' + lensSvg() + "</button>");
    }

    $cards.append($opt);
  });

  $optContainer.hide();

  // ===== State =====
  var ranks = new Array(selCount).fill(null); // ranks[i] = optId (bnIndex) assegnato a (i+1)ª scelta
  var history = [];

  function filledCount() { return ranks.filter(Boolean).length; }
  function nextFreeIndex() { return ranks.indexOf(null); }
  function canAssignTo(index) { return index === nextFreeIndex(); }

  function getOptPreview(optId) {
    var $opt = $cards.find('.ranking-option[data-optid="' + optId + '"]');
    if (!$opt.length) return null;

    var src = getOptImageSrc($opt);
    if (src) return { type: "img", src: src };

    return { type: "text", text: getOptText($opt) };
  }

  function isNextEnabled() {
    var f = filledCount();
    return strictSelection ? (f === selCount) : (f >= 1);
  }

  function renderProgress() {
    $ui.find(".imr-rankui__progress").text(T.progress(filledCount(), selCount));
    if ($nextBtn.length) $nextBtn.prop("disabled", !isNextEnabled());
  }

  function setBadge(optId, rankIndexOrNull) {
    var $opt = $cards.find('.ranking-option[data-optid="' + optId + '"]');
    var $b = $opt.find(".imr-rankbadge").first();
    if (!$b.length) return;

    if (rankIndexOrNull === null) $b.addClass("imr-none").text("");
    else $b.removeClass("imr-none").text(String(rankIndexOrNull + 1));
  }

  function syncBadges() {
    $cards.find(".ranking-option").each(function () {
      var optId = $(this).attr("data-optid");
      if (optId !== undefined && optId !== null && optId !== "") setBadge(optId, null);
    });
    for (var i = 0; i < ranks.length; i++) if (ranks[i] != null) setBadge(ranks[i], i);
  }

  function markSelectedCards() {
    $cards.find(".ranking-option").each(function () {
      var $opt = $(this);
      var optId = $opt.attr("data-optid");
      $opt.toggleClass("imr-selected", optId && ranks.indexOf(optId) !== -1);
    });
  }

  function buildSlots() {
    $slots.empty();
    $slots.css("grid-template-columns", "repeat(" + Math.min(selCount, 4) + ", minmax(140px,1fr))");

    for (var i = 0; i < selCount; i++) {
      var assignedId = ranks[i];
      var isActive = canAssignTo(i);

      var innerHtml;
      if (assignedId) {
        var p = getOptPreview(assignedId);
        if (p && p.type === "img") {
          innerHtml =
            '<div class="imr-rankslot__thumb">' +
              '<img src="' + p.src + '" alt="">' +
              '<div class="imr-thumbtxt">' +
                "<b>" + T.slotAssignedTitle + "</b>" +
                "<div>" + T.slotAssignedHint + "</div>" +
                '<button type="button" class="btn btn-default imr-unassign" data-optid="' + assignedId + '">' + T.remove + "</button>" +
              "</div>" +
            "</div>";
        } else {
          var snippet = (p && p.text) ? p.text : "";
          if (snippet.length > 60) snippet = snippet.slice(0, 60) + "…";
          innerHtml =
            '<div class="imr-rankslot__thumb">' +
              '<div class="imr-thumbtxt">' +
                "<b>" + T.slotAssignedTitle + "</b>" +
                "<div>" + snippet + "</div>" +
                '<button type="button" class="btn btn-default imr-unassign" data-optid="' + assignedId + '">' + T.remove + "</button>" +
              "</div>" +
            "</div>";
        }
      } else {
        innerHtml = '<div class="imr-rankslot__hint">' + (isActive ? T.slotHintDrop : T.slotHintFillPrev) + "</div>";
      }

      var $s = $(
        '<div class="imr-rankslot" data-rankindex="' + i + '">' +
          '<div class="imr-rankslot__label">' + T.slotLabel(i + 1) + "</div>" +
          innerHtml +
        "</div>"
      );

      if (!isActive && !assignedId) $s.css("opacity", 0.7);
      $slots.append($s);
    }
  }

  // ======= QUI SONO I FIX IMPORTANTI =======
  function updateHidden() {
    var filled = filledCount();

    // selIdxs: 1;2;...;filled;
    if ($selIdxsEl.length) {
      var idxs = [];
      for (var i = 1; i <= filled; i++) idxs.push(i);
      $selIdxsEl.val(idxs.join(";") + (idxs.length ? ";" : ""));
    }

    // selIds: usa optId = indice stabile bnX (non ordine di apparizione, non value casuali)
    if ($selIdsEl.length) {
      var ids = ranks.slice(0, filled).filter(function (x) { return x !== null && x !== undefined && x !== ""; });
      $selIdsEl.val(ids.join(";") + (ids.length ? ";" : ""));
    }

    // NON tocchiamo #sortorder (richiesto)
  }
  // ========================================

  function compactRanks() {
    // mantiene ordine senza buchi (shift a sinistra)
    var c = ranks.filter(Boolean);
    while (c.length < selCount) c.push(null);
    ranks = c.slice(0, selCount);
  }

  function redraw() {
    syncBadges();
    markSelectedCards();
    if (showSlots) buildSlots();
    updateHidden();
    renderProgress();
    setHeadline(getHeadline(filledCount(), selCount));
  }

  function assignToIndexStrict(optId, targetIndex) {
    optId = String(optId);
    var existing = ranks.indexOf(optId);
    if (existing !== -1) ranks[existing] = null;
    ranks[targetIndex] = optId;
    compactRanks();
  }

  function assignStrict(optId, requestedIndex) {
    var nfi = nextFreeIndex();
    if (nfi === -1) return;

    var idx = (requestedIndex === nfi) ? requestedIndex : nfi;

    history.push(ranks.slice());
    assignToIndexStrict(optId, idx);

    if (requestedIndex !== idx) toast(T.toastOrder);
    redraw();
  }

  function assignNextFree(optId) {
    var nfi = nextFreeIndex();
    if (nfi === -1) return;
    assignStrict(optId, nfi);
  }

  function unassign(optId) {
    optId = String(optId);
    var idx = ranks.indexOf(optId);
    if (idx === -1) return;
    history.push(ranks.slice());
    ranks[idx] = null;
    compactRanks();
    redraw();
  }

  // ===== Events =====
  $cards.on("click", ".imr-zoombtn", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var $opt = $(this).closest(".ranking-option");
    var src = getOptImageSrc($opt);
    if (src) openModalWithImage(src);
    else openModalWithText(getOptText($opt));
  });

  $cards.on("click", ".ranking-option", function (e) {
    e.preventDefault();
    var optId = $(this).attr("data-optid");
    if (!optId && optId !== "0") return;

    if (ranks.indexOf(optId) !== -1) unassign(optId);
    else assignNextFree(optId);
  });

  $cards.on("dragstart", ".ranking-option", function (e) {
    var optId = $(this).attr("data-optid");
    if (!optId && optId !== "0") return;
    e.originalEvent.dataTransfer.setData("text/optId", optId);
    e.originalEvent.dataTransfer.effectAllowed = "move";
  });

  $slots.on("dragover", ".imr-rankslot", function (e) {
    e.preventDefault();
    var idx = parseInt($(this).attr("data-rankindex"), 10);
    if (canAssignTo(idx)) $(this).addClass("imr-dragover");
  });

  $slots.on("dragleave", ".imr-rankslot", function () {
    $(this).removeClass("imr-dragover");
  });

  $slots.on("drop", ".imr-rankslot", function (e) {
    e.preventDefault();
    $(this).removeClass("imr-dragover");
    var optId = e.originalEvent.dataTransfer.getData("text/optId");
    if (!optId && optId !== "0") return;
    assignStrict(optId, parseInt($(this).attr("data-rankindex"), 10));
  });

  $slots.on("click", ".imr-unassign", function (e) {
    e.preventDefault();
    e.stopPropagation();
    var optId = $(this).attr("data-optid");
    if (!optId && optId !== "0") return;
    unassign(optId);
  });

  $ui.find(".imr-undo").on("click", function () {
    var prev = history.pop();
    if (!prev) return;
    ranks = prev.slice();
    redraw();
  });

  $ui.find(".imr-reset").on("click", function () {
    history.push(ranks.slice());
    ranks = new Array(selCount).fill(null);
    redraw();
  });

  // Init
  if ($nextBtn.length) $nextBtn.prop("disabled", !isNextEnabled());
  setHeadline(getHeadline(0, selCount));
  redraw();
};




function choiceSmartInit (cfg) {
  cfg = cfg || {};
  var forcedLang = cfg.lang;
  var COPY = cfg.copy || {};

  // NEW: layout / densità testo
  var columns = parseInt(cfg.columns, 10);
  if (!columns || columns < 1) columns = 2; // default come prima
  var textDensity = cfg.text_density; // "normal" | "compact" | undefined

  // ===== i18n minimal =====
  function detectLang() {
    var lang = (forcedLang || (navigator.languages && navigator.languages[0]) || navigator.language || "en").toLowerCase();
    return lang.split("-")[0];
  }
  var LANG = detectLang();

  var I18N = {
    it: { note: "Suggerimento: usa la lente per ingrandire.", modalTitle: "Anteprima", modalClose: "Chiudi ✕" },
    en: { note: "Tip: use the magnifier to zoom in.", modalTitle: "Preview", modalClose: "Close ✕" }
  };
  var T = I18N[LANG] || I18N.it;

  // ===== DOM helpers =====
  function nearestChoiceOptionsFromCurrentScript() {
    var cs = document.currentScript;
    if (cs) {
      var el = cs;
      while (el && el !== document.body) {
        // in Primis le opzioni stanno spesso dentro ".col-..." del container domanda
        var found = el.querySelector ? el.querySelectorAll(".choice-option") : null;
        if (found && found.length) return $(found);
        el = el.parentElement;
      }
    }
    return $(".choice-option");
  }

  function injectCssOnce() {
    if (document.getElementById("imr-choicesmart-css")) return;

    var css = `
      .imr-choiceui{margin:12px 0 16px;padding:12px;border:1px solid #e5e7eb;border-radius:14px;background:#fafafa}
      .imr-choiceui__top{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px}

      .imr-choiceui__note{font-size:12px;opacity:.75;margin-top:8px}

      /* NEW: colonne configurabili con CSS var --imr-cols (default 2) */
      .imr-choicegrid{
        display:grid;
        grid-template-columns:repeat(var(--imr-cols, 2),minmax(240px,1fr));
        gap:14px;
        margin-top:14px;
      }
      @media (max-width:992px){ .imr-choicegrid{grid-template-columns:1fr;} }

      /* Card */
      .imr-choicecard{
        position:relative;
        border-radius:14px;
        overflow:hidden;
        border:1px solid #e5e7eb;
        background:#fff;
        cursor:pointer;
        transition:transform .08s ease, box-shadow .08s ease, border-color .08s ease;
      }
      .imr-choicecard:hover{
        transform:translateY(-1px);
        box-shadow:0 6px 18px rgba(0,0,0,.08);
      }

      /* Nascondo radio obsoleto */
      .imr-choicecard input[type="radio"]{
        position:absolute !important;
        left:-9999px !important;
        width:1px !important;
        height:1px !important;
        overflow:hidden !important;
      }

      /* Contenuto (immagine o testo) */
      .imr-choicecard__content{position:relative}
      .imr-choicecard img{width:100% !important; height:auto; display:block}

      .imr-choicecard__textbox{
        padding:16px 14px;
        font-size:16px;
        line-height:1.35;
        font-weight:800;
        color:#0f172a;
        min-height:120px;
        display:flex;
        align-items:center;
      }

      /* NEW: compatto per testo (liste lunghe) */
      .imr-compact .imr-choicecard__textbox{
        min-height:unset;
        padding:10px 12px;
        font-size:14px;
        line-height:1.25;
      }

      /* 🔽 FLAG PIÙ PICCOLO IN COMPACT */
      .imr-compact .imr-choicecheck{
        width:24px;
        height:24px;
        font-size:12px;
        top:6px;
        right:6px;
      }      

      /* Selezione “figa”: bordo arancio + check + glow */
      .imr-choicecard.is-selected{
        border-color: rgba(249,115,22,.95);
        box-shadow:0 0 0 4px rgba(249,115,22,.18), 0 10px 26px rgba(0,0,0,.10);
      }
      .imr-choicecheck{
        position:absolute;
        top:10px; right:10px;
        width:36px; height:36px;
        border-radius:999px;
        background:rgba(249,115,22,.96);
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-weight:1000;
        box-shadow:0 10px 24px rgba(0,0,0,.22);
        z-index:6;
        transform:scale(.95);
        opacity:0;
        transition:opacity .08s ease, transform .08s ease;
        pointer-events:none;
      }
      .imr-choicecard.is-selected .imr-choicecheck{
        opacity:1;
        transform:scale(1);
      }

      /* Lente */
      .imr-zoombtn{
        position:absolute;left:10px;top:10px;z-index:7;
        width:34px;height:34px;border-radius:999px;
        background:rgba(255,255,255,.92);
        border:1px solid rgba(0,0,0,.12);
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 6px 18px rgba(0,0,0,.12);
        cursor:pointer;
      }
      .imr-zoombtn:hover{background:#fff}
      .imr-zoombtn svg{width:18px;height:18px;opacity:.85}

      /* Modal */
      .imr-modal{position:fixed;inset:0;z-index:9999;display:none}
      .imr-modal__backdrop{position:absolute;inset:0;background:rgba(0,0,0,.65)}
      .imr-modal__panel{
        position:relative;
        width:min(92vw, 980px);
        height:min(86vh, 720px);
        margin:6vh auto 0;
        background:#0b0f19;
        border-radius:16px;
        overflow:hidden;
        box-shadow:0 20px 60px rgba(0,0,0,.4);
        display:flex;flex-direction:column;
      }
      .imr-modal__topbar{
        display:flex;align-items:center;justify-content:space-between;
        padding:10px 12px;background:rgba(255,255,255,.06);color:#fff;
        font-size:13px;
      }
      .imr-modal__close{
        border:0;background:rgba(255,255,255,.12);color:#fff;
        border-radius:10px;padding:6px 10px;cursor:pointer;
      }
      .imr-modal__body{flex:1;display:flex;align-items:center;justify-content:center;padding:12px}
      .imr-modal__body img{max-width:100%;max-height:100%;object-fit:contain}
      .imr-modal__body .imr-modal__text{
        width:100%;
        max-width:860px;
        color:#fff;
        font-size:22px;
        line-height:1.35;
        font-weight:900;
        padding:16px;
        border-radius:14px;
        background:rgba(255,255,255,.06);
      }
    `;

    var style = document.createElement("style");
    style.id = "imr-choicesmart-css";
    style.textContent = css;
    document.head.appendChild(style);
  }

  function lensSvg() {
    return '' +
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
      '<path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>' +
      '<path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
      "</svg>";
  }

  function getOptionText($opt) {
    // testo “pulito” dell’opzione: prendo il testo del label senza l’input
    var $label = $opt.find("label").first();
    if (!$label.length) return "";
    var $clone = $label.clone();
    $clone.find("input, img, button").remove();
    var txt = $clone.text().replace(/\s+/g, " ").trim();
    return txt;
  }

  function getOptionImageSrc($opt) {
    var $img = $opt.find("img").first();
    return $img.length ? $img.attr("src") : null;
  }

  // ===== Start =====
  var $opts = nearestChoiceOptionsFromCurrentScript();
  if (!$opts.length) return;
  var hasAnyImage = ($opts.find("img").length > 0);

  // evita doppia init
  var $scope = $opts.first().closest(".row");
  if ($scope.data("imrChoiceSmartInited")) return;
  $scope.data("imrChoiceSmartInited", true);

  injectCssOnce();

  // Metto wrapper UI prima delle opzioni
  var headline = (COPY.headline != null) ? COPY.headline : "";
  var $ui = $(
    '<div class="imr-choiceui">' +
      '<div class="imr-choicegrid"></div>' +
      '<div class="imr-choiceui__note"></div>' +
    '</div>'
  );


  if (headline) $ui.find(".imr-choiceui__headline").text(headline);
  else $ui.find(".imr-choiceui__headline").hide();

  if (hasAnyImage) {
  $ui.find(".imr-choiceui__note").text(T.note);
} else {
  $ui.find(".imr-choiceui__note").hide();
}


  // NEW: compact (esplicito o auto se tante opzioni)
  if (textDensity === "compact" || (!textDensity && $opts.length > 12)) {
    $ui.addClass("imr-compact");
  }

  // Inserisco UI prima della prima choice-option
  $opts.first().before($ui);

  var $grid = $ui.find(".imr-choicegrid");

  // NEW: applica numero colonne (desktop) via CSS var
  if ($grid.length && $grid[0] && $grid[0].style) {
    $grid[0].style.setProperty("--imr-cols", String(columns));
  }

  // Modal unico
  var $modal = $(
    '<div class="imr-modal" role="dialog" aria-modal="true">' +
      '<div class="imr-modal__backdrop"></div>' +
      '<div class="imr-modal__panel">' +
        '<div class="imr-modal__topbar">' +
          '<div class="imr-modal__hint">' + T.modalTitle + '</div>' +
          '<button type="button" class="imr-modal__close">' + T.modalClose + "</button>" +
        "</div>" +
        '<div class="imr-modal__body"></div>' +
      "</div>" +
    "</div>"
  );
  $("body").append($modal);

  function openModalImage(src) {
    if (!src) return;
    $modal.find(".imr-modal__body").html('<img alt="">');
    $modal.find("img").attr("src", src);
    $modal.fadeIn(120);
  }
  function openModalText(text) {
    $modal.find(".imr-modal__body").html('<div class="imr-modal__text"></div>');
    $modal.find(".imr-modal__text").text(text || "");
    $modal.fadeIn(120);
  }
  function closeModal() {
    $modal.fadeOut(120, function () { $modal.find(".imr-modal__body").empty(); });
  }
  $modal.on("click", ".imr-modal__backdrop, .imr-modal__close", closeModal);
  $(document).on("keydown.imrChoiceSmart", function (e) { if (e.key === "Escape") closeModal(); });

  // Trasformo ogni opzione in card
  $opts.each(function () {
    var $opt = $(this);
    var $label = $opt.find("label").first();
    var $radio = $opt.find('input[type="radio"]').first();

    // sposto tutto dentro la grid, mantenendo DOM originale dentro
    var $card = $('<div class="imr-choicecard" role="button" tabindex="0"></div>');

    // check overlay
    $card.append('<div class="imr-choicecheck">✓</div>');

    // content wrapper
    var $content = $('<div class="imr-choicecard__content"></div>');
    $card.append($content);

    // Riutilizzo il markup originale (label + img ecc)
    // Se non c’è immagine, costruisco una textbox leggibile
    var src = getOptionImageSrc($opt);
    if (src) {
      // NEW: lente SOLO se immagine
      $card.append("<button type=\"button\" class=\"imr-zoombtn\" aria-label=\"Zoom\">" + lensSvg() + "</button>");
      $content.append($label);
    } else {
      // testo
      var txt = getOptionText($opt);
      // riciclo label per mantenere il click/for/optionClicked compatibile,
      // ma pulisco e metto textbox
      if ($label.length) {
        $label.empty();
        if ($radio.length) $label.append($radio); // radio nascosto via CSS
        $label.append('<div class="imr-choicecard__textbox"></div>');
        $label.find(".imr-choicecard__textbox").text(txt);
        $content.append($label);
      } else {
        $content.append('<div class="imr-choicecard__textbox"></div>');
        $content.find(".imr-choicecard__textbox").text(txt);
      }
    }

    // Metto la card in griglia e nascondo il wrapper originale
    $grid.append($card);
    $opt.hide();

    // inizializza selezione
    if ($radio.length && $radio.is(":checked")) $card.addClass("is-selected");

    // click su card -> seleziona radio
    function selectThis() {
      // se già selezionata, non fare nulla
      if ($radio.length && $radio.is(":checked")) return;

      // deseleziona altre cards
      $grid.find(".imr-choicecard").removeClass("is-selected");

      // seleziona questa
      $card.addClass("is-selected");

      if ($radio.length) {
        $radio.prop("checked", true);

        // in Primis spesso c’è onclick="optionClicked('opt0')"
        // quindi triggero sia click che change per massima compatibilità
        try { $radio.trigger("click"); } catch (e) {}
        try { $radio.trigger("change"); } catch (e) {}

        // se esiste una function globale optionClicked, la chiamo anche
        if (typeof window.optionClicked === "function") {
          var rid = $radio.attr("id");
          if (rid) window.optionClicked(rid);
        }
      }
    }

    // click card
    $card.on("click", function (e) {
      // se clicco la lente, non seleziono
      if ($(e.target).closest(".imr-zoombtn").length) return;
      e.preventDefault();
      selectThis();
    });

    // tastiera (Enter/Space)
    $card.on("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        selectThis();
      }
    });

    // lente (esiste solo se src => immagine)
    $card.on("click", ".imr-zoombtn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var imgSrc = src || getOptionImageSrc($opt);
      if (imgSrc) openModalImage(imgSrc);
      else openModalText(getOptionText($opt));
    });
  });
};



