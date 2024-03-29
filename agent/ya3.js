
function ask4Actions(){
	var entityURI = $("#entityURI").val();
	
	$.ajax({
		type: "GET",
		url: ya3URI,
		data: "entity=" + urlencode(entityURI),
		success: function(data){
			$("#results").html(data);
			setStatus("Looked up entity and retrieved possbile actions.");
		},
		error:  function(msg){
			setStatus("Error retrieving entity: " + msg.status + " " + msg.statusText);
		} 
	});
	
}

function executeAction(actionURI, entityConceptURI){
	var entityURI = $("#entityURI").val();
	
	
	// check against getAttributeValueCount() first!!!
	
	$.ajax({
		type: "GET",
		url: ya3URI,
		data: "action=" + urlencode(actionURI) + "&onEntity=" + urlencode(entityURI) + "&onEntityConcept=" + urlencode(entityConceptURI),
		success: function(data){
			window.open(
				data,
				'execResultWindow',
				'left=100,top=100,width=800,height=600,toolbar=0,resizable=1'
			);
			setStatus("Executed action on entity.");
		},
		error:  function(msg){
			setStatus("Error executing action on entity: " + msg.status + " " + msg.statusText);
		} 
	});
	
}

function listActions(){
	
	$.ajax({
		type: "GET",
		url: ya3URI,
		data: "list",
		success: function(data){
			$("#info").html(data);
			setStatus("Listed  all available entities and actions known to me.");
		},
		error:  function(msg){
			setStatus("Error retrieving list: " + msg.status + " " + msg.statusText);
		} 
	});
	
}



// other stuff

function setBusy(){
	$("#results").html("<img src='img/ajax-loader.gif' id='busy' width='32px' alt='busy />");	
}

function setStatus(status){
	$("#statusMsg").text(status);
	$("#statusMsg").fadeIn(1000);
	$("#statusMsg").fadeOut(5000);
}

function urlencode(str) {
    // URL-encodes string  
    // 
    // version: 907.503
    // discuss at: http://phpjs.org/functions/urlencode
    // +   original by: Philip Peterson
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: AJ
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: travc
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Lars Fischer
    // +      input by: Ratheous
    // %          note 1: info on what encoding functions to use from: http://xkr.us/articles/javascript/encode-compare/
    // *     example 1: urlencode('Kevin van Zonneveld!');
    // *     returns 1: 'Kevin+van+Zonneveld%21'
    // *     example 2: urlencode('http://kevin.vanzonneveld.net/');
    // *     returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
    // *     example 3: urlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
    // *     returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'
                             
    var hash_map = {}, unicodeStr='', hexEscStr='';
    var ret = (str+'').toString();
    
    var replacer = function(search, replace, str) {
        var tmp_arr = [];
        tmp_arr = str.split(search);
        return tmp_arr.join(replace);
    };
    
    // The hash_map is identical to the one in urldecode.
    hash_map["'"]   = '%27';
    hash_map['(']   = '%28';
    hash_map[')']   = '%29';
    hash_map['*']   = '%2A';
    hash_map['~']   = '%7E';
    hash_map['!']   = '%21';
    hash_map['%20'] = '+';
    hash_map['\u00DC'] = '%DC';
    hash_map['\u00FC'] = '%FC';
    hash_map['\u00C4'] = '%D4';
    hash_map['\u00E4'] = '%E4';
    hash_map['\u00D6'] = '%D6';
    hash_map['\u00F6'] = '%F6';
    hash_map['\u00DF'] = '%DF';
    hash_map['\u20AC'] = '%80';
    hash_map['\u0081'] = '%81';
    hash_map['\u201A'] = '%82';
    hash_map['\u0192'] = '%83';
    hash_map['\u201E'] = '%84';
    hash_map['\u2026'] = '%85';
    hash_map['\u2020'] = '%86';
    hash_map['\u2021'] = '%87';
    hash_map['\u02C6'] = '%88';
    hash_map['\u2030'] = '%89';
    hash_map['\u0160'] = '%8A';
    hash_map['\u2039'] = '%8B';
    hash_map['\u0152'] = '%8C';
    hash_map['\u008D'] = '%8D';
    hash_map['\u017D'] = '%8E';
    hash_map['\u008F'] = '%8F';
    hash_map['\u0090'] = '%90';
    hash_map['\u2018'] = '%91';
    hash_map['\u2019'] = '%92';
    hash_map['\u201C'] = '%93';
    hash_map['\u201D'] = '%94';
    hash_map['\u2022'] = '%95';
    hash_map['\u2013'] = '%96';
    hash_map['\u2014'] = '%97';
    hash_map['\u02DC'] = '%98';
    hash_map['\u2122'] = '%99';
    hash_map['\u0161'] = '%9A';
    hash_map['\u203A'] = '%9B';
    hash_map['\u0153'] = '%9C';
    hash_map['\u009D'] = '%9D';
    hash_map['\u017E'] = '%9E';
    hash_map['\u0178'] = '%9F';
    
    // Begin with encodeURIComponent, which most resembles PHP's encoding functions
    ret = encodeURIComponent(ret);

    for (unicodeStr in hash_map) {
        hexEscStr = hash_map[unicodeStr];
        ret = replacer(unicodeStr, hexEscStr, ret); // Custom replace. No regexing
    }
    
    // Uppercase for full PHP compatibility
    return ret.replace(/(\%([a-z0-9]{2}))/g, function(full, m1, m2) {
        return "%"+m2.toUpperCase();
    });
}

function getCacheBusterParam(){
	// http://mousewhisperer.co.uk/js_page.html
	return  "&rcb=" + parseInt(Math.random()*99999999); 
}