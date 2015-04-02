# -*- coding: utf-8 -*-
import web
import re, datetime, simplejson, time, random, pytz, calendar
import mymongo, myutil

class api_search:
    def GET(self):
        web.header('Content-Type', 'application/json; charset=utf-8')
        user_data = web.input(begin=None, end=None, region=None, brand=None, count=20, skip=0, fansonly=None, sort=None)

        # handle the parameters
        begin_time = myutil.get_time(user_data.begin) # timestamp, ms, in UTC
        end_time = myutil.get_time(user_data.end)
        regions = myutil.get_regions(user_data.region) # 11+21+32
        brands = myutil.get_brands(user_data.brand) 

        count = user_data.count
        try:
            count = int(count)
            if count > 200:
                count = 200
            if count < 0:
                count = 0
        except:
            count = 20
        skip = user_data.skip
        try:
            skip = int(skip)
            if skip < 0:
                skip = 0
        except:
            skip = 0

        fansonly = False
        if user_data.fansonly is not None and user_data.fansonly == '1':
            fansonly = True

        sortobj = myutil.get_sort(user_data.sort)

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

        sortkey = []
        if sortobj is not None:
            for key in sortobj['query']:
                query[key] = sortobj['query'][key]
            sortkey.append((sortobj['key'][0], sortobj['key'][1]))
        sortkey.append(('postTime', -1))

        # query!
        print 'api_search query:', query, 'sortkey:', sortkey
        resultobj = None
        if True:
            cursor = mymongo.mymongoconn.find('messages', query=query, limit=count, offset=skip, sortkey=sortkey)
            resultobj = {'total': cursor.count(), 'brand': brands[1], 'count': count, 'skip': skip, 'begin': begin_time, 'end': end_time, 'messages': [], 'negative': []}
            for message in cursor:
                for key in message:
                    if type(message[key]) == datetime.datetime:
                        message[key] = calendar.timegm(message[key].timetuple())
                if 'originalMessage' in message:
                    originalId = message['originalMessage']['_id']
                    origMessage = mymongo.mymongoconn.find_one('messages', query=originalId)
                    for key in origMessage:
                        if type(origMessage[key]) == datetime.datetime:
                            origMessage[key] = calendar.timegm(origMessage[key].timetuple())
                    message['originalMessage'] = origMessage
                resultobj['messages'].append(message)
        else: # fansonly, this part of code won't run
            samplecount = 500
            print 'api_search query:', query
            cursor = mymongo.mymongoconn.find('messages', query=query, fields=['user.id', 'user.screenName'])
            print 'api_search count:', cursor.count()
            distinctid = cursor.distinct('user.id')
            resulttotal = len(distinctid)
            print 'api_search distinct count:', resulttotal
            sampled = False
            if resulttotal > samplecount:
                random.shuffle(distinctid)
                distinctid = distinctid[0 : samplecount]
                sampled = True
                print 'api_search random sampled ' + str(samplecount) + ' id'
            cursor1 = mymongo.mymongoconn212.find('fans', query={'_id':{'$in':distinctid}})
            print 'api_search fans id count:', cursor1.count()
            fansdict = {} # key: id, value: set of fans
            fansidset = set([])
            for fan in cursor1:
                fansdict[fan['_id']] = set(fan['fans'])
                fansidset.add(fan['_id'])
            
            userdict = {} # key: id, value: {id, screenName, fans}
            isolateidset = set([])
            for message in cursor:
                if ('id' not in message['user']) or ('screenName' not in message['user']):
                    continue
                uid = message['user']['id']
                if not uid in fansidset:
                    continue
                if uid in isolateidset: # already added
                    continue
                intersectid = list(fansdict[uid] & fansidset)
                userdict[uid] = {'id':uid, 'screenName':message['user']['screenName'], 'fans':{'total':len(fansdict[uid]), 'id':intersectid}}
                isolateidset.add(uid)
            print 'api_search userdict count:', len(userdict), len(isolateidset)

            # now check isolated users
            for uid in userdict:
                if len(userdict[uid]['fans']['id']) <= 0: # maybe isolated
                    continue
                if len(isolateidset) <= 0: # no isolate uid any more
                    break
                if uid in isolateidset:
                    isolateidset.remove(uid)
                    # print 'removed uid', uid
                for fansid in userdict[uid]['fans']['id']:
                    if fansid in isolateidset:
                        isolateidset.remove(fansid)
                        # print 'removed', fansid
            print 'api_search isolatedidset count:', len(isolateidset)

            # now add the users to the result
            resultobj = {'total':resulttotal, 'user':[], 'sampled':sampled}
            for uid in userdict:
                if uid in isolateidset:
                    continue
                # print userdict[uid]
                resultobj['user'].append(userdict[uid])

        return simplejson.dumps(resultobj)

    def POST(self):
        return self.GET()
