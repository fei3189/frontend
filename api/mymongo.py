# -*- coding: utf-8 -*-
'''
My Mongodb connection
'''
import pymongo

class MyMongoConn:
    mgconn = None
    db = None

    # connection ip and port, database name
    def __init__(self, ip='192.168.56.6', port=27027, dbname='textimage'):
        self.mgconn = pymongo.Connection(ip, port)
        self.db = self.mgconn[dbname]

    # save a record to the collection
    def save(self, coll, obj):
        return self.db[coll].save(obj)

    # query operations
    def find(self, coll, query=None, fields=None, limit=0, offset=0, sortkey=[]):
        if limit == 1:
            return self.find_one(coll, query=query, fields=fields)
        result = self.db[coll].find(spec=query, fields=fields, limit=limit).skip(offset)
        if len(sortkey) <= 0:
            return result
        else:
            return result.sort(sortkey)

    def find_one(self, coll, query=None, fields=None):
        return self.db[coll].find_one(spec_or_id=query, fields=fields)

    # group by operations
    def group(self, coll, key, condition, initial={'num':0}, reduce='function(doc, prev) {prev.num++}', finalize=None):
        return self.db[coll].group(key=key, condition=condition, initial=initial, reduce=reduce, finalize=finalize)

    def inline_mapreduce(self, coll, map, reduce, full_response=False, query=None):
        return self.db[coll].inline_map_reduce(map=map, reduce=reduce, full_response=full_response, query=query)

    # close connection
    def close(self):
        self.mgconn.close() # alias of disconnect()


mymongoconn = MyMongoConn(ip='10.0.17.154', port=27017)
mymongoconn212 = MyMongoConn(ip='10.0.17.154', port=27017, dbname='textimage')
