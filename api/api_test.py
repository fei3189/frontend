# -*- coding: utf-8 -*-
import web
import random, datetime

class test_hello:
    def GET(self):
        web.header('Content-Type', 'text/plain; charset=utf-8')
        user_data = web.input(lang='')
        if user_data.lang == 'zh-cn':
            return '世界!'
        elif user_data.lang == 'hi-in':
            return 'दुनिया!'
        elif user_data.lang == 'ms-my':
            return 'dunia!'
        return 'world!'
    def POST(self):
        return self.GET()

class test_random:
    def GET(self):
        web.header('Content-Type', 'text/plain; charset=us-ascii')
        return random.random()
    def POST(self):
        return self.GET()

class test_datestr:
    def GET(self, name):
        web.header('Content-Type', 'text/plain; charset=utf-8')
        return name + ':' + str(datetime.date.today())
    def POST(self, name):
        return self.GET(name)
