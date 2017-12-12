function muyu_get(url, callback)
{
	var request = new XMLHttpRequest();
	request.open("GET", url);
	request.send();
	request.onreadystatechange = function()
	{
		if(request.readyState === 4)
			callback(JSON.parse(request.responseText));
	}
}
function muyu_post(url, postData, callback)
{
	var request = new XMLHttpRequest();
	request.open("POST", url);
	request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	if(typeof(postData) == 'object')
	{
		let str = '';
		for(let key in postData)
			if(postData.hasOwnProperty(key))
				str += '&' + key + '=' + postData[key];
		str = str.substr(1, str.length);
		postData = str;
	}
	request.send(postData);
	request.onreadystatechange = function()
	{
		if(request.readyState === 4)
			callback(JSON.parse(request.responseText));
	}
}
function muyu_getCookie(cookieName)
{
	if (document.cookie.length > 0)
  	{
  		c_start=document.cookie.indexOf(cookieName + "=")
  		if (c_start != -1)
   		{
   			c_start = c_start + cookieName.length + 1 
   			c_end=document.cookie.indexOf(";", c_start)
   			if (c_end == -1) 
				c_end = document.cookie.length
   			return unescape(document.cookie.substring(c_start,c_end))
   		} 
   	}
	return null;
}
function muyu_sort(str,split,mode)
{
	str = str.split(split);
	if(mode == "str")
		str = str.sort();
	else if(mode == "num")
		str = str.sort(sort);
	else
		return "invalid mode";
	str = str.join(split);
	return str;
	function sort(a, b) 
	{
		return a - b;
   	}	
}
function muyu_noty(content, type, layout)
{
    content = arguments[0] ? arguments[0] : null;
    type = arguments[1] ? arguments[1] : 'alert';
    layout =  arguments[2] ? arguments[2] : 'top';
    $.noty.consumeAlert({layout: layout, type: type, dismissQueue: true, timeout: 5000});
    if(content !== null)
        alert(content);
}
function muyu_enter(btn)
{
    $(document).off("keydown");
    $(document).keydown(function(event){
        if(event.keyCode === 13)
            btn.trigger("click");
    });
}
function uniqid (prefix, moreEntropy) {
    if (typeof prefix === 'undefined') {
        prefix = ''
    }
    var retId;
    var _formatSeed = function (seed, reqWidth) {
        seed = parseInt(seed, 10).toString(16);
        if (reqWidth < seed.length) {
            return seed.slice(seed.length - reqWidth)
        }
        if (reqWidth > seed.length) {
            return new Array(1 + (reqWidth - seed.length)).join('0') + seed
        }
        return seed
    };
    var $global = (typeof window !== 'undefined' ? window : global);
    $global.$locutus = $global.$locutus || {};
    var $locutus = $global.$locutus;
    $locutus.php = $locutus.php || {};
    if (!$locutus.php.uniqidSeed) {
        $locutus.php.uniqidSeed = Math.floor(Math.random() * 0x75bcd15)
    }
    $locutus.php.uniqidSeed++;
    retId = prefix;
    retId += _formatSeed(parseInt(new Date().getTime() / 1000, 10), 8);
    retId += _formatSeed($locutus.php.uniqidSeed, 5);
    if (moreEntropy) {
        retId += (Math.random() * 10).toFixed(8).toString()
    }
    return retId;
}
function muyu_date()
{
	var date = new Date(+new Date()+8*3600*1000).toISOString().replace(/T/g,' ').replace(/\.[\d]{3}Z/,'');
	date=date.split(" ");
    date=date[0].split("-");
    date="&nbsp;" + date[1].replace("0","") + "/" + date[2].replace("0","");
	return date;
}
function muyu_time()
{
	var time = new Date(+new Date()+8*3600*1000).toISOString().replace(/T/g,' ').replace(/\.[\d]{3}Z/,'');
	time=time.split(" ");
	time=time[1].split(":");
	time=time[0] + ":" + time[1];
	return time;
}
function muyu_tranNum(obj,incre,time)
{
	if(incre>0)
		var incresing = setInterval(function(){
			$(obj).html(parseInt($(obj).html()) + 1);
			if(--incre==0)
				clearInterval(incresing);
		},time/incre);
	else
		var incresing = setInterval(function(){
			$(obj).html(parseInt($(obj).html()) - 1);
			if(++incre==0)
				clearInterval(incresing);
		},time/-incre);
}
function muyu_totop(btn)
{
	btn = document.getElementById(btn);
	var clientHeight = document.documentElement.clientHeight;
	var timer = null;	
	var isTop = true;
	window.onscroll = function()
	{
		var osTop = document.documentElement.scrollTop || document.body.scrollTop;
		if(osTop >= clientHeight)
			btn.style.display = "block";
		else 
			btn.style.display = "none";
		if(!isTop)
			clearInterval(timer);
		isTop = false;
	}
	btn.onclick = function()
	{
		timer = setInterval(function()
		{
			var osTop = document.documentElement.scrollTop || document.body.scrollTop;
			var ispeed = Math.floor(-osTop/6);
			document.documentElement.scrollTop = document.body.scrollTop = osTop + ispeed;
			isTop = true;
			if(osTop == 0)
				clearInterval(timer);
		},30);
	}
}
function muyu_trim(str,mode)
{
	if(mode == 'left')
		return str.replace(/(^\s*)/g,"");
	else if(mode == 'right')
		return str.replace(/(\s*$)/g,"");
	else if(mode == 'both')
		return str.replace(/(^\s*)|(\s*$)/g, "");
	else
		alert("muyu_trim中mode参数错误");
}
function muyu_hideFooter()
{
  $(window).bind("resize", resizeWindow);
  function resizeWindow(e) 
  {
    if($("footer").css("display") == "block")
      $("footer").css("display","none");
    else
      $("footer").css("display","block");
  }
}