var $I = function(id) {
  return document.getElementById(id);
};

var $D = function(id, show) {
  if (show) {
    $I(id).style.display = "block";
  } else {
    $I(id).style.display = "none";
  }
};

var supportsSvg = function() {
    return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#Shape", "1.0");
};

var showLoading = function(n) {
  switch (n) {
  case 0: // region map
    $I('region_div').innerHTML = '<img src="img/loading.gif"/>';
    break;
  case 1: // trend graph
    $I('trend_div').innerHTML = '<center><img src="img/loading.gif" width="33"/></center>';
    break;
  case 2: // user network
    $I('network_canvas_span').innerHTML = '<img src="img/loading.gif"/>';
    break;
  case 3: // messages
    $I('message_div').innerHTML = '<center><img src="img/loading.gif" width="33"/></center>';
    break;
  case 4: // wordle
    $I('cloudcontainer').innerHTML = '<center><img src="img/loading.gif" width="33"/></center>';
    break;
  }
};

var showMessage = function(n, msg) {
  switch (n) {
  case 0:
    $I('region_div').innerHTML = msg;
    break;
  case 1:
    $I('trend_div').innerHTML = msg;
    break;
  case 2:
    $I('network_canvas_span').innerHTML = msg;
    break;
  case 3:
    $I('message_div').innerHTML = msg;
    break;
  case 4:
    $I('cloudcontainer').innerHTML = msg;
    break;
  }
};

// serialize an object to a url param
// http://stackoverflow.com/questions/1714786/querystring-encoding-of-a-javascript-object
var serializeParam = function(obj, prefix) {
  var str = [];
  for (var p in obj) {
    var k = prefix ? prefix + "[" + p + "]" : p, v = obj[p];
      str.push(typeof v == "object" ? 
        serializeParam(v, k) :
        encodeURIComponent(k) + "=" + encodeURIComponent(v));
  }
  return str.join("&");
};

var getXmlHttpObject = function() {
  var xmlHttp = null;
  try { // Firefox, Opera 8.0+, Safari
    xmlHttp = new XMLHttpRequest();
  } catch (e) { // Internet Explorer
    try {
      xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {
        // alert("您的浏览器不支持AJAX！");
      }
    }
  }
  return xmlHttp;
};

var ajaxQuery = function(url, data, callback) {
  var xmlHttp = getXmlHttpObject();
  if (xmlHttp == null) return;
  xmlHttp.onreadystatechange = function() {
    switch (xmlHttp.readyState) {
    case 1:
      // $("status").innerHTML = "请求已提出";
      break;
    case 2:
      // $("status").innerHTML = "请求已发送";
      break;
    case 3:
      // $("status").innerHTML = "请求处理中";
      break;
    case 4:
      if (xmlHttp.status == 200) {
        var obj = null;
        try {
          obj = JSON.parse(xmlHttp.responseText);
        } catch (e) {
        }
        callback(obj);
        // $("status").innerHTML = "就绪";
      } else {
        // $("status").innerHTML = "发生了错误：" + xmlHttp.status;
      }
      break;
    }
  };
  var params = serializeParam(data);
  xmlHttp.open("GET", url + "?" + params, true);
  xmlHttp.send(null);
};

var getMaxIndex = function (arr) {
  var maxIndex = 0;
  for (var index = 1; index < arr.length; index++) {
    if (arr[index] > arr[maxIndex]) maxIndex = index;
  }
  return maxIndex;
};

var getConvertedIndex = function (arr) {
  var maxIndex = 0, secondMaxIndex = 0;
  var sum = 0;
  for (var index = 0; index < arr.length; index++) {
    if (arr[index] > arr[maxIndex]) {
      secondMaxIndex = maxIndex;
      maxIndex = index;
    } else if (arr[index] > arr[secondMaxIndex]) {
      secondMaxIndex = index;
    }
    sum = sum + arr[index];
  }
  var newIndex = maxIndex * 2 + 1;
  var alpha = (arr[maxIndex] - arr[secondMaxIndex]) / arr[maxIndex];
//  if (alpha > 1) alpha = 1;
  //alpha = alpha * 3;
 // newIndex = alpha * newIndex + (1 - alpha) * (newIndex + 1);
  // if (newIndex > 9) newIndex = 18 - newIndex;
  return newIndex + (1 - arr[maxIndex]/sum);
}

var formatDate = function (seconds) { // seconds is in UTC
  var d = new Date(seconds * 1000);
  var timestring = (d.getHours() < 10 ? "0" + d.getHours() : d.getHours()) + ":";
  timestring += (d.getMinutes() < 10 ? "0" + d.getMinutes() : d.getMinutes()) + ":";
  timestring += (d.getSeconds() < 10 ? "0" + d.getSeconds() : d.getSeconds());
  return d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate() + " " + timestring;
};

// decode html entities: http://stackoverflow.com/questions/1912501/unescape-html-entities-in-javascript
var htmlDecode = function (input) {
  var e = document.createElement('div');
  e.innerHTML = input;
  return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
};

// given the timestamp, value and probability, convert to a HighCharts point with some weights for drawing the trends
var probabilityThreshold = 0.2;
var breakingPoint = function (timestamp, value, probability) {
  var w = 0;
  if (probability > probabilityThreshold) {
    w = 0;
  } else {
    if (probability < 0.025) probability = 0.025;
    w = 0.4 / probability;
  }
  
  var point = {
    x:timestamp, 
    y:value, 
    marker:{
      enabled:true, 
      radius:w, 
      symbol:'circle'
    }
  };
  return point;
  // return [timestamp, value];
};
