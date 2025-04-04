function copyUrl(){
	var $temp = $("<input>");
	$("body").append($temp);
	var activeLink = window.location.href;
	if(activeLink.substring(activeLink.length - 3) == "php"){
		activeLink = activeLink + "?" + encodedQueryStr(sessionStorage.querystr);
	}
	$temp.val(activeLink).select();
	document.execCommand("copy");
	$temp.remove();
}

function addVoucherToCl(occidIn,clidIn,tidIn){
	$.ajax({
		type: "POST",
		url: "../checklists/rpc/linkvoucher.php",
		data: { occid: occidIn, clid: clidIn, taxon: tidIn }
	}).done(function( msg ) {
		alert(msg);
	});
}

function toggleFieldBox(target){
	var objDiv = document.getElementById(target);
	if(objDiv){
		if(objDiv.style.display=="none"){
			objDiv.style.display = "block";
		}
		else{
			objDiv.style.display = "none";
		}
	}
	else{
		var divs = document.getElementsByTagName("div");
		for (var h = 0; h < divs.length; h++) {
			var divObj = divs[h];
			if(divObj.className == target){
				if(divObj.style.display=="none"){
					divObj.style.display="block";
				}
				else {
					divObj.style.display="none";
				}
			}
		}
	}
}

function openIndPU(occId,clid){
	//var wWidth = 1100;
	//if(document.body.offsetWidth < wWidth) wWidth = document.body.offsetWidth*0.9;
	//var newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
	//if (newWindow.opener == null) newWindow.opener = self;
	
	// neon edit - changed from popup to new tab
	var url = 'individual/index.php?occid=' + occId + '&clid=' + clid;
    var newWindow = window.open(url, '_blank');
    newWindow.opener = self;
	// end neon edit
	return false;
}

function openMapPU(){
	var url = 'map/googlemap.php?' + encodedQueryStr(sessionStorage.querystr);
	window.open(url,'gmap','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=1150,height=900,left=20,top=20');
}

function encodedQueryStr(querystr){
	let encodedQueryStr = "";
	querystr.split("&").forEach(function(part) {
		let eq = part.indexOf("=");
		let key = eq > -1 ? part.substr(0, eq) : part;
		let val = eq > -1 ? encodeURIComponent(part.substr(eq + 1)) : "";
		if(encodedQueryStr != "") encodedQueryStr = encodedQueryStr + "&";
		encodedQueryStr = encodedQueryStr + key + "=" + val;
	});
	return encodedQueryStr;
}

function targetPopup(f) {
	window.open('', 'downloadpopup', 'left=100,top=50,width=900,height=700');
	f.target = 'downloadpopup';
}