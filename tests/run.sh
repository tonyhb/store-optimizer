#!/bin/bash
 
while getopts ":r" opt; do
  case $opt in
    r)
      casperjs test --cookies-file=./cookies.txt --pre=pre.coffee --log-level=debug --direct --fail-fast ./suite/ --run=ok
      # casperjs test --cookies-file=./cookies.txt --pre=pre.coffee --fail-fast ./suite/ --run=ok
      exit 1;
      ;;
  esac
done

casperjs test --cookies-file=./cookies.txt --pre=pre.coffee ./suite/
