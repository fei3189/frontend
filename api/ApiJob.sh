#!/bin/bash

# control the API python service
BASEDIR=/home/jiangfei/samsung/api
BIND=10.0.17.154:8181

if [ "$#" == "0" ]; then
  echo 'Usage: sh ApiJob.sh {start | stop | restart}'
fi

if [ "$1" == "stop" ] || [ "$1" == "restart" ]; then
  echo 'Stopping API service...'
  kill `ps aux | grep python | grep 8181 | grep -v 'grep' | awk '{print $2}'`
  echo 'OK'
fi

if [ "$1" == "start" ] || [ "$1" == "restart" ]; then
  echo 'Starting API service...'
  DATESTR=`date +%Y%m%d`
  cd $BASEDIR
  mkdir -p log/
  nohup python main.py $BIND 1>>log/apilog.stdout.$DATESTR 2>>log/apilog.stderr.$DATESTR &
  echo 'Please check:'
  sleep 1
  ps aux | grep python | grep 8181 | grep -v 'grep'
fi
