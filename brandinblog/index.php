<?php
include_once('util.php');

if (array_key_exists('lang', $_REQUEST)) {
  if ($_REQUEST['lang'] === 'zh')
    Util::$LANG = 'zh';
  elseif ($_REQUEST['lang'] === 'ja')
    Util::$LANG = 'ja';
}

$forceSVG = False;
if (array_key_exists('svg', $_REQUEST) && $_REQUEST['svg'] === '1') {
  $forceSVG = True;
}
$forceSVG = True;

// $drawNetwork = True;
$drawNetwork = False;
if (array_key_exists('network', $_REQUEST) && $_REQUEST['network'] === '1') {
  $drawNetwork = True;
}

// $begin_timestamp = mktime(0, 0, 0, 1, 1, 2012);
// $begin_timestamp = floor((time() - 86400 * 4) / 86400) * 86400;
// $end_timestamp = time();
$begin_timestamp = mktime(0, 0, 0, 2, 12, 2015);
$end_timestamp = mktime(0, 0, 0, 4, 6, 2015);

?>
<!DOCTYPE html>
<html lang="<?=Util::langtext('en', 'zh-CN', 'ja')?>">
  <head>
    <meta charset="utf-8" />
    <title><?=Util::langtext("BrandInBlog - Analytics of Corporate Reputation in Microblog Messages", "博然般若 - 企业微博口碑分析", "ブランディンブログ - 企業マイクロブログ評判分析")?></title>
    <!-- Responsive features -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Le styles -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">


    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="shortcut icon" href="img/favicon.ico">
    <!--
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
    -->

    <style type="text/css">
    .wordcloud {
        float: left;
        position: relative;
        width: 360px;
        height: 360px;
    }
    #dialog { 
        display: none; 
    }
    .ui-dialog-title, .ui-dialog-content, .ui-widget-content {
        font-family: "Trebuchet MS", "Helvetica", "Arial",  "Verdana", "sans-serif";
            font-size: 62.5%;
    }
    </style>

    <script type='text/javascript' src='js/util.js'></script>

    <script type='text/javascript' src='lib/d3/d3.js'></script>
    <script type='text/javascript' src='d3.layout.cloud.js'></script>
    <script type='text/javascript' src='wordcloud2.js'></script>
    <script type='text/javascript' src='js/wordle/diffwordle.js'></script>
    <script type='text/javascript' src='js/wordle/rgbcolor.js'></script>
    <script type='text/javascript' src='js/wordle/stopwords.js'></script>
    <script type='text/javascript' src='js/wordle/wordanalyzer.js'></script>
    <script type='text/javascript' src='https://www.google.com/jsapi'></script> 
    <script type='text/javascript'>
      google.load('visualization', '1', {'packages': ['geochart']});

