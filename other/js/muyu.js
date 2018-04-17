function muyu_get(url, callback)
{
	var request = new XMLHttpRequest();
	request.onreadystatechange = function()
	{
		if(request.readyState === 4)
			callback(JSON.parse(request.responseText));
	}
	request.open("GET", url);
	request.send();
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
	request.onreadystatechange = function()
	{
		if(request.readyState === 4)
			callback(JSON.parse(request.responseText));
	}
	request.send(postData);
}
function muyu_query_param(name, url) 
{
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}
function muyu_inputs(inputData)
{
	let inputObj = {};
	for (let [key, val] of Object.entries(inputData))
		inputObj[key] = document.querySelector('#' + val).value;
	return inputObj;
}
function muyu_fill_inputs(inputs)
{
	for (let [key, val] of Object.entries(inputs))
	{
		let elem = document.querySelector('#' + key);
		if(elem.tagName === 'INPUT' || elem.tagName === 'SELECT')
			elem.value = val;
		else if(elem.tagName === 'IMG')
			elem.src = val;
		else
			elem.innerHTML = val;
	}
}
function muyu_file_base64(file, callback)
{
	let reader = new FileReader();
	try
	{
		reader.readAsDataURL(file);
		reader.onload = () => {
			callback(reader.result);
		};
	} catch (e) 
	{
		callback('');
	}
}
function muyu_img_src_base64(src)
{
	return src.split(' ').join('+');
}
function mv(obj, ...attr)
{
	return muyu_val(obj, ...attr);
}
function muyu_val(obj, ...attr)
{
	let rs = obj;
	for(let i = 0;i < attr.length;i++)
		if(obj[attr[i]])
			rs = obj = obj[attr[i]];	
		else
			return undefined;
	return rs;
}
function me(exp, def, justify, modify)
{
	return muyu_express(exp, def, justify, modify);
}
function mee(...exp)
{
	let def = exp.pop();
	return muyu_express(exp, def);
}
function muyu_express(exp, def, justify, modify)
{
	let rs = null;
	if(Array.isArray(exp))
	{
		let pass = false;
		for(let i = 0;i < exp.length;i++)
		{
			if(justify)
			{
				if(justify(exp[i]))
				{
					pass = true;
					rs = exp[i];
				}
			}
			else
			{
				if(exp[i])
				{
					if(Array.isArray(exp[i]))
					{
						if(exp[i].length !== 0)
						{
							pass = true;
							rs = exp[i];
						}
					}
					else
					{
						pass = true;
						rs = exp[i];
					}
				}
			}			
			if(pass)
				break;
		}
		if(pass)
			return modify ? modify(rs) : rs;
		else
			return def;
	}
	if(justify)
	{
		if(modify)
			rs = justify(exp) ? modify(exp) : (def ? def : null);
		else
			rs = justify(exp) ? exp : (def ? def : null);
	}
	else
	{
		if(modify)
		{
			if(Array.isArray(exp))
				rs = exp.length !== 0 ? modify(exp) : (def ? def : null);
			else
				rs = exp ? modify(exp) : (def ? def : null);
		}
		else
		{
			if(Array.isArray(exp))
				rs = exp.length !== 0 ? exp : (def ? def : null);
			else
				rs = exp ? exp : (def ? def : null);
		}
	}
	return rs;
}
function muyu_date(date)
{
	return date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0');
}
function muyu_time(date)
{
	return date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0') + ' ' + date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0') + ':' + date.getSeconds().toString().padStart(2, '0');
}
function muyu_time_str(date)
{
    date = date * 1000;
    let minute = 1000 * 60;
    let hour = minute * 60;
    let day = hour * 24;
    let halfamonth = day * 15;
    let month = day * 30;

    let now = new Date().getTime();
    let diffValue = now - date;
    let monthC = diffValue / month;
    let weekC = diffValue / (7 * day);
    let dayC = diffValue / day;
    let hourC = diffValue / hour;
    let minC = diffValue / minute;
    if(monthC>=1)
        result = parseInt(monthC) + '个月前';
    else if(weekC>=1)
        result = parseInt(weekC) + '周前';
    else if(dayC>=1)
        result = parseInt(dayC) +'天前';
    else if(hourC>=1)
        result = parseInt(hourC) +'小时前';
    else if(minC>=1)
        result = parseInt(minC) +'分钟前';
    else
        result='刚刚';
    return result;
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
function muyu_setCookie(c_name,value,expiredays)
{
	let exdate = new Date();
	exdate.setDate(exdate.getDate() + expiredays);
	document.cookie = c_name + "=" + escape(value) + ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
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
            $(btn).trigger("click");
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
function muyu_trim(str, mode)
{
	if(mode == 'left')
		return str.replace(/(^\s*)/g,"");
	else if(mode == 'right')
		return str.replace(/(\s*$)/g,"");
	else if(mode == 'both')
		return str.replace(/(^\s*)|(\s*$)/g, "");
	else
	{
		console.log("muyu_trim中mode参数错误");
		return str;
	}
}
function muyu_hideFooter()
{
	$(window).bind("resize", function(e)
	{
	  if($("footer").css("display") == "block")
			$("footer").css("display","none");
	  else
			$("footer").css("display","block");
	});
}