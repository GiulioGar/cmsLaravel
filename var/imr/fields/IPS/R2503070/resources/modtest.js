$(window).load(function(){
	

	
	
	
////////////////////////////////////////////////////////////////////////////DOMANDA OPEN//////////////////////////////////////////////////////////////////////////////////////////////////////

//leggo il tipo di domanda
var tipodomanda=$("#debugInfo").val();

//se il tipo di domanda contiente queste 2 strignhe è una singola
if (tipodomanda.indexOf("open") >= 0)
{
	
	var testoautomatico=0;
	testoautomatico = Math.floor(Math.random() * (80 - 14 + 1)) + 14;
	
	var txtu=parseInt(testoautomatico);
	
	$("#ans").val(txtu);
	$(".date-picker").val("2018.01.25");
	
	
	//clicco tasto avanti
	$('#bnNext').click();
	
	
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////DOMANDA COMPOSED//////////////////////////////////////////////////////////////////////////////////////////////////////

//leggo il tipo di domanda
var tipodomanda=$("#debugInfo").val();
var numerooption=0;

//se il tipo di domanda contiente queste 2 strignhe è una singola
if (tipodomanda.indexOf("composed") >= 0)
{
	
	//clicco tasto avanti
	//$('#bnNext').click();
	
	
		
		var testoautomatico=0;
		testoautomatico=Math.floor(Math.random() * (65 - 18 + 1)) + 18;
		
		var txtv=parseInt(testoautomatico);
		
		$(".form-control").val(txtv);
		
		
		
		
		
	var elements = document.getElementsByClassName('row container-component');
	var tot=elements.length;
	
	
	
	
	for (i = 0; i < tot; i++) 
	{
	
		var nomeid=elements[i].id;
		var qid=nomeid.replace("cmp", "q");
		
		
		//alert(qid);
		
		//SINGOLA//////////////////////////////////////////////////////////////////
		var numopt=elements[i].getElementsByClassName('radio').length;
		
		if (numopt>0)
		{
		var elencoinput=elements[i].getElementsByClassName('radio');
		
		//estraggo random l'opzione da selezionare
		var selezione1=Math.floor(Math.random() * numopt);
		
		
		
		var stringacompleta="#"+qid+"_opt"+selezione1;
		var stringacomp="#"+qid+"_comp"+selezione1;
		$(stringacompleta).prop("checked", true);
		
		var testoautomatico=0;
		testoautomatico=Math.floor(Math.random() * (65 - 18 + 1)) + 18;
		
		var txtv=parseInt(testoautomatico);
		
		$("input:text").val(txtv);
		}
		///////////////////////////////////////////////////////////////////////////
		
		
		
		
		//MULTIPLA////////////////////////////////////////////////////////////////
		numerooption=elements[i].getElementsByClassName('checkbox').length;
		
		if (numerooption>0)
		{
		var myarray = [];
		var dimvett;
		var ultimo=numerooption-1;
		var esclusivo=false;
		var conta=0;
		
		//riempio il vettore con tutte le opzioni selezionabili
		for (j = 0; j < numerooption; j++) 
		{
			
			myarray[j]=j;
			
		}
		
		//estraggo random il numero di opzioni da selezionare
		var numopt=Math.floor(Math.random() * numerooption);
		numopt=numopt+1;
		
		
		
		for (k = 0; k < numopt; k++) 
		{
			dimvett=myarray.length;
			
			//estraggo random l'opzione da selezionare
			var selezione=Math.floor(Math.random() * dimvett);
			
			//alert(selezione);
			
			var testoautomatico=0;
			testoautomatico=Math.floor(Math.random() * (99 - 1 + 1)) + 1;
			
			var select=myarray[selezione];
			myarray.splice(selezione, 1);
			var stringacompleta="#"+qid+"_opt"+select;
			var tipo=$("input"+stringacompleta).attr("type");
			var stringacomp="#"+qid+"_comp"+select;
			if ((tipo=='radio' && conta==0)||(conta>=0 && tipo!='radio'))
			{
				if (esclusivo==false)
				{
					$(stringacompleta).prop("checked", true);
					$("input:text").val(testoautomatico);
					if (tipo=='radio'){esclusivo=true;}
					conta=conta+1;
				}
			}
			
		}	
		}
		//////////////////////////////////////////////////////////////////////////////////////////
		
		
		
	}
	$(".date-picker").val("2018.01.25");
	$('#bnNext').click();
}
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
	
	
	
	////////////////////////////////////////////////////////////////////////////DOMANDA SINGOLA//////////////////////////////////////////////////////////////////////////////////////////////////////
	
	//leggo il tipo di domanda
	var tipodomanda=$("#debugInfo").val();
	var vista=$(".variables tr:nth-child(7) td:nth-child(2)").text();
	
	

	//se il tipo di domanda contiente queste 2 strignhe è una singola
	if ((tipodomanda.indexOf("choice") >= 0)&&(tipodomanda.indexOf("single") >= 0))
	{
		
	if (vista=="default")
	{	
	
	//leggo il numero delle opzioni selezionabili
	var numerooption=$( ".radio" ).length;
	
	
	//estraggo random l'opzione da selezionare
	var selezione=Math.floor(Math.random() * numerooption);
	 
	 var testoautomatico=0;
			testoautomatico=Math.floor(Math.random() * (99 - 1 + 1)) + 1;
	
	//autoimputo la selezione
	 $("#opt"+selezione).prop("checked", true)
	 $("input:text").val(testoautomatico);
	 
	}
	
		if (vista=="menu")
		{	
		var numerooption=$( "option" ).length;
			//estraggo random l'opzione da selezionare
			var selezione=Math.floor(Math.random() * numerooption);
		 document.getElementById('optGroup').value=selezione;
		}			
	 
	
	 
	 
	 //clicco tasto avanti
	 $('#bnNext').click();
	 
	
	 
     
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
	
	
	




////////////////////////////////////////////////////////////////////////////DOMANDA MULTIPLA//////////////////////////////////////////////////////////////////////////////////////////////////////
	
if ((tipodomanda.indexOf("choice") >= 0)&&(tipodomanda.indexOf("multiple") >= 0))
{
	
	
	
	
	var conta=0;
	var myarray = [];
	var dimvett;
	var lett;
	
	
	
	//leggo il numero delle opzioni selezionabili
	var numerooption=$( ".choice-option" ).length;
	var ultimo=numerooption-1;
	var esclusivo=false;
	
	var selminimo=$(".variables tr:nth-child(5) td:nth-child(2)").text();
	var selmassimo=$(".variables tr:nth-child(4) td:nth-child(2)").text();
	

	
	
	
	
	
	
	
	
//riempio il vettore con tutte le opzioni selezionabili
		for (i = 0; i < numerooption; i++) 
		{
			
			myarray[i]=i;
			
		}
	
	
	
	//estraggo random il numero di opzioni da selezionare
	var numopt=Math.floor(Math.random() * numerooption);
	
	numopt=numopt+1;
	
	
	
	if (numopt>selmassimo){numopt=selmassimo;}
	if (numopt<selminimo){numopt=selminimo;}
	
	
	
	for (i = 0; i < numopt; i++) 
	{
		
	dimvett=myarray.length;
	
	//estraggo random l'opzione da selezionare
	var selezione=Math.floor(Math.random() * dimvett);
	
	var select=myarray[selezione];
	myarray.splice(selezione, 1);
	
	//alert(myarray);
	
	var tipo=$("input#opt"+select).attr("type");
	
	var testoautomatico=0;
	testoautomatico=Math.floor(Math.random() * (99 - 1 + 1)) + 1;
	
	
	if ((tipo=='radio' && conta==0)||(conta>=0 && tipo!='radio'))
	{


	
	//alert(conta+" "+tipo+" "+selezione);
		
	if (esclusivo==false)
	{
	$("#opt"+select).prop("checked", true);
	$("input:text").val(testoautomatico);
	$("#comp0").val(12);
	$("#comp1").val(12);
	if (tipo=='radio'){esclusivo=true;}
	
	conta=conta+1;
	}
	}
	
	}	
	
	
	$('#bnNext').click();
	
	
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////











////////////////////////////////////////////////////////////////////////////DOMANDA SCALE//////////////////////////////////////////////////////////////////////////////////////////////////////



//se il tipo di domanda contiente la stringa scale
if (tipodomanda.indexOf("qst_scale") >= 0)
{

	console.log("entro");
	//leggo html per verifica se la domanda è singola o multipla
	var txt=$("html").html();
	
	//solo se la domanda è sinogla
	if (txt.indexOf("selType = \"single\"") >= 0) 
	{ 
		var iter=0;
		var row=1;
		var numerooption;
		var selezione;
		
		
		
		$( "tbody tr" ).each(function() 
		{
			
			//leggo il numero delle opzioni selezionabili
			numerooption=$( "tr:eq("+row+") .cell" ).length;
			console.log("numerooption"+numerooption);
			//estraggo random l'opzione da selezionare
			selezione=Math.floor(Math.random() * numerooption);
			
			//autoimputo la selezione
			
			$("input[id*='cell" + iter + ":" + selezione + "']").attr('checked', 'checked');
			iter++;
			row++;
		}); 
		
		
		
	}
	
	
	if (txt.indexOf("selType = \"multiple\"") >= 0) 
	{ 
		var iter=0;
		var row=1;
		var numerooption;
		var selezione;
		var myarray = [];
		var dimvett;
		var ultimo;
		var esclusivo=false;
		var conta=0;
		
		var selminimo=$(".variables tr:nth-child(5) td:nth-child(2)").text();
		var selmassimo=$(".variables tr:nth-child(4) td:nth-child(2)").text();
		var vista=$(".variables tr:nth-child(9) td:nth-child(2)").text();
		
		
		
		if (vista=="default")
		{
		//alert("default");
		
		$( "tbody tr" ).each(function() 
		{
			myarray = [];
			esclusivo=false;
			conta=0;
			//leggo il numero delle opzioni selezionabili
			numerooption=$( "th" ).length;
			numerooption=numerooption-1;
			
			//alert(numerooption);
			
			ultimo=numerooption-1;
			
			for (j = 0; j < numerooption; j++) 
			{
				
				myarray[j]=j;
				
			}
			
			//estraggo random il numero di opzioni da selezionare
			var numopt=Math.floor(Math.random() * numerooption);
			numopt=numopt+1;
			
			if (numopt>selmassimo){numopt=selmassimo;}
			if (numopt<selminimo){numopt=selminimo;}
			
			
			
			//alert(numopt);
			
			for (k = 0; k < numopt; k++) 
			{
				dimvett=myarray.length;
				
				//estraggo random l'opzione da selezionare
				var selezione=Math.floor(Math.random() * dimvett);
				
				var select=myarray[selezione];
				myarray.splice(selezione, 1);
				
				var tipo = $("input[id*='cell" + iter + ":" + select + "']").attr("type");

				
				if ((tipo=='radio' && conta==0)||(conta>=0 && tipo!='radio'))
				{
					if (esclusivo==false)
					{
						//alert("ciao");
						$("input[id*='cell" + iter + ":" + select + "']").prop("checked", true);
						
						if (tipo=='radio'){esclusivo=true;}
						conta=conta+1;
					}
				}
				
			}	
			
		
			iter++;
			row++;
		}); 
		
		}
		

		if (vista=="columns")
		{
			//alert("columns");
			
			$( "th.even" ).each(function() 
			{
				myarray = [];
				esclusivo=false;
				conta=0;
				//leggo il numero delle opzioni selezionabili
				numerooption=$( ".row-label" ).length;
				numerooption=numerooption;
				
				//alert(numerooption);
				
				ultimo=numerooption-1;
				
				for (j = 0; j < numerooption; j++) 
				{
					
					myarray[j]=j;
					
				}
				
				//estraggo random il numero di opzioni da selezionare
				var numopt=Math.floor(Math.random() * numerooption);
				numopt=numopt+1;
				
				if (numopt>selmassimo){numopt=selmassimo;}
				if (numopt<selminimo){numopt=selminimo;}
				
				
				
				//alert(numopt);
				
				for (k = 0; k < numopt; k++) 
				{
					dimvett=myarray.length;
					
					//estraggo random l'opzione da selezionare
					var selezione=Math.floor(Math.random() * dimvett);
					
					var select=myarray[selezione];
					myarray.splice(selezione, 1);
					
					var tipo = $("input[id*='cell" + select + ":" + iter + "']").attr("type");
					
					if ((tipo=='radio' && conta==0)||(conta>=0 && tipo!='radio'))
					{
						if (esclusivo==false)
						{
							//alert("ciao");
							$("input[id*='cell" + select + ":" + iter + "']").prop("checked", true);
							
							if (tipo=='radio'){esclusivo=true;}
							conta=conta+1;
						}
					}
					
				}	
				
				
				iter++;
				row++;
			}); 
			
		}
		
		
	}
	
	//$("input#cell0\\:0").prop("checked", true);
	
	$('#bnNext').click();
	
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////






////////////////////////////////////////////////////////////////////////////DOMANDA FIXED//////////////////////////////////////////////////////////////////////////////////////////////////////////


//se il tipo di domanda contiente queste 2 strignhe è una fixed
if ((tipodomanda.indexOf("fixed_sum") >= 0)&&(tipodomanda.indexOf("fixed_sum") >= 0))
{
	
	//leggo il numero delle opzioni selezionabili
	var numerooption=$( ".form-control" ).not("#status").length;
	
	var ultima=numerooption-1;
	
	var totale=0;
	
	//estraggo random l'opzione da selezionare
	
	var selezione;
	var qid=$(".variables tr:nth-child(2) td:nth-child(2)").text();
	qid="ans"+qid;
	
	qid=new RegExp(qid, 'g');
	
	var valid=$(".variables tr:nth-child(8) td:nth-child(2)").text();
	var operat=$(".variables tr:nth-child(3) td:nth-child(2)").text();
	var sommast=$(".variables tr:nth-child(5) td:nth-child(2)").text();
	if (sommast=="n/a"){sommast="100";}
	var somma=parseInt(sommast);
	var min=0;
	var max=somma;
	
	
	//alert(operat);
	
	if (valid!="none")
	{
		valid=valid.replace(qid, "");
		valid=valid.replace(">=", "");
		valid=valid.replace("<=", "");
		valid=valid.replace("&&", "");
		valid=valid.replace(" ", ",");
		
		
		
		
		var minmax = valid.split(",").map(Number);
		min=minmax[0];
		max=minmax[1];
	}
	
	
	//alert(min+"\n"+max);
	
	
	for (i = 0; i < numerooption; i++) 
	{
		selezione=Math.floor(Math.random() * (max - min + 1)) + min;
		totale=totale+selezione;
		//alert(totale);
		
		if (valid=="none")
		{
			if ((operat=="<=") && (totale>somma)){totale=totale-selezione; selezione=0;}
			if ((operat=="<") && (totale>=somma)){totale=totale-selezione; selezione=0;}
			
			if ((operat==">") && (totale<=somma)&&(i==ultima)){selezione=somma-totale+1;}
			if ((operat==">=") && (totale<somma)&&(i==ultima)){selezione=somma-totale;}
			
			
			if ((operat=="==") && (totale>=somma)){totale=totale-selezione; selezione=0;}
			if ((operat=="==") && (totale<somma)&&(i==ultima)){selezione=somma-totale;}
		}
		
		
		$("#field"+i).val(selezione);
		
		
		
	}
	
	
	//clicco tasto avanti
	$('#bnNext').click();


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
}












////////////////////////////////////////////////////////////////////////////DOMANDA ORDERING//////////////////////////////////////////////////////////////////////////////////////////////////////

if (tipodomanda.indexOf("ordering") >=0)
{
	
	
	
	var conta=0;
	var myarray = [];
	var dimvett;
	var numopt;
	
	
	//leggo il numero delle opzioni selezionabili
	var numerooption=$(".variables tr:nth-child(4) td:nth-child(2)").text();
	var strict=$(".variables tr:nth-child(6) td:nth-child(2)").text();
	
	
	//var numerooption=$( ".ranking-option" ).length;
	var ultimo=numerooption-1;
	
	
	
	
	
	
	//riempio il vettore con tutte le opzioni selezionabili
	for (i = 0; i < numerooption; i++) 
	{
		
		myarray[i]=i;
		
	}
	
	//estraggo random il numero di opzioni da selezionare
	numopt=Math.floor(Math.random() * numerooption);
	
	numopt=numopt+1;
	
	if (strict=="true"){numopt=numerooption;}
	
	for (i = 0; i <= numopt; i++) 
	{
		
		dimvett=myarray.length;
		
		//estraggo random l'opzione da selezionare
		var selezione=Math.floor(Math.random() * dimvett);
		

		myarray.slice(selezione, 1);
		
		
			
			
		$('button:eq('+selezione+')').click();
				
				conta=conta+1;
			
		
		
	}	
	
	
	$('#bnNext').click();
	
	
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////








////////////////////////////////////////////////////////////////////////////DOMANDA CONCEPT EVAL//////////////////////////////////////////////////////////////////////////////////////////////////////

if (tipodomanda.indexOf("concept_eval") >= 0)
{
	
	
	
	
	var conta=0;
	var myarray = [];
	var dimvett;
	
	
	
	//leggo il numero delle opzioni selezionabili
	var numerooption=$( "span.comp" ).length;
	var ultimo=numerooption-1;
	var esclusivo=false;
	
	var selminimo=$(".variables tr:nth-child(6) td:nth-child(2)").text();
	var selmassimo=$(".variables tr:nth-child(5) td:nth-child(2)").text();
	
	
	
	
	
	
	
	
	//riempio il vettore con tutte le opzioni selezionabili
	for (i = 0; i < numerooption; i++) 
	{
		
		myarray[i]=i;
		
	}
	
	
	
	//estraggo random il numero di opzioni da selezionare
	var numopt=Math.floor(Math.random() * numerooption);
	
	numopt=numopt+1;
	
	
	
	if (numopt>selmassimo){numopt=selmassimo;}
	if (numopt<selminimo){numopt=selminimo;}
	
	
	
	for (i = 0; i < numopt; i++) 
	{
		
		dimvett=myarray.length;
		
		//estraggo random l'opzione da selezionare
		var selezione=Math.floor(Math.random() * dimvett);
		
		var select=myarray[selezione];
		myarray.splice(selezione, 1);
		
		//alert(myarray);
		
		//alert(conta+" "+tipo+" "+selezione);
			
			
				$('span#s'+select).click();
				
				conta=conta+1;
			
		
		
	}	
	
	
	$('#bnNext').click();
	
	
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////DOMANDA CONCEPT//////////////////////////////////////////////////////////////////////////////////////////////////////

//leggo il tipo di domanda
var tipodomanda=$("#debugInfo").val();

//se il tipo di domanda contiente queste 2 strignhe è una singola
if (tipodomanda.indexOf("concept") >= 0)
{
	
	
	
	
	//clicco tasto avanti
	$('#bnNext').click();
	
	
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////DOMANDA NUMERIC//////////////////////////////////////////////////////////////////////////////////////////////////////



//se il tipo di domanda contiente la stringa scale
if (tipodomanda.indexOf("qst_numeric_grid") >= 0)
{
	
		
		$('input[type=number]').each(function() 
		{
			
			$(this).val(5);
		}); 
		
		
		

	
	

	
	//$("input#cell0\\:0").prop("checked", true);
	$('#console').show();
	$('#bnNext').click();
	
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




	
    });