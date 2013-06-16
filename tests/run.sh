#!/bin/bash

while getopts ":rdv:s:" opt; do
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
    s)
      SUITES=$OPTARG
      ;;
  esac
done

if [ -z "${RUN+xxx}" ]; then
    # The run flag wasn't set
    casperjs test --cookies-file=./.cookies.txt --pre=includes/pre.coffee ./suite/
    exit
fi

if [ -z "${VERSION+xxx}" ]; then
    # The run flag wasn't set
    casperjs test --cookies-file=./.cookies.txt --pre=includes/pre.coffee ./suite/
    exit
fi

IFS=','

# Are we testing selected suites only?
if [ -z "${SUITES}" ]; then
    SUITEPATH="./suite/"
else
    SUITEPATH=""
    # Go through each numbered suite
    for SUITE in $SUITES
    do
        for DIR in "$PWD/suite/$SUITE - "*/
        do
            #DIR=$(printf '%q' $DIR)
            if [ -z "${SUITEPATH}" ]; then
                SUITEPATH="$DIR"
            else
                SUITEPATH="$SUITEPATH $DIR"
            fi
        done
    done
fi

# Delete the cookie file
rm ".cookies.txt"

# Are we testing multiple versions?
IFS=','
for V in $VERSION
do
    if [[ -n "$DEBUG" ]]; then
        casperjs test --cookies-file=./.cookies.txt --pre=includes/pre.coffee --log-level=debug --direct --fail-fast $SUITEPATH --run=ok --v=$V
    else
        casperjs test --cookies-file=./.cookies.txt --pre=includes/pre.coffee --fail-fast $SUITEPATH --run=ok --v=$V
    fi
done

rm ".cookies.txt"
