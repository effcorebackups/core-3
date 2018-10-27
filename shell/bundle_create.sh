#!/usr/bin/env bash

git -C ../ reset --hard
git -C ../ pull
./cache_clear.sh

{ find .. -maxdepth 1 -type f -not -name '.*'             & \
  find .. -maxdepth 1 -type f      -name '.editorconfig'  & \
  find .. -maxdepth 1 -type f      -name '.gitattributes' & \
  find .. -maxdepth 1 -type f      -name '.gitignore'     & \
  find .. -maxdepth 1 -type f      -name '.htaccess'      & \
  find .. -maxdepth 1 -type f      -name '.nginx'         & \
  find ../dynamic     -type f      -name 'readme.md'      & \
  find ../shell       -type f -not -name '.*'             & \
  find ../system      -type f -not -name '.*'; \
} | zip -9 ../../bundle-0000.zip -@