# -*- coding: utf-8 -*-
import web
import re, datetime, simplejson, time
import mymongo, myutil

class api_user:
    def GET(self):
        web.header('Content-Type', 'application/json; charset=utf-8')
        user_data = web.input(uid=None, fans=0)
        if user_data.uid == None:
            return web.webapi.BadRequest()

        # handle the parameters
        uids = []
        try:
            uids = re.split('\\+|\\s', user_data.uid)
        except:
            pass
        if len(uids) <= 0:
            return web.webapi.BadRequest()

        querylist = []
        for uid in uids:
            querylist.append({'_id':long(uid)})

        fans = False
        if user_data.fans is not None and user_data.fans == '1':
            fans = True

        # query!
        cursor = mymongo.mymongoconn212.find('users', query={'$or':querylist})
        resultobj = {'total': cursor.count(), 'users': []}
        for user in cursor:
            for key in user:
                if type(user[key]) == datetime.datetime:
                    user[key] = time.mktime(user[key].timetuple())
            if False:
                obj = mymongo.mymongoconn212.find_one('fans', query={'_id':user['_id']})
                if obj is not None:
                    user['fans'] = obj
            resultobj['users'].append(user)
        return simplejson.dumps(resultobj)

    def POST(self):
        return self.GET()
