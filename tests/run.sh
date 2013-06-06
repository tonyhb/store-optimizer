#!/bin/bash

while getopts ":rd" opt; do
  case $opt in
    r)
      RUN=1
      ;;
    d)
      DEBUG=1
      ;;
  esac
done

if [[ -n "$RUN" ]]; then
    if [[ -n "$DEBUG" ]]; then
        casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee --log-level=debug --direct --fail-fast ./suite/ --run=ok
        exit
    else
        casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee --fail-fast ./suite/ --run=ok
        exit
    fi
fi

casperjs test --cookies-file=./cookies.txt --pre=includes/pre.coffee ./suite/
