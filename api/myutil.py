# -*- coding: utf-8 -*-

import datetime, calendar, re

# convert a string '20121101' to python's datetime.date(2012, 11, 1)
# second_offset=0: 0h0m0s, second_offset=86399: 23h59m59s
def get_datetime(s, second_offset):
    if s is None:
        return None
    year = None
    month = None
    date = None
    try:
        year = int(s[0 : 4])
        month = int(s[4 : 6])
        day = int(s[6 : 8])
    except:
        pass
    today = datetime.date.today()
    if year is None or year < 2010 or year > today.year:
        year = today.year
    if month is None or month < 1 or month > 12:
        month = today.month
    if day is None:
        day = today.day
    elif day < 1:
        day = 1
    elif day > calendar.monthrange(year, month)[1]:
        day = calendar.monthrange(year, month)[1]
    return datetime.datetime(year, month, day, 0, 0, 0) + datetime.timedelta(seconds=second_offset)


def get_time(s):
    if s is None:
        return None
    t = 0
    try:
        t = long(s)
        if t < 0:
            t = 0
    except:
        pass
    return t


REGIONS = [11, 12, 13, 14, 15, 21, 22, 23, 31, 32, 33, 34, 35, 36, 37, 41, 42, 43, 44, 45, 46, 50, 51, 52, 53, 54, 61, 62, 63, 64, 65, 71, 81, 82, 111, 112, 113, 114, 115, 116, 117, 118, 119, 1111, 1112, 1113, 1114, 1115, 1116, 1117, 1128, 1129]
# check if the region code (13+1112+21) is valid, returns the array of integer codes
def get_regions(s):
    if s is None or len(s) == 0:
        return None
    regions = []
    try:
        for code in re.split('\\+|\\s', s):
            if int(code) in REGIONS:
                regions.append(code)
    except:
        pass
    return regions

# check if the brand name is valid
# returns an array of [brand queries, brand keys]
#BRANDS = {'toshiba':['toshiba', '"东芝"'], 'fujitsu':['fujitsu', '"富士通"'], 'sony':['sony', '"索尼"'], 'nec':['nec', '"日本电气"'], 'hitachi':['hitachi', '"日立"'], 'panasonic':['panasonic', '松下'], 'samsung':['三星', 'galaxy']}
BRANDS = {'samsung':['三星', 'galaxy', 'samsung', 'note'], 'xiaomi':['小米4', '小米note', '红米'], 'huawei':['mate 7', '荣耀6'], 'apple':['iphone 6', '6 plus', 'apple watch']}
def get_brands(s):
    if s is None or len(s) == 0:
        s = '+'.join(BRANDS.keys())
    s = s.lower()
    brands = []
    keys = []
    try:
        for brand in re.split('\\+|\\s', s):
            if not BRANDS.has_key(brand):
                continue
            keys.append(brand)
            for query in BRANDS[brand]:
                brands.append(query)
    except:
        pass
    if len(brands) == 0:
        return get_brands(None)
    return [brands, keys]

# valid sort filter:
# sentiment:[positive|negative]:[asc|desc]
# emotion:<1..31>:[asc|desc]
def get_sort(sortstr):
    if sortstr is None or len(sortstr) == 0:
        return None
    sortkey = None
    sortorder = 1 # 1 for ascending, -1 for descending
    fields = sortstr.split(':')
    if len(fields) >= 3:
        sortcategory = fields[0]
        sortvalue = fields[1]
        sortorder = fields[2]
    elif len(fields) == 2:
        sortcategory = fields[0]
        sortvalue = fields[1]
        sortorder = 'desc'
    else:
        sortcategory = fields[0]
        sortvalue = 'sentiment'
        sortorder = 'desc'
    if not sortcategory in ['sentiment', 'emotion']:
        return None
    if sortcategory == 'sentiment':
        if sortvalue is None or len(sortvalue) <= 0 or sortvalue not in ['positive', 'negative', 'neutral']:
            sortvalue = 'positive'
    else: # sortcategory == 'emotion'
        sortval = 1
        try:
            sortval = int(sortvalue)
        except:
            pass
        if sortval <= 0:
            sortval = 1
        if sortval >= 32:
            sortval = 31
    if sortorder not in ['asc', 'desc']:
        sortorder = 'desc'

    if sortcategory == 'sentiment':
        sortcat = 'sentimentDifferentAbs'
        if sortvalue == 'positive':
            sortquery = {'sentimentPolarity': 1}
        elif sortvalue == 'negative':
            sortquery = {'sentimentPolarity': -1}
        else: # neutral
            sortquery = {'sentimentPolarity': 0}
    else:
        sortcat = 'maxEmotionDifference'
        sortquery = {'maxEmotionTypePFDAH': sortval}

    if sortorder == 'asc':
        sortord = 1
    else:
        sortord = -1

    sortobj = {'query':sortquery, 'key':[sortcat, sortord]}
    return sortobj
