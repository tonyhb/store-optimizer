#!/bin/bash

while getopts ":rdv:" opt; do
  case $opt in
    r)
      RUN=1
      ;;
    d)
      DEBUG=1
      ;;
    v)
      VERSION=$OPTARG
      ;;
  esac
done

if [ -z "${RUN+xxx}" ]; then
    # The run flag wasn't set
    casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee ./suite/
    exit
fi

if [ -z "${VERSION+xxx}" ]; then
    # The run flag wasn't set
    casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee ./suite/
    exit
fi

# Are we testing multiple versions?
IFS=','
for V in $VERSION
do
    if [[ -n "$DEBUG" ]]; then
        casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee --log-level=debug --direct --fail-fast ./suite/ --run=ok --v=$V
    else
        casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee --fail-fast ./suite/ --run=ok --v=$V
    fi
done

