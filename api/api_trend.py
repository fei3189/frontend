# -*- coding: utf-8 -*-
import web
import re, datetime, simplejson, time, pytz, math
import mymongo, myutil

class api_trend:
    def GET(self):
        web.header('Content-Type', 'application/json; charset=utf-8')
        user_data = web.input(begin=None, end=None, region=None, brand=None, score=0, probability=0)
        print 'api_trend user_data:', user_data

        # handle the parameters
        begin_time = myutil.get_time(user_data.begin) # timestamp, ms, in UTC
        end_time = myutil.get_time(user_data.end)
        regions = myutil.get_regions(user_data.region) # 11+21+32
        brands = myutil.get_brands(user_data.brand) # toshiba+fujitsu+sony+nec
        score = user_data.score
        probability = user_data.probability
        if score not in ['0', '1', '2']:
            score = '0'
        score = int(score)
        if probability not in ['0', '1']:
            probability = '0'
        probability = int(probability)

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
            pattern = '^' + '(' + ')|('.join(regions) + ')'
            query['region'] = re.compile(pattern)
        brandlist = []
        for brand in brands[0]:
            brandlist.append({'keywords.' + brand: {'$exists': True}})
        if len(brandlist) > 0:
            query['$or'] = brandlist

        # group operation's paramters
        emit = "{count: 1"
        if score == 1:
            emit += ", pos:this.positive, neg:this.negative"
        elif score == 2:
            emit += ", " + "happiness: this.happiness, " \
                + "anger: this.anger, " + "sadness: this.sadness, " \
                + "fear: this.fear, " + "surprise: this.surprise"
        emit += "}"

	mp = "function() { if (this.postTime != null) { key = Math.floor((this.postTime.getTime() + 8 * 3600000) / 1000 / 86400);" \
            + " emit( key, " + emit + " ); } }"
        rdc = "function( key, values ) { var stats = {};" \
            + " for ( var i = 0; i < values.length; i++ ) {" \
            + " for ( var key in values[i] ) { if (stats[key] == null) stats[key] = 0; stats[key] += values[i][key]; }" \
            + " } return stats; }";

        # query!
        print 'api_trend query:', query
        results = mymongo.mymongoconn.inline_mapreduce('messages', mp, rdc, query=query)
        # [{"_id": 14501.0, "value": {"count": 1.0}}, ...]

        # refine the results
        resultobj = {'trends': [], 'brand': brands[1], 'score': score}
        total = 0
        minTime = time.mktime(datetime.datetime.now().timetuple()) * 1000
        maxTime = -1
        for result in results:
            timems = float(result['_id']) * 86400000
            if timems == 0:
                continue
            if timems < minTime:
                minTime = timems
            if timems > maxTime:
                maxTime = timems
            values = result['value']
            for key in values:
                if key != 'count':
                    values[key] /= values['count']
            resultobj['trends'].append({'time': timems, 'values': values})

            total += values['count']
        resultobj['total'] = total
        resultobj['begin'] = minTime
        resultobj['end'] = maxTime
        resultobj['probability'] = probability

        if probability == 0: # no probability output
            return simplejson.dumps(resultobj)

        # now refine the output, adding the probability of each point

        # first polishing the points if missing between minTime and maxTime
        npoints = int(math.floor((maxTime - minTime) / 86400000) + 1)
        if npoints <= 0:
            return simplejson.dumps(resultobj)
        points = [None] * npoints
        for point in resultobj['trends']:
            k = int(math.floor((point['time'] - minTime) / 86400000))
            points[k] = point['values']
        # check the missing points
        idx = 0
        while idx < npoints:
            if points[idx] is None:
                jdx = idx + 1
                while jdx < npoints and points[jdx] is None:
                    jdx += 1
                # jdx won't be more than npoints-1: that's the last point
                # interpolate from idx to jdx-1
                v1 = points[idx - 1]
                v2 = points[jdx]
                for kdx in range(idx, jdx):
                    points[kdx] = {}
                    for key in v1:
                        points[kdx][key] = (v2[key] - v1[key] + 0.0) / (jdx - idx + 1) * (kdx - idx + 1) + v1[key]
                # the empty points have values now. goto the j-th point directly
                idx = jdx - 1
            idx += 1

        # the key part: compute the breaking probability
        windowsize = 5
        # compute each measurement (count, pos, neg...) in the loop.
        sums = {}
        sum2s = {}
        avgs = {}
        stdevs = {}
        ps = {}
        for key in points[0]:
            sum = [0] * npoints # sigma(value)
            sum2 = [0] * npoints # sigma(value^2)
            avg = [0] * npoints # sigma(value) / n
            stdev = [0] * npoints
            p = [1.0] * npoints
            for idx in range(0, npoints):
                if idx >= 1:
                    sum[idx] += sum[idx - 1]
                    sum2[idx] += sum2[idx - 1]
                if idx >= windowsize:
                    v = points[idx - windowsize][key]
                    sum[idx] -= v
                    sum2[idx] -= v * v
                v = points[idx][key]
                sum[idx] += v
                sum2[idx] += v * v
                nn = idx + 1
                if nn > windowsize:
                    nn = windowsize
                avg[idx] = sum[idx] * 1.0 / nn
                if nn > 1:
                    stdev[idx] = math.sqrt((sum2[idx] * 1.0 / nn - avg[idx] * avg[idx]) * nn / (nn - 1))
                    if stdev[idx] == 0: 
                        stdev[idx] = 1e-9
                    cdf1 = (1 + math.erf((v - avg[idx]) / math.sqrt(2) / stdev[idx])) / 2
                    cdf2 = (1 + math.erf((avg[idx] - v) / math.sqrt(2) / stdev[idx])) / 2
                    p[idx] = 1 - math.fabs(cdf1 - cdf2)
            sums[key] = sum
            sum2s[key] = sum2
            avgs[key] = avg
            stdevs[key] = stdev
            ps[key] = p

        # add the probabilities into the original points in the resultobj
        newtrends = []
        for point in resultobj['trends']:
            k = int(math.floor((point['time'] - minTime) / 86400000))
            prob = {}
            for key in points[0]:
                #if key not in prob:
                #    prob[key] = {}
                #prob[key]['sum'] = sums[key][k]
                #prob[key]['sum2'] = sum2s[key][k]
                #prob[key]['avg'] = avgs[key][k]
                #prob[key]['stdev'] = stdevs[key][k]
                #prob[key]['probability'] = ps[key][k]
                prob[key] = ps[key][k]
            point['probability'] = prob
            newtrends.append(point)
        resultobj['trends'] = newtrends
        return simplejson.dumps(resultobj)

    def POST(self):
        return self.GET()
