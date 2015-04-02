# -*- coding: utf-8 -*-
import web
import re, datetime, simplejson, time
import mymongo, myutil
import pytz

class api_count:
    def GET(self):
        web.header('Content-Type', 'application/json; charset=utf-8')
        user_data = web.input(begin=None, end=None, region=None, brand=None, score=0)
        print 'api_count user_data', user_data

        # handle the parameters

        begin_time = myutil.get_time(user_data.begin) # timestamp, ms, in GMT
        end_time = myutil.get_time(user_data.end)
        regions = myutil.get_regions(user_data.region) # 11+21+32
        brands = myutil.get_brands(user_data.brand) # toshiba+fujitsu+sony+nec
        score = user_data.score
        if score not in ['0', '1', '2']:
            score = '0'
        score = int(score)

        # refine the parameters to generate mongodb queries

        query = {}
        if begin_time is not None:
            query['postTime'] = {'$gte': datetime.datetime.fromtimestamp(begin_time / 1000, pytz.utc)}
        if end_time is not None:
            if 'postTime' in query:
                query['postTime']['$lte'] = datetime.datetime.fromtimestamp(end_time / 1000, pytz.utc)
            else:
                query['postTime'] = {'$lte': datetime.datetime.fromtimestamp(end_time / 1000, pytz.utc)}
        if regions is not None:
            query['region'] = re.compile('^' + '(' + ')|('.join(regions) + ')')
        brandlist = []
        for brand in brands[0]:
            brandlist.append({'keywords.' + brand: {'$exists': True}})
        if len(brandlist) > 0:
            query['$or'] = brandlist

        # group operation's paramters

        minTime = time.mktime(datetime.datetime.now().timetuple()) * 1000
        maxTime = -1
        initial = {'count':0, 'minTime': minTime, 'maxTime': maxTime}
        key = 'region'

        reducefunc = "function (doc, prev) { prev.count++; " \
            + "if (doc.postTime != null && doc.postTime.getTime() < prev.minTime) prev.minTime = doc.postTime.getTime(); " \
            + "if (doc.postTime != null && doc.postTime.getTime() > prev.maxTime) prev.maxTime = doc.postTime.getTime(); "
        if score == 1:
            initial['pos'] = 0
            initial['neg'] = 0
            reducefunc += "if (doc.positive != null) prev.pos += doc.positive; " \
                + "if (doc.negative != null) prev.neg += doc.negative; "
        elif score == 2:
            initial['happiness'] = 0
            initial['anger'] = 0
            initial['sadness'] = 0
            initial['fear'] = 0
            initial['surprise'] = 0
            reducefunc += "if (doc.happiness != null) prev.happiness += doc.happiness; " \
                + "if (doc.anger != null) prev.anger += doc.anger; " \
                + "if (doc.sadness != null) prev.sadness += doc.sadness; " \
                + "if (doc.fear != null) prev.fear += doc.fear; " \
                + "if (doc.surprise != null) prev.surprise += doc.surprise; "

        reducefunc += "}"

        # query!
        print 'api_count begin query:', query
        results = mymongo.mymongoconn.group('messages', {key: True}, query, initial=initial, reduce=reducefunc)
        # [{"minTime": ..., "maxTime": ..., "region": xxx, "count": ...}, {...}, ..., ]
        print 'api_count generating response...'

        # refine the results
        resultobj = {'groups': [], 'region': [], 'brand': brands[1], 'score': score}
        total = 0
        sentimentabs = 0
        for result in results:
            if result['minTime'] < minTime and result['minTime'] > 0:
                minTime = result['minTime']
            if result['maxTime'] > maxTime:
                maxTime = result['maxTime']
            result.pop('minTime')
            result.pop('maxTime')
            if score == 1:
                if result['count'] > 0:
                    result['sentiment'] = (result['pos'] - result['neg']) / result['count']
                else:
                    result['sentiment'] = 0
                if abs(result['sentiment']) > sentimentabs:
                    sentimentabs = abs(result['sentiment'])
                result.pop('pos')
                result.pop('neg')
            elif score == 2:
                for key in result:
                    if key != 'count' and type(result[key]) == float:
                        if result['count'] > 0:
                            result[key] = result[key] / result['count']
                        else:
                            result[key] = 0
            resultobj['groups'].append(result)
            resultobj['region'].append(result['region'])
            total += result['count']
        resultobj['total'] = total
        resultobj['begin'] = minTime
        resultobj['end'] = maxTime
        if score == 1:
            resultobj['maxSentimentAbs'] = sentimentabs

        return simplejson.dumps(resultobj)
    def POST(self):
        return self.GET()
