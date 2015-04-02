# -*- coding: utf-8 -*-

import web
from api_test import *
from api_count import *
from api_trend import *
from api_search import *
from api_negative import *
from api_user import *
from api_fans import *

web.config.debug = False

product_name = 'brandinblog'
version = 1
apiroot = '/' + product_name + '/api/' + str(version) + '/'

urls = (
    '/test/hello', 'test_hello',
    '/test/random', 'test_random',
    '/test/date/(.+)', 'test_datestr',
    apiroot + 'count', 'api_count',
    apiroot + 'trend', 'api_trend',
    apiroot + 'search', 'api_search',
    apiroot + 'user', 'api_user',
    apiroot + 'fans', 'api_fans',
    apiroot + 'negative', 'api_negative'
)

if __name__ == "__main__":
    app = web.application(urls, globals())
    app.run()
