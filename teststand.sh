#!/bin/bash
set -e
set -x

function cleanup {
  echo "Removing build directory ${BUILDENV}"
  rm -rf ${BUILDENV}
}

trap cleanup EXIT

# check if this is a travis environment
if [ ! -z $TRAVIS_BUILD_DIR ] ; then
  WORKSPACE=$TRAVIS_BUILD_DIR
fi

if [ -z $WORKSPACE ] ; then
  echo "No workspace configured, please set your WORKSPACE environment"
  exit
fi

BUILDENV=`mktemp -d /tmp/mageteststand.XXXXXXXX`

echo "Using build directory ${BUILDENV}"

MAGENTO_DB_ALLOWSAME="1"

git clone -b master https://github.com/AOEpeople/MageTestStand.git ${BUILDENV}
cp -rf ${WORKSPACE} ${BUILDENV}/.modman/
${BUILDENV}/install.sh

cd ${BUILDENV}/htdocs
${BUILDENV}/bin/phpunit --colors -d display_errors=1