//      var diffwordle = new DiffWordle('cloudcontainer', 
 //                    { 'width':360, 'height':200, 
  //                     'type':0, 'scale':'sqrt' });

      var tabType = 0; // 0: count, 1: sentiment, 2: emotion

      var provinces = {
<?php
  foreach (Util::$PROVINCES as $code => $arr) {
?>
        '<?=$code?>': '<?=Util::langtext($arr[0], $arr[1], $arr[2])?>',
<?php
  }
?>
      };

      var emotions = [
        '<?=Util::langtext("Happiness", "喜悦", "喜び")?>',
        '<?=Util::langtext("Anger", "愤怒", "怒り")?>',
        '<?=Util::langtext("Sadness", "悲哀", "悲しみ")?>',
        '<?=Util::langtext("Fear", "恐惧", "恐怖")?>',
        '<?=Util::langtext("Surprise", "惊讶", "驚き")?>'
      ];

      var init = function() {
<?php
  if (!$forceSVG) {
?>
        if (!supportsSvg())
          return;
<?php
  }
?>
        Highcharts.setOptions({
          global: {
            useUTC: false
          }
        });

        loadCount('<?=$begin_timestamp * 1000?>', '<?=$end_timestamp * 1000?>', null, null, tabType);
        loadTrend('<?=$begin_timestamp * 1000?>', '<?=$end_timestamp * 1000?>', null, null, tabType);
        loadMessage('<?=$begin_timestamp * 1000?>', '<?=$end_timestamp * 1000?>', null, null, tabType, -1, 100, 0);
        <?=$drawNetwork ? "" : "//" ?>loadNetwork('<?=$begin_timestamp * 1000?>', '<?=$end_timestamp * 1000?>', null, null);
      };

      var mapAxis = { colors: ['#003F00', '#00FF00'] };
      var mapScores;
      function drawRegionsMap(rows) {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'ProvinceCode');
        data.addColumn('number', 'Weight');
        data.addColumn({
          type : 'string',
          role : 'tooltip'
        });

        data.addRows(rows);

        var options = {
          region : 'CN',
          resolution : 'provinces',
          colorAxis : mapAxis
        };
        if (tabType == 2) options['legend'] = 'none';

        var regionChart = new google.visualization.GeoChart($I('region_div'));
        google.visualization.events.addListener(regionChart, 'regionClick', function (eventData) {
          var region = eventData['region'];
          var regioncode = "11";
          if (region == 'TW') {
            regioncode = "71";
          } else if (region == 'HK') {
            regioncode = "81";
          } else if (region == 'MO') {
            regioncode = "82";
          } else {
            regioncode = region.substring(3);
          }
          submitQuery(14, {'region': regioncode, 'score': mapScores[region]});
        });
        regionChart.draw(data, options);
      }

      var loadCount = function(begin, end, region, brand, score) {
        var rows = [];
        showLoading(0);
        ajaxQuery(
          './get.php', 
          {
            url: '/brandinblog/api/1/count', 
            params: {'begin':begin, 'end':end, 'region':region, 'brand':brand, 'score': score}
          }, 
          function (result) {
            if (result == null) {
              showMessage(0, "<?=Util::langtext("Failed to load data.", "无法读取数据。", "読み込みに失敗しました。")?>");
              return;
            }
            $I('totalcount').innerHTML = result['total'];
            var tableCount = {}, tableValue = {};
            for (var key in result['groups']) {
              var data = result['groups'][key];
              var regioncode = data['region'].substring(0, 2);
              if (!(regioncode in tableCount)) {
                tableCount[regioncode] = 0;
                tableValue[regioncode] = (tabType == 2 ? [0, 0, 0, 0, 0] : 0);
              }
              tableCount[regioncode] += data['count'];
              switch (tabType) {
              case 1:
                tableValue[regioncode] += data['sentiment'] * data['count'];
                break;
              case 2:
                tableValue[regioncode][0] += data['happiness'] * data['count'];
                tableValue[regioncode][1] += data['anger'] * data['count'];
                tableValue[regioncode][2] += data['sadness'] * data['count'];
                tableValue[regioncode][3] += data['fear'] * data['count'];
                tableValue[regioncode][4] += data['surprise'] * data['count'];
              }
            }

            if (tabType == 1) {
              var sentimentMax = 0;
              for (var key in tableCount) {
                if (tableCount[key] <= 0) continue;
                if (Math.abs(tableValue[key] / tableCount[key]) > sentimentMax) {
                  sentimentMax = Math.abs(tableValue[key] / tableCount[key]);
                }
              }
            }

            mapScores = {};
            for (var key in tableCount) {
              var newkey = 'CN-' + key;
              switch (key) {
              case '81':
                newkey = 'HK';
                break;
              case '82':
                newkey = 'MO';
                break;
              case '71':
                newkey = 'TW';
                break;
              }
              var txt = provinces[key] + "(" + tableCount[key] + ")";
              var val = tableCount[key];
              switch (tabType) {
              case 0:
                mapAxis = { colors: ['#003F00', '#00FF00'] };
                break;
              case 1:
                if (tableCount[key] > 0) tableValue[key] /= tableCount[key];
                txt += ": " + tableValue[key];
                val = tableValue[key];
                mapAxis = {minValue: -sentimentMax, maxValue: sentimentMax, colors: ['#0000FF', '#FF0000']};
                break;
              case 2:
                txt += ":";
                for (var i = 0; i < emotions.length; i++) {
                  tableValue[key][i] /= tableCount[key];
                  txt += " " + emotions[i] + " " + Math.round(tableValue[key][i] * 10000) / 10000;
                }
                val = getConvertedIndex(tableValue[key]);
                mapAxis = { minValue: 1, maxValue: 10, colors: ['<?=implode("', '" . Util::$EMOTIONCOLORGRAY . "', '", Util::$EMOTIONCOLORS) . "', '" . Util::$EMOTIONCOLORGRAY?>']};
                break;
              }
              rows.push([newkey, val, txt]);
              mapScores[newkey] = val;
            }
            drawRegionsMap(rows);
          }
        );
      };

      var loadTrend = function(begin, end, region, brand, score) {
        showLoading(1);
        ajaxQuery(
          './get.php',
          {
            url: '/brandinblog/api/1/trend',
            params: {'begin':begin, 'end':end, 'region':region, 'brand':brand, 'score':score, 'probability':1}
          },
          function (result) {
            if (result == null) {
              showMessage(1, "<?=Util::langtext("Failed to load data.", "无法读取数据。", "読み込みに失敗しました。")?>");
              return;
            }
            result['trends'].sort(function(a, b){return a['time'] - b['time'];});
            var series = [];
            for (var i = 0; i < tabType + 1; i++) {
              series.push([]);
            }
            if (tabType == 2) {
              // three more
              series.push([]);
              series.push([]);
              series.push([]);
            }
            // add the points into the series
            // also mark the points with lower probabilies some markers
            var probthreshold = 0.2;
            for (var key in result['trends']) {
              var timestamp = result['trends'][key]['time'];
              var values = result['trends'][key]['values'];
              var probability = result['trends'][key]['probability'];
              switch (tabType) {
              case 0:
                series[0].push(breakingPoint(timestamp, values['count'], probability['count']));
                break;
              case 1:
                series[0].push(breakingPoint(timestamp, values['pos'], probability['pos']));
                series[1].push(breakingPoint(timestamp, values['neg'], probability['neg']));
                break;
              case 2:
                series[0].push(breakingPoint(timestamp, values['happiness'], probability['happiness']));
                series[1].push(breakingPoint(timestamp, values['anger'], probability['anger']));
                series[2].push(breakingPoint(timestamp, values['sadness'], probability['sadness']));
                series[3].push(breakingPoint(timestamp, values['fear'], probability['fear']));
                series[4].push(breakingPoint(timestamp, values['surprise'], probability['surprise']));
                break;
              }
            }
            drawTrends(series, begin, end);
          }
        );
      };

      var loadNetwork = function(begin, end, region, brand, score) {
        <?=$drawNetwork ? "" : "return;"?>
        showLoading(2);
        ajaxQuery(
          './get.php',
          {
            url: '/brandinblog/api/1/negative',
            params: {'begin':begin, 'end':end, 'region':region, 'brand':brand, 'fansonly':1}
          },
          function (result) {
            if (result == null) {
              showMessage(2, "<?=Util::langtext("Failed to load data.", "无法读取数据。", "読み込みに失敗しました。")?>");
              return;
            }
            var users = result['user'];
            drawNetwork(users);
          }
        );
      };

      var weiboIndex = 0, weiboCount = 0, weiboMsg = [];
      var loadMessage = function(begin, end, region, brand, tabType, scoreType, count, skip) {
        showLoading(3);
        showLoading(4);
        showLoading(5);
        var sortStr = "";
        switch (tabType) {
        case 0:
          sortStr = "";
          break;
        case 1:
          sortStr = "sentiment:";
          if (scoreType > 0)
            sortStr += "positive";
          else if (scoreType < 0)
            sortStr += "negative";
          else
            sortStr += "neutral";
          sortStr += ":desc"
          break;
        case 2:
          sortStr = "emotion:";
          var mask = 1;
          for (var i = 1; i < scoreType; i++)
            mask += mask;
          sortStr += mask + ":desc"
          break;
        }
        ajaxQuery(
          './get.php',
          {
            url: '/brandinblog/api/1/negative',
            params: {'begin':begin, 'end':end, 'region':region, 'brand':brand, 'sort': sortStr, 'count': count, 'skip': skip}
          },
          function (result) {
            if (result == null) {
              showMessage(3, "<?=Util::langtext("Failed to load data.", "无法读取数据。", "読み込みに失敗しました。")?>");
              showMessage(4, "");
              return;
            }
            weiboIndex = 0;
            weiboMsg = result['messages'];
            weiboMessages = result['messages'];
            weiboCount = weiboMsg.length;
            var userDict = {}, uids = [];
            for (var i = 0; i < weiboCount; i++) {
              var uid = weiboMsg[i]['user']['id'];
              uids.push(uid);
              userDict[uid] = {'id':uid, 'screenName':weiboMsg[i]['user']['screenName'], 'fans':{'total':0, 'id':[]}};
            }

            // invoke network drawing
            if (false && weiboCount > 0) {
              showLoading(2);
              ajaxQuery(
                './get.php',
                {
                  url: '/brandinblog/api/1/fans',
                  params: {'uid': uids.join(' '), 'degree':1}
                },
                function (result) {
                  if (result == null) {
                    showMessage(2, "<?=Util::langtext("Failed to load data.", "无法读取数据。", "読み込みに失敗しました。")?>");
                    return;
                  }
                  // {total:1, users:[{uid:2, fans:[3,4,5...]}, ...], info:[{}, ...]}
                  // first add the 'info' into userDict
                  if ('info' in result) {
                    for (var idx = 0; idx < result['info'].length; idx++) {
                      var user = result['info'][idx];
                      userDict[user['uid']] = {'id':user['uid'], 'screenName':user['nick'], 'fans':{'total':user['attmenum'], 'id':[]}};
                    }
                  }
                  // then add the fans into the userDict
                  if ('users' in result) {
                    for (var idx = 0; idx < result['users'].length; idx++) {
                      var user = result['users'][idx];
                      userDict[user['uid']]['fans']['total'] = user['fans']['total']
                      userDict[user['uid']]['fans']['id'] = user['fans']['uid']
                    }
                  }
                  // finally convert userDict to a list of users
                  var users = [];
                  for (var uid in userDict) {
                    users.push(userDict[uid]);
                  }
                  drawNetwork(users);
                }
              );
            }

            // update the message block
            var html = "";
            html += "<ul class='pager'><li id='previous_li' class='previous disabled'><a href='#' onclick='showWeibo(-1);'>&lt;</a></li><li><span id='batch_span'>1 / 100</span></li><li id='next_li' class='next" + (weiboCount <= 1 ? " disabled" : "") + "'><a href='#' onclick='showWeibo(1);'>&gt;</a></li></ul>";
            html += "<div id='weibo_div'></div>";
            $I("message_div").innerHTML = html;

            if (weiboCount > 0) showWeibo(0);

            function getwcs(field) {
                var wordcs = []; 
                for (var i = 0; i < weiboCount; ++i) {
                    var ws = weiboMsg[i][field].trim().split(/(\s+)/);
                    ws.map(function(d) {
                        if (!(d in wordcs))
                            wordcs[d] = 0;
                        wordcs[d] = wordcs[d] + 1;
                    })
                }
                textsize = []
                for (var key in wordcs) {
                    textsize.push([key, 5 * Math.sqrt(5 * wordcs[key])])
//                    textsize.push({text:key,size:5 * wordcs[key]})
                }
                return textsize
            }
            textsizen = getwcs("negative_words");
            textsizep = getwcs("positive_words");
            console.log(textsizen.length);
            // draw the wordle
            showMessage(4, "");
            var wordleList = [];
            switch (tabType) {
            case 0:
              wordleList = [{"color":"#003F00"}];
              break;
            case 1:
              wordleList = [{"color":"#FF0000"}, {"color":"#0000FF"}];
              break;
            case 2:
              wordleList = [{"color":"<?=implode("\"}, {\"color\":\"", Util::$EMOTIONCOLORS)?>"}];
              wordleList[0]['color'] = "#7F7F2A";
              break;
            }
            for (var j = 0; j < wordleList.length; j++) {
              wordleList[j]['weight'] = 0;
              wordleList[j]['text'] = "";
              wordleList[j]['count'] = 0;
            }
            for (var i = 0; i < weiboCount; i++) {
              var w = weiboMsg[i];
              var negwords = htmlDecode(w['negative_words']) + " ";
              if (tabType == 0) { // popularity
                wordleList[0]['text'] += negwords; //htmlDecode(w['plainContent']) + " ";
                w = w['originalMessage'];
                if (w != null) {
                  wordleList[0]['text'] += negwords; //htmlDecode(w['plainContent']) + " ";
                }
              } else if (tabType == 1) { // sentiment
                var p = w['sentimentPolarity'];
                if (p >= 0) {
                  wordleList[0]['weight'] += w['sentimentDifferenceAbs'];
                  wordleList[0]['count']++;
                  wordleList[0]['text'] += negwords; //htmlDecode(w['plainContent']) + " ";
                }
                if (p <= 0) {
                  wordleList[1]['weight'] += w['sentimentDifferenceAbs'];
                  wordleList[1]['count']++;
                  wordleList[1]['text'] += negwords;  //htmlDecode(w['plainContent']) + " ";
                }
                w = w['originalMessage'];
                if (w != null) {
                  p = w['sentimentPolarity'];
                  if (p >= 0) {
                    wordleList[0]['weight'] += w['sentimentDifferenceAbs'];
                    wordleList[0]['count']++;
                    wordleList[0]['text'] += negwords;//htmlDecode(w['plainContent']) + " ";
                  }
                  if (p <= 0) {
                    wordleList[1]['weight'] += w['sentimentDifferenceAbs'];
                    wordleList[1]['count']++;
                    wordleList[1]['text'] += negwords;//htmlDecode(w['plainContent']) + " ";
                  }
                }
              } else if (tabType == 2) { // emotion
                var d = w['maxEmotionTypePFDAH'];
                var j = 0;
                while (d > 0) {
                  var dd = d % 2;
                  if (dd > 0) {
                    wordleList[j]['weight'] += w['maxEmotionScore'];
                    wordleList[j]['count']++;
                    wordleList[j]['text'] += htmlDecode(w['plainContent']) + " ";
                  }
                  d = Math.floor(d / 2);
                  j++;
                }
                w = w['originalMessage'];
                if (w != null) {
                  d = w['maxEmotionTypePFDAH'];
                  j = 0;
                  while (d > 0) {
                    var dd = d % 2;
                    if (dd > 0) {
                      wordleList[j]['weight'] += w['maxEmotionScore'];
                      wordleList[j]['count']++;
                      wordleList[j]['text'] += htmlDecode(w['plainContent']) + " ";
                    }
                    d = Math.floor(d / 2);
                    j++;
                  }
                }
              }
            }
            if (tabType == 0)
              wordleList[0]['weight'] = 1;
            else {
              for (var j = 0; j < wordleList.length; j++) {
                if (wordleList[j]['count'] > 0) wordleList[j]['weight'] /= wordleList[j]['count'];
              }
            }
            function drawcloud(textsize, tagid, idx) {
                var fill = d3.scale.category20();
                d3.layout.cloud().size([300, 300])
                .words(textsize)
                .rotate(function() { return 0;})//~~(Math.random() * 2) * 90; })
                .font("Impact")
                .fontSize(function(d) { return d.size; })
                .on("end", draw)
                .start();

                function draw(words) {
                    d3.select(tagid).append("svg")
                    .attr("width", 300)
                    .attr("height", 300)
                    .append("g")
                    .attr("transform", "translate(150,150)")
                    .selectAll("text")
                    .data(words)
                    .enter().append("text")
                    .style("font-size", function(d) { return d.size + "px"; })
                    .style("font-family", "Impact")
                    .style("fill", function(d, i) { return fill(i); })
                    .attr("text-anchor", "middle")
                    .attr("transform", function(d) {
                        return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
                    })
                    .text(function(d) { return d.text; });
                }
            }
//            drawcloud(textsizen, "#cloudcontainer", 1);
            console.log(WordCloud.isSupported);
            WordCloud(document.getElementById('cloudcontainer'), { list: textsizen, minRotation : 0, maxRotation : 0, color: '#000' } );
            WordCloud(document.getElementById('cloudcontainerp'), { list: textsizep, minRotation : 0, maxRotation : 0, color: '#000' } );
//            drawcloud(textsizep, "#cloudcontainerp", 0);
          }
        );
      };

      var showWeibo = function(idxDelta) {
        if (weiboIndex == 0 && idxDelta < 0) return;
        if (weiboIndex == weiboCount - 1 && idxDelta > 0) return;
        weiboIndex += idxDelta;
        var msg = weiboMsg[weiboIndex];
        var user = msg['user'];
        var html = "";
        html += "<table><tr>";
        html += "<td style='width:50px;vertical-align:top;'><a href='" + user["profileURL"] + "' target='_blank'>" + (user["avatarURL"] != null ? "<img src='" + user["avatarURL"] + "'/>" : "") + "</a></td>";
        html += "<td><a href='" + user["profileURL"] + "' target='_blank'><strong>" + user["screenName"] + "</strong></a><br/>";
        html += msg["htmlContent"] + "<br/>";
        if (msg['imageURLs'] != null && msg['imageURLs'].length > 0) {
          for (var idx = 0; idx < msg['imageURLs'].length; idx++) {
            html += "<img src='" + msg['imageURLs'][idx] + "' />";
          }
          html += "<br/>";
        }
        if (msg['originalMessage'] != null) {
          var origMsg = msg['originalMessage'];
          if (origMsg['postTime'] != null) { // not deleted
            var origUser = origMsg['user'];
            html += "<pre>\n<a href='" + origUser['profileURL'] + "' target='_blank'><strong>" + origUser['screenName'] + "</strong></a>\n";
            html += origMsg['htmlContent'] + "\n";
            if (origMsg['imageURLs'] != null && origMsg['imageURLs'].length > 0) {
              for (var idx = 0; idx < origMsg['imageURLs'].length; idx++) {
                html += "<img src='" + origMsg['imageURLs'][idx] + "' />\n";
              }
            }
            html += "<a href='" + origMsg["url"] + "' target='_blank'>" + formatDate(origMsg["postTime"]) + "</a> <?=Util::langtext("from", "来自", "ソース:")?> " + "<a href='" + origMsg['postSource'][1] + "' target='_blank'>" + origMsg['postSource'][0] + "</a> " + (origMsg["repostCount"] > 0 ? '<?=Util::langtext("Repost:", "转发:", "転送:")?>' + origMsg["repostCount"] : "") + " " + (origMsg["commentCount"] > 0 ? '<?=Util::langtext("Comment:", "评论:", "コメント:")?>' + origMsg["commentCount"] : "") + "</pre>";
          }
        }
//        html += "<a href='" + msg["url"] + "' target='_blank'>" + formatDate(msg["postTime"]) + "</a> <?=Util::langtext("from", "来自", "ソース:")?> " + "<a href='" + msg['postSource'][1] + "' target='_blank'>" + msg['postSource'][0] + "</a> " + (msg["repostCount"] > 0 ? '<?=Util::langtext("Repost:", "转发:", "転送:")?>' + msg["repostCount"] : "") + " " + (msg["commentCount"] > 0 ? '<?=Util::langtext("Comment:", "评论:", "コメント:")?>' + msg["commentCount"] : "");

        html += "</tr></table>";
        $I("weibo_div").innerHTML = html;
        if (weiboIndex == 0) 
          $('#previous_li').addClass('disabled');
        else
          $('#previous_li').removeClass('disabled');
        if (weiboIndex >= weiboCount - 1)
          $('#next_li').addClass('disabled');
        else
          $('#next_li').removeClass('disabled');
        $I('batch_span').innerHTML = (weiboIndex + 1) + " / " + weiboCount;
      };

      var drawTrends = function(seriesData, begin, end) {
        var colors, names;
        switch (tabType) {
        case 0:
          colors = ['blue'];
          names = ['Count'];
          break;
        case 1:
          colors = ['red', 'blue'];
          names = ['Positive', 'Negative'];
          break;
        case 2:
          colors = ['<?=implode("', '", Util::$EMOTIONCOLORS)?>'];
          names = ['Happiness', 'Anger', 'Sadness', 'Fear', 'Surprise'];
          break;
        }

        var series = [];
        for (var key in seriesData) {
          series.push({
            type: 'line',
            name: names[key],
            color: colors[key],
            pointStart: begin,
            pointInterval: 86400000,
            data: seriesData[key]
          });
        }

        new Highcharts.Chart({
          chart: {
            marginBottom: 20,
            renderTo: 'trend_div',
            reflow: false,
            marginLeft: 35,
            marginRight: 15,
          },
          credits: {
            enabled: false
          },
          title: {
            text: ''
          },
          subtitle: {
            text: ''
          },
          xAxis: {
            type: 'datetime'
          },
          yAxis: {
            title: {
              text: null
            },
            min: 0
          },
          tooltip: {
            formatter: function() {
              var d = new Date(this.x);
              var s = d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate() + ":<br/>";
              for (var i = 0; i < this.points.length; i++) {
                s += this.points[i].series.name + ' ' + Math.round(this.points[i].y * 10000) / 10000 + " ";
                if (i % 2 == 1) s += "<br/>";
              }
              return s;
            },
            shared: true
          },
          legend: {
            enabled: false
          },
          plotOptions: {
            series: {
              marker: {
                enabled: false,
                states: {
                  hover: {
                    enabled: true,
                    radius: 3
                  }
                }
              }
            }
          },
          series: series,
          exporting: {
            enabled: false
          }
        }); // new Highcharts.Chart
      };

      var drawNetwork = function(users) {
        var graph = new Graph();
        var nodesDict = {} // key: id, value: graphNode and fans info
        for (var i = 0; i < users.length; i++) {
          nodesDict[users[i]['id']] = {'info':users[i], 'node':graph.newNode({label:'@' + users[i]['screenName']})};
        }
        for (var uid in nodesDict) {
          if (nodesDict[uid]['info']['fans']['id'].length > 0) {
            for (var i = 0; i < nodesDict[uid]['info']['fans']['id'].length; i++) {
              var celebrity = nodesDict[uid]['node'];
              var fanid = nodesDict[uid]['info']['fans']['id'][i];
              if (fanid in nodesDict) {
                var fan = nodesDict[fanid]['node'];
                graph.newEdge(fan, celebrity, {color:'black', directional:true, weight:1});
              }
            }
          }
        }
        $I("network_canvas_span").innerHTML = '<canvas id="network_canvas" width="360px" height="360px"><?=Util::langtext("Your browser does not support the HTML5 canvas tag.", "您的浏览器不支持HTML5的canvas标签。", "図形を表示するには、canvasタグをサポートしたブラウザが必要です。")?></canvas>';
        var springy = $('#network_canvas').springy({
          graph: graph
        });
      };

      var submitQuery = function(bitmask, args) {
        var f = document['paramsForm'];
        var begintime = new Date(f['begin-year'].value, f['begin-month'].value - 1, f['begin-date'].value, 0, 0, 0, 0),
          endtime = new Date(f['end-year'].value, f['end-month'].value - 1, f['end-date'].value, 23, 59, 59, 999);
        var brands = [];
        for (var i = 0; i < f['keywords'].length; i++) {
          if (f['keywords'][i].checked) {
            brands.push(f['keywords'][i].value);
          }
        }
        var region = null;
        if (args != null && 'region' in args) {
          region = args['region'];
        }
        if ((bitmask & 1) > 0)
          loadCount(begintime.getTime(), endtime.getTime(), region, brands.join(' '), tabType);
        if ((bitmask & 2) > 0)
          loadTrend(begintime.getTime(), endtime.getTime(), region, brands.join(' '), tabType);
        if ((bitmask & 4) > 0)
          // loadNetwork(begintime.getTime(), endtime.getTime(), region, brands.join(' '), tabType);
        if ((bitmask & 8) > 0) {
          var score = -1;
          if (args != null && 'score' in args && tabType > 0) {
            score = args['score'];
            // score = Math.ceil(score / 2);
            score = score / 2;
          }
          loadMessage(begintime.getTime(), endtime.getTime(), region, brands.join(' '), tabType, score, 100, 0);
        }
      };

      var loadTab = function(tab) {
        tabType = tab;
        for (var i = 0; i < 3; i++) {
          if (i == tabType) {
            $('#tab' + i).addClass('active');
            $D('note' + i, true);
          } else {
            $('#tab' + i).removeClass('active');
            $D('note' + i, false);
          }
        }
        submitQuery(15);
      };

    </script>
  </head>

  <body onload="init();">

    <div class="container">
      <div class="row">
        <div class="span8">
          <h3><?=Util::langtext("BrandinBlog", "博然般若", "ブランディンブログ")?></h3>
          <h4 class="muted"><?=Util::langtext("Analytics of Corporate Reputation in Microblog Messages", "企业微博口碑分析", "企業マイクロブログ評判分析")?></h4>
        </div>
        <div class="span4">
          <div class="btn-group pull-right">
            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
              <?=Util::langtext("Language", "语言", "言語")?> <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="?lang=en<?= $forceSVG ? "&svg=1" : "" ?><?= !$drawNetwork ? "&network=0" : "" ?>">English</a></li>
              <li><a href="?lang=zh<?= $forceSVG ? "&svg=1" : "" ?><?= !$drawNetwork ? "&network=0" : "" ?>">中文</a></li>
            </ul>
          </div>
          <a class="btn btn-link pull-right" href="#" onclick="$D('about', true);"><?=Util::langtext("About", "关于", "このサイトについて")?></a>
        </div>
    </div> <!-- container -->

    <div id="about" style="position:fixed; z-index:99; width:754px; height:423px; margin-left:23px; background:url('img/brandenburggate.jpg'); display:none;">
      <div style="width:714px; height:383px; margin:20px 20px; background-color:#ffffff; opacity:0.7;filter:alpha(opacity=70);">
        <div style="padding:10px 10px; height: 363px;">
          <button class="close" onclick="$D('about', false);">&times;</button>
          <h4>BrandinBlog 博然般若</h4>
          <p>
            <em>BrandinBlog</em> (<em>brand-in-blog</em>) is an analytical system of corporate reputation in microblog (Weibo) messages.  
            The system tracks the real-time regional messages relevant to some commercial brands and presents the popularity, sentiments and moods of the messages.
            The analysis is based on a sentiment-lexicon constructed from a big microblog corpus.
          </p>
          <p>
            <em>博然般若</em>是一个企业微博口碑分析系统。该系统自动跟踪微博客中与特定商品品牌相关的微博，并展现不同区域或时段的微博流行程度及其体现的情感与情绪。
            情感分析方法基于海量微博客语料自动构建的情感词典。<br/>
            系统名称中“博然”谐音“勃然”，体现情绪引发的变化；“博”也指“微博”，对应微博分析。“般若”（音“波惹”）为佛教术语“智慧”之义，指运用人的智慧、计算机的智能进行分析。
          </p>
          <br/>
          <br/>
          <br/>
          <br/>
          <p>
            The system is developed by the <a href="http://www.thuir.org/" target="_blank">Information Retrieval Group</a> of State Key Laboratory of Intelligent Technology and Systems, Tsinghua University.
            <br/>
            本系统由清华大学智能技术与系统国家重点实验室<a href="http://www.thuir.org/" target="_blank">信息检索组</a>开发完成。
          </p>
        </div>
        <span class="pull-right"><small>Photo by <a style="color:#333333;" href="https://plus.google.com/u/0/photos/107347245500336837496/albums/5752164307399733681/5752164449846380866" target="_blank">Wei Yu</a></small></span>
      </div>
    </div> <!-- about -->

    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span12">
          <ul class="nav nav-tabs">
            <li id="tab0" class="active"><a href="#" onclick="loadTab(0);"><?=Util::langtext("Popularity", "关注程度", "人気")?></a></li>
            <li id="tab1"><a href="#" onclick="loadTab(1);"><?=Util::langtext("Sentiment", "褒贬倾向", "センチメント")?></a></li>
          </ul>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span8">

          <form name="paramsForm" action="" onsubmit="submitQuery(15); return false;">

            <?=Util::langtext("Total", "总数", "合計")?>: <span id='totalcount'>--</span>

            <span class="pull-right">
              <select class="input-small" name="begin-year">
<?php
  foreach (range(2014, date('Y')) as $year) {
?>              <option value="<?=$year?>"<?=$year == date('Y', $begin_timestamp) ? " selected" : ""?>><?=$year . Util::langtext("", "年")?></option>
<?php
  }
?>            </select>
              <select class="input-mini" name="begin-month">
<?php
  foreach (range(1, 12) as $month) {
?>              <option value="<?=$month?>"<?=$month == date('n', $begin_timestamp) ? " selected" : ""?>><?=Util::get_month_name($month)?></option>
<?php
        }
?>            </select>
              <select class="input-mini" name="begin-date">
<?php
  foreach (range(1, 31) as $day) {
?>              <option value="<?=$day?>"<?=$day == date('j', $begin_timestamp) ? " selected" : ""?>><?=Util::get_day_name($day)?></option>
<?php
  }
?>            </select>

            <?=Util::langtext(" to ", " 至 ", " ～ ")?>

              <select class="input-small" name="end-year">
<?php
  foreach (range(2012, date('Y')) as $year) {
?>              <option value="<?=$year?>"<?=$year == date('Y', $end_timestamp) ? " selected" : ""?>><?=$year . Util::langtext("", "年")?></option>
<?php
  }
?>            </select>
              <select class="input-mini" name="end-month">
<?php
  foreach (range(1, 12) as $month) {
?>              <option value="<?=$month?>"<?=$month == date('n', $end_timestamp) ? " selected" : ""?>><?=Util::get_month_name($month)?></option>
<?php
  }
?>            </select>
              <select class="input-mini" name="end-date">
<?php
  foreach (range(1, 31) as $day) {
?>              <option value="<?=$day?>"<?=$day == date('j', $end_timestamp) ? " selected" : ""?>><?=Util::get_day_name($day)?></option>
<?php
  }
?>            </select>

            </span> <!-- date selections -->
            <br/>
            <br/>
<?php
  foreach (Util::$PRODUCTS as $product) {
?>          <label class="checkbox inline">
              <input type="checkbox" name="keywords" value="<?=strtolower($product[0])?>" checked="Checked"><?=Util::langtext($product[0], $product[1], $product[2])?>
            </label>
<?php
  }
?>
            <button type="submit" class="btn pull-right"><?=Util::langtext("Submit", "查询", "検索") ?></button>

          </form>

          <small id="note0" style="display:none;"><?= Util::langtext("", "") ?></small>
          <small id="note1" style="display:none;"><?= Util::langtext("Colors: Blue for negative, red for positive.", "颜色：蓝色表示贬义，红色表示褒义", "青色:ネガティブ, 赤色:ポジティブ" ) ?></small>
          <small id="note2" style="display:none;"><?= Util::langtext("Palette from <i>Plutchik's wheel of emotions</i>", "色彩源自普拉契克的情绪模型", "パレット") . ": " . 
            "<span style='background-color:" . Util::$EMOTIONCOLORS[0] . ";font-weight:bold;'>" . Util::langtext("Happiness", "喜悦", "喜び") . "</span>, " . 
            "<span style='background-color:" . Util::$EMOTIONCOLORS[1] . ";font-weight:bold;'>" . Util::langtext("Anger", "愤怒", "怒り") . "</span>, " . 
            "<span style='background-color:" . Util::$EMOTIONCOLORS[2] . ";font-weight:bold;'>" . Util::langtext("Sadness", "悲哀", "悲しみ") . "</span>, " . 
            "<span style='background-color:" . Util::$EMOTIONCOLORS[3] . ";font-weight:bold;'>" . Util::langtext("Fear", "恐惧", "恐怖") . "</span>, " . 
            "<span style='background-color:" . Util::$EMOTIONCOLORS[4] . ";font-weight:bold;'>" . Util::langtext("Surprise", "惊讶", "驚き") . "</span>, " .
            "<span style='background-color:" . Util::$EMOTIONCOLORGRAY . ";font-weight:bold;'>" . Util::langtext("Mixed", "混合情绪", "混合された感情") . "</span>" ?></small>

          <div id="region_div" style="width: 720; height: 450px; background-image: url('img/emptymap720x450.jpg'); background-repeat:no-repeat; background-position:center top;line-height:450px; text-align:center;">
            <?=Util::langtext("We're sorry, but a browser with <a href='http://caniuse.com/svg' target='_blank'>SVG support</a> is required.", "很抱歉，请您更换支持<a href='http://caniuse.com/svg' target='_blank'>SVG绘图</a>的浏览器。", "図形を表示するには、<a href='http://caniuse.com/svg' target='_blank'>SVGの描画</a>をサポートしたブラウザが必要です。")?>
          </div>

          <small><?=Util::langtext("Map data from Google Chart Tools API. The <i>South China Sea Islands</i> are not shown.", "地图数据源自Google Chart Tools API, 南海诸岛未画出。", "地図データは、Google Chart Tools APIからのものです。南シナ海の島々は表示されません。") ?></small>

        </div> <!-- span8 -->

        <div class="span4">
          <!--<?=Util::langtext("Trends", "趋势", "トレンド")?>:-->
          <div id="trend_div" style="width:360px; height:100px;"></div>
          <div id="message_div" style="width:360px"></div>
          <div id="cloudcontainer" class="wordcloud"></div>
          <div id="cloudcontainerp" class="wordcloud"></div>
<div id="dialog" title="微博">
  <p>This is the default dialog which is useful for displaying information. The dialog window can be moved, resized and closed with the 'x' icon.</p>
</div>
          <span id="network_canvas_span"<?=$drawNetwork ? "" : " style='display:none;'"?>><canvas id="network_canvas" width='360px' height='360px'><?=Util::langtext("Your browser does not support the HTML5 canvas tag.", "您的浏览器不支持HTML5的canvas标签。", "図形を表示するには、canvasタグをサポートしたブラウザが必要です。")?></canvas></span>
        </div> <!-- span4 -->

      </div> <!-- row-fluid -->
    </div> <!-- container-fluid -->

    <hr/>

    <div class="footer">
      <p>
        &copy; 2014 - 2015
        <?=Util::langtext("Information Retrieval Group, State Key Lab of Intell. Tech. & Sys., Tsinghua University", "清华大学智能技术与系统国家重点实验室信息检索组", "清華大学インテリジェントな技術やシステムの国家重点実験室情報検索研究グループ")?> (<a href="http://www.thuir.org/" target="_blank">THUIR</a>)
      </p>
    </div>

  </div>
  <!-- /container -->

  <!-- Le javascript
    ================================================== -->
  <!-- Placed at the end of the document so the pages load faster -->
  <script type='text/javascript' src="bootstrap/js/jquery.js"></script>
  <script type='text/javascript' src="js/highcharts.js"></script>
  <script type='text/javascript' src="bootstrap/js/bootstrap-dropdown.js"></script>
  <script type='text/javascript' src="js/springy.js"></script>
  <script type='text/javascript' src="js/springyui.js"></script>
  <script type='text/javascript' src="js/network.js"></script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-40071215-1', 'caq9.info');
  ga('send', 'pageview');

</script>
</body>
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css">
    <script src="http://code.jquery.com/jquery-1.9.1.js"></script>
    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
</html>
