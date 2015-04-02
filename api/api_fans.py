# -*- coding: utf-8 -*-
import web
import re, simplejson
import mymongo, myutil

class api_fans:
    def GET(self):
        web.header('Content-Type', 'application/json; charset=utf-8')
        user_data = web.input(uid=None, degree=0)
        if user_data.uid == None:
            return web.webapi.BadRequest()

        # handle the parameters
        uids = []
        try:
            uids = re.split('\\++|\\s+', user_data.uid.strip())
        except:
            pass

        querylist = []
        for uid in uids:
            try:
                querylist.append({'_id':long(uid)})
            except:
                pass
        uids = [long(uid) for uid in uids]
        if len(uids) <= 0:
            return web.webapi.BadRequest()

        degree = 0
        try:
            degree = int(user_data.degree)
        except:
            pass
        if degree < 0:
            degree = 0
        if degree > 1:
            degree = 1

        cursor = mymongo.mymongoconn212.find('fans', query={'$or':querylist})
        # we return the fans with only the ids occurred in the querylist
        resultobj = {'total': cursor.count(), 'users': []}
        uidIntersect = None
        uidIntersectStart = 0
        userIdx = 0
        newUids = []
        for user in cursor:
            obj = {'uid': user['_id'], \
                   'fans': {'total': len(user['fans']), 'uid':[]} \
                  }
            if degree == 1:
                currentSet = set(user['fans'])
                if uidIntersect is None:
                    uidIntersect = currentSet
                else:
                    intersect = uidIntersect & currentSet
                    if len(intersect) <= 0:
                        idx = uidIntersectStart
                        while idx < len(resultobj['users']):
                            user = resultobj['users'][idx]
                            added = 0
                            for intersectUid in uidIntersect:
                                if (intersectUid not in uids) and (intersectUid not in newUids):
                                    newUids.append(intersectUid)
                                if intersectUid not in user['fans']['uid']:
                                    user['fans']['uid'].append(intersectUid)
                                    added += 1
                                if added >= 10:
                                    break
                            resultobj['users'][idx] = user
                            idx += 1
                        uidIntersect = None
                        uidIntersectStart = userIdx + 1
                    else:
                        uidIntersect = intersect
            for fanid in user['fans']:
                if fanid in uids:
                    obj['fans']['uid'].append(fanid)
            resultobj['users'].append(obj)
            userIdx += 1

        if degree == 1 and len(newUids) > 0:
            resultobj['info'] = []
            querylist = []
            for newUid in newUids:
                querylist.append({'_id':newUid})
            cursor = mymongo.mymongoconn212.find('users', query={'$or':querylist})
            for user in cursor:
                resultobj['info'].append(user)

        return simplejson.dumps(resultobj)

    def POST(self):
        return self.GET()
